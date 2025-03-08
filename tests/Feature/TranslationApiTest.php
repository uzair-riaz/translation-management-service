<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationApiTest extends TestCase
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
        // Create a translation with tags
        $translation = Translation::factory()->create();
        $tag = Tag::factory()->create(['name' => 'test-tag']);
        $translation->tags()->attach($tag->id);

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/tags/test-tag');

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
                            'tags',
                        ],
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_can_search_translations_by_key()
    {
        // Create a translation with a specific key
        Translation::factory()->create(['key' => 'test.search.key']);

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/keys/search');

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
                            'tags',
                        ],
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_can_search_translations_by_content()
    {
        // Create a translation with specific content
        Translation::factory()->create(['value' => 'This is a searchable content']);

        // Make the request with authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/translations/search/content/searchable');

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
                            'tags',
                        ],
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_can_export_translations_as_json()
    {
        // Create translations for a specific locale
        Translation::factory()->count(5)->create(['locale' => 'en']);

        // Make the request (no authentication required for export)
        $response = $this->getJson('/api/translations/export/en');

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonStructure([]);
    }

    #[Test]
    public function it_returns_translations_in_less_than_500ms()
    {
        // Create a large number of translations
        Translation::factory()->count(1000)->create(['locale' => 'en']);

        // Measure the response time
        $startTime = microtime(true);
        
        $response = $this->getJson('/api/translations/export/en');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert the response time is less than 500ms
        $this->assertLessThan(500, $responseTime, "Response time should be less than 500ms, but was {$responseTime}ms");
        
        // Assert the response
        $response->assertStatus(200);
    }
} 