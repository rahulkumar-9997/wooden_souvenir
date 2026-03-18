<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CustomerAuthController extends Controller
{
    public function loginOrCreateAccountWithOtp(Request $request)
    {
        return $this->sendOtp($request); 
    }


    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }
        $isEmail = filter_var($request->contact, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'phone_number';
        $customer = Customer::where($field, $request->contact)->first();
        $isNewUser = false;
        if (!$customer) {
            $isNewUser = true;
            $customerData = [
                $field => $request->contact,
                'customer_id' => Customer::generateCustomerId(),
                'status' => 1,
                'login_attempts' => 0
            ];
            if ($isEmail) {
                $customerData['name'] = explode('@', $request->contact)[0]; 
            } else {
                $customerData['name'] = 'User_' . substr($request->contact, -4);
            }            
            $customer = Customer::create($customerData);
        }
        $otp = rand(100000, 999999);
        $customer->otp = $otp;
        $customer->save();        
        /* Store in cache with expiry (5 minutes) */
        cache()->put('otp_' . $customer->id, $otp, now()->addMinutes(5));
        cache()->put('otp_sent_' . $customer->id, now(), now()->addMinutes(1));
        // Send OTP via appropriate channel
        if ($isEmail) {
            $this->sendEmailOtp($customer->email, $otp);
        } else {
            $this->sendSmsOtp($customer->phone_number, $otp);
        }
        Log::info("OTP for {$request->contact}: {$otp}");
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => [
                'customer_id' => $customer->id,
                'contact' => $request->contact,
                'contact_type' => $isEmail ? 'email' : 'phone',
                'is_new_user' => $isNewUser,
                'otp' => env('APP_ENV') === 'local' ? $otp : null
            ]
        ]);
    }

    
    public function verifyOtpAndLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Detect contact type
        $isEmail = filter_var($request->contact, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'phone_number';

        // Find customer
        $customer = Customer::where($field, $request->contact)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid contact'
            ], 404);
        }

        // Verify OTP
        $cachedOtp = cache()->get('otp_' . $customer->id);
        
        if (!$cachedOtp || $cachedOtp != $request->otp) {
            // Check database OTP as fallback
            if ($customer->otp != $request->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 401);
            }
        }

        // Check account status
        if ($customer->status != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated'
            ], 403);
        }

        // Clear OTP
        $customer->otp = null;
        $customer->last_login_at = now();
        $customer->login_attempts = 0;
        $customer->save();

        // Clear cached OTP
        cache()->forget('otp_' . $customer->id);

        // Check if profile is complete
        $isProfileIncomplete = empty($customer->email) || empty($customer->name) || 
                              (empty($customer->phone_number) && $isEmail) ||
                              str_starts_with($customer->name, 'User_');

        // Delete old tokens and create new one
        $customer->tokens()->delete();
        $token = $customer->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'customer' => $customer,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'is_profile_complete' => !$isProfileIncomplete,
                'expires_in' => 30 * 24 * 60 * 60
            ]
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $isEmail = filter_var($request->contact, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'phone_number';

        $customer = Customer::where($field, $request->contact)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found'
            ], 404);
        }

        // Rate limiting - check if OTP was sent in last 60 seconds
        $lastOtpTime = cache()->get('otp_sent_' . $customer->id);
        if ($lastOtpTime && now()->diffInSeconds($lastOtpTime) < 60) {
            $waitTime = 60 - now()->diffInSeconds($lastOtpTime);
            return response()->json([
                'success' => false,
                'message' => "Please wait {$waitTime} seconds",
                'data' => ['wait_time' => $waitTime]
            ], 429);
        }

        // Generate new OTP
        $otp = rand(100000, 999999);
        
        $customer->otp = $otp;
        $customer->save();
        
        cache()->put('otp_' . $customer->id, $otp, now()->addMinutes(5));
        cache()->put('otp_sent_' . $customer->id, now(), now()->addMinutes(1));

        // Send OTP
        if ($isEmail) {
            $this->sendEmailOtp($customer->email, $otp);
        } else {
            $this->sendSmsOtp($customer->phone_number, $otp);
        }

        Log::info("Resent OTP for {$request->contact}: {$otp}");

        return response()->json([
            'success' => true,
            'message' => 'OTP resent successfully',
            'data' => [
                'customer_id' => $customer->id,
                'contact' => $request->contact,
                'otp' => env('APP_ENV') === 'local' ? $otp : null
            ]
        ]);
    }

    
    private function sendEmailOtp($email, $otp)
    {
        try {
            Mail::send('emails.otp', ['otp' => $otp], function ($message) use ($email) {
                $message->to($email)
                        ->subject('Your Login OTP');
            });
        } catch (\Exception $e) {
            Log::error("Email sending failed: " . $e->getMessage());
        }
    }

    
    private function sendSmsOtp($phone, $otp)
    {
        // Implement your SMS gateway here
        Log::info("SMS OTP for {$phone}: {$otp}");
    }

    
    public function checkContactExists(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $isEmail = filter_var($request->contact, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'phone_number';
        $exists = Customer::where($field, $request->contact)->exists();
        return response()->json([
            'success' => true,
            'data' => [
                'exists' => $exists,
                'contact_type' => $isEmail ? 'email' : 'phone'
            ]
        ]);
    }
}
