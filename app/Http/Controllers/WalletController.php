<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function showFundForm()
    {
        return view('wallet.fund');
    }

    public function manualFund(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);

        Transaction::create([
            'user_id' => auth()->id(),
            'type' => 'manual',
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Manual funding request submitted. Awaiting approval.');
    }

    public function savePhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20|unique:users,phone,' . Auth::id(),
        ], [
            'phone.unique' => '❌ This phone number has already been taken.',
        ]);

        $user = Auth::user();
        $user->phone = $request->phone;
        $user->save();

        return redirect()->back()->with('success', '📱 Phone number saved. You can now generate your virtual account.');
    }

    public function generateVirtualAccount()
    {
        $user = Auth::user();

        if (empty($user->phone)) {
            return back()->with('error', '❌ Please provide your phone number first.');
        }

        $payload = [
            'email' => $user->email,
            'name' => $user->name,
            'phoneNumber' => $user->phone,
            'bankCode' => [config('services.paymentpoint.bank_code')],
            'businessId' => config('services.paymentpoint.business_id'),
        ];

        $headers = [
            'Authorization' => 'Bearer ' . config('services.paymentpoint.secret'),
            'Content-Type' => 'application/json',
            'api-key' => config('services.paymentpoint.key'),
        ];

        $response = Http::withHeaders($headers)->post(
            'https://api.paymentpoint.co/api/v1/createVirtualAccount',
            $payload
        );

        $data = $response->json();

        if ($response->successful() && isset($data['bankAccounts'][0])) {
            $account = $data['bankAccounts'][0];

            $user->update([
                'virtual_account_number' => $account['accountNumber'] ?? null,
                'virtual_account_bank' => $account['bankName'] ?? null,
                'virtual_account_name' => $account['accountName'] ?? null,
            ]);

            return back()->with('success', '✅ Virtual account generated and saved successfully.');
        }

        Log::error('PaymentPoint Error', ['response' => $data]);

        return back()->with('error', $data['message'] ?? '❌ Failed to generate virtual account.');
    }

    public function initiateFunding(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        if (Setting::get('sprintpay_enabled') !== '1') {
            return back()->with('error', 'SprintPay is currently disabled.');
        }

        $user = auth()->user();
        $amount = $request->amount;
        $ref = 'SP-' . strtoupper(Str::random(12));
        $apiKey = Setting::get('sprintpay_api_key') ?: config('services.sprintpay.key');
        $email = $user->email;

        if (empty($apiKey)) {
            Log::error('SprintPay API key not configured');
            return back()->with('error', 'Payment gateway not configured. Please contact support.');
        }

        Transaction::create([
            'user_id'   => $user->id,
            'type'      => 'credit',
            'method'    => 'sprintpay',
            'amount'    => $amount,
            'status'    => 'pending',
            'reference' => $ref,
        ]);

        $url = "https://web.sprintpay.online/pay?amount={$amount}&key={$apiKey}&ref={$ref}&email={$email}";
        
        Log::info('SprintPay Payment Initiated', [
            'user_id' => $user->id,
            'amount' => $amount,
            'reference' => $ref,
            'url' => $url
        ]);

        return redirect()->away($url);
    }
public function viewLogs()
{
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
}
    public function paystackRedirect(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);
    }

    public function flutterwaveRedirect(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);
    }
}