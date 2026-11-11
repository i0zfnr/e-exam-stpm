<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('password');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_login_requires_identity_and_password(): void
    {
        $response = $this->from(route('login'))->post(route('login'), []);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['identity', 'password']);
    }

    public function test_login_rate_limiting_triggers_after_repeated_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'identity' => 'invalid_user',
                'password' => 'wrong_password',
            ]);
        }

        $response = $this->post(route('login'), [
            'identity' => 'invalid_user',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(429);
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        User::query()->forceCreate([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'status' => 'Tidak Aktif',
        ]);

        $this->post(route('login'), [
            'identity' => 'inactive@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('identity');

        $this->assertGuest();
    }

    public function test_active_account_can_log_in(): void
    {
        $user = User::query()->forceCreate([
            'email' => 'active@example.com',
            'password' => Hash::make('password'),
            'status' => 'Aktif',
        ]);

        $this->post(route('login'), [
            'identity' => 'active@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rate_limiting_covers_multiple_identities_from_one_ip(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->post(route('login'), [
                'identity' => 'invalid_'.$i,
                'password' => 'wrong_password',
            ]);
        }

        $this->post(route('login'), [
            'identity' => 'another_invalid_user',
            'password' => 'wrong_password',
        ])->assertStatus(429);
    }
}
