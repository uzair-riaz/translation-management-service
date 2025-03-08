<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user and generate a token
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
        $this->headers = ['Authorization' => 'Bearer ' . $this->token];
        
        // Create some tags
        Tag::factory()->create(['name' => 'web']);
        Tag::factory()->create(['name' => 'mobile']);
        Tag::factory()->create(['name' => 'desktop']);
    }

    /** @test */
    public function complete_translation_flow_works_correctly()
    {
        // 1. Create a new translation
        $createData = [
            'key' => 'welcome.message',
            'value' => 'Welcome to our application',
            'locale' => 'en',
            'tags' => ['web', 'mobile']
        ];

        $createResponse = $this->withHeaders($this->headers)
            ->postJson('/api/translations', $createData);

        $createResponse->assertStatus(201)
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
                    'tags'
                ]
            ]);

        // Extract the translation ID
        $translationId = $createResponse->json('data.id');
        $this->assertNotNull($translationId);

        // 2. Verify the translation was created in the database
        $this->assertDatabaseHas('translations', [
            'id' => $translationId,
            'key' => 'welcome.message',
            'value' => 'Welcome to our application',
            'locale' => 'en'
        ]);

        // 3. Verify the tags were attached
        $translation = Translation::find($translationId);
        $this->assertEquals(2, $translation->tags->count());
        $this->assertTrue($translation->tags->pluck('name')->contains('web'));
        $this->assertTrue($translation->tags->pluck('name')->contains('mobile'));

        // 4. Get the translation by ID
        $showResponse = $this->withHeaders($this->headers)
            ->getJson("/api/translations/{$translationId}");

        $showResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'key',
                    'value',
                    'locale',
                    'created_at',
                    'updated_at',
                    'tags'
                ]
            ]);

        // 5. Update the translation
        $updateData = [
            'value' => 'Updated welcome message',
            'tags' => ['web', 'desktop']
        ];

        $updateResponse = $this->withHeaders($this->headers)
            ->putJson("/api/translations/{$translationId}", $updateData);

        $updateResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ]);

        // 6. Verify the translation was updated
        $this->assertDatabaseHas('translations', [
            'id' => $translationId,
            'value' => 'Updated welcome message'
        ]);

        // 7. Verify the tags were updated
        $translation->refresh();
        $this->assertEquals(2, $translation->tags->count());
        $this->assertTrue($translation->tags->pluck('name')->contains('web'));
        $this->assertTrue($translation->tags->pluck('name')->contains('desktop'));
        $this->assertFalse($translation->tags->pluck('name')->contains('mobile'));

        // 8. Search for the translation by tag
        $tagSearchResponse = $this->withHeaders($this->headers)
            ->getJson('/api/translations/search/tags/web');

        $tagSearchResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data'
            ]);

        $this->assertNotEmpty($tagSearchResponse->json('data.data'));
        $this->assertTrue(collect($tagSearchResponse->json('data.data'))->pluck('id')->contains($translationId));

        // 9. Search for the translation by key
        $keySearchResponse = $this->withHeaders($this->headers)
            ->getJson('/api/translations/search/keys/welcome');

        $keySearchResponse->assertStatus(200);
        $this->assertNotEmpty($keySearchResponse->json('data.data'));
        $this->assertTrue(collect($keySearchResponse->json('data.data'))->pluck('id')->contains($translationId));

        // 10. Search for the translation by content
        $contentSearchResponse = $this->withHeaders($this->headers)
            ->getJson('/api/translations/search/content/Updated');

        $contentSearchResponse->assertStatus(200);
        $this->assertNotEmpty($contentSearchResponse->json('data.data'));
        $this->assertTrue(collect($contentSearchResponse->json('data.data'))->pluck('id')->contains($translationId));

        // 11. Export translations
        $exportResponse = $this->getJson('/api/translations/export/en');

        $exportResponse->assertStatus(200)
            ->assertJsonStructure([
                'welcome.message'
            ]);

        $this->assertEquals('Updated welcome message', $exportResponse->json('welcome.message'));

        // 12. Delete the translation
        $deleteResponse = $this->withHeaders($this->headers)
            ->deleteJson("/api/translations/{$translationId}");

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Translation deleted successfully'
            ]);

        // 13. Verify the translation was deleted
        $this->assertDatabaseMissing('translations', [
            'id' => $translationId
        ]);

        // 14. Verify the tag relationships were deleted
        $this->assertDatabaseMissing('translation_tag', [
            'translation_id' => $translationId
        ]);

        // 15. Verify the translation is no longer in the export
        $exportAfterDeleteResponse = $this->getJson('/api/translations/export/en');
        $this->assertArrayNotHasKey('welcome.message', $exportAfterDeleteResponse->json());
    }

    /** @test */
    public function translation_validation_works_correctly()
    {
        // Test with missing fields
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/translations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key', 'value', 'locale', 'tags']);

        // Test with invalid locale
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/translations', [
                'key' => 'welcome.message',
                'value' => 'Welcome to our application',
                'locale' => str_repeat('x', 20), // Too long
                'tags' => ['web']
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);

        // Test with empty tags array
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/translations', [
                'key' => 'welcome.message',
                'value' => 'Welcome to our application',
                'locale' => 'en',
                'tags' => []
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);

        // Test with duplicate key and locale
        $existingTranslation = Translation::factory()->create([
            'key' => 'existing.key',
            'locale' => 'en'
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/translations', [
                'key' => 'existing.key',
                'value' => 'New value',
                'locale' => 'en',
                'tags' => ['web']
            ]);

        $response->assertStatus(409);
    }

    /** @test */
    public function update_validation_works_correctly()
    {
        // Create a translation to update
        $translation = Translation::factory()->create();
        $translation->tags()->attach(Tag::where('name', 'web')->first()->id);

        // Test with missing fields
        $response = $this->withHeaders($this->headers)
            ->putJson("/api/translations/{$translation->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['value', 'tags']);

        // Test with empty tags array
        $response = $this->withHeaders($this->headers)
            ->putJson("/api/translations/{$translation->id}", [
                'value' => 'Updated value',
                'tags' => []
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);

        // Test with non-existent translation
        $response = $this->withHeaders($this->headers)
            ->putJson('/api/translations/9999', [
                'value' => 'Updated value',
                'tags' => ['web']
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function delete_validation_works_correctly()
    {
        // Test with non-existent translation
        $response = $this->withHeaders($this->headers)
            ->deleteJson('/api/translations/9999');

        $response->assertStatus(404);
    }

    /** @test */
    public function search_endpoints_return_correct_results()
    {
        // Create translations with different tags, keys, and content
        $translation1 = Translation::factory()->create([
            'key' => 'welcome.message',
            'value' => 'Welcome to our application',
            'locale' => 'en'
        ]);
        $translation1->tags()->attach(Tag::where('name', 'web')->first()->id);

        $translation2 = Translation::factory()->create([
            'key' => 'login.title',
            'value' => 'Login to your account',
            'locale' => 'en'
        ]);
        $translation2->tags()->attach(Tag::where('name', 'mobile')->first()->id);

        $translation3 = Translation::factory()->create([
            'key' => 'welcome.subtitle',
            'value' => 'Explore our features',
            'locale' => 'fr'
        ]);
        $translation3->tags()->attach(Tag::where('name', 'desktop')->first()->id);

        // Test tag search
        $tagResponse = $this->withHeaders($this->headers)
            ->getJson('/api/translations/search/tags/web');

        $tagResponse->assertStatus(200);
        $this->assertEquals(1, count($tagResponse->json('data.data')));
        $this->assertEquals($translation1->id, $tagResponse->json('data.data.0.id'));

        // Test key search
        $keyResponse = $this->withHeaders($this->headers)
            ->getJson('/api/translations/search/keys/welcome');

        $keyResponse->assertStatus(200);
        $this->assertEquals(2, count($keyResponse->json('data.data')));

        // Test content search
        $contentResponse = $this->withHeaders($this->headers)
            ->getJson('/api/translations/search/content/Login');

        $contentResponse->assertStatus(200);
        $this->assertEquals(1, count($contentResponse->json('data.data')));
        $this->assertEquals($translation2->id, $contentResponse->json('data.data.0.id'));

        // Test locale filtering
        $localeResponse = $this->withHeaders($this->headers)
            ->getJson('/api/translations?locale=fr');

        $localeResponse->assertStatus(200);
        $this->assertEquals(1, count($localeResponse->json('data.data')));
        $this->assertEquals($translation3->id, $localeResponse->json('data.data.0.id'));

        // Test export by locale
        $exportResponse = $this->getJson('/api/translations/export/en');

        $exportResponse->assertStatus(200)
            ->assertJsonStructure([
                'welcome.message',
                'login.title'
            ]);
        $this->assertArrayNotHasKey('welcome.subtitle', $exportResponse->json());
    }
} 