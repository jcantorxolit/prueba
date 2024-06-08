<?php
    
	/**
     *Module: CustomerProject
     */
    Route::get('customer-project/get', 'AdeN\Api\Modules\Project\Http\Controllers\CustomerProjectController@show');
    Route::post('customer-project/save', 'AdeN\Api\Modules\Project\Http\Controllers\CustomerProjectController@store');
    Route::post('customer-project/delete', 'AdeN\Api\Modules\Project\Http\Controllers\CustomerProjectController@destroy');
    Route::post('customer-project/import', 'AdeN\Api\Modules\Project\Http\Controllers\CustomerProjectController@import');
    Route::post('customer-project/upload', 'AdeN\Api\Modules\Project\Http\Controllers\CustomerProjectController@upload');
    Route::match(['get', 'post'], 'customer-project', 'AdeN\Api\Modules\Project\Http\Controllers\CustomerProjectController@index');
    Route::match(['get', 'post'], 'customer-project/activities', 'AdeN\Api\Modules\Project\Http\Controllers\CustomerProjectController@indexActivities');
	Route::match(['get', 'post'], 'customer-project/download', 'AdeN\Api\Modules\Project\Http\Controllers\CustomerProjectController@download');