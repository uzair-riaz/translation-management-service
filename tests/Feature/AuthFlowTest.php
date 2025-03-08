<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function complete_auth_flow_works_correctly()
    {
        // 1. Register a new user
        $registerData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $registerResponse = $this->postJson('/api/register', $registerData);

        $registerResponse->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ],
                    'access_token',
                    'token_type',
                ],
            ]);

        // Extract token for later use
        $token = $registerResponse->json('data.access_token');
        $this->assertNotEmpty($token);

        // 2. Verify the user was created in the database
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 3. Verify the token was created
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user->tokens()->first());

        // 4. Test accessing a protected endpoint with the token
        $protectedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/translations');

        $protectedResponse->assertStatus(200);

        // 5. Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User logged out successfully',
            ]);

        // 6. Verify the token was revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // 7. Try to access protected endpoint with revoked token
        $unauthorizedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/translations');

        $unauthorizedResponse->assertStatus(401);

        // 8. Login again
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $loginResponse = $this->postJson('/api/login', $loginData);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user',
                    'access_token',
                    'token_type',
                ],
            ]);

        // Extract new token
        $newToken = $loginResponse->json('data.access_token');
        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($token, $newToken);

        // 9. Access protected endpoint with new token
        $newProtectedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $newToken,
        ])->getJson('/api/translations');

        $newProtectedResponse->assertStatus(200);
    }

    #[Test]
    public function registration_validation_works_correctly()
    {
        // Test with missing fields
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        // Test with invalid email
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test with password confirmation mismatch
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Test with too short password
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function login_validation_works_correctly()
    {
        // Test with missing fields
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);

        // Test with invalid email
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test with non-existent user
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);

        // Test with wrong password
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function logout_requires_authentication()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    #[Test]
    public function protected_routes_require_authentication()
    {
        // Test various protected endpoints
        $protectedEndpoints = [
            ['method' => 'GET', 'url' => '/api/translations'],
            ['method' => 'POST', 'url' => '/api/translations'],
            ['method' => 'GET', 'url' => '/api/translations/1'],
            ['method' => 'PUT', 'url' => '/api/translations/1'],
            ['method' => 'DELETE', 'url' => '/api/translations/1'],
            ['method' => 'GET', 'url' => '/api/translations/search/tags/web'],
            ['method' => 'GET', 'url' => '/api/translations/search/keys/welcome'],
            ['method' => 'GET', 'url' => '/api/translations/search/content/welcome'],
        ];

        foreach ($protectedEndpoints as $endpoint) {
            $response = $this->json($endpoint['method'], $endpoint['url']);
            $response->assertStatus(401, "Endpoint {$endpoint['method']} {$endpoint['url']} should require authentication");
        }

        // Test that export endpoint is public (doesn't require authentication)
        $response = $this->getJson('/api/translations/export/en');
        $response->assertStatus(200);
    }
} 