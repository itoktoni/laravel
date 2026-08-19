<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\Api\MediaController;
use Modules\Cms\Http\Controllers\CategoryController;
use Modules\Cms\Http\Controllers\ContentController;
use Modules\Cms\Http\Controllers\FieldController;
use Modules\Cms\Http\Controllers\MenuController;
use Modules\Cms\Http\Controllers\SectionController;
use Modules\Cms\Http\Controllers\TagController;
use Modules\Cms\Http\Controllers\TypeController;

$cruds = [
    ['prefix' => '/cms/type', 'name' => 'cms-type', 'controller' => TypeController::class],
    ['prefix' => '/cms/field', 'name' => 'field', 'controller' => FieldController::class],
    ['prefix' => '/cms/section', 'name' => 'section', 'controller' => SectionController::class],
    ['prefix' => '/cms/content', 'name' => 'content', 'controller' => ContentController::class],
    ['prefix' => '/cms/category', 'name' => 'category', 'controller' => CategoryController::class],
    ['prefix' => '/cms/tag', 'name' => 'tag', 'controller' => TagController::class],
    ['prefix' => '/cms/menu', 'name' => 'menu', 'controller' => MenuController::class],
];

foreach ($cruds as $crud) {
    Route::group(['name' => $crud['name'], 'prefix' => $crud['prefix'], 'as' => $crud['name'].'.'], function () use ($crud) {
        Route::get('/', [$crud['controller'], 'index'])->name('index');
        Route::get('/table', [$crud['controller'], 'getTable'])->name('getTable');
        Route::get('/create', [$crud['controller'], 'getCreate'])->name('getCreate');
        Route::post('/create', [$crud['controller'], 'postCreate'])->name('postCreate');
        Route::get('/update/{id}', [$crud['controller'], 'getUpdate'])->name('getUpdate');
        Route::post('/update/{id}', [$crud['controller'], 'postUpdate'])->name('postUpdate');
        Route::get('/delete/{id}', [$crud['controller'], 'getDelete'])->name('getDelete');
        Route::post('/delete', [$crud['controller'], 'postDelete'])->name('postDelete');
        Route::get('/show/{id}', [$crud['controller'], 'getShow'])->name('getShow');
    });
}

Route::get('/cms/content/field-group-html/{id}', [ContentController::class, 'getSectionHtml'])->name('cms.section.html');

Route::prefix('api/media')->group(function () {
    Route::get('/', [MediaController::class, 'index']);
    Route::post('/upload', [MediaController::class, 'upload']);
    Route::delete('/{media}', [MediaController::class, 'destroy']);
});