<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BulkSmsLog;
use App\Services\BulkSmsService;
use App\Support\SmsConfig;
use Illuminate\Http\Request;

class BulkSmsController extends Controller
{
    public function index(BulkSmsService $bulkSms)
    {
        return view('super-admin.bulk-sms.index', [
            'organizations' => $bulkSms->organizationsForMessaging(),
            'recentLogs' => BulkSmsLog::query()
                ->with(['sender', 'items'])
                ->latest()
                ->take(10)
                ->get(),
            'smsConfigured' => SmsConfig::isConfigured(),
            'smsDriver' => SmsConfig::driver(),
        ]);
    }

    public function show(BulkSmsLog $bulkSmsLog)
    {
        $bulkSmsLog->load(['sender', 'items.company']);

        return view('super-admin.bulk-sms.show', [
            'log' => $bulkSmsLog,
        ]);
    }

    public function send(Request $request, BulkSmsService $bulkSms)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:5', 'max:1000'],
            'company_ids' => ['required', 'array', 'min:1'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
        ]);

        try {
            $log = $bulkSms->send(
                $data['company_ids'],
                $data['message'],
                $data['title'] ?? null,
                $request->user()
            );
        } catch (\Throwable $exception) {
            return redirect()->route('super-admin.bulk-sms.index')
                ->with('error', $exception->getMessage())
                ->withInput();
        }

        $summary = "{$log->success_count} sent";
        if ($log->failed_count > 0) {
            $summary .= ", {$log->failed_count} failed";
        }
        if ($log->skipped_count > 0) {
            $summary .= ", {$log->skipped_count} skipped (no phone)";
        }

        return redirect()->route('super-admin.bulk-sms.show', $log)
            ->with('status', "Bulk SMS completed: {$summary}.");
    }
}
