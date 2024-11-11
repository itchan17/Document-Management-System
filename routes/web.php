<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Livewire\Requests;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');

Route::get('/requests', Requests::class);