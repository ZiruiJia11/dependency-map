<?php

use App\Http\Controllers\DependencyController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    // Projects are the user-owned top-level resources.
    Route::resource('projects', ProjectController::class);

    // Node positions can be updated independently of the node itself.
    Route::patch(
        'projects/{project}/nodes/{node}/position',
        [NodeController::class, 'updatePosition'],
    )
        ->name('projects.nodes.position')
        ->scopeBindings();

    Route::delete(
        'projects/{project}/nodes/positions',
        [NodeController::class, 'resetPositions'],
    )->name('projects.nodes.positions.destroy');

    // Scoped nesting blocks access to nodes through the wrong project.
    Route::resource('projects.nodes', NodeController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped();

    // Dependencies are edited on the project page and share the same boundary.
    Route::resource('projects.dependencies', DependencyController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped();
});

require __DIR__.'/settings.php';
