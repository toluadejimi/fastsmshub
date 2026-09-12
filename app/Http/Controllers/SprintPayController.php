<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Transaction;

class SprintPayController extends Controller
{
    public function verifyViaUrl(Request $request)
    {
        $trans_id = $request->query('trans_id');
        $amount   = $request->query('amount');
        $status   = $request->query('status');

        if (!$trans_id || !$amount || !$status) {
            return redirect('/fund-wallet')->with('error', 'Missing parameters from SprintPay.');
        }

        if ($status !== 'success') {
            return redirect('/fund-wallet')->with('error', 'Transaction was not successful.');
        }

        $verifyUrl = "http://web.sprintpay.online/api/verify-transaction?trans_id={$trans_id}";
        $response = Http::get($verifyUrl);

        if ($response->failed()) {
            return redirect('/fund-wallet')->with('error', 'Unable to verify transaction.');
        }

        $data = $response->json();

        if (
            !isset($data['status']) || $data['status'] !== true ||
            !isset($data['data']) || $data['data']['status'] !== 'success'
        ) {
            return redirect('/fund-wallet')->with('error', 'Invalid or failed transaction.');
        }

        $email = $data['data']['email'];
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect('/fund-wallet')->with('error', 'User not found.');
        }

        if (Transaction::where('reference', $trans_id)->exists()) {
            return redirect('/fund-wallet')->with('success', 'Transaction already processed.');
        }

        DB::beginTransaction();
        try {
            $user->wallet += $amount;
            $user->save();

            Transaction::create([
                'user_id'   => $user->id,
                'type'      => 'credit',
                'method'    => 'sprintpay',
                'amount'    => $amount,
                'status'    => 'completed',
                'reference' => $trans_id,
            ]);

            DB::commit();
            return redirect('/fund-wallet')->with('success', 'Wallet funded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect('/fund-wallet')->with('error', 'Failed to process transaction. Please contact support.');
        }
    }

   public function webhook(Request $request)
{
    Log::info('SprintPay Webhook Received', $request->all());

    $trans_id = $request->input('trans_id') 
                ?? $request->input('order_id') 
                ?? $request->input('session_id');
    $amount = $request->input('amount');
    $status = $request->input('status', 'success'); // Default to success if not provided
    $email = $request->input('email');

    if (!$trans_id || !$amount || !$email) {
        Log::error('SprintPay Webhook - Missing parameters', $request->all());
        return response()->json(['error' => 'Missing parameters'], 400);
    }

    // If status field exists and is not success, reject
    if ($request->has('status') && $status !== 'success') {
        Log::warning('SprintPay Webhook - Failed Transaction', $request->all());
        return response()->json(['message' => 'Transaction not successful'], 200);
    }

    $user = User::where('email', $email)->first();

    if (!$user) {
        Log::error('SprintPay Webhook - User not found', ['email' => $email]);
        return response()->json(['error' => 'User not found'], 404);
    }

    if (Transaction::where('reference', $trans_id)->where('status', 'completed')->exists()) {
        Log::info('SprintPay Webhook - Transaction already processed', ['reference' => $trans_id]);
        return response()->json(['message' => 'Transaction already processed'], 200);
    }

    DB::beginTransaction();
    try {
        $user->wallet += $amount;
        $user->save();

        Transaction::updateOrCreate(
            ['reference' => $trans_id],
            [
                'user_id' => $user->id,
                'type' => 'credit',
                'method' => 'sprintpay',
                'amount' => $amount,
                'status' => 'completed',
            ]
        );

        DB::commit();

        Log::info('SprintPay Webhook - Transaction completed', [
            'user_id' => $user->id,
            'amount' => $amount,
            'reference' => $trans_id
        ]);

        return response()->json(['message' => 'Wallet funded successfully'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('SprintPay Webhook - Error processing transaction', [
            'error' => $e->getMessage(),
            'reference' => $trans_id
        ]);
        return response()->json(['error' => 'Failed to process transaction'], 500);
    }
}

    public function e_check(Request $request)
{
    $user = User::where('email', $request->email)->first();
    
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'No user found, please check email and try again',
        ]);
    }
    
    return response()->json([
        'status' => true,
        'user' => $user->username ?? $user->name ?? $user->email,
        'name' => $user->name,
        'email' => $user->email,
    ]);
}

    public function e_fund(Request $request)
    {
        Log::info('SprintPay e_fund called', $request->all());
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'No user found',
            ]);
        }
        
        $amount = $request->amount;
        $order_id = $request->order_id;
        
        if (Transaction::where('reference', $order_id)->where('status', 'completed')->exists()) {
            return response()->json([
                'status' => true,
                'message' => 'Transaction already processed',
            ]);
        }
        
        DB::beginTransaction();
        try {
            $user->wallet += $amount;
            $user->save();
            
            Transaction::updateOrCreate(
                ['reference' => $order_id],
                [
                    'user_id' => $user->id,
                    'type' => 'credit',
                    'method' => 'sprintpay',
                    'amount' => $amount,
                    'status' => 'completed',
                ]
            );
            
            DB::commit();
            
            $formatted_amount = number_format($amount, 2);
            $message = "Oprime Access | " . $request->email . " | just funded | " . $formatted_amount . " | with SprintPay";
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.telegram.org/bot6493243183:AAHpZ97GioBOLayRCob64HKqe-pzUOmKntc/sendMessage?chat_id=6743906881',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => [
                    'chat_id' => "649324318",
                    'text' => $message,
                ],
            ]);
            curl_exec($curl);
            curl_close($curl);
            
            Log::info('SprintPay e_fund completed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $order_id
            ]);
            
            return response()->json([
                'status' => true,
                'message' => "NGN $formatted_amount has been successfully added to your wallet",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SprintPay e_fund error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to process transaction',
            ], 500);
        }
    }

    public function verify_username(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'username' => "Not Found, Please try again"
            ]);
        }
        
        return response()->json([
            'username' => $user->username
        ]);
    }
}