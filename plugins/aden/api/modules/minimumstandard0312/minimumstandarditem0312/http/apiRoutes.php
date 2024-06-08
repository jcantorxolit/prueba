<?php
    
	/**
     *Module: MinimumStandardItem0312
     */
    Route::get('minimum-standard-item-0312/get', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312\Http\Controllers\MinimumStandardItem0312Controller@show');
    Route::post('minimum-standard-item-0312/save', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312\Http\Controllers\MinimumStandardItem0312Controller@store');
    Route::post('minimum-standard-item-0312/delete', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312\Http\Controllers\MinimumStandardItem0312Controller@destroy');
    Route::post('minimum-standard-item-0312/import', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312\Http\Controllers\MinimumStandardItem0312Controller@import');
    Route::post('minimum-standard-item-0312/upload', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312\Http\Controllers\MinimumStandardItem0312Controller@upload');
    Route::match(['get', 'post'], 'minimum-standard-item-0312', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312\Http\Controllers\MinimumStandardItem0312Controller@index');
	Route::match(['get', 'post'], 'minimum-standard-item-0312/download', 'AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312\Http\Controllers\MinimumStandardItem0312Controller@download');