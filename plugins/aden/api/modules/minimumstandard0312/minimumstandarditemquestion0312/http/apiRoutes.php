<?php
    
	/**
     *Module: MinimumStandardItemQuestion0312
     */
    Route::get('minimum-standard-item-question-0312/get', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemQuestion0312\Http\Controllers\MinimumStandardItemQuestion0312Controller@show');
    Route::post('minimum-standard-item-question-0312/save', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemQuestion0312\Http\Controllers\MinimumStandardItemQuestion0312Controller@store');
    Route::post('minimum-standard-item-question-0312/batch', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemQuestion0312\Http\Controllers\MinimumStandardItemQuestion0312Controller@batch');
    Route::post('minimum-standard-item-question-0312/delete', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemQuestion0312\Http\Controllers\MinimumStandardItemQuestion0312Controller@destroy');
    Route::post('minimum-standard-item-question-0312/import', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemQuestion0312\Http\Controllers\MinimumStandardItemQuestion0312Controller@import');
    Route::post('minimum-standard-item-question-0312/upload', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemQuestion0312\Http\Controllers\MinimumStandardItemQuestion0312Controller@upload');
    Route::match(['get', 'post'], 'minimum-standard-item-question-0312', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemQuestion0312\Http\Controllers\MinimumStandardItemQuestion0312Controller@index');
    Route::match(['get', 'post'], 'minimum-standard-item-question-0312-available', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemQuestion0312\Http\Controllers\MinimumStandardItemQuestion0312Controller@indexAvailable');
	Route::match(['get', 'post'], 'minimum-standard-item-question-0312/download', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemQuestion0312\Http\Controllers\MinimumStandardItemQuestion0312Controller@download');