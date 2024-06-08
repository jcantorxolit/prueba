<?php
    
	/**
     *Module: Olmed
     */
    /*Route::get('resource-library/get', 'AdeN\Api\Modules\Olmed\Http\Controllers\OlmedController@show');
    Route::post('resource-library/save', 'AdeN\Api\Modules\Olmed\Http\Controllers\OlmedController@store');
    Route::post('resource-library/delete', 'AdeN\Api\Modules\Olmed\Http\Controllers\OlmedController@destroy');
    Route::post('resource-library/import', 'AdeN\Api\Modules\Olmed\Http\Controllers\OlmedController@import');
    Route::post('resource-library/upload', 'AdeN\Api\Modules\Olmed\Http\Controllers\OlmedController@upload');
    Route::match(['get', 'post'], 'resource-library', 'AdeN\Api\Modules\Olmed\Http\Controllers\OlmedController@index');
    Route::match(['get', 'post'], 'resource-library/download', 'AdeN\Api\Modules\Olmed\Http\Controllers\OlmedController@download');*/
    
    Route::get('olmed', 'AdeN\Api\Modules\Olmed\Http\Controllers\OlmedController@show');