<?php

namespace App\Services;

use App\Interfaces\TagRepositoryInterface;
use App\Interfaces\TranslationRepositoryInterface;
use App\Interfaces\TranslationServiceInterface;
use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TranslationService implements TranslationServiceInterface
{
    /**
     * @var TranslationRepositoryInterface
     */
    protected $translationRepository;

    /**
     * @var TagRepositoryInterface
     */
    protected $tagRepository;

    /**
     * TranslationService constructor.
     *
     * @param TranslationRepositoryInterface $translationRepository
     * @param TagRepositoryInterface $tagRepository
     */
    public function __construct(
        TranslationRepositoryInterface $translationRepository,
        TagRepositoryInterface $tagRepository
    ) {
        $this->translationRepository = $translationRepository;
        $this->tagRepository = $tagRepository;
    }

    /**
     * Get all translations with pagination.
     *
     * @param string|null $locale
     * @param int|null $limit
     * @param int|null $offset
     * @return LengthAwarePaginator
     */
    public function getAllTranslations(?string $locale = null, ?int $limit = null, ?int $offset = null): LengthAwarePaginator
    {
        $limit = $limit ?? 15; // Default limit is 15
        $cacheKey = $locale
            ? "translations.list.{$locale}.{$limit}.{$offset}"
            : "translations.list.all.{$limit}.{$offset}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($locale, $limit, $offset) {
            if ($locale) {
                return $this->translationRepository->getByLocale($locale, $limit, $offset);
            }

            return $this->translationRepository->paginate($limit, $offset);
        });
    }

    /**
     * Create a new translation.
     *
     * @param array $data
     * @return Translation
     * @throws \Exception
     */
    public function createTranslation(array $data): Translation
    {
        // Check if translation already exists
        if ($this->translationRepository->existsByKeyAndLocale($data['key'], $data['locale'])) {
            throw new \Exception('Translation already exists for this key and locale', 409);
        }

        // Ensure tags are provided
        if (empty($data['tags'])) {
            throw new \Exception('At least one tag is required for each translation', 422);
        }

        DB::beginTransaction();

        try {
            // Create the translation
            $translation = $this->translationRepository->create([
                'key' => $data['key'],
                'value' => $data['value'],
                'locale' => $data['locale'],
            ]);

            // Attach tags
            $tagIds = $this->tagRepository->getTagIdsFromNames($data['tags']);
            $this->translationRepository->attachTags($translation->id, $tagIds);

            // Clear the cache for this locale
            $this->clearTranslationCache($data['locale']);

            DB::commit();

            // Load the tags relationship
            $translation->load('tags');

            return $translation;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get a translation by ID.
     *
     * @param int $id
     * @return Translation
     * @throws ModelNotFoundException
     */
    public function getTranslationById(int $id): Translation
    {
        $translation = $this->translationRepository->find($id);

        if (!$translation) {
            throw new ModelNotFoundException('Translation not found');
        }

        $translation->load('tags');

        return $translation;
    }

    /**
     * Update a translation.
     *
     * @param int $id
     * @param array $data
     * @return Translation
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function updateTranslation(int $id, array $data): Translation
    {
        $translation = $this->translationRepository->find($id);

        if (!$translation) {
            throw new ModelNotFoundException('Translation not found');
        }

        // Ensure tags are provided
        if (empty($data['tags'])) {
            throw new \Exception('At least one tag is required for each translation', 422);
        }

        DB::beginTransaction();

        try {
            // Update the translation value
            $translation = $this->translationRepository->update([
                'value' => $data['value'],
            ], $id);

            // Update tags
            $tagIds = $this->tagRepository->getTagIdsFromNames($data['tags']);
            $this->translationRepository->syncTags($translation->id, $tagIds);

            // Clear the cache for this locale
            $this->clearTranslationCache($translation->locale);

            DB::commit();

            // Load the tags relationship
            $translation->load('tags');

            return $translation;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a translation.
     *
     * @param int $id
     * @return bool
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function deleteTranslation(int $id): bool
    {
        $translation = $this->translationRepository->find($id);

        if (!$translation) {
            throw new ModelNotFoundException('Translation not found');
        }

        // Store the locale before deleting
        $locale = $translation->locale;

        DB::beginTransaction();

        try {
            // Delete the translation
            $result = $this->translationRepository->delete($id);

            // Clear the cache for this locale
            $this->clearTranslationCache($locale);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
        $cacheKey = "translations.search.tag.{$tag}." . ($locale ?? 'all') . ".{$limit}.{$offset}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($tag, $locale, $limit, $offset) {
            return $this->translationRepository->searchByTag($tag, $locale, $limit, $offset);
        });
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
        $cacheKey = "translations.search.key.{$key}." . ($locale ?? 'all') . ".{$limit}.{$offset}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($key, $locale, $limit, $offset) {
            return $this->translationRepository->searchByKey($key, $locale, $limit, $offset);
        });
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
        $cacheKey = "translations.search.content.{$content}." . ($locale ?? 'all') . ".{$limit}.{$offset}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($content, $locale, $limit, $offset) {
            return $this->translationRepository->searchByContent($content, $locale, $limit, $offset);
        });
    }

    /**
     * Export translations as a flat key-value object.
     *
     * @param string|null $locale
     * @return array
     */
    public function exportTranslations(?string $locale = null): array
    {
        // If no locale is provided, use the default locale
        $locale = $locale ?? config('app.locale');

        // Use cache to improve performance
        $cacheKey = "translations.export.{$locale}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($locale) {
            return $this->translationRepository->exportByLocale($locale);
        });
    }

    /**
     * Clear the translation cache for a specific locale.
     *
     * @param string $locale
     * @return void
     */
    protected function clearTranslationCache(string $locale): void
    {
        // Clear all related caches for this locale
        Cache::forget("translations.{$locale}");
        Cache::forget("translations.export.{$locale}");

        // Clear list caches - we need to use a pattern to clear all offset/limit combinations
        Cache::forget("translations.list.{$locale}.*");
        Cache::forget("translations.list.all.*");

        // We can't clear all search caches efficiently with wildcard patterns,
        // but they will expire after 15 minutes anyway
    }
}
