<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for tests that require an authenticated user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Generate a token for authenticated requests
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    #[Test]
    public function it_can_register_a_new_user()
    {
        $registerData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $registerData);

        $response->assertStatus(201)
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

        // Verify the user was created in the database
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);

        // Verify a token was created
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user->tokens()->first());
    }

    #[Test]
    public function it_can_login_a_user()
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user',
                    'access_token',
                    'token_type',
                ],
            ]);

        // Verify a new token was created
        $this->assertNotEmpty($response->json('data.access_token'));
    }

    #[Test]
    public function it_can_logout_a_user()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User logged out successfully',
            ]);

        // Verify the token was revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function it_can_access_protected_routes_with_valid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_validates_registration_input()
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
            'email' => 'valid@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Test with too short password
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'valid@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function it_validates_login_input()
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
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_authentication_for_logout()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_authentication_for_protected_routes()
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
    }

    #[Test]
    public function it_allows_public_access_to_export_endpoint()
    {
        // Test that export endpoint is public (doesn't require authentication)
        $response = $this->getJson('/api/translations/export/en');
        $response->assertStatus(200);
    }
}
