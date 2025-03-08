<?php

namespace App\Console\Commands;

use App\Interfaces\TagRepositoryInterface;
use App\Interfaces\TranslationRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:generate 
                            {count=100000 : Number of translations to generate}
                            {--locales=en,fr,es : Comma-separated list of locales}
                            {--tags=web,mobile,desktop : Comma-separated list of tags}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test translations for performance testing';

    /**
     * @var TagRepositoryInterface
     */
    protected $tagRepository;

    /**
     * @var TranslationRepositoryInterface
     */
    protected $translationRepository;

    /**
     * GenerateTranslations constructor.
     *
     * @param TagRepositoryInterface $tagRepository
     * @param TranslationRepositoryInterface $translationRepository
     */
    public function __construct(
        TagRepositoryInterface $tagRepository,
        TranslationRepositoryInterface $translationRepository
    ) {
        parent::__construct();
        $this->tagRepository = $tagRepository;
        $this->translationRepository = $translationRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $locales = explode(',', $this->option('locales'));
        $tagNames = explode(',', $this->option('tags'));
        
        $this->info("Generating {$count} translations for locales: " . implode(', ', $locales));
        $this->output->newLine();
        
        // Create tags first
        $tags = [];
        foreach ($tagNames as $tagName) {
            $tag = $this->tagRepository->findOrCreate($tagName);
            $tags[] = $tag;
        }
        
        $this->info('Tags created: ' . implode(', ', $tagNames));
        $this->output->newLine();
        
        // Calculate translations per locale
        $translationsPerLocale = (int) ceil($count / count($locales));
        
        // Initialize progress tracking
        $totalCreated = 0;
        
        foreach ($locales as $locale) {
            $this->info("Generating translations for locale: {$locale}");
            
            // Create a progress bar for this locale
            $localeCount = min($translationsPerLocale, $count - $totalCreated);
            if ($localeCount <= 0) {
                break;
            }
            
            $bar = $this->output->createProgressBar($localeCount);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
            $bar->start();
            
            $this->generateTranslationsForLocale($locale, $localeCount, $tags, $bar, $totalCreated);
            
            $bar->finish();
            $this->output->newLine(2);
            
            if ($totalCreated >= $count) {
                break;
            }
        }
        
        $this->info("Generated {$totalCreated} translations successfully!");
    }
    
    /**
     * Generate translations for a specific locale.
     *
     * @param  string  $locale
     * @param  int  $count
     * @param  array  $tags
     * @param  \Symfony\Component\Console\Helper\ProgressBar  $bar
     * @param  int  $totalCreated
     * @return void
     */
    private function generateTranslationsForLocale($locale, $count, $tags, $bar, &$totalCreated)
    {
        // Use chunks to avoid memory issues
        $chunkSize = 1000;
        $chunks = (int) ceil($count / $chunkSize);
        $localeCreated = 0;
        
        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $remainingCount = $count - $localeCreated;
            $currentChunkSize = min($chunkSize, $remainingCount);
            
            if ($currentChunkSize <= 0) {
                break;
            }
            
            DB::beginTransaction();
            
            try {
                for ($i = 0; $i < $currentChunkSize; $i++) {
                    $keyIndex = $totalCreated + $localeCreated;
                    $key = "test.key.{$keyIndex}";
                    
                    // Check if translation already exists
                    if ($this->translationRepository->existsByKeyAndLocale($key, $locale)) {
                        // Still advance the bar even if we skip
                        $bar->advance();
                        continue;
                    }
                    
                    // Create translation
                    $translation = $this->translationRepository->create([
                        'key' => $key,
                        'value' => "This is a test value for {$key} in {$locale}",
                        'locale' => $locale,
                    ]);
                    
                    // Randomly assign 1-3 tags
                    $tagCount = rand(1, min(3, count($tags)));
                    $selectedTags = array_rand($tags, $tagCount);
                    
                    if (!is_array($selectedTags)) {
                        $selectedTags = [$selectedTags];
                    }
                    
                    $tagIds = [];
                    foreach ($selectedTags as $tagIndex) {
                        $tagIds[] = $tags[$tagIndex]->id;
                    }
                    
                    $this->translationRepository->attachTags($translation->id, $tagIds);
                    
                    $totalCreated++;
                    $localeCreated++;
                    $bar->advance();
                    
                    // If we've reached the total count, break out
                    if ($totalCreated >= (int) $this->argument('count')) {
                        break;
                    }
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->output->newLine();
                $this->error("Error generating translations: {$e->getMessage()}");
                throw $e;
            }
        }
    }
} 