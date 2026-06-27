<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeveloperUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_can_view_user_management(): void
    {
        $dev = User::factory()->create(['is_developer' => true]);

        $this->actingAs($dev)->get('/developer/users')->assertOk();
    }

    public function test_non_developer_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['is_developer' => false]);

        $this->actingAs($user)->get('/developer/users')->assertNotFound();
    }

    public function test_developer_can_change_another_users_email_and_password(): void
    {
        $dev = User::factory()->create(['is_developer' => true]);
        $target = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($dev)
            ->put("/developer/users/{$target->id}", [
                'email' => 'new@example.com',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ])
            ->assertSessionHasNoErrors();

        $target->refresh();
        $this->assertSame('new@example.com', $target->email);
        $this->assertTrue(Hash::check('brand-new-pass', $target->password));
        // Email change by developer keeps the account verified.
        $this->assertNotNull($target->email_verified_at);
    }

    public function test_developer_can_change_email_only_without_touching_password(): void
    {
        $dev = User::factory()->create(['is_developer' => true]);
        $target = User::factory()->create(['email' => 'a@example.com']);
        $originalHash = $target->password;

        $this->actingAs($dev)
            ->put("/developer/users/{$target->id}", ['email' => 'b@example.com'])
            ->assertSessionHasNoErrors();

        $target->refresh();
        $this->assertSame('b@example.com', $target->email);
        $this->assertSame($originalHash, $target->password);
    }
}
