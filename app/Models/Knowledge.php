<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Knowledge extends Model
{
    use HasFactory;

    protected $connection = 'project_challenge';
    protected $table = 'knowledge';

    public function category()
    {
        return $this->belongsToMany(Category::class, 'knowledge_rel_category');
    }
}
