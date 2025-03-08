<?php

namespace App\Interfaces;

interface TranslationServiceInterface
{
    /**
     * Get all translations with pagination.
     *
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllTranslations(?string $locale = null, int $perPage = 50);

    /**
     * Create a new translation.
     *
     * @param array $data
     * @return \App\Models\Translation
     */
    public function createTranslation(array $data);

    /**
     * Get a translation by ID.
     *
     * @param int $id
     * @return \App\Models\Translation
     */
    public function getTranslationById(int $id);

    /**
     * Update a translation.
     *
     * @param int $id
     * @param array $data
     * @return \App\Models\Translation
     */
    public function updateTranslation(int $id, array $data);

    /**
     * Delete a translation.
     *
     * @param int $id
     * @return bool
     */
    public function deleteTranslation(int $id);

    /**
     * Search translations by tag.
     *
     * @param string $tag
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByTag(string $tag, ?string $locale = null, int $perPage = 50);

    /**
     * Search translations by key.
     *
     * @param string $key
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByKey(string $key, ?string $locale = null, int $perPage = 50);

    /**
     * Search translations by content.
     *
     * @param string $content
     * @param string|null $locale
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchByContent(string $content, ?string $locale = null, int $perPage = 50);

    /**
     * Export translations for a specific locale.
     *
     * @param string|null $locale
     * @return array
     */
    public function exportTranslations(?string $locale = null);
} 