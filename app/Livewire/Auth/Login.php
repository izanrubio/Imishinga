<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            $this->addError('credentials', 'These credentials do not match our records.');

            return;
        }

        session()->regenerate();

        $this->redirectRoute('boards.index');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
