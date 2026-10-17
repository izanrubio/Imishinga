<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'correct-password')
            ->call('login')
            ->assertRedirect(route('boards.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_credentials_show_the_does_not_match_error(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['credentials'])
            ->assertSee('These credentials do not match our records.');

        $this->assertGuest();
    }

    public function test_email_and_password_are_required(): void
    {
        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['email' => 'required', 'password' => 'required']);
    }
}
