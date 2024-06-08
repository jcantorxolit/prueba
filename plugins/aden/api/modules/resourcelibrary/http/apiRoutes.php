<?php
    
	/**
     *Module: ResourceLibrary
     */
    /*Route::get('resource-library/get', 'AdeN\Api\Modules\ResourceLibrary\Http\Controllers\ResourceLibraryController@show');
    Route::post('resource-library/save', 'AdeN\Api\Modules\ResourceLibrary\Http\Controllers\ResourceLibraryController@store');
    Route::post('resource-library/delete', 'AdeN\Api\Modules\ResourceLibrary\Http\Controllers\ResourceLibraryController@destroy');
    Route::post('resource-library/import', 'AdeN\Api\Modules\ResourceLibrary\Http\Controllers\ResourceLibraryController@import');
    Route::post('resource-library/upload', 'AdeN\Api\Modules\ResourceLibrary\Http\Controllers\ResourceLibraryController@upload');
    Route::match(['get', 'post'], 'resource-library', 'AdeN\Api\Modules\ResourceLibrary\Http\Controllers\ResourceLibraryController@index');
    Route::match(['get', 'post'], 'resource-library/download', 'AdeN\Api\Modules\ResourceLibrary\Http\Controllers\ResourceLibraryController@download');*/
    
    Route::match(['post'], 'resource-library', 'AdeN\Api\Modules\ResourceLibrary\Http\Controllers\ResourceLibraryController@index');