<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $connection = 'project_challenge';
    protected $table = 'user';


    public function profile(){
       return $this->hasOne(Profile::class, 'id','profile_id');
    }
}
