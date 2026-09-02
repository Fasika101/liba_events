<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyBulkSmsLog;
use App\Models\CompanySenderIdRequest;
use App\Models\Event;
use App\Services\CompanyCustomerBulkSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BulkSmsController extends Controller
{
    public function index(CompanyCustomerBulkSmsService $bulkSms)
    {
        $company = $this->company();

        $events = Event::query()
            ->where('company_id', $company->id)
            ->orderByDesc('start_at')
            ->get(['id', 'title', 'start_at']);

        $eventRecipientCounts = [];
        foreach ($events as $event) {
            $eventRecipientCounts[$event->id] = $bulkSms->countEventRecipients($company, $event->id);
        }

        $recentLogs = $company->bulkSmsLogs()->with('sender')->take(10)->get();

        $senderIdRequest = $company->senderIdRequests()
            ->whereNotIn('status', [CompanySenderIdRequest::STATUS_APPROVED, CompanySenderIdRequest::STATUS_REJECTED])
            ->latest()
            ->first();

        $crmTotalCount = $bulkSms->countAllCustomers($company);

        return view('admin.bulk-sms.index', compact(
            'company',
            'events',
            'eventRecipientCounts',
            'recentLogs',
            'senderIdRequest',
            'crmTotalCount'
        ));
    }

    public function previewCount(Request $request, CompanyCustomerBulkSmsService $bulkSms)
    {
        $company = $this->company();

        $data = $request->validate([
            'manual_phones' => ['nullable', 'string', 'max:5000'],
            'crm_phones' => ['nullable', 'array'],
            'crm_phones.*' => ['string', 'max:20'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'crm_select_all' => ['sometimes', 'boolean'],
        ]);

        if ($data['event_id'] ?? null) {
            Event::query()
                ->where('company_id', $company->id)
                ->findOrFail($data['event_id']);
        }

        $manualPhones = array_filter(preg_split('/[\r\n,]+/', $data['manual_phones'] ?? '') ?: []);

        $breakdown = $bulkSms->previewRecipientBreakdown(
            $company,
            $manualPhones,
            $data['crm_phones'] ?? [],
            isset($data['event_id']) ? (int) $data['event_id'] : null,
            $request->boolean('crm_select_all')
        );

        return response()->json(array_merge($breakdown, [
            'credits_available' => $company->sms_credits,
            'has_enough_credits' => $company->sms_credits >= $breakdown['total'],
        ]));
    }

    public function listCustomers(Request $request, CompanyCustomerBulkSmsService $bulkSms)
    {
        $company = $this->company();

        if (! $company->canSendSms()) {
            return response()->json(['customers' => [], 'total' => 0]);
        }

        $customers = $bulkSms->allCustomers($company, $request->input('q'));

        return response()->json([
            'customers' => $customers->values(),
            'total' => $bulkSms->countAllCustomers($company),
        ]);
    }

    public function searchCustomers(Request $request, CompanyCustomerBulkSmsService $bulkSms)
    {
        $company = $this->company();

        if (! $company->canSendSms()) {
            return response()->json([]);
        }

        return response()->json(
            $bulkSms->searchCustomers($company, $request->input('q', ''))
        );
    }

    public function send(Request $request, CompanyCustomerBulkSmsService $bulkSms)
    {
        $company = $this->company();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:5', 'max:480'],
            'manual_phones' => ['nullable', 'string', 'max:5000'],
            'crm_phones' => ['nullable', 'array'],
            'crm_phones.*' => ['string', 'max:20'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'crm_select_all' => ['sometimes', 'boolean'],
        ]);

        if ($data['event_id'] ?? null) {
            Event::query()
                ->where('company_id', $company->id)
                ->findOrFail($data['event_id']);
        }

        $manualPhones = array_filter(preg_split('/[\r\n,]+/', $data['manual_phones'] ?? '') ?: []);

        try {
            $log = $bulkSms->send(
                $company,
                $data['message'],
                $request->user(),
                $manualPhones,
                $data['crm_phones'] ?? [],
                isset($data['event_id']) ? (int) $data['event_id'] : null,
                $data['title'] ?? null,
                $request->boolean('crm_select_all')
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        return redirect()->route('admin.bulk-sms.show', $log)
            ->with('status', "Bulk SMS completed: {$log->success_count} sent, {$log->failed_count} failed, {$log->skipped_count} skipped.");
    }

    public function show(CompanyBulkSmsLog $bulkSmsLog)
    {
        $company = $this->company();

        if ((int) $bulkSmsLog->company_id !== $company->id) {
            abort(404);
        }

        $bulkSmsLog->load(['items', 'sender']);

        return view('admin.bulk-sms.show', compact('bulkSmsLog', 'company'));
    }

    public function requestSenderId(Request $request)
    {
        $company = $this->company();

        if (! $company->isPremium()) {
            return back()->with('error', 'Premium plan required.');
        }

        if (filled($company->sms_sender_id)) {
            return back()->with('error', 'Your organization already has a custom sender ID.');
        }

        $open = $company->senderIdRequests()->whereNotIn('status', [
            CompanySenderIdRequest::STATUS_APPROVED,
            CompanySenderIdRequest::STATUS_REJECTED,
        ])->exists();

        if ($open) {
            return back()->with('error', 'You already have an open sender ID request.');
        }

        $data = $request->validate([
            'requested_sender_id' => ['required', 'string', 'regex:/^[A-Za-z0-9]{1,11}$/'],
        ], [
            'requested_sender_id.regex' => 'Sender ID must be 1–11 letters or numbers (no spaces).',
        ]);

        CompanySenderIdRequest::create([
            'company_id' => $company->id,
            'requested_sender_id' => strtoupper($data['requested_sender_id']),
        ]);

        return back()->with('status', 'Custom sender ID request submitted. Our team will review it shortly.');
    }

    public function uploadSenderIdPayment(Request $request)
    {
        $company = $this->company();

        $senderRequest = $company->senderIdRequests()
            ->where('status', CompanySenderIdRequest::STATUS_AWAITING_PAYMENT)
            ->latest()
            ->firstOrFail();

        $data = $request->validate([
            'payment_screenshot' => ['required', 'image', 'max:5120'],
        ]);

        if ($senderRequest->payment_screenshot_path) {
            Storage::disk('public')->delete($senderRequest->payment_screenshot_path);
        }

        $path = $data['payment_screenshot']->store('sender-id-payments', 'public');

        $senderRequest->update([
            'payment_screenshot_path' => $path,
            'status' => CompanySenderIdRequest::STATUS_PAYMENT_SUBMITTED,
        ]);

        return back()->with('status', 'Payment screenshot uploaded. We will verify and activate your sender ID.');
    }

    private function company(): Company
    {
        return Company::findOrFail(auth()->user()->requireCompanyId());
    }
}
