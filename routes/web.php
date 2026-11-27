<?php

use App\Http\Controllers\PublicTicketReceiptController;
use App\Http\Controllers\Admin\BankAccountController as AdminBankAccountController;
use App\Http\Controllers\Admin\OnlineOrderController as AdminOnlineOrderController;
use App\Http\Controllers\Marketplace\CheckoutController as MarketplaceCheckoutController;
use App\Http\Controllers\Marketplace\DiscoverController as MarketplaceDiscoverController;
use App\Http\Controllers\Marketplace\OnboardingController as MarketplaceOnboardingController;
use App\Http\Controllers\Marketplace\ProfileController as MarketplaceProfileController;
use App\Http\Controllers\Marketplace\TelegramWebhookController;
use App\Http\Controllers\Marketplace\VerifyEtWebhookController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\CheckInController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\BulkSmsController as AdminBulkSmsController;
use App\Http\Controllers\Admin\PremiumFeaturesController as AdminPremiumFeaturesController;
use App\Http\Controllers\Admin\TicketSalesController as AdminTicketSalesController;
use App\Http\Controllers\SuperAdmin\CompanyPremiumController as SuperAdminCompanyPremiumController;
use App\Http\Controllers\SuperAdmin\SmsPackageController as SuperAdminSmsPackageController;
use App\Http\Controllers\Agent\DashboardController as AgentDashboardController;
use App\Http\Controllers\Agent\TicketController as AgentTicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\BulkSmsController as SuperAdminBulkSmsController;
use App\Http\Controllers\SuperAdmin\TelegramBroadcastController as SuperAdminTelegramBroadcastController;
use App\Http\Controllers\SuperAdmin\MarketplaceAdController as SuperAdminMarketplaceAdController;
use App\Http\Controllers\SuperAdmin\ErrorLogController as SuperAdminErrorLogController;
use App\Http\Controllers\SuperAdmin\CompanyAdminController as SuperAdminCompanyAdminController;
use App\Http\Controllers\SuperAdmin\CompanyController as SuperAdminCompanyController;
use App\Http\Controllers\SuperAdmin\CrmController as SuperAdminCrmController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\RegistrationRequestController as SuperAdminRegistrationRequestController;
use App\Http\Controllers\SuperAdmin\SmsAccountingController as SuperAdminSmsAccountingController;
use App\Http\Controllers\SuperAdmin\SmsSettingsController as SuperAdminSmsSettingsController;
use App\Http\Controllers\SuperAdmin\UserController as SuperAdminUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login');

Route::get('/tickets/{ticket}/receipt/public', [PublicTicketReceiptController::class, 'show'])
    ->name('tickets.public-receipt');

Route::post('/webhooks/verify-et', VerifyEtWebhookController::class)->name('webhooks.verify-et');
Route::post('/webhooks/telegram', TelegramWebhookController::class)->name('webhooks.telegram');

Route::middleware('public.locale')->prefix('discover')->name('public.')->group(function () {
    Route::post('/telegram-auth', [MarketplaceProfileController::class, 'authTelegram'])->name('telegram-auth');
    Route::post('/locale', [MarketplaceProfileController::class, 'setLocale'])->name('locale');

    Route::get('/gate', [MarketplaceOnboardingController::class, 'gate'])->name('gate');
    Route::get('/onboarding', [MarketplaceOnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding/channel', [MarketplaceOnboardingController::class, 'checkChannel'])->name('onboarding.channel');
    Route::post('/onboarding/register', [MarketplaceOnboardingController::class, 'register'])->name('onboarding.register');

    // Deep links from SMS / Telegram should work even before onboarding is finished.
    Route::get('/orders/{publicCode}', [MarketplaceCheckoutController::class, 'show'])->name('orders.show');
    Route::get('/orders/{publicCode}/ticket', [MarketplaceProfileController::class, 'ticketLink'])->name('orders.ticket');

    Route::middleware('public.telegram')->group(function () {
        Route::get('/', [MarketplaceDiscoverController::class, 'index'])->name('discover');
        Route::get('/organizers', [MarketplaceDiscoverController::class, 'organizers'])->name('organizers');
        Route::get('/organizers/{company}', [MarketplaceDiscoverController::class, 'organizer'])->name('organizers.show');
        Route::get('/events/{event}', [MarketplaceDiscoverController::class, 'event'])->name('events.show');
        Route::post('/events/{event}/checkout', [MarketplaceCheckoutController::class, 'store'])->name('events.checkout');
        Route::post('/events/{event}/verify-payment', [MarketplaceCheckoutController::class, 'previewVerify'])->name('events.verify-payment');
        Route::get('/tickets', [MarketplaceProfileController::class, 'tickets'])->name('tickets');
        Route::get('/me', [MarketplaceProfileController::class, 'show'])->name('profile');
        Route::post('/me', [MarketplaceProfileController::class, 'updateProfile'])->name('profile.update');
    });
});

Route::post('/logout', function (Request $request) {
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->name('logout')->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        }
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('agent.dashboard');
    })->name('dashboard')->middleware('company.active');

    Route::middleware('role:super_admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/companies', [SuperAdminCompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/create', [SuperAdminCompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [SuperAdminCompanyController::class, 'store'])->name('companies.store');
        Route::get('/companies/{company}', [SuperAdminCompanyController::class, 'show'])->name('companies.show');
        Route::get('/companies/{company}/edit', [SuperAdminCompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{company}', [SuperAdminCompanyController::class, 'update'])->name('companies.update');
        Route::delete('/companies/{company}', [SuperAdminCompanyController::class, 'destroy'])->name('companies.destroy');
        Route::post('/companies/{company}/suspension', [SuperAdminCompanyController::class, 'toggleSuspension'])->name('companies.suspension.toggle');
        Route::get('/company-admins/create', [SuperAdminCompanyAdminController::class, 'create'])->name('company-admins.create');
        Route::post('/company-admins', [SuperAdminCompanyAdminController::class, 'store'])->name('company-admins.store');
        Route::get('/company-admins', [SuperAdminCompanyAdminController::class, 'index'])->name('company-admins.index');

        Route::get('/registration-requests', [SuperAdminRegistrationRequestController::class, 'index'])->name('registration-requests.index');
        Route::get('/registration-requests/organizations/{company}/edit', [SuperAdminRegistrationRequestController::class, 'editOrganization'])->name('registration-requests.organizations.edit');
        Route::put('/registration-requests/organizations/{company}', [SuperAdminRegistrationRequestController::class, 'updateOrganization'])->name('registration-requests.organizations.update');
        Route::get('/registration-requests/{registrationRequest}', [SuperAdminRegistrationRequestController::class, 'show'])->name('registration-requests.show');
        Route::put('/registration-requests/{registrationRequest}', [SuperAdminRegistrationRequestController::class, 'update'])->name('registration-requests.update');
        Route::post('/registration-requests/{registrationRequest}/approve', [SuperAdminRegistrationRequestController::class, 'approve'])->name('registration-requests.approve');
        Route::post('/registration-requests/{registrationRequest}/reject', [SuperAdminRegistrationRequestController::class, 'reject'])->name('registration-requests.reject');
        Route::delete('/registration-requests/{registrationRequest}', [SuperAdminRegistrationRequestController::class, 'destroy'])->name('registration-requests.destroy');

        Route::delete('/users/{user}', [SuperAdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/sms-settings', [SuperAdminSmsSettingsController::class, 'edit'])->name('sms-settings.edit');
        Route::put('/sms-settings', [SuperAdminSmsSettingsController::class, 'update'])->name('sms-settings.update');
        Route::post('/sms-settings/test', [SuperAdminSmsSettingsController::class, 'test'])->name('sms-settings.test');

        Route::get('/bulk-sms', [SuperAdminBulkSmsController::class, 'index'])->name('bulk-sms.index');
        Route::post('/bulk-sms/send', [SuperAdminBulkSmsController::class, 'send'])->name('bulk-sms.send');
        Route::get('/bulk-sms/{bulkSmsLog}', [SuperAdminBulkSmsController::class, 'show'])->name('bulk-sms.show');

        Route::get('/telegram-broadcast', [SuperAdminTelegramBroadcastController::class, 'index'])->name('telegram-broadcast.index');
        Route::post('/telegram-broadcast', [SuperAdminTelegramBroadcastController::class, 'send'])->name('telegram-broadcast.send');

        Route::resource('marketplace-ads', SuperAdminMarketplaceAdController::class)->except(['show']);

        Route::resource('sms-packages', SuperAdminSmsPackageController::class)->except(['show']);

        Route::get('/premium/purchase-requests', [SuperAdminCompanyPremiumController::class, 'purchaseRequests'])->name('premium.purchase-requests');
        Route::post('/premium/purchase-requests/{purchaseRequest}/approve', [SuperAdminCompanyPremiumController::class, 'approvePurchase'])->name('premium.purchase-requests.approve');
        Route::post('/premium/purchase-requests/{purchaseRequest}/reject', [SuperAdminCompanyPremiumController::class, 'rejectPurchase'])->name('premium.purchase-requests.reject');
        Route::post('/companies/{company}/premium/upgrade', [SuperAdminCompanyPremiumController::class, 'upgrade'])->name('companies.premium.upgrade');
        Route::post('/companies/{company}/premium/downgrade', [SuperAdminCompanyPremiumController::class, 'downgrade'])->name('companies.premium.downgrade');
        Route::post('/companies/{company}/premium/top-up', [SuperAdminCompanyPremiumController::class, 'topUp'])->name('companies.premium.top-up');
        Route::put('/companies/{company}/sms-credentials', [SuperAdminCompanyPremiumController::class, 'updateSmsCredentials'])->name('companies.sms-credentials.update');
        Route::post('/sender-id-requests/{senderIdRequest}/approve', [SuperAdminCompanyPremiumController::class, 'approveSenderIdRequest'])->name('sender-id-requests.approve');
        Route::post('/sender-id-requests/{senderIdRequest}/activate', [SuperAdminCompanyPremiumController::class, 'activateSenderIdRequest'])->name('sender-id-requests.activate');
        Route::post('/sender-id-requests/{senderIdRequest}/reject', [SuperAdminCompanyPremiumController::class, 'rejectSenderIdRequest'])->name('sender-id-requests.reject');

        Route::get('/accounting', [SuperAdminSmsAccountingController::class, 'index'])->name('accounting.index');

        Route::get('/error-logs', [SuperAdminErrorLogController::class, 'index'])->name('error-logs.index');

        Route::get('/crm', [SuperAdminCrmController::class, 'index'])->name('crm.index');
        Route::get('/crm/data', [SuperAdminCrmController::class, 'data'])->name('crm.data');
        Route::get('/crm/export', [SuperAdminCrmController::class, 'export'])->name('crm.export');
    });

    Route::middleware(['role:admin', 'company.active'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('events', AdminEventController::class);
        Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
        Route::post('agents', [AgentController::class, 'store'])->name('agents.store');
        Route::post('agents/telegram-menu-button', [AgentController::class, 'setTelegramMenuButton'])->name('agents.telegram-menu');
        Route::post('agents/{user}/suspend', [AgentController::class, 'suspend'])->name('agents.suspend');
        Route::post('agents/{user}/delete', [AgentController::class, 'destroy'])->name('agents.destroy');

        Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/data', [AdminCustomerController::class, 'data'])->name('customers.data');
        Route::get('customers/export', [AdminCustomerController::class, 'export'])->name('customers.export');

        Route::get('checkin', [CheckInController::class, 'index'])->name('checkin.index');
        Route::get('checkin/{event}', [CheckInController::class, 'show'])->name('checkin.show');
        Route::post('checkin/{event}/verify', [CheckInController::class, 'verify'])->name('checkin.verify');

        Route::get('ticket-sales', [AdminTicketSalesController::class, 'index'])->name('ticket-sales.index');
        Route::get('ticket-sales/{event}/export', [AdminTicketSalesController::class, 'export'])->name('ticket-sales.export');
        Route::post('ticket-sales/{event}/export', [AdminTicketSalesController::class, 'exportSelected'])->name('ticket-sales.export-selected');
        Route::get('ticket-sales/{event}/tickets/{ticket}/receipt', [AdminTicketSalesController::class, 'receipt'])->name('ticket-sales.tickets.receipt');
        Route::post('ticket-sales/{event}/tickets/{ticket}/send-receipt', [AdminTicketSalesController::class, 'sendReceiptEmail'])->name('ticket-sales.tickets.send-receipt');
        Route::get('ticket-sales/{event}/tickets/{ticket}/edit', [AdminTicketSalesController::class, 'edit'])->name('ticket-sales.tickets.edit');
        Route::put('ticket-sales/{event}/tickets/{ticket}', [AdminTicketSalesController::class, 'update'])->name('ticket-sales.tickets.update');
        Route::get('ticket-sales/{event}', [AdminTicketSalesController::class, 'show'])->name('ticket-sales.show');

        Route::get('premium', [AdminPremiumFeaturesController::class, 'index'])->name('premium.index');
        Route::post('premium/request', [AdminPremiumFeaturesController::class, 'requestPremium'])->name('premium.request');
        Route::put('premium/settings', [AdminPremiumFeaturesController::class, 'updateSettings'])->name('premium.settings');
        Route::post('premium/purchase-package', [AdminPremiumFeaturesController::class, 'requestPackage'])->name('premium.purchase-package');
        Route::post('premium/custom-sms', [AdminPremiumFeaturesController::class, 'sendCustomSms'])->name('premium.custom-sms');

        Route::get('bulk-sms', [AdminBulkSmsController::class, 'index'])->name('bulk-sms.index');
        Route::post('bulk-sms/send', [AdminBulkSmsController::class, 'send'])->name('bulk-sms.send');
        Route::get('bulk-sms/customers/search', [AdminBulkSmsController::class, 'searchCustomers'])->name('bulk-sms.customers.search');
        Route::get('bulk-sms/customers/list', [AdminBulkSmsController::class, 'listCustomers'])->name('bulk-sms.customers.list');
        Route::post('bulk-sms/preview-count', [AdminBulkSmsController::class, 'previewCount'])->name('bulk-sms.preview-count');
        Route::post('bulk-sms/sender-id/request', [AdminBulkSmsController::class, 'requestSenderId'])->name('bulk-sms.sender-id.request');
        Route::post('bulk-sms/sender-id/payment', [AdminBulkSmsController::class, 'uploadSenderIdPayment'])->name('bulk-sms.sender-id.payment');
        Route::get('bulk-sms/{bulkSmsLog}', [AdminBulkSmsController::class, 'show'])->name('bulk-sms.show');

        Route::get('accounts', [AdminBankAccountController::class, 'index'])->name('accounts.index');
        Route::post('accounts', [AdminBankAccountController::class, 'store'])->name('accounts.store');
        Route::put('accounts/{bankAccount}', [AdminBankAccountController::class, 'update'])->name('accounts.update');
        Route::delete('accounts/{bankAccount}', [AdminBankAccountController::class, 'destroy'])->name('accounts.destroy');

        Route::get('online-orders', [AdminOnlineOrderController::class, 'index'])->name('online-orders.index');
        Route::get('online-orders/{onlineOrder}', [AdminOnlineOrderController::class, 'show'])->name('online-orders.show');
        Route::post('online-orders/{onlineOrder}/paid', [AdminOnlineOrderController::class, 'markPaid'])->name('online-orders.paid');
        Route::post('online-orders/{onlineOrder}/reject', [AdminOnlineOrderController::class, 'reject'])->name('online-orders.reject');
        Route::delete('online-orders/{onlineOrder}', [AdminOnlineOrderController::class, 'destroy'])->name('online-orders.destroy');
    });

    Route::middleware(['role:agent', 'company.active'])->prefix('agent')->name('agent.')->group(function () {
        Route::get('/dashboard', [AgentDashboardController::class, 'index'])->name('dashboard');
        Route::get('tickets/data', [AgentTicketController::class, 'data'])->name('tickets.data');
        Route::resource('tickets', AgentTicketController::class)->only(['index', 'create', 'store']);
        Route::get('tickets/{ticket}/receipt', [AgentTicketController::class, 'receipt'])->name('tickets.receipt');

        Route::get('checkin', [CheckInController::class, 'index'])->name('checkin.index');
        Route::get('checkin/{event}', [CheckInController::class, 'show'])->name('checkin.show');
        Route::post('checkin/{event}/verify', [CheckInController::class, 'verify'])->name('checkin.verify');
    });

    Route::middleware('company.active')->group(function () {
        Route::view('profile', 'profile')->name('profile');
        Route::post('profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    });
});

require __DIR__.'/auth.php';
