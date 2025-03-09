<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Translation;
use App\Repositories\TranslationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $translationRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translationRepository = new TranslationRepository(new Translation());
    }

    #[Test]
    public function it_can_create_a_translation()
    {
        $data = [
            'key' => 'welcome.message',
            'value' => 'Welcome to our application',
            'locale' => 'en',
        ];

        $translation = $this->translationRepository->create($data);

        $this->assertInstanceOf(Translation::class, $translation);
        $this->assertEquals('welcome.message', $translation->key);
        $this->assertEquals('Welcome to our application', $translation->value);
        $this->assertEquals('en', $translation->locale);
        $this->assertDatabaseHas('translations', $data);
    }

    #[Test]
    public function it_can_update_a_translation()
    {
        $translation = Translation::factory()->create([
            'key' => 'welcome.message',
            'value' => 'Welcome to our application',
            'locale' => 'en',
        ]);

        $updatedData = [
            'value' => 'Updated welcome message',
        ];

        $updatedTranslation = $this->translationRepository->update($updatedData, $translation->id);

        $this->assertInstanceOf(Translation::class, $updatedTranslation);
        $this->assertEquals('welcome.message', $updatedTranslation->key);
        $this->assertEquals('Updated welcome message', $updatedTranslation->value);
        $this->assertEquals('en', $updatedTranslation->locale);
        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'value' => 'Updated welcome message',
        ]);
    }

    #[Test]
    public function it_can_delete_a_translation()
    {
        $translation = Translation::factory()->create();

        $result = $this->translationRepository->delete($translation->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('translations', [
            'id' => $translation->id,
        ]);
    }

    #[Test]
    public function it_can_find_a_translation_by_id()
    {
        $translation = Translation::factory()->create();

        $foundTranslation = $this->translationRepository->find($translation->id);

        $this->assertInstanceOf(Translation::class, $foundTranslation);
        $this->assertEquals($translation->id, $foundTranslation->id);
    }

    #[Test]
    public function it_can_get_translations_by_locale()
    {
        Translation::factory()->count(3)->create(['locale' => 'en']);
        Translation::factory()->count(2)->create(['locale' => 'fr']);

        $enTranslations = $this->translationRepository->getByLocale('en');
        $frTranslations = $this->translationRepository->getByLocale('fr');

        $this->assertEquals(3, $enTranslations->count());
        $this->assertEquals(2, $frTranslations->count());
    }

    #[Test]
    public function it_can_get_translations_by_locale_with_limit_and_offset()
    {
        Translation::factory()->count(5)->create(['locale' => 'en']);

        // Test with limit only
        $limitedTranslations = $this->translationRepository->getByLocale('en', 2);
        $this->assertEquals(2, $limitedTranslations->count());

        // Test with limit and offset
        $offsetTranslations = $this->translationRepository->getByLocale('en', 2, 2);
        $this->assertEquals(2, $offsetTranslations->count());
        $this->assertNotEquals(
            $limitedTranslations->first()->id,
            $offsetTranslations->first()->id
        );
    }

    #[Test]
    public function it_can_search_translations_by_tag()
    {
        // Create tags
        $webTag = Tag::factory()->create(['name' => 'web']);
        $mobileTag = Tag::factory()->create(['name' => 'mobile']);
        
        // Create translations with tags
        $webTranslation = Translation::factory()->create(['locale' => 'en']);
        $webTranslation->tags()->attach($webTag->id);
        
        $mobileTranslation = Translation::factory()->create(['locale' => 'en']);
        $mobileTranslation->tags()->attach($mobileTag->id);
        
        $bothTranslation = Translation::factory()->create(['locale' => 'fr']);
        $bothTranslation->tags()->attach([$webTag->id, $mobileTag->id]);
        
        // Search by web tag
        $webResults = $this->translationRepository->searchByTag('web');
        $this->assertEquals(2, $webResults->count());
        
        // Search by mobile tag
        $mobileResults = $this->translationRepository->searchByTag('mobile');
        $this->assertEquals(2, $mobileResults->count());
        
        // Search by tag with locale filter
        $frResults = $this->translationRepository->searchByTag('web', 'fr');
        $this->assertEquals(1, $frResults->count());
    }

    #[Test]
    public function it_can_search_translations_by_tag_with_limit_and_offset()
    {
        // Create tag
        $webTag = Tag::factory()->create(['name' => 'web']);
        
        // Create 5 translations with the same tag
        for ($i = 0; $i < 5; $i++) {
            $translation = Translation::factory()->create(['locale' => 'en']);
            $translation->tags()->attach($webTag->id);
        }
        
        // Test with limit only
        $limitedResults = $this->translationRepository->searchByTag('web', null, 2);
        $this->assertEquals(2, $limitedResults->count());
        
        // Test with limit and offset
        $offsetResults = $this->translationRepository->searchByTag('web', null, 2, 2);
        $this->assertEquals(2, $offsetResults->count());
        $this->assertNotEquals(
            $limitedResults->first()->id,
            $offsetResults->first()->id
        );
    }

    #[Test]
    public function it_can_search_translations_by_key()
    {
        // Create translations with different keys
        Translation::factory()->create([
            'key' => 'welcome.message',
            'locale' => 'en'
        ]);
        
        Translation::factory()->create([
            'key' => 'welcome.title',
            'locale' => 'en'
        ]);
        
        Translation::factory()->create([
            'key' => 'welcome.message',
            'locale' => 'fr'
        ]);
        
        // Search by key
        $results = $this->translationRepository->searchByKey('welcome');
        $this->assertEquals(3, $results->count());
        
        // Search by specific key
        $messageResults = $this->translationRepository->searchByKey('message');
        $this->assertEquals(2, $messageResults->count());
        
        // Search by key with locale filter
        $frResults = $this->translationRepository->searchByKey('welcome', 'fr');
        $this->assertEquals(1, $frResults->count());
    }

    #[Test]
    public function it_can_search_translations_by_key_with_limit_and_offset()
    {
        // Create 5 translations with similar keys
        for ($i = 0; $i < 5; $i++) {
            Translation::factory()->create([
                'key' => "welcome.message{$i}",
                'locale' => 'en'
            ]);
        }
        
        // Test with limit only
        $limitedResults = $this->translationRepository->searchByKey('welcome', null, 2);
        $this->assertEquals(2, $limitedResults->count());
        
        // Test with limit and offset
        $offsetResults = $this->translationRepository->searchByKey('welcome', null, 2, 2);
        $this->assertEquals(2, $offsetResults->count());
        $this->assertNotEquals(
            $limitedResults->first()->id,
            $offsetResults->first()->id
        );
    }

    #[Test]
    public function it_can_search_translations_by_content()
    {
        // Create translations with different content
        Translation::factory()->create([
            'value' => 'Welcome to our application',
            'locale' => 'en'
        ]);
        
        Translation::factory()->create([
            'value' => 'Hello world',
            'locale' => 'en'
        ]);
        
        Translation::factory()->create([
            'value' => 'Bienvenue à notre application',
            'locale' => 'fr'
        ]);
        
        // Search by content
        $results = $this->translationRepository->searchByContent('Welcome');
        $this->assertEquals(1, $results->count());
        
        // Search by content with locale filter
        $frResults = $this->translationRepository->searchByContent('Bienvenue', 'fr');
        $this->assertEquals(1, $frResults->count());
    }

    #[Test]
    public function it_can_search_translations_by_content_with_limit_and_offset()
    {
        // Create 5 translations with similar content
        for ($i = 0; $i < 5; $i++) {
            Translation::factory()->create([
                'value' => "Welcome message {$i}",
                'locale' => 'en'
            ]);
        }
        
        // Test with limit only
        $limitedResults = $this->translationRepository->searchByContent('Welcome', null, 2);
        $this->assertEquals(2, $limitedResults->count());
        
        // Test with limit and offset
        $offsetResults = $this->translationRepository->searchByContent('Welcome', null, 2, 2);
        $this->assertEquals(2, $offsetResults->count());
        $this->assertNotEquals(
            $limitedResults->first()->id,
            $offsetResults->first()->id
        );
    }

    #[Test]
    public function it_can_export_translations_by_locale()
    {
        Translation::factory()->create([
            'key' => 'welcome.message',
            'value' => 'Welcome to our application',
            'locale' => 'en',
        ]);
        Translation::factory()->create([
            'key' => 'login.title',
            'value' => 'Login to your account',
            'locale' => 'en',
        ]);
        Translation::factory()->create([
            'key' => 'welcome.message',
            'value' => 'Bienvenue dans notre application',
            'locale' => 'fr',
        ]);

        $enExport = $this->translationRepository->exportByLocale('en');
        $frExport = $this->translationRepository->exportByLocale('fr');

        $this->assertCount(2, $enExport);
        $this->assertCount(1, $frExport);

        $this->assertEquals('Welcome to our application', $enExport['welcome.message']);
        $this->assertEquals('Login to your account', $enExport['login.title']);
        $this->assertEquals('Bienvenue dans notre application', $frExport['welcome.message']);
    }

    #[Test]
    public function it_can_attach_tags_to_a_translation()
    {
        $translation = Translation::factory()->create();
        $tag1 = Tag::factory()->create(['name' => 'web']);
        $tag2 = Tag::factory()->create(['name' => 'mobile']);

        $this->translationRepository->attachTags($translation->id, [$tag1->id, $tag2->id]);

        $this->assertEquals(2, $translation->tags()->count());
        $this->assertTrue($translation->tags->pluck('id')->contains($tag1->id));
        $this->assertTrue($translation->tags->pluck('id')->contains($tag2->id));
    }

    #[Test]
    public function it_can_sync_tags_for_a_translation()
    {
        $translation = Translation::factory()->create();
        $tag1 = Tag::factory()->create(['name' => 'web']);
        $tag2 = Tag::factory()->create(['name' => 'mobile']);
        $tag3 = Tag::factory()->create(['name' => 'desktop']);

        // First attach two tags
        $translation->tags()->attach([$tag1->id, $tag2->id]);
        $this->assertEquals(2, $translation->tags()->count());

        // Then sync with a different set of tags
        $this->translationRepository->syncTags($translation->id, [$tag2->id, $tag3->id]);

        // Refresh the model
        $translation->refresh();

        // Should now have only tag2 and tag3
        $this->assertEquals(2, $translation->tags()->count());
        $this->assertFalse($translation->tags->pluck('id')->contains($tag1->id));
        $this->assertTrue($translation->tags->pluck('id')->contains($tag2->id));
        $this->assertTrue($translation->tags->pluck('id')->contains($tag3->id));
    }

    #[Test]
    public function it_can_check_if_translation_exists_by_key_and_locale()
    {
        Translation::factory()->create([
            'key' => 'welcome.message',
            'locale' => 'en',
        ]);

        $exists = $this->translationRepository->existsByKeyAndLocale('welcome.message', 'en');
        $notExists = $this->translationRepository->existsByKeyAndLocale('welcome.message', 'fr');

        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }
} 