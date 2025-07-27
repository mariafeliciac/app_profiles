<?php

namespace App\Models;

use App\Models\KnowledgeCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $connection = 'project_challenge';
    protected $table = 'category';

    public function knowledge()
    {
        return $this->belongsToMany(Knowledge::class, 'knowledge_rel_category')
           ->whereRaw('knowledge_rel_category.deleted_at IS NULL');
    }
}
