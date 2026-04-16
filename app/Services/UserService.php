<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function createUser(string $name, string $email): array
    {
        $password = Str::password(12);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        return ['user' => $user, 'password' => $password];
    }

    public function register(string $name, string $email, string $password): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }

    public function login(string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user;
    }

    public function changePassword(string $email): array
    {
        $user = User::where('email', $email)->firstOrFail();
        $password = Str::password(12);

        $user->update(['password' => Hash::make($password)]);

        return ['user' => $user, 'password' => $password];
    }

    public function resetPassword(string $email, string $oldPassword): array
    {
        $user = User::where('email', $email)->firstOrFail();

        if (! Hash::check($oldPassword, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['The old password is incorrect.'],
            ]);
        }

        $newPassword = Str::password(12);
        $user->update(['password' => Hash::make($newPassword)]);

        return ['user' => $user, 'password' => $newPassword];
    }
}
