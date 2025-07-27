<?php

use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UpdateController;
use Illuminate\Support\Facades\Route;


Route::get('/get-roles',[RegisterController::class, 'getRoles' ]);
Route::post('/create-user',[RegisterController::class, 'createUser']);
Route::get('/get-levels', [RegisterController::class,'getLevels']);

Route::get('/get-users', [SearchController::class,'getUsers']);
Route::get('/get-categories', [SearchController::class,'getCategories']);
Route::get('/get-knowledges', [SearchController::class,'getKnowledges']);

Route::put('/update-user/{user_id}', [UpdateController::class, 'updateUser']);


