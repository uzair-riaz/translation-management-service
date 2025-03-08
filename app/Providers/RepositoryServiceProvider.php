<?php

namespace App\Providers;

use App\Interfaces\AuthServiceInterface;
use App\Interfaces\RepositoryInterface;
use App\Interfaces\TagRepositoryInterface;
use App\Interfaces\TranslationRepositoryInterface;
use App\Interfaces\TranslationServiceInterface;
use App\Repositories\BaseRepository;
use App\Repositories\TagRepository;
use App\Repositories\TranslationRepository;
use App\Services\AuthService;
use App\Services\TranslationService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Repositories
        $this->app->bind(RepositoryInterface::class, BaseRepository::class);
        $this->app->bind(TranslationRepositoryInterface::class, TranslationRepository::class);
        $this->app->bind(TagRepositoryInterface::class, TagRepository::class);

        // Services
        $this->app->bind(TranslationServiceInterface::class, TranslationService::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
} 