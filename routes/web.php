<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\CheckInController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\TicketSalesController as AdminTicketSalesController;
use App\Http\Controllers\Agent\DashboardController as AgentDashboardController;
use App\Http\Controllers\Agent\TicketController as AgentTicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\CompanyAdminController as SuperAdminCompanyAdminController;
use App\Http\Controllers\SuperAdmin\CompanyController as SuperAdminCompanyController;
use App\Http\Controllers\SuperAdmin\CrmController as SuperAdminCrmController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::redirect('/', 'login');

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

        Route::get('checkin',                                           [CheckInController::class, 'index'])->name('checkin.index');
        Route::get('checkin/{event}',                                   [CheckInController::class, 'show'])->name('checkin.show');
        Route::post('checkin/{event}/verify',                           [CheckInController::class, 'verify'])->name('checkin.verify');

        Route::get('ticket-sales',                                      [AdminTicketSalesController::class, 'index'])->name('ticket-sales.index');
        Route::get('ticket-sales/{event}/export',                       [AdminTicketSalesController::class, 'export'])->name('ticket-sales.export');
        Route::post('ticket-sales/{event}/export',                      [AdminTicketSalesController::class, 'exportSelected'])->name('ticket-sales.export-selected');
        Route::get('ticket-sales/{event}/tickets/{ticket}/edit',        [AdminTicketSalesController::class, 'edit'])->name('ticket-sales.tickets.edit');
        Route::put('ticket-sales/{event}/tickets/{ticket}',             [AdminTicketSalesController::class, 'update'])->name('ticket-sales.tickets.update');
        Route::get('ticket-sales/{event}',                              [AdminTicketSalesController::class, 'show'])->name('ticket-sales.show');
    });

    Route::middleware(['role:agent', 'company.active'])->prefix('agent')->name('agent.')->group(function () {
        Route::get('/dashboard', [AgentDashboardController::class, 'index'])->name('dashboard');
        Route::get('tickets/data', [AgentTicketController::class, 'data'])->name('tickets.data');
        Route::resource('tickets', AgentTicketController::class)->only(['index', 'create', 'store']);
        Route::get('tickets/{ticket}/receipt', [AgentTicketController::class, 'receipt'])->name('tickets.receipt');

        Route::get('checkin',                [CheckInController::class, 'index'])->name('checkin.index');
        Route::get('checkin/{event}',        [CheckInController::class, 'show'])->name('checkin.show');
        Route::post('checkin/{event}/verify',[CheckInController::class, 'verify'])->name('checkin.verify');
    });

    Route::middleware('company.active')->group(function () {
        Route::view('profile', 'profile')->name('profile');
        Route::post('profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    });
});

require __DIR__.'/auth.php';
