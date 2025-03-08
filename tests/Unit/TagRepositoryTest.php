<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Repositories\TagRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TagRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $tagRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tagRepository = new TagRepository(new Tag());
    }

    #[Test]
    public function it_can_find_or_create_a_tag()
    {
        // Test creating a new tag
        $tag = $this->tagRepository->findOrCreate('web');
        
        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertEquals('web', $tag->name);
        $this->assertDatabaseHas('tags', ['name' => 'web']);
        
        // Test finding an existing tag
        $sameTag = $this->tagRepository->findOrCreate('web');
        
        $this->assertEquals($tag->id, $sameTag->id);
        $this->assertEquals(1, Tag::count());
    }

    #[Test]
    public function it_can_get_tag_ids_from_names()
    {
        // Create some tags first
        Tag::factory()->create(['name' => 'web']);
        Tag::factory()->create(['name' => 'mobile']);
        
        // Test with existing tags
        $tagIds = $this->tagRepository->getTagIdsFromNames(['web', 'mobile']);
        
        $this->assertCount(2, $tagIds);
        $this->assertEquals(Tag::where('name', 'web')->first()->id, $tagIds[0]);
        $this->assertEquals(Tag::where('name', 'mobile')->first()->id, $tagIds[1]);
        
        // Test with a mix of existing and new tags
        $tagIds = $this->tagRepository->getTagIdsFromNames(['web', 'desktop']);
        
        $this->assertCount(2, $tagIds);
        $this->assertEquals(Tag::where('name', 'web')->first()->id, $tagIds[0]);
        $this->assertEquals(Tag::where('name', 'desktop')->first()->id, $tagIds[1]);
        
        // Verify the new tag was created
        $this->assertDatabaseHas('tags', ['name' => 'desktop']);
        $this->assertEquals(3, Tag::count());
    }

    #[Test]
    public function it_handles_empty_tag_names_array()
    {
        $tagIds = $this->tagRepository->getTagIdsFromNames([]);
        
        $this->assertIsArray($tagIds);
        $this->assertEmpty($tagIds);
    }

    #[Test]
    public function it_handles_duplicate_tag_names()
    {
        $tagIds = $this->tagRepository->getTagIdsFromNames(['web', 'web', 'web']);
        
        $this->assertCount(3, $tagIds);
        $this->assertEquals($tagIds[0], $tagIds[1]);
        $this->assertEquals($tagIds[0], $tagIds[2]);
        $this->assertEquals(1, Tag::count());
    }
} 