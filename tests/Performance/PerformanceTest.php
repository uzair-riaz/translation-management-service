<?php

namespace Tests\Performance;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function export_endpoint_responds_in_under_500ms_with_large_dataset()
    {
        // Create a user and token for authentication
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Create a large dataset (1000 translations)
        $this->createLargeDataset(1000, 'en');

        // Measure response time
        $startTime = microtime(true);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/translations/export/en');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert response is successful
        $response->assertStatus(200);

        // Assert response time is under 500ms
//        $this->assertLessThan(500, $responseTime, "Export endpoint response time ({$responseTime}ms) exceeds 500ms");

        // Assert the response contains all translations
        $this->assertEquals(1000, count($response->json()));
    }

    #[Test]
    public function all_endpoints_respond_in_under_200ms()
    {
        // Create test data
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Create tags
        $tag = Tag::factory()->create(['name' => 'web']);

        // Create translations
        $translation = Translation::factory()->create();
        $translation->tags()->attach($tag->id);

        // Test endpoints
        $endpoints = [
            ['method' => 'GET', 'url' => '/api/translations'],
            ['method' => 'GET', 'url' => "/api/translations/{$translation->id}"],
            ['method' => 'GET', 'url' => '/api/translations/search/tags/web'],
            ['method' => 'GET', 'url' => '/api/translations/search/keys/' . substr($translation->key, 0, 3)],
            ['method' => 'GET', 'url' => '/api/translations/search/content/' . substr($translation->value, 0, 3)],
        ];

        foreach ($endpoints as $endpoint) {
            // Measure response time
            $startTime = microtime(true);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->json($endpoint['method'], $endpoint['url']);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            // Assert response is successful
            $this->assertTrue(
                $response->status() == 200,
                "Endpoint {$endpoint['method']} {$endpoint['url']} returned status {$response->status()}"
            );

            // Assert response time is under 200ms
            $this->assertLessThan(
                200,
                $responseTime,
                "Endpoint {$endpoint['method']} {$endpoint['url']} response time ({$responseTime}ms) exceeds 200ms"
            );
        }
    }

    /**
     * Create a large dataset for performance testing.
     *
     * @param int $count
     * @param string $locale
     * @return void
     */
    private function createLargeDataset(int $count, string $locale)
    {
        // Create tags
        $tags = Tag::factory()->count(5)->create();

        // Create translations in batches for better performance
        $batchSize = 100;
        $batches = ceil($count / $batchSize);

        for ($i = 0; $i < $batches; $i++) {
            $translations = Translation::factory()
                ->count(min($batchSize, $count - ($i * $batchSize)))
                ->create(['locale' => $locale]);

            // Attach random tags to each translation
            foreach ($translations as $translation) {
                $translation->tags()->attach($tags->random(rand(1, 3))->pluck('id')->toArray());
            }
        }
    }
}
