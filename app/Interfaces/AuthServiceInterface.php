<?php

namespace App\Interfaces;

interface AuthServiceInterface
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array;

    /**
     * Login a user.
     *
     * @param array $credentials
     * @return array
     */
    public function login(array $credentials): array;

    /**
     * Logout a user.
     *
     * @param int $userId
     * @return bool
     */
    public function logout(int $userId): bool;
} 