<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');