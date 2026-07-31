<?php

use App\Http\Controllers\DependencyController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('projects', ProjectController::class);
    Route::resource('projects.nodes', NodeController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped();
    Route::resource('projects.dependencies', DependencyController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped();
});

require __DIR__.'/settings.php';
