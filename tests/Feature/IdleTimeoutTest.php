<?php

namespace Tests\Feature;

use App\Models\AdminGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdleTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeAuthenticatedUser(): array
    {
        $group = AdminGroup::create([
            'title_en' => 'Staff',
            'title_ar' => 'موظف',
        ]);

        $user = User::factory()->create([
            'admin_group_id' => $group->id,
            'is_active' => true,
        ]);

        $plainTextToken = $user->createToken('invoice-system-token')->plainTextToken;

        return [$user, $plainTextToken];
    }

    public function test_request_within_idle_window_is_allowed(): void
    {
        [$user, $token] = $this->makeAuthenticatedUser();

        // ✅ آخر نشاط قبل 30 دقيقة فقط — أقل من حد الـ 60 دقيقة الافتراضي
        $user->tokens()->first()->forceFill([
            'last_used_at' => now()->subMinutes(30),
        ])->save();

        $response = $this->withToken($token)->getJson('/api/admin/me');

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_request_past_idle_window_is_rejected_and_token_revoked(): void
    {
        [$user, $token] = $this->makeAuthenticatedUser();

        // ✅ آخر نشاط قبل 61 دقيقة — تجاوز حد الـ 60 دقيقة الافتراضي
        $user->tokens()->first()->forceFill([
            'last_used_at' => now()->subMinutes(61),
        ])->save();

        $response = $this->withToken($token)->getJson('/api/admin/me');

        $response->assertStatus(401);
        $response->assertJson([
            'status' => false,
            'message' => __('messages.session_expired_idle'),
        ]);

        // ✅ التوكن يجب أن يُحذف نهائياً بعد انتهاء الجلسة بسبب عدم النشاط
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_brand_new_token_with_no_prior_activity_is_not_rejected(): void
    {
        // أول طلب مباشرة بعد تسجيل الدخول — last_used_at لا يزال null
        [, $token] = $this->makeAuthenticatedUser();

        $response = $this->withToken($token)->getJson('/api/admin/me');

        $response->assertStatus(200);
    }

    public function test_idle_timeout_duration_is_configurable(): void
    {
        config(['sanctum.idle_timeout' => 5]);

        [$user, $token] = $this->makeAuthenticatedUser();

        $user->tokens()->first()->forceFill([
            'last_used_at' => now()->subMinutes(6),
        ])->save();

        $response = $this->withToken($token)->getJson('/api/admin/me');

        $response->assertStatus(401);
    }
}
