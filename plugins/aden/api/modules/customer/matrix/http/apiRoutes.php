<?php
    
	/**
     *Module: CustomerMatrix
     */
    Route::get('customer-matrix/get', 'AdeN\Api\Modules\Customer\Matrix\Http\Controllers\CustomerMatrixController@show');
    Route::post('customer-matrix/save', 'AdeN\Api\Modules\Customer\Matrix\Http\Controllers\CustomerMatrixController@store');
    Route::post('customer-matrix/delete', 'AdeN\Api\Modules\Customer\Matrix\Http\Controllers\CustomerMatrixController@destroy');
    Route::post('customer-matrix/import', 'AdeN\Api\Modules\Customer\Matrix\Http\Controllers\CustomerMatrixController@import');
    Route::post('customer-matrix/upload', 'AdeN\Api\Modules\Customer\Matrix\Http\Controllers\CustomerMatrixController@upload');
    Route::match(['get', 'post'], 'customer-matrix', 'AdeN\Api\Modules\Customer\Matrix\Http\Controllers\CustomerMatrixController@index');
	Route::match(['get', 'post'], 'customer-matrix/download', 'AdeN\Api\Modules\Customer\Matrix\Http\Controllers\CustomerMatrixController@download');