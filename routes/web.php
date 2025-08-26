<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\FileShareController;

Route::get('/', [FileShareController::class, 'index'])->name('home');
Route::post('/create', [FileShareController::class, 'create'])->name('fileshare.create');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rotas para compartilhamento de arquivos
Route::delete('/file/{file}', [FileShareController::class, 'deleteFile'])->name('fileshare.delete');
Route::get('/download/{file}', [FileShareController::class, 'download'])->name('fileshare.download');
Route::post('/{identifier}/validate-password', [FileShareController::class, 'validatePassword'])->name('fileshare.validate-password');
Route::delete('/{identifier}/password', [FileShareController::class, 'removePassword'])->name('fileshare.remove-password');
Route::delete('/{identifier}', [FileShareController::class, 'deletePage'])->name('fileshare.delete-page');
Route::get('/{identifier}', [FileShareController::class, 'show'])
    ->middleware('App\Http\Middleware\SanitizeIdentifier')
    ->name('fileshare.show');
Route::post('/{identifier}/upload', [FileShareController::class, 'upload'])
    ->middleware(['throttle:5,1', 'App\Http\Middleware\SanitizeIdentifier'])
    ->name('fileshare.upload');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
