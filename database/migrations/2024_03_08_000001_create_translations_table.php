<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->text('value');
            $table->string('locale', 10)->index();
            $table->timestamps();
            
            // Create a composite unique index for key and locale
            $table->unique(['key', 'locale']);
        });
        
        // Add a fulltext index to the value column for better search performance
        DB::statement('ALTER TABLE translations ADD FULLTEXT search_index (value)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
}; 