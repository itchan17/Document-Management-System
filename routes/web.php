<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Livewire\Requests;
use App\Http\Middleware\AdminMiddleware;


Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['AdminMiddleware'])->group(function () {
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])
        ->name('documents.view');
});

Route::get('/requests', Requests::class);

