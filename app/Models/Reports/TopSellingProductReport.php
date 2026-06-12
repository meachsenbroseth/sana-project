<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;

class TopSellingProductReport extends Model
{
    protected $table = 'top_selling_products';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
