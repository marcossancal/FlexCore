<?php

declare(strict_types=1);

namespace FlexCore\App\Controllers;
use Auth;
use DB;
/**
 * AuthController — SRP: só cuida de autenticação.
 */
class AuthController
{
    public function showLogin(): void
    {
        view('auth/login');
    }

    public function login(): void
    {
        if (!Auth::attempt(post('email'), post('password'))) {
            $_SESSION['login_error'] = 'E-mail ou senha incorretos.';
            admin_redirect('/login');
        }
        admin_redirect('/');
    }

    public function logout(): void
    {
        Auth::logout(); // já faz redirect + exit internamente
    }
}
