<?php

namespace Tests\Feature\Feature\Auth;

use App\Livewire\Auth\LoginForm;
use App\Livewire\Auth\RegisterForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_auth_pages_register_and_login(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Sign in');
        $this->get(route('register'))->assertOk()->assertSee('Create account');

        Livewire::test(RegisterForm::class)
            ->set('name', 'Dana Viewer')
            ->set('username', 'dana')
            ->set('email', 'dana@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'dana@example.com',
            'username' => 'dana',
        ]);
    }

    public function test_existing_user_can_log_in_and_out(): void
    {
        $user = User::factory()->create([
            'email' => 'viewer@example.com',
            'password' => 'password',
        ]);

        Livewire::test(LoginForm::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('public.home'));

        $this->assertGuest();
    }
}
