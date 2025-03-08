<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Translation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'locale',
    ];

    /**
     * The relationships that should be eager loaded by default.
     *
     * @var array
     */
    protected $with = ['tags'];

    /**
     * Get the tags associated with the translation.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'translation_tag');
    }

    /**
     * Scope a query to only include translations for a specific locale.
     */
    public function scopeLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope a query to search translations by key.
     */
    public function scopeSearchByKey($query, string $key)
    {
        return $query->where('key', 'LIKE', "%{$key}%");
    }

    /**
     * Scope a query to search translations by value.
     */
    public function scopeSearchByValue($query, string $value)
    {
        return $query->where('value', 'LIKE', "%{$value}%");
    }
}
