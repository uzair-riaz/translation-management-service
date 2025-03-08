<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    #[Test]
    public function it_can_register_a_user()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $result = $this->authService->register($data);

        // Assert user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Assert result structure
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('token_type', $result);

        // Assert user data
        $this->assertEquals('Test User', $result['user']->name);
        $this->assertEquals('test@example.com', $result['user']->email);

        // Assert token was created
        $this->assertNotEmpty($result['access_token']);
        $this->assertEquals('Bearer', $result['token_type']);

        // Assert password was hashed
        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    #[Test]
    public function it_can_login_a_user()
    {
        // Create a user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $result = $this->authService->login($credentials);

        // Assert result structure
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('token_type', $result);

        // Assert user data
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertEquals($user->email, $result['user']->email);

        // Assert token was created
        $this->assertNotEmpty($result['access_token']);
        $this->assertEquals('Bearer', $result['token_type']);
    }

    #[Test]
    public function it_throws_exception_for_invalid_credentials()
    {
        // Create a user
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ];

        $this->expectException(ValidationException::class);

        $this->authService->login($credentials);
    }

    #[Test]
    public function it_can_logout_a_user()
    {
        // Create a user with a token
        $user = User::factory()->create();
        $user->createToken('auth_token');

        // Assert token exists
        $this->assertEquals(1, $user->tokens()->count());

        // Logout
        $result = $this->authService->logout($user->id);

        // Assert result
        $this->assertTrue($result);

        // Assert token was deleted
        $this->assertEquals(0, $user->tokens()->count());
    }

    #[Test]
    public function it_returns_false_when_logging_out_nonexistent_user()
    {
        $result = $this->authService->logout(999);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_deletes_existing_tokens_when_logging_in()
    {
        // Create a user with a token
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        
        $initialToken = $user->createToken('initial_token')->plainTextToken;
        
        // Assert initial token exists
        $this->assertEquals(1, $user->tokens()->count());
        
        // Login again
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];
        
        $result = $this->authService->login($credentials);
        
        // Assert new token is different
        $this->assertNotEquals($initialToken, $result['access_token']);
        
        // Assert only one token exists (old one was deleted)
        $this->assertEquals(1, $user->tokens()->count());
    }
} 