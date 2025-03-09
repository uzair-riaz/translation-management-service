<?php

namespace App\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TranslationRepositoryInterface extends RepositoryInterface
{
    /**
     * Get translations by locale.
     *
     * @param string $locale
     * @param int|null $limit
     * @param int|null $offset
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByLocale(string $locale, ?int $limit = null, ?int $offset = null): LengthAwarePaginator;

    /**
     * Search translations by tag.
     *
     * @param string $tag
     * @param string|null $locale
     * @param int|null $limit
     * @param int|null $offset
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByTag(string $tag, ?string $locale = null, ?int $limit = null, ?int $offset = null): LengthAwarePaginator;

    /**
     * Search translations by key.
     *
     * @param string $key
     * @param string|null $locale
     * @param int|null $limit
     * @param int|null $offset
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByKey(string $key, ?string $locale = null, ?int $limit = null, ?int $offset = null): LengthAwarePaginator;

    /**
     * Search translations by content.
     *
     * @param string $content
     * @param string|null $locale
     * @param int|null $limit
     * @param int|null $offset
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByContent(string $content, ?string $locale = null, ?int $limit = null, ?int $offset = null): LengthAwarePaginator;

    /**
     * Export translations for a specific locale.
     *
     * @param string $locale
     * @return array
     */
    public function exportByLocale(string $locale): array;

    /**
     * Attach tags to a translation.
     *
     * @param int $translationId
     * @param array $tagIds
     * @return void
     */
    public function attachTags(int $translationId, array $tagIds): void;

    /**
     * Sync tags for a translation.
     *
     * @param int $translationId
     * @param array $tagIds
     * @return void
     */
    public function syncTags(int $translationId, array $tagIds): void;

    /**
     * Check if a translation exists by key and locale.
     *
     * @param string $key
     * @param string $locale
     * @return bool
     */
    public function existsByKeyAndLocale(string $key, string $locale): bool;
} 