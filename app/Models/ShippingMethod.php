<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $name
 * @property string|null $note
 * @property bool $requires_direct_arrangement
 */
class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'note',
        'requires_direct_arrangement',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_direct_arrangement' => 'boolean',
            'status' => 'string',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function provinces(): BelongsToMany
    {
        return $this->belongsToMany(Province::class, 'shipping_method_province')
            ->withPivot('fee')
            ->withTimestamps();
    }
}
