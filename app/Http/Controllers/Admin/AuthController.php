<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminPermission;
use App\Constants\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use App\Http\Requests\Admin\Auth\SendOtpRequest;
use App\Http\Requests\Admin\Auth\VerifyOtpRequest;
use Illuminate\Http\JsonResponse;
use App\Models\OtpLog;

class AuthController extends Controller
{
    use ResponseTrait;

    // ✅ الحد الأقصى لمحاولات تسجيل الدخول قبل القفل
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES   = 15;

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email|max:255',
                'password' => 'required|string|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return $this->failureResponse(
                    __('messages.validation_error'),
                    $validator->errors(),
                    Constants::RESPONSE_VALIDATION_ERROR
                );
            }

            $ip    = $request->ip();
            $email = strtolower(trim($request->email));

            // ✅ فحص الـ Lockout قبل أي شيء
            $lockKey = 'login_lockout_' . md5($ip . $email);
            if (Cache::has($lockKey)) {
                $remaining = Cache::get($lockKey . '_remaining', self::LOCKOUT_MINUTES);
                return $this->failureResponse(
                    "تم قفل الحساب مؤقتاً بسبب محاولات متعددة. حاول بعد {$remaining} دقيقة.",
                    null,
                    Constants::RESPONSE_TOO_MANY_REQUESTS
                );
            }

            // ✅ عدّاد المحاولات الفاشلة
            $attemptsKey = 'login_attempts_' . md5($ip . $email);
            $attempts    = Cache::get($attemptsKey, 0);

            $user = User::with(['adminGroup.permissions'])
                ->where('email', $email)
                ->first();

            // ✅ منع Timing Attack: نفس وقت المعالجة سواء وُجد المستخدم أم لا
            if (!$user) {
                Hash::check('dummy', '$2y$10$dummyhashtopreventtimingattack00000000000000000000000000');
                $this->incrementFailedAttempts($attemptsKey, $lockKey, $attempts);

                return $this->failureResponse(
                    __('messages.invalid_credentials'),
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            if (!Hash::check($request->password, $user->password)) {
                $this->incrementFailedAttempts($attemptsKey, $lockKey, $attempts);

                $remaining = self::MAX_LOGIN_ATTEMPTS - ($attempts + 1);
                $message   = $remaining > 0
                    ? __('messages.invalid_credentials') . " ({$remaining} محاولات متبقية)"
                    : __('messages.invalid_credentials');

                return $this->failureResponse($message, null, Constants::RESPONSE_UNAUTHORIZED);
            }

            if (!$user->is_active) {
                return $this->failureResponse(
                    __('messages.inactive_account'),
                    null,
                    Constants::RESPONSE_FORBIDDEN
                );
            }

            // ✅ تسجيل دخول ناجح — امسح عدّاد المحاولات
            Cache::forget($attemptsKey);
            Cache::forget($lockKey);

            $permissions = $this->getUserPermissions($user);
            $is_admin    = $user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID;

            // ✅ احذف التوكنات القديمة قبل إنشاء جديدة (منع تراكم التوكنات)
            $user->tokens()
                ->where('name', 'invoice-system-token')
                ->where('created_at', '<', now()->subDays(30))
                ->delete();

            $token = $user->createToken('invoice-system-token')->plainTextToken;

            // ✅ لا تسجّل كلمة المرور أبداً
            Log::info('Login successful', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => $ip,
            ]);

            return $this->successResponse(
                __('messages.login_success'),
                [
                    'user' => $this->formatUser($user, $is_admin),
                    'token'        => $token,
                    'token_type'   => 'Bearer',
                    'permissions'  => $permissions,
                    'is_admin'     => $is_admin,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Login error', [
                'ip'    => $request->ip(),
                // ✅ لا نسجّل الـ email في حالة الخطأ لتجنب تسريب المعلومات
                'error' => $e->getMessage(),
            ]);

            return $this->failureResponse(
                __('messages.login_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->successResponse(__('messages.logout_success'), null);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->failureResponse(
                    __('messages.unauthenticated'),
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            $user->load(['adminGroup.permissions']);
            $permissions = $this->getUserPermissions($user);
            $is_admin    = $user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID;

            return $this->successResponse(
                __('messages.user_fetched'),
                [
                    'user'        => $this->formatUser($user, $is_admin),
                    'permissions' => $permissions,
                    'is_admin'    => $is_admin,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Me endpoint error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function refresh(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->failureResponse(
                    __('messages.unauthenticated'),
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            $request->user()->currentAccessToken()->delete();
            $token = $user->createToken('invoice-system-token')->plainTextToken;

            return $this->successResponse(
                __('messages.token_refreshed'),
                ['token' => $token, 'token_type' => 'Bearer']
            );
        } catch (\Exception $e) {
            Log::error('Refresh token error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
            ]);

            if ($validator->fails()) {
                return $this->failureResponse(
                    __('messages.validation_error'),
                    $validator->errors(),
                    Constants::RESPONSE_VALIDATION_ERROR
                );
            }

            // ✅ نفس الرسالة سواء وُجد الإيميل أم لا (منع User Enumeration)
            // لا تقل "الإيميل غير موجود" — هذا يكشف معلومات حساسة
            Log::info('Password reset requested', ['ip' => $request->ip()]);

            return $this->successResponse(
                'إذا كان البريد الإلكتروني مسجلاً، ستصلك رسالة لإعادة تعيين كلمة المرور.',
                null
            );
        } catch (\Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email|exists:users,email',
                'password' => 'required|string|min:8|confirmed',
                'token'    => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->failureResponse(
                    __('messages.validation_error'),
                    $validator->errors(),
                    Constants::RESPONSE_VALIDATION_ERROR
                );
            }

            return $this->successResponse(__('messages.password_changed'), null);
        } catch (\Exception $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    // ================================================================
    // Private Helpers
    // ================================================================

    /**
     * زيادة عدد المحاولات الفاشلة وقفل الحساب عند الوصول للحد
     */
    private function incrementFailedAttempts(string $attemptsKey, string $lockKey, int $currentAttempts): void
    {
        $newAttempts = $currentAttempts + 1;
        Cache::put($attemptsKey, $newAttempts, now()->addMinutes(self::LOCKOUT_MINUTES));

        if ($newAttempts >= self::MAX_LOGIN_ATTEMPTS) {
            Cache::put($lockKey, true, now()->addMinutes(self::LOCKOUT_MINUTES));
            Cache::put($lockKey . '_remaining', self::LOCKOUT_MINUTES, now()->addMinutes(self::LOCKOUT_MINUTES));
            Cache::forget($attemptsKey);

            Log::warning('Account locked due to too many failed attempts', [
                'attempts' => $newAttempts,
            ]);
        }
    }

    /**
     * تنسيق بيانات المستخدم للاستجابة
     * ✅ لا ترجع: password, remember_token, أي بيانات حساسة
     */
    private function formatUser(User $user, bool $is_admin): array
    {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'phone'          => $user->phone,
            'company_name'   => $user->company_name,
            'admin_group_id' => $user->admin_group_id,
            'is_active'      => $user->is_active,
            'is_admin'       => $is_admin,
            'img_url'        => $user->img_url,
            'adminGroup'     => $user->adminGroup,
        ];
    }

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !$user->is_active) {
                return $this->successResponse('إذا كان البريد الإلكتروني مسجلاً، سيصلك رمز التحقق', null);
            }

            if ($user->otp_created_at && $user->otp_created_at->diffInSeconds(now()) < 120) {
                $remaining = 120 - $user->otp_created_at->diffInSeconds(now());
                $minutes   = ceil($remaining / 60);
                return $this->failureResponse("تم إرسال رمز مسبقاً، حاول بعد {$minutes} دقيقة", null, Constants::RESPONSE_TOO_MANY_REQUESTS);
            }

            $plainOtp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $user->otp            = Hash::make($plainOtp);
            $user->otp_via        = 'email';
            $user->otp_created_at = now();
            $user->otp_attempts   = 0;
            $user->save();

            // ✅ حفظ السجل بدون الرمز
            OtpLog::create([
                'user_id'    => $user->id,
                'email'      => $user->email,
                'status'     => 'sent',
                'ip_address' => $request->ip(),
                'expires_at' => now()->addMinutes(10),
            ]);

            Mail::to($user->email)->send(new OtpMail($plainOtp));

            return $this->successResponse('إذا كان البريد الإلكتروني مسجلاً، سيصلك رمز التحقق', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('Send OTP error: ' . $e->getMessage());
            return $this->failureResponse(__('messages.operation_failed'), null, Constants::RESPONSE_SERVER_ERROR);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $user = User::with(['adminGroup.permissions'])->find($request->user_id);

            // ✅ فحص is_active
            if (!$user->is_active) {
                return $this->failureResponse(
                    __('messages.inactive_account'),
                    null,
                    Constants::RESPONSE_FORBIDDEN
                );
            }

            // ✅ فحص انتهاء صلاحية OTP (10 دقائق) بالـ seconds
            if (!$user->otp_created_at || $user->otp_created_at->diffInSeconds(now()) > 600) {
                $user->otp          = null;
                $user->otp_attempts = 0;
                $user->save();

                return $this->failureResponse(
                    'انتهت صلاحية الرمز، اطلب رمزاً جديداً',
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            // ✅ فحص عدد المحاولات قبل التحقق
            if ($user->otp_attempts >= self::MAX_LOGIN_ATTEMPTS) {
                return $this->failureResponse(
                    'تم تجاوز الحد المسموح من المحاولات، اطلب رمزاً جديداً',
                    null,
                    Constants::RESPONSE_TOO_MANY_REQUESTS
                );
            }

            // ✅ Hash::check بدل المقارنة المباشرة
            if (!$user->otp || !Hash::check($request->otp, $user->otp)) {
                $user->increment('otp_attempts');
                $remaining = self::MAX_LOGIN_ATTEMPTS - $user->fresh()->otp_attempts;

                return $this->failureResponse(
                    "رمز التحقق غير صحيح ({$remaining} محاولات متبقية)",
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            // ✅ OTP صحيح — امسح وأصدر التوكن
            $user->otp             = null;
            $user->otp_via         = null;
            $user->otp_created_at  = null;
            $user->otp_attempts    = 0;
            $user->otp_verified_at = now();
            $user->last_login_ip   = $request->ip();
            $user->save();

            $permissions = $this->getUserPermissions($user);
            $is_admin    = $user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID;
            $token       = $user->createToken('invoice-system-token')->plainTextToken;

            Log::info('OTP login successful', [
                'user_id' => $user->id,
                'ip'      => $request->ip(),
            ]);

            // ✅ بعد نجاح التحقق أضف هذا السطر قبل save()
            OtpLog::where('user_id', $user->id)
                ->where('status', 'sent')
                ->latest()
                ->first()
                ?->update(['status' => 'verified', 'verified_at' => now()]);

            return $this->successResponse(
                __('messages.login_success'),
                [
                    'user'        => $this->formatUser($user, $is_admin),
                    'token'       => $token,
                    'token_type'  => 'Bearer',
                    'permissions' => $permissions,
                    'is_admin'    => $is_admin,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Verify OTP error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    /**
     * جلب صلاحيات المستخدم
     */
    private function getUserPermissions(User $user): array
    {
        try {
            if ($user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID) {
                return AdminPermission::where('is_active', true)->pluck('title')->toArray();
            }

            if ($user->adminGroup && $user->adminGroup->permissions) {
                return $user->adminGroup->permissions
                    ->where('is_active', true)
                    ->pluck('title')
                    ->toArray();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Get user permissions error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * التحقق من الصلاحية
     */
    private function hasPermission(User $user, string $permission): bool
    {
        if ($user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID) {
            return true;
        }

        if ($user->adminGroup && $user->adminGroup->permissions) {
            return $user->adminGroup->permissions
                ->where('is_active', true)
                ->where('title', $permission)
                ->isNotEmpty();
        }

        return false;
    }
}
