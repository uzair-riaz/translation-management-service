<?php

namespace App\Services;

use App\Interfaces\AuthServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function register(array $data): array
    {
        DB::beginTransaction();
        
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;
            
            DB::commit();

            return [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Login a user.
     *
     * @param array $credentials
     * @return array
     * @throws ValidationException
     * @throws \Exception
     */
    public function login(array $credentials): array
    {
        DB::beginTransaction();
        
        try {
            $user = User::where('email', $credentials['email'])->first();
            
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            
            // Delete existing tokens before creating a new one
            $user->tokens()->delete();
            
            $token = $user->createToken('auth_token')->plainTextToken;
            
            DB::commit();

            return [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Logout a user.
     *
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function logout(int $userId): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }
        
        DB::beginTransaction();
        
        try {
            // Revoke all tokens
            $user->tokens()->delete();
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 