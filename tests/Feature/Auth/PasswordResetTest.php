<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\SendPasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_reset_code_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.code', ['email' => $user->email]));

        Notification::assertSentOnDemand(SendPasswordResetCode::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_code_screen_can_be_rendered(): void
    {
        $this->get('/reset-password?email=test@example.com')->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_code(): void
    {
        $user = User::factory()->create();

        // Seed a known code the same way the controller does.
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make('123456'), 'created_at' => now()],
        );

        $response = $this->post('/reset-password', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_password_cannot_be_reset_with_invalid_code(): void
    {
        $user = User::factory()->create();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make('123456'), 'created_at' => now()],
        );

        $this->post('/reset-password', [
            'email' => $user->email,
            'code' => '000000',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('code');
    }
}
