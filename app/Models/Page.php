<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Page extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pages';

    public function getTable(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'public.pages'
            : 'pages';
    }

    protected $fillable = [
        'title',
        'slug',
        'meta_description',
        'meta_keywords',
        'og_image',
        'content',
        'is_published',
    ];

    protected $casts = [
        'content' => 'array',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (Page $page): void {
            $normalizedSlug = trim((string) $page->slug, '/');
            $normalizedSlug = ($normalizedSlug === '' || $normalizedSlug === 'home') ? '/' : $normalizedSlug;
            Cache::forget('cms_page_'.md5($normalizedSlug));
            Cache::forget('cms_page_'.md5('/'));
        });

        static::deleted(function (Page $page): void {
            $normalizedSlug = trim((string) $page->slug, '/');
            $normalizedSlug = ($normalizedSlug === '' || $normalizedSlug === 'home') ? '/' : $normalizedSlug;
            Cache::forget('cms_page_'.md5($normalizedSlug));
            Cache::forget('cms_page_'.md5('/'));
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        $normalized = trim($slug, '/');
        $normalized = $normalized === '' ? '/' : $normalized;

        return $query->where('slug', $normalized);
    }
}
