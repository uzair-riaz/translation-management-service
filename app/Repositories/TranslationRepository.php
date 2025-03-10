<?php

namespace App\Repositories;

use App\Interfaces\TranslationRepositoryInterface;
use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * @param int|null $limit
     * @param int|null $offset
     * @return LengthAwarePaginator
     */
    public function getByLocale(string $locale, ?int $limit = null, ?int $offset = null): LengthAwarePaginator
    {
        $limit = $limit ?? 15; // Default limit is 15
        $page = $offset ? floor($offset / $limit) + 1 : 1;
        
        return $this->model->with('tags')
            ->locale($locale)
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Search translations by tag.
     *
     * @param string $tag
     * @param string|null $locale
     * @param int|null $limit
     * @param int|null $offset
     * @return LengthAwarePaginator
     */
    public function searchByTag(string $tag, ?string $locale = null, ?int $limit = null, ?int $offset = null): LengthAwarePaginator
    {
        $limit = $limit ?? 15; // Default limit is 15
        $page = $offset ? floor($offset / $limit) + 1 : 1;
        
        $query = $this->model->whereHas('tags', function ($query) use ($tag) {
            $query->where('name', 'LIKE', "%$tag%");
        });

        if ($locale) {
            $query->locale($locale);
        }

        return $query->with('tags')->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Search translations by key.
     *
     * @param string $key
     * @param string|null $locale
     * @param int|null $limit
     * @param int|null $offset
     * @return LengthAwarePaginator
     */
    public function searchByKey(string $key, ?string $locale = null, ?int $limit = null, ?int $offset = null): LengthAwarePaginator
    {
        $limit = $limit ?? 15; // Default limit is 15
        $page = $offset ? floor($offset / $limit) + 1 : 1;
        
        $query = $this->model->searchByKey($key);

        if ($locale) {
            $query->locale($locale);
        }

        return $query->with('tags')->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Search translations by content.
     *
     * @param string $content
     * @param string|null $locale
     * @param int|null $limit
     * @param int|null $offset
     * @return LengthAwarePaginator
     */
    public function searchByContent(string $content, ?string $locale = null, ?int $limit = null, ?int $offset = null): LengthAwarePaginator
    {
        $limit = $limit ?? 15; // Default limit is 15
        $page = $offset ? floor($offset / $limit) + 1 : 1;
        
        // Use MySQL's MATCH AGAINST for fulltext search which is much faster
        // than LIKE queries for large datasets
        $query = $this->model->where('value', 'LIKE', "%$content%");

        if ($locale) {
            $query->locale($locale);
        }

        return $query->with('tags')->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Export translations by locale as a flat key-value object.
     *
     * @param string $locale
     * @return array
     */
    public function exportByLocale(string $locale): array
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
    public function attachTags(int $translationId, array $tagIds): void
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
    public function syncTags(int $translationId, array $tagIds): void
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
    public function existsByKeyAndLocale(string $key, string $locale): bool
    {
        return $this->model->where('key', $key)
            ->where('locale', $locale)
            ->exists();
    }
}
