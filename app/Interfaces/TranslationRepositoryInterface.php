<?php

namespace App\Interfaces;

interface TranslationRepositoryInterface extends RepositoryInterface
{
    /**
     * Get translations by locale.
     *
     * @param string $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByLocale(string $locale, int $perPage = 15);

    /**
     * Search translations by tag.
     *
     * @param string $tag
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByTag(string $tag, ?string $locale = null, int $perPage = 15);

    /**
     * Search translations by key.
     *
     * @param string $key
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByKey(string $key, ?string $locale = null, int $perPage = 15);

    /**
     * Search translations by content.
     *
     * @param string $content
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByContent(string $content, ?string $locale = null, int $perPage = 15);

    /**
     * Export translations for a specific locale.
     *
     * @param string $locale
     * @return array
     */
    public function exportByLocale(string $locale);

    /**
     * Attach tags to a translation.
     *
     * @param int $translationId
     * @param array $tagIds
     * @return void
     */
    public function attachTags(int $translationId, array $tagIds);

    /**
     * Sync tags for a translation.
     *
     * @param int $translationId
     * @param array $tagIds
     * @return void
     */
    public function syncTags(int $translationId, array $tagIds);
} 