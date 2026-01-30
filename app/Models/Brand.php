<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'is_active',
        'sort_order',
    ];

    #[Scope()]
    protected function active(Builder $builder){
        $builder->where('is_active',true);
    }

    #[Scope()]
    protected function sorted(Builder $builder){
        $builder->orderBy('sort_order', 'asc');
    }

    public function products(){
        return $this->hasMany(Product::class);
    }

    protected static function boot(){
        parent::boot();

        static::creating(function ($brand) {
            if(empty($brand->slug)){
                $brand->slug = Str::slug($brand->name);
            }
        });

        static::updated(function($brand){
            if($brand->isDirty('name') && empty($brand->empty)){
                $brand->slug = Str::slug($brand->name);
            }
        });

    }
}
