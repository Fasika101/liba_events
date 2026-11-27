<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BulkSmsLog;
use App\Models\CompanySmsLog;
use App\Models\TicketOrder;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['sms', 'verify-et'], true)) {
            $tab = 'sms';
        }

        $smsFailedCount = CompanySmsLog::query()
            ->where('status', CompanySmsLog::STATUS_FAILED)
            ->count();

        $verifyFailedCount = TicketOrder::query()
            ->where(function ($q) {
                $q->whereNotNull('verify_et_status')
                    ->where(function ($inner) {
                        $inner->where('verify_et_status', 'like', '%error%')
                            ->orWhere('verify_et_status', 'like', '%fail%')
                            ->orWhere('verify_et_status', 'like', '%mismatch%')
                            ->orWhere('verify_et_status', 'like', '%not found%')
                            ->orWhere('verify_et_status', 'like', '%unavailable%')
                            ->orWhere('verify_et_status', 'like', '%invalid%');
                    });
            })
            ->count();

        $smsLogs = collect();
        $verifyOrders = collect();
        $bulkSmsFails = collect();

        if ($tab === 'sms') {
            $smsLogs = CompanySmsLog::query()
                ->with(['company', 'ticket.event', 'sender'])
                ->where('status', CompanySmsLog::STATUS_FAILED)
                ->latest()
                ->paginate(30)
                ->withQueryString();

            $bulkSmsFails = BulkSmsLog::query()
                ->with('sender')
                ->where('failed_count', '>', 0)
                ->latest()
                ->take(20)
                ->get();
        } else {
            $verifyOrders = TicketOrder::query()
                ->with(['company', 'event', 'attendee'])
                ->where(function ($q) {
                    $q->whereNotNull('verify_et_status')
                        ->where(function ($inner) {
                            $inner->where('verify_et_status', 'like', '%error%')
                                ->orWhere('verify_et_status', 'like', '%fail%')
                                ->orWhere('verify_et_status', 'like', '%mismatch%')
                                ->orWhere('verify_et_status', 'like', '%not found%')
                                ->orWhere('verify_et_status', 'like', '%unavailable%')
                                ->orWhere('verify_et_status', 'like', '%invalid%');
                        });
                })
                ->latest()
                ->paginate(30)
                ->withQueryString();
        }

        return view('super-admin.error-logs.index', compact(
            'tab',
            'smsLogs',
            'verifyOrders',
            'bulkSmsFails',
            'smsFailedCount',
            'verifyFailedCount',
        ));
    }
}
