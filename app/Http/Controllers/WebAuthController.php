<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WebAuthController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $user = $this->userService->login(
                $request->email,
                $request->password
            );

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput($request->only('email', 'remember'));
        }
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->userService->register(
            $request->name,
            $request->email,
            $request->password
        );

        return redirect()->route('login')->with('success', 'Registration successful! Please login.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
