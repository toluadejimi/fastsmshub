<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

use App\Http\Controllers\{
    ProfileController,
    VerificationController,
    FundingController,
    WalletController,
    TransactionController,
    DashboardController,
    Server2VerificationController,
    TelegramController,
    ChatController,
 SprintPayController,
    ReferralController,
    Server3VerificationController,
    Server4VerificationController,
    Server5VerificationController,
    Server6VerificationController,
    VirtualNumberController,
    PVAPinsController
};

use App\Http\Controllers\Admin\{
    AdminController,
    Auth\LoginController,
    UserImpersonationController,
    SupportChatController,
    ManualFundingAccountController,
    UserManagementController,
    ReferralController as AdminReferralController
};

use App\Http\Middleware\EnforceSingleSession;

use App\Http\Controllers\Auth\AuthenticatedSessionController;

// 🌐 Landing Page
Route::get('/', fn() => view('welcome'));

// ✅ User Authenticated Routes
Route::middleware(['auth', EnforceSingleSession::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 📲 Server 1 (Default)
    // Route::get('/home', [VerificationController::class, 'dashboard'])->name('home');
    Route::get('/home', fn() => redirect()->route('dashboard'));

    Route::post('/purchase', [VerificationController::class, 'purchaseNumber'])->name('purchase.number');
    Route::get('/cancel/{id}', [VerificationController::class, 'cancel'])->name('cancel');
    Route::get('/mark-done/{id}', [VerificationController::class, 'markDone'])->name('mark.done');
    Route::get('/poll-code/{id}', [VerificationController::class, 'pollCode'])->name('poll.code');
    Route::get('/sms-history', [VerificationController::class, 'smsHistory'])->name('verifications.sms_history');
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');

// New routes to add (make sure to place them BEFORE any wildcard routes):
Route::delete('/chat/message/{message}', [ChatController::class, 'deleteMessage'])->name('chat.delete-message');
Route::post('/chat/clear', [ChatController::class, 'clearChat'])->name('chat.clear');
Route::get('/chat/search', [ChatController::class, 'search'])->name('chat.search');
    // 📡 Server 2
    Route::prefix('server2')->name('server2.')->group(function () {
        Route::get('/', [Server2VerificationController::class, 'index'])->name('index');
        Route::get('/buy', [Server2VerificationController::class, 'buyNumber'])->name('buy');
        Route::get('/status/{id}', [Server2VerificationController::class, 'checkStatus'])->name('status');
        Route::get('/cancel/{id}', [Server2VerificationController::class, 'cancel'])->name('cancel');
        Route::get('/read-sms', [Server2VerificationController::class, 'readSms'])->name('read_sms');
        Route::get('/read-sms/id/{activationId}', [Server2VerificationController::class, 'readSms'])->name('read_sms_by_id');
    });

    // 🌍 Server 3
    Route::prefix('verifications/server3')->name('server3.')->group(function () {
        Route::get('/', [Server3VerificationController::class, 'index'])->name('index');
        Route::post('/purchase', [Server3VerificationController::class, 'purchase'])->name('purchase');
        Route::get('/check/{id}', [Server3VerificationController::class, 'check'])->name('check');
        Route::get('/cancel/{id}', [Server3VerificationController::class, 'cancel'])->name('cancel');
        Route::post('/get-price', [Server3VerificationController::class, 'getPrice'])->name('get-price');
    });

    // 🌍 Server 4
    Route::prefix('verifications/server4')->name('server4.')->group(function () {
        Route::get('/', [Server4VerificationController::class, 'index'])->name('index');
        Route::post('/get-price', [Server4VerificationController::class, 'getPrice'])->name('get-price');
        Route::post('/purchase', [Server4VerificationController::class, 'purchase'])->name('purchase');
        Route::get('/check/{id}', [Server4VerificationController::class, 'check'])->name('check');
        Route::get('/cancel/{id}', [Server4VerificationController::class, 'cancel'])->name('cancel');
    });
    Route::prefix('virtual')->name('virtual.')->group(function () {
        Route::get('/',                          [VirtualNumberController::class, 'index'])->name('index');
        Route::get('/buy',                       [VirtualNumberController::class, 'buy'])->name('buy');
        Route::get('/services/{countryId}',      [VirtualNumberController::class, 'services'])->name('services');
        Route::post('/prices',                   [VirtualNumberController::class, 'prices'])->name('prices');
        Route::post('/purchase',                 [VirtualNumberController::class, 'purchase'])->name('purchase');
        Route::get('/check/{activationId}',      [VirtualNumberController::class, 'check'])->name('check');
        Route::get('/check/{activationId}/poll', [VirtualNumberController::class, 'poll'])->name('poll');
        Route::post('/cancel/{activationId}',    [VirtualNumberController::class, 'cancel'])->name('cancel');
        Route::get('/history',                   [VirtualNumberController::class, 'history'])->name('history');
    });
    // 🌍 Server 5
    Route::prefix('verifications/server5')->name('server5.')->group(function () {
        Route::get('/', [Server5VerificationController::class, 'index'])->name('index');
        Route::post('/purchase', [Server5VerificationController::class, 'purchase'])->name('purchase');
        Route::get('/live-price', [Server5VerificationController::class, 'getLivePrice'])->name('live-price');
        Route::get('/countries', [Server5VerificationController::class, 'getCountries'])->name('countries');
        Route::get('/services', [Server5VerificationController::class, 'getServices'])->name('services');
        Route::get('/poll-code/{id}', [Server5VerificationController::class, 'pollCode'])->name('poll-code');
        Route::post('/cancel/{id}', [Server5VerificationController::class, 'cancel'])->name('cancel');
    });

    // 🌍 Server 6
    Route::get('/server6', [Server6VerificationController::class, 'index'])->name('server6.index');
    Route::post('/server6/purchase', [Server6VerificationController::class, 'purchase'])->name('server6.purchase');
    Route::get('/server6/services/{country}', [Server6VerificationController::class, 'getServicesJson']);
    Route::post('/server6/cancel/{id}', [Server6VerificationController::class, 'cancel'])->name('server6.cancel');
    Route::get('/server6/check-status/{id}', [Server6VerificationController::class, 'checkStatus']);

    // 📲 PVAPins
    Route::get('/pvapins', [PVAPinsController::class, 'index'])->name('pvapins.index');
    Route::post('/pvapins/purchase', [PVAPinsController::class, 'purchase'])->name('pvapins.purchase');
    Route::get('/pvapins/sms/{number}/{country}/{app}', [PVAPinsController::class, 'checkSms'])->name('pvapins.checkSms');

    // 💳 Funding
    Route::get('/wallet/fund', [FundingController::class, 'showFundPage'])->name('wallet.fund');
    Route::post('/wallet/fund', [FundingController::class, 'processFund']);
    Route::post('/wallet/fund/paystack', [FundingController::class, 'paystackRedirect'])->name('funding.paystack.redirect');
    Route::get('/wallet/fund/paystack/callback', [FundingController::class, 'paystackCallback'])->name('funding.paystack.callback');
    Route::post('/wallet/fund/flutterwave', [FundingController::class, 'flutterwaveRedirect'])->name('funding.flutterwave.redirect');
    Route::get('/wallet/fund/flutterwave/callback', [FundingController::class, 'flutterwaveCallback'])->name('funding.flutterwave.callback');
    Route::post('/fund-wallet/manual', [FundingController::class, 'submitManual'])->name('funding.manual.submit');
    Route::get('/fund-wallet', [FundingController::class, 'show'])->name('funding.show');
       Route::post('/funding/sprintpay/redirect', [WalletController::class, 'initiateFunding'])->name('funding.sprintpay.redirect');
    Route::get('/funding/sprintpay/verify', [SprintPayController::class, 'verifyViaUrl'])->name('funding.sprintpay.verify');

    // 🏦 Virtual Account
    Route::post('/virtual-account/save-phone', [WalletController::class, 'savePhone'])->name('virtual.account.phone.submit');
    Route::post('/generate-virtual-account', [WalletController::class, 'generateVirtualAccount'])->name('virtual.account.generate');

    // 🎯 Referral System
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
    Route::post('/referrals/withdraw', [ReferralController::class, 'withdraw'])->name('referrals.withdraw');

    // 💼 Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // 👤 Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 📌 Force session clear
Route::post('/force-clear-session', [AuthenticatedSessionController::class, 'forceClearSession'])->name('session.clear');

// ✅ Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/track-visitors', [AdminController::class, 'trackVisitors'])->name('visitors.track');
        Route::get('/referrals', [AdminReferralController::class, 'index'])->name('referrals.index');
        Route::get('/verifications', [AdminController::class, 'verificationList'])->name('verifications.index');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        
        // Support Chat Routes (fixed order and naming)
        Route::post('/support-chats/mark-read/{user}', [SupportChatController::class, 'markAsRead'])->name('chat.mark-read');
        Route::delete('/support-chats/message/{message}', [SupportChatController::class, 'deleteMessage'])->name('chat.delete-message');
        Route::delete('/support-chats/conversation/{user}', [SupportChatController::class, 'deleteConversation'])->name('chat.delete-conversation');
        Route::get('/support-chats/search', [SupportChatController::class, 'search'])->name('chat.search');
        Route::get('/support-chats', [SupportChatController::class, 'inbox'])->name('chat.inbox');
        Route::get('/support-chats/{user}', [SupportChatController::class, 'view'])->name('chat.view');
        Route::post('/support-chats/{user}/reply', [SupportChatController::class, 'reply'])->name('chat.reply');
        
        Route::get('/users/{id}', [UserManagementController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/fund', [UserManagementController::class, 'fundUserWallet'])->name('users.fund');
        Route::post('/users/{user}/debit', [UserManagementController::class, 'debitWallet'])->name('users.debit');
        Route::post('/users/{id}/toggle-ban', [UserManagementController::class, 'toggleBan'])->name('users.toggleBan');
        Route::post('/users/{id}/impersonate', [UserImpersonationController::class, 'impersonate'])->name('users.impersonate');
        Route::post('/users/leave-impersonation', [UserImpersonationController::class, 'leave'])->name('users.leave');
        Route::get('/transactions', [AdminController::class, 'allTransactions'])->name('transactions.index');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings.index');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::get('/manual-fundings', [AdminController::class, 'manualFundings'])->name('manual-fundings.index');
        Route::get('/manual-funding-account', [ManualFundingAccountController::class, 'index'])->name('manual.account');
        Route::post('/manual-funding-account', [ManualFundingAccountController::class, 'store'])->name('manual.account.store');
        Route::post('/manual-fundings/{id}/approve', [AdminController::class, 'approveManualFunding'])->name('manual-fundings.approve');
        Route::post('/manual-fundings/{id}/reject', [AdminController::class, 'rejectManualFunding'])->name('manual-fundings.reject');
    });
});
Route::get('/logs', function () {
    $logFile = storage_path('logs/laravel.log');
    
    if (!file_exists($logFile)) {
        return 'No log file found';
    }
    
    $logs = file_get_contents($logFile);
    $logs = nl2br(e($logs));
    
    return '<div style="background: #1e1e1e; color: #d4d4d4; padding: 20px; font-family: monospace; font-size: 12px;">' 
        . '<h2 style="color: #fff;">Laravel Logs</h2>' 
        . '<div style="max-height: 80vh; overflow-y: scroll;">' . $logs . '</div>'
        . '</div>';
})->middleware('auth');
Route::view('/privacy', 'privacy')->name('privacy');

// ✅ Email Verification Route (public, no auth required)
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response()->json(['message' => 'Email verified successfully']);
})->middleware(['signed'])->name('verification.verify');


// 🛡 Breeze Auth Routes
require __DIR__.'/auth.php';
