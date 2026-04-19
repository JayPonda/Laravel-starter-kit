<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class WebRegisterController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

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
}
