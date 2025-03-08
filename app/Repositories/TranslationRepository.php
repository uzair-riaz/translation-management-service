<?php

namespace App\Repositories;

use App\Interfaces\TranslationRepositoryInterface;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;

class TranslationRepository extends BaseRepository implements TranslationRepositoryInterface
{
    /**
     * TranslationRepository constructor.
     *
     * @param Translation $model
     */
    public function __construct(Translation $model)
    {
        parent::__construct($model);
    }

    /**
     * Get translations by locale.
     *
     * @param string $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByLocale(string $locale, int $perPage = 15)
    {
        return $this->model->with('tags')
            ->locale($locale)
            ->paginate($perPage);
    }

    /**
     * Search translations by tag.
     *
     * @param string $tag
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByTag(string $tag, ?string $locale = null, int $perPage = 15)
    {
        $query = $this->model->whereHas('tags', function ($query) use ($tag) {
            $query->where('name', 'LIKE', "%{$tag}%");
        });

        if ($locale) {
            $query->locale($locale);
        }

        return $query->with('tags')->paginate($perPage);
    }

    /**
     * Search translations by key.
     *
     * @param string $key
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByKey(string $key, ?string $locale = null, int $perPage = 15)
    {
        $query = $this->model->searchByKey($key);

        if ($locale) {
            $query->locale($locale);
        }

        return $query->with('tags')->paginate($perPage);
    }

    /**
     * Search translations by content.
     *
     * @param string $content
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByContent(string $content, ?string $locale = null, int $perPage = 15)
    {
        // Use MySQL's MATCH AGAINST for fulltext search which is much faster
        // than LIKE queries for large datasets
        $query = $this->model->whereRaw('MATCH(value) AGAINST(? IN BOOLEAN MODE)', [$content . '*']);

        if ($locale) {
            $query->locale($locale);
        }

        return $query->with('tags')->paginate($perPage);
    }

    /**
     * Export translations by locale as a flat key-value object.
     *
     * @param string $locale
     * @return array
     */
    public function exportByLocale(string $locale)
    {
        // Use a more efficient query for large datasets
        // Select only the needed columns and use a cursor for memory efficiency
        $translations = $this->model->locale($locale)
            ->select('key', 'value')
            ->orderBy('key')
            ->cursor();
        
        // Use a generator to build the result array efficiently
        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->key] = $translation->value;
        }
        
        return $result;
    }

    /**
     * Attach tags to a translation.
     *
     * @param int $translationId
     * @param array $tagIds
     * @return void
     */
    public function attachTags(int $translationId, array $tagIds)
    {
        $translation = $this->find($translationId);
        $translation->tags()->attach($tagIds);
    }

    /**
     * Sync tags for a translation.
     *
     * @param int $translationId
     * @param array $tagIds
     * @return void
     */
    public function syncTags(int $translationId, array $tagIds)
    {
        $translation = $this->find($translationId);
        $translation->tags()->sync($tagIds);
    }

    /**
     * Check if a translation exists by key and locale.
     *
     * @param string $key
     * @param string $locale
     * @return bool
     */
    public function existsByKeyAndLocale(string $key, string $locale)
    {
        return $this->model->where('key', $key)
            ->where('locale', $locale)
            ->exists();
    }
} 