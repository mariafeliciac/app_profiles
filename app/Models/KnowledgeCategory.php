<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'project_challenge';
    protected $table = 'knowledge_rel_category';
}
