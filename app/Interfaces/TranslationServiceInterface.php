<?php

namespace App\Interfaces;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TranslationServiceInterface
{
    /**
     * Get all translations with pagination.
     *
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllTranslations(?string $locale = null, int $perPage = 50): LengthAwarePaginator;

    /**
     * Create a new translation.
     *
     * @param array $data
     * @return \App\Models\Translation
     */
    public function createTranslation(array $data): Translation;

    /**
     * Get a translation by ID.
     *
     * @param int $id
     * @return \App\Models\Translation
     */
    public function getTranslationById(int $id): Translation;

    /**
     * Update a translation.
     *
     * @param int $id
     * @param array $data
     * @return \App\Models\Translation
     */
    public function updateTranslation(int $id, array $data): Translation;

    /**
     * Delete a translation.
     *
     * @param int $id
     * @return bool
     */
    public function deleteTranslation(int $id): bool;

    /**
     * Search translations by tag.
     *
     * @param string $tag
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByTag(string $tag, ?string $locale = null, int $perPage = 50): LengthAwarePaginator;

    /**
     * Search translations by key.
     *
     * @param string $key
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByKey(string $key, ?string $locale = null, int $perPage = 50): LengthAwarePaginator;

    /**
     * Search translations by content.
     *
     * @param string $content
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByContent(string $content, ?string $locale = null, int $perPage = 50): LengthAwarePaginator;

    /**
     * Export translations for a specific locale.
     *
     * @param string|null $locale
     * @return array
     */
    public function exportTranslations(?string $locale = null): array;
} 