<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function get(Request $request): JsonResponse
    {
        $users = User::orderBy('id', 'asc')->paginate(10);

        return response()->json($users, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return response()->json($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        return response()->json($user, 200);
    }

    public function updateUsername(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'username' => ['nullable', 'string', 'min:3', 'max:255'],
            'new_username' => ['nullable', 'string', 'min:3', 'max:255'],
        ]);

        $newUsername = $data['username'] ?? $data['new_username'] ?? null;

        if (! $newUsername) {
            return response()->json([
                'message' => 'Debes proporcionar el nuevo nombre de usuario en el campo "username" o "new_username".',
            ], 422);
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user->username = $newUsername;
        $user->save();

        return response()->json($user, 200);
    }

    public function update_username(Request $request): JsonResponse
    {
        return $this->updateUsername($request);
    }

    public function updateEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'string', 'email'],
            'current_email' => ['nullable', 'string', 'email'],
            'old_email' => ['nullable', 'string', 'email'],
            'new_email' => ['nullable', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $currentEmail = null;
        $newEmail = null;

        if (! empty($data['new_email'])) {
            $currentEmail = $data['email'] ?? $data['current_email'] ?? $data['old_email'] ?? null;
            $newEmail = $data['new_email'];
        } elseif (! empty($data['current_email'])) {
            $currentEmail = $data['current_email'];
            $newEmail = $data['email'] ?? null;
        } elseif (! empty($data['old_email'])) {
            $currentEmail = $data['old_email'];
            $newEmail = $data['email'] ?? null;
        }

        if (! $currentEmail || ! $newEmail) {
            return response()->json([
                'message' => 'Debes proporcionar el email actual y el nuevo email (ej: "email" y "new_email").',
            ], 422);
        }

        $user = User::where('email', $currentEmail)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $emailInUse = User::where('email', $newEmail)->where('id', '!=', $user->id)->exists();
        if ($emailInUse) {
            return response()->json([
                'message' => 'El nuevo email ya está en uso por otro usuario.',
            ], 422);
        }

        $user->email = $newEmail;
        $user->save();

        return response()->json($user, 200);
    }

    public function update_email(Request $request): JsonResponse
    {
        return $this->updateEmail($request);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['nullable', 'string'],
            'current_password' => ['nullable', 'string'],
            'old_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $currentPassword = null;
        $newPassword = null;

        if (! empty($data['new_password'])) {
            $currentPassword = $data['password'] ?? $data['current_password'] ?? $data['old_password'] ?? null;
            $newPassword = $data['new_password'];
        } elseif (! empty($data['current_password'])) {
            $currentPassword = $data['current_password'];
            $newPassword = $data['password'] ?? null;
        } elseif (! empty($data['old_password'])) {
            $currentPassword = $data['old_password'];
            $newPassword = $data['password'] ?? null;
        }

        if (! $currentPassword || ! $newPassword) {
            return response()->json([
                'message' => 'Debes proporcionar la contraseña actual y la nueva contraseña (ej: "password" y "new_password").',
            ], 422);
        }

        if (strlen($newPassword) < 8) {
            return response()->json([
                'message' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            ], 422);
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($currentPassword, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user->password = $newPassword;
        $user->save();

        return response()->json($user, 200);
    }

    public function update_password(Request $request): JsonResponse
    {
        return $this->updatePassword($request);
    }

    public function delete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ], 200);
    }
}
