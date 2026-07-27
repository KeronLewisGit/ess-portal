<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => static::flushCache($setting->key));
        static::deleted(fn (Setting $setting) => static::flushCache($setting->key));
    }

    /**
     * Cached accessor for a setting value.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever(
            static::cacheKey($key),
            fn () => static::query()->where('key', $key)->value('value')
        ) ?? $default;
    }

    /**
     * Create or update a setting (cache is flushed by the saved event).
     */
    public static function set(string $key, ?string $value): Setting
    {
        return static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function flushCache(string $key): void
    {
        Cache::forget(static::cacheKey($key));
    }

    protected static function cacheKey(string $key): string
    {
        return 'settings.'.$key;
    }
}
