<?php

namespace App\Http\Middleware;

use App\Constants\Constants;
use App\Traits\ResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * ✅ إنهاء الجلسة تلقائياً بعد فترة من عدم النشاط (Idle Timeout)
 *
 * ملاحظة مهمة عن الترتيب: يجب أن يعمل هذا الـ middleware *قبل*
 * auth:sanctum مباشرة في مجموعة الـ routes المحمية (وليس بعده).
 * السبب: عند مصادقة الطلب، يقوم Sanctum تلقائياً بتحديث عمود
 * last_used_at للتوكن إلى الوقت الحالي. لو نفّذنا الفحص بعد
 * auth:sanctum لكانت القيمة قد استُبدلت بالفعل بـ "الآن"، فتصبح
 * كل مقارنة idle = 0 دائماً. لذلك نقرأ last_used_at (وهو وقت آخر
 * طلب فعلي سابق) هنا أولاً، قبل أن يلمسه Sanctum لهذا الطلب.
 */
class CheckIdleTimeout
{
    use ResponseTrait;

    public function handle(Request $request, Closure $next)
    {
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            $token = PersonalAccessToken::findToken($bearerToken);

            if ($token && $token->last_used_at) {
                $idleTimeoutMinutes = (int) config('sanctum.idle_timeout', 60);

                if ($idleTimeoutMinutes > 0 && $token->last_used_at->diffInMinutes(now()) >= $idleTimeoutMinutes) {
                    $token->delete();

                    return $this->failureResponse(
                        __('messages.session_expired_idle'),
                        null,
                        Constants::RESPONSE_UNAUTHORIZED
                    );
                }
            }
        }

        // ✅ لا حاجة لتحديث last_used_at يدوياً هنا — auth:sanctum
        // (الذي يعمل مباشرة بعد هذا الـ middleware) يقوم بذلك تلقائياً
        // كجزء من مصادقة الطلب، وهذا بالضبط ما يُعتبر "نشاطاً جديداً".
        return $next($request);
    }
}
