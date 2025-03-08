<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Interfaces\TranslationServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TranslationController extends Controller
{
    /**
     * @var TranslationServiceInterface
     */
    protected $translationService;

    /**
     * TranslationController constructor.
     *
     * @param TranslationServiceInterface $translationService
     */
    public function __construct(TranslationServiceInterface $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Display a listing of the translations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $request->has('locale') ? $request->locale : null;
        $translations = $this->translationService->getAllTranslations($locale);
        
        return response()->json([
            'status' => 'success',
            'data' => $translations,
        ]);
    }

    /**
     * Store a newly created translation in storage.
     *
     * @param  \App\Http\Requests\StoreTranslationRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTranslationRequest $request): JsonResponse
    {
        try {
            $translation = $this->translationService->createTranslation($request->validated());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Translation created successfully',
                'data' => $translation,
            ], 201);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Translation creation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->validated()
            ]);
            
            $statusCode = $e->getCode() === 409 ? 409 : 500;
            $message = $e->getCode() === 409 ? $e->getMessage() : 'Internal Server Error';
            
            return response()->json([
                'status' => 'error',
                'message' => $message,
            ], $statusCode);
        }
    }

    /**
     * Display the specified translation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $translation = $this->translationService->getTranslationById($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $translation,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Translation not found',
            ], 404);
        }
    }

    /**
     * Update the specified translation in storage.
     *
     * @param  \App\Http\Requests\UpdateTranslationRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTranslationRequest $request, $id): JsonResponse
    {
        try {
            $translation = $this->translationService->updateTranslation($id, $request->validated());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Translation updated successfully',
                'data' => $translation,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Translation not found',
            ], 404);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Translation update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'translation_id' => $id,
                'request_data' => $request->validated()
            ]);
            
            $statusCode = $e->getCode() === 422 ? 422 : 500;
            $message = $e->getCode() === 422 ? $e->getMessage() : 'Internal Server Error';
            
            return response()->json([
                'status' => 'error',
                'message' => $message,
            ], $statusCode);
        }
    }

    /**
     * Remove the specified translation from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->translationService->deleteTranslation($id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Translation deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Translation not found',
            ], 404);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Translation deletion failed: ' . $e->getMessage(), [
                'exception' => $e,
                'translation_id' => $id
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    /**
     * Search translations by tag.
     *
     * @param  string  $tag
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByTag($tag, Request $request): JsonResponse
    {
        try {
            $locale = $request->has('locale') ? $request->locale : null;
            $translations = $this->translationService->searchByTag($tag, $locale);
            
            return response()->json([
                'status' => 'success',
                'data' => $translations,
            ]);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Search by tag failed: ' . $e->getMessage(), [
                'exception' => $e,
                'tag' => $tag,
                'locale' => $request->locale
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    /**
     * Search translations by key.
     *
     * @param  string  $key
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByKey($key, Request $request): JsonResponse
    {
        try {
            $locale = $request->has('locale') ? $request->locale : null;
            $translations = $this->translationService->searchByKey($key, $locale);
            
            return response()->json([
                'status' => 'success',
                'data' => $translations,
            ]);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Search by key failed: ' . $e->getMessage(), [
                'exception' => $e,
                'key' => $key,
                'locale' => $request->locale
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    /**
     * Search translations by content.
     *
     * @param  string  $content
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByContent($content, Request $request): JsonResponse
    {
        try {
            $locale = $request->has('locale') ? $request->locale : null;
            $translations = $this->translationService->searchByContent($content, $locale);
            
            return response()->json([
                'status' => 'success',
                'data' => $translations,
            ]);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Search by content failed: ' . $e->getMessage(), [
                'exception' => $e,
                'content' => $content,
                'locale' => $request->locale
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    /**
     * Export translations as JSON for frontend applications.
     *
     * @param  string|null  $locale
     * @return \Illuminate\Http\JsonResponse
     */
    public function export($locale = null): JsonResponse
    {
        try {
            $translations = $this->translationService->exportTranslations($locale);
            
            return response()->json($translations);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Export translations failed: ' . $e->getMessage(), [
                'exception' => $e,
                'locale' => $locale
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
} 