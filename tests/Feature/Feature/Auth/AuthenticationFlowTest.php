<?php

namespace Tests\Feature\Feature\Auth;

use App\Enums\UserRole;
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
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in to Screenbase')
            ->assertSee('Continue with Apple')
            ->assertSee('Browse catalog')
            ->assertDontSee('Search titles, people, and public lists')
            ->assertDontSee('Movies');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create your Screenbase account')
            ->assertSee('Continue with Google')
            ->assertSee('Browse catalog')
            ->assertDontSee('Search titles, people, and public lists')
            ->assertDontSee('Movies');

        Livewire::test(RegisterForm::class)
            ->set('form.name', 'Dana Viewer')
            ->set('form.username', 'dana')
            ->set('form.email', 'dana@example.com')
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'dana@example.com',
            'username' => 'dana',
            'role' => UserRole::RegularUser->value,
        ]);
    }

    public function test_auth_pages_render_sheaf_member_entry_controls(): void
    {
        $loginResponse = $this->get(route('login'))->assertOk();
        $registerResponse = $this->get(route('register'))->assertOk();

        $loginMarkup = $loginResponse->getContent();
        $registerMarkup = $registerResponse->getContent();

        self::assertIsString($loginMarkup);
        self::assertIsString($registerMarkup);

        self::assertStringContainsString('data-slot="checkbox-wrapper"', $loginMarkup);
        self::assertStringContainsString('data-slot="link"', $loginMarkup);
        self::assertStringContainsString('data-slot="link"', $registerMarkup);
        self::assertGreaterThanOrEqual(3, substr_count($loginMarkup, 'data-slot="button"'));
        self::assertGreaterThanOrEqual(3, substr_count($registerMarkup, 'data-slot="button"'));
    }

    public function test_existing_user_can_log_in_and_out(): void
    {
        $user = User::factory()->create([
            'email' => 'viewer@example.com',
            'password' => 'password',
        ]);

        Livewire::test(LoginForm::class)
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('public.home'));

        $this->assertGuest();
    }
}
