<?php

namespace App\Repositories;

use App\Interfaces\TagRepositoryInterface;
use App\Models\Tag;

class TagRepository extends BaseRepository implements TagRepositoryInterface
{
    /**
     * TagRepository constructor.
     *
     * @param Tag $model
     */
    public function __construct(Tag $model)
    {
        parent::__construct($model);
    }

    /**
     * Find or create a tag by name.
     *
     * @param string $name
     * @return \App\Models\Tag
     */
    public function findOrCreate(string $name)
    {
        return $this->model->firstOrCreate(['name' => $name]);
    }

    /**
     * Get tag IDs from tag names.
     *
     * @param array $tagNames
     * @return array
     */
    public function getTagIdsFromNames(array $tagNames)
    {
        $tagIds = [];
        
        foreach ($tagNames as $tagName) {
            $tag = $this->findOrCreate($tagName);
            $tagIds[] = $tag->id;
        }
        
        return $tagIds;
    }
} 