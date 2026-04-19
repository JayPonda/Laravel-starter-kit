<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = $this->userService->login(
            $request->email,
            $request->password
        );

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'old_password' => 'required|string',
        ]);

        $result = $this->userService->resetPassword(
            $request->email,
            $request->old_password
        );

        return response()->json([
            'message' => 'Password reset successfully',
            'temporary_password' => $result['password'],
        ]);
    }
}
