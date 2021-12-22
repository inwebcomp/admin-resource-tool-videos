<?php

Route::put('main/{video}', 'VideosController@main');
Route::get('{resource}/{resourceId}', 'VideosController@index');
Route::put('{resource}/{resourceId}', 'VideosController@store');
Route::post('{resource}/{resourceId}/{video}/thumbnail', 'VideosController@storeThumbnail');
Route::post('{resource}/{resourceId}/positions', 'VideosController@changePositions');
Route::delete('{resource}/{resourceId?}', 'VideosController@destroy');
Route::delete('{resource}/{resourceId?}/thumbnail', 'VideosController@destroyThumbnail');
Route::post('{resource}/{resourceId?}/thumbnail/main', 'VideosController@setMainThumbnail');
Route::post('thumbnail/refresh/{video}', 'VideosController@refreshThumbnails');
