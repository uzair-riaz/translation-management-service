<?php

namespace App\Interfaces;

use App\Models\Tag;

interface TagRepositoryInterface extends RepositoryInterface
{
    /**
     * Find or create a tag by name.
     *
     * @param string $name
     * @return \App\Models\Tag
     */
    public function findOrCreate(string $name): Tag;

    /**
     * Get tag IDs from tag names.
     *
     * @param array $tagNames
     * @return array
     */
    public function getTagIdsFromNames(array $tagNames): array;
} 