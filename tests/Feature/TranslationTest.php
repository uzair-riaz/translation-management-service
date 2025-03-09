<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and generate a token
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    #[Test]
    public function it_can_list_translations()
    {
        // Create some translations
        Translation::factory()->count(5)->create();

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations');

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'key',
                            'value',
                            'locale',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'current_page',
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links',
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                ],
            ]);
    }

    #[Test]
    public function it_can_list_translations_with_limit_and_offset()
    {
        // Create some translations
        Translation::factory()->count(10)->create();

        // Test with limit only
        $responseWithLimit = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations?limit=3');

        $responseWithLimit->assertStatus(200);
        $this->assertCount(3, $responseWithLimit->json('data.data'));

        // Test with limit and offset
        $responseWithOffset = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations?limit=3&offset=3');

        $responseWithOffset->assertStatus(200);
        $this->assertCount(3, $responseWithOffset->json('data.data'));
        
        // Ensure different pages have different data
        $this->assertNotEquals(
            $responseWithLimit->json('data.data.0.id'),
            $responseWithOffset->json('data.data.0.id')
        );
    }

    #[Test]
    public function it_can_list_translations_by_locale()
    {
        // Create translations with different locales
        Translation::factory()->count(3)->create(['locale' => 'en']);
        Translation::factory()->count(2)->create(['locale' => 'fr']);

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations?locale=en');

        // Assert the response
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.total'));
    }

    #[Test]
    public function it_can_create_a_translation()
    {
        // Create test data
        $data = [
            'key' => 'test.key',
            'value' => 'Test Value',
            'locale' => 'en',
            'tags' => ['web', 'mobile'],
        ];

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/translations', $data);

        // Assert the response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'key',
                    'value',
                    'locale',
                    'created_at',
                    'updated_at',
                    'tags',
                ],
            ]);

        // Assert the data was stored in the database
        $this->assertDatabaseHas('translations', [
            'key' => 'test.key',
            'value' => 'Test Value',
            'locale' => 'en',
        ]);

        // Assert the tags were created and attached
        $this->assertDatabaseHas('tags', ['name' => 'web']);
        $this->assertDatabaseHas('tags', ['name' => 'mobile']);
    }

    #[Test]
    public function it_can_show_a_translation()
    {
        // Create a translation
        $translation = Translation::factory()->create();

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/' . $translation->id);

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'key',
                    'value',
                    'locale',
                    'created_at',
                    'updated_at',
                    'tags',
                ],
            ]);
    }

    #[Test]
    public function it_can_update_a_translation()
    {
        // Create a translation
        $translation = Translation::factory()->create();

        // Create test data
        $data = [
            'value' => 'Updated Value',
            'tags' => ['web', 'desktop'],
        ];

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/translations/' . $translation->id, $data);

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'key',
                    'value',
                    'locale',
                    'created_at',
                    'updated_at',
                    'tags',
                ],
            ]);

        // Assert the data was updated in the database
        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'value' => 'Updated Value',
        ]);

        // Assert the tags were created and attached
        $this->assertDatabaseHas('tags', ['name' => 'web']);
        $this->assertDatabaseHas('tags', ['name' => 'desktop']);
    }

    #[Test]
    public function it_can_delete_a_translation()
    {
        // Create a translation
        $translation = Translation::factory()->create();

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/translations/' . $translation->id);

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
            ]);

        // Assert the data was deleted from the database
        $this->assertDatabaseMissing('translations', [
            'id' => $translation->id,
        ]);
    }

    #[Test]
    public function it_can_search_translations_by_tag()
    {
        // Create a tag
        $tag = Tag::factory()->create(['name' => 'web']);
        
        // Create translations and attach the tag
        $translation = Translation::factory()->create();
        $translation->tags()->attach($tag->id);
        
        // Create another translation without the tag
        Translation::factory()->create();

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/tags/web');

        // Assert the response
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
    }

    #[Test]
    public function it_can_search_translations_by_tag_with_limit_and_offset()
    {
        // Create a tag
        $tag = Tag::factory()->create(['name' => 'web']);
        
        // Create 10 translations and attach the tag
        for ($i = 0; $i < 10; $i++) {
            $translation = Translation::factory()->create();
            $translation->tags()->attach($tag->id);
        }

        // Test with limit only
        $responseWithLimit = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/tags/web?limit=3');

        $responseWithLimit->assertStatus(200);
        $this->assertCount(3, $responseWithLimit->json('data.data'));

        // Test with limit and offset
        $responseWithOffset = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/tags/web?limit=3&offset=3');

        $responseWithOffset->assertStatus(200);
        $this->assertCount(3, $responseWithOffset->json('data.data'));
        
        // Ensure different pages have different data
        $this->assertNotEquals(
            $responseWithLimit->json('data.data.0.id'),
            $responseWithOffset->json('data.data.0.id')
        );
    }

    #[Test]
    public function it_can_search_translations_by_key()
    {
        // Create translations with specific keys
        Translation::factory()->create(['key' => 'welcome.message']);
        Translation::factory()->create(['key' => 'login.title']);

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/keys/welcome');

        // Assert the response
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
    }

    #[Test]
    public function it_can_search_translations_by_key_with_limit_and_offset()
    {
        // Create 10 translations with similar keys
        for ($i = 0; $i < 10; $i++) {
            Translation::factory()->create(['key' => "welcome.message{$i}"]);
        }

        // Test with limit only
        $responseWithLimit = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/keys/welcome?limit=3');

        $responseWithLimit->assertStatus(200);
        $this->assertCount(3, $responseWithLimit->json('data.data'));

        // Test with limit and offset
        $responseWithOffset = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/keys/welcome?limit=3&offset=3');

        $responseWithOffset->assertStatus(200);
        $this->assertCount(3, $responseWithOffset->json('data.data'));
        
        // Ensure different pages have different data
        $this->assertNotEquals(
            $responseWithLimit->json('data.data.0.id'),
            $responseWithOffset->json('data.data.0.id')
        );
    }

    #[Test]
    public function it_can_search_translations_by_content()
    {
        // Create translations with specific content
        Translation::factory()->create(['value' => 'Welcome to our application']);
        Translation::factory()->create(['value' => 'Login to your account']);

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/content/Welcome');

        // Assert the response
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
    }

    #[Test]
    public function it_can_search_translations_by_content_with_limit_and_offset()
    {
        // Create 10 translations with similar content
        for ($i = 0; $i < 10; $i++) {
            Translation::factory()->create(['value' => "Welcome message {$i}"]);
        }

        // Test with limit only
        $responseWithLimit = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/content/Welcome?limit=3');

        $responseWithLimit->assertStatus(200);
        $this->assertCount(3, $responseWithLimit->json('data.data'));

        // Test with limit and offset
        $responseWithOffset = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/content/Welcome?limit=3&offset=3');

        $responseWithOffset->assertStatus(200);
        $this->assertCount(3, $responseWithOffset->json('data.data'));
        
        // Ensure different pages have different data
        $this->assertNotEquals(
            $responseWithLimit->json('data.data.0.id'),
            $responseWithOffset->json('data.data.0.id')
        );
    }

    #[Test]
    public function it_can_export_translations_as_json()
    {
        // Create translations for a specific locale
        Translation::factory()->count(5)->create(['locale' => 'en']);

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/export/en');

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonStructure([]);
    }
}
