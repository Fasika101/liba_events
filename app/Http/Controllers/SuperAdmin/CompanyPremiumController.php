<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySenderIdRequest;
use App\Models\CompanySmsPurchaseRequest;
use App\Models\SmsPackage;
use App\Services\CompanySmsWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyPremiumController extends Controller
{
    public function purchaseRequests()
    {
        $requests = CompanySmsPurchaseRequest::query()
            ->with(['company', 'package', 'requester'])
            ->latest()
            ->paginate(20);

        $premiumUpgradeRequests = Company::query()
            ->where('plan', Company::PLAN_STANDARD)
            ->whereNotNull('premium_requested_at')
            ->orderByDesc('premium_requested_at')
            ->get();

        $senderIdRequests = CompanySenderIdRequest::query()
            ->with('company')
            ->whereNotIn('status', [CompanySenderIdRequest::STATUS_APPROVED, CompanySenderIdRequest::STATUS_REJECTED])
            ->latest()
            ->get();

        return view('super-admin.premium.purchase-requests', compact('requests', 'premiumUpgradeRequests', 'senderIdRequests'));
    }

    public function approvePurchase(CompanySmsPurchaseRequest $purchaseRequest, CompanySmsWalletService $wallet)
    {
        if ($purchaseRequest->status !== CompanySmsPurchaseRequest::STATUS_PENDING) {
            return back()->with('error', 'This request was already processed.');
        }

        DB::transaction(function () use ($purchaseRequest, $wallet) {
            $wallet->topUpFromPackage(
                $purchaseRequest->company,
                $purchaseRequest->package,
                auth()->user()
            );

            $purchaseRequest->update([
                'status' => CompanySmsPurchaseRequest::STATUS_APPROVED,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);
        });

        return back()->with('status', 'Package credited to organization wallet.');
    }

    public function rejectPurchase(CompanySmsPurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== CompanySmsPurchaseRequest::STATUS_PENDING) {
            return back()->with('error', 'This request was already processed.');
        }

        $purchaseRequest->update([
            'status' => CompanySmsPurchaseRequest::STATUS_REJECTED,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('status', 'Purchase request rejected.');
    }

    public function upgrade(Company $company)
    {
        $company->update([
            'plan' => Company::PLAN_PREMIUM,
            'premium_enabled_at' => now(),
        ]);

        return back()->with('status', "{$company->name} upgraded to Premium.");
    }

    public function downgrade(Company $company)
    {
        $company->update([
            'plan' => Company::PLAN_STANDARD,
            'auto_sms_on_sale' => false,
        ]);

        return back()->with('status', "{$company->name} moved to Standard plan.");
    }

    public function topUp(Request $request, Company $company, CompanySmsWalletService $wallet)
    {
        $data = $request->validate([
            'sms_package_id' => ['required', 'exists:sms_packages,id'],
        ]);

        $package = SmsPackage::findOrFail($data['sms_package_id']);
        $wallet->topUpFromPackage($company, $package, $request->user());

        return back()->with('status', "Added {$package->sms_count} SMS credits ({$package->price_etb} ETB) to {$company->name}.");
    }

    public function updateSmsCredentials(Request $request, Company $company)
    {
        $data = $request->validate([
            'sms_api_key' => ['nullable', 'string', 'max:500'],
            'sms_sender_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{0,11}$/'],
            'clear_api_key' => ['sometimes', 'boolean'],
        ]);

        $updates = [];

        if ($request->boolean('clear_api_key')) {
            $updates['sms_api_key'] = null;
        } elseif (filled($data['sms_api_key'] ?? null)) {
            $updates['sms_api_key'] = $data['sms_api_key'];
        }

        if ($request->has('sms_sender_id')) {
            $updates['sms_sender_id'] = filled($data['sms_sender_id'])
                ? strtoupper($data['sms_sender_id'])
                : null;
        }

        if ($updates !== []) {
            $company->update($updates);
        }

        return back()->with('status', 'SMS credentials updated for '.$company->name.'.');
    }

    public function approveSenderIdRequest(Request $request, CompanySenderIdRequest $senderIdRequest)
    {
        if ($senderIdRequest->status !== CompanySenderIdRequest::STATUS_PENDING) {
            return back()->with('error', 'This request was already processed.');
        }

        $data = $request->validate([
            'price_etb' => ['required', 'numeric', 'min:0'],
            'payment_instructions' => ['required', 'string', 'max:2000'],
        ]);

        $senderIdRequest->update([
            'status' => CompanySenderIdRequest::STATUS_AWAITING_PAYMENT,
            'price_etb' => $data['price_etb'],
            'payment_instructions' => $data['payment_instructions'],
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('status', 'Payment instructions sent to the organization.');
    }

    public function activateSenderIdRequest(CompanySenderIdRequest $senderIdRequest)
    {
        if ($senderIdRequest->status !== CompanySenderIdRequest::STATUS_PAYMENT_SUBMITTED) {
            return back()->with('error', 'Payment must be submitted before activation.');
        }

        DB::transaction(function () use ($senderIdRequest) {
            $senderIdRequest->company->update([
                'sms_sender_id' => $senderIdRequest->requested_sender_id,
            ]);

            $senderIdRequest->update([
                'status' => CompanySenderIdRequest::STATUS_APPROVED,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);
        });

        return back()->with('status', "Sender ID {$senderIdRequest->requested_sender_id} activated for {$senderIdRequest->company->name}.");
    }

    public function rejectSenderIdRequest(Request $request, CompanySenderIdRequest $senderIdRequest)
    {
        if (in_array($senderIdRequest->status, [CompanySenderIdRequest::STATUS_APPROVED, CompanySenderIdRequest::STATUS_REJECTED], true)) {
            return back()->with('error', 'This request was already processed.');
        }

        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($senderIdRequest->payment_screenshot_path) {
            Storage::disk('public')->delete($senderIdRequest->payment_screenshot_path);
        }

        $senderIdRequest->update([
            'status' => CompanySenderIdRequest::STATUS_REJECTED,
            'admin_notes' => $data['admin_notes'] ?? null,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'payment_screenshot_path' => null,
        ]);

        return back()->with('status', 'Sender ID request rejected.');
    }
}
