<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'photo_url',
        'parent_id',
        'discount_percent',
        'external_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $baseSlug = Str::slug($category->name, '-');
                $slug = $baseSlug;
                $count = 1;
                while (self::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$count}";
                    $count++;
                }
                $category->slug = $slug;
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)
            ->where('is_active', 1);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function parentRecursive(): BelongsTo
    {
        return $this->parent()->with('parentRecursive');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function getFullNameAttribute(): string
    {
        $names = [$this->name];
        $visited = [$this->id];
        $parent = $this->relationLoaded('parentRecursive') ? $this->parentRecursive : $this->parent;

        while ($parent) {
            if (in_array($parent->id, $visited, true)) {
                break;
            }

            $names[] = $parent->name;
            $visited[] = $parent->id;
            $parent = $parent->relationLoaded('parentRecursive') ? $parent->parentRecursive : $parent->parent;
        }

        return implode(' > ', array_reverse($names));
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function descendantIds()
    {
        $this->loadMissing('childrenRecursive');

        $ids = collect();
        $stack = $this->childrenRecursive->values();

        while ($stack->isNotEmpty()) {
            $child = $stack->shift();
            $ids->push($child->id);

            if ($child->relationLoaded('childrenRecursive')) {
                $stack = $stack->merge($child->childrenRecursive);
            }
        }

        return $ids;
    }

    public function selfAndDescendantIds()
    {
        return $this->descendantIds()->prepend($this->id);
    }
}
