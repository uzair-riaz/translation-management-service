<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Interfaces\AuthServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @var AuthServiceInterface
     */
    protected $authService;

    /**
     * AuthController constructor.
     *
     * @param AuthServiceInterface $authService
     */
    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user.
     *
     * @param  \App\Http\Requests\RegisterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('User registration failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->validated()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    /**
     * Login user and create token.
     *
     * @param  \App\Http\Requests\LoginRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'User logged in successfully',
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('User login failed: ' . $e->getMessage(), [
                'exception' => $e,
                'email' => $request->email
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    /**
     * Logout user (revoke the token).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request->user()->id);

            return response()->json([
                'status' => 'success',
                'message' => 'User logged out successfully',
            ]);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('User logout failed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
