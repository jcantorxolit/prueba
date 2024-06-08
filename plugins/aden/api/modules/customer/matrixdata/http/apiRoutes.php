<?php
    
	/**
     *Module: CustomerMatrixData
     */
    Route::get('customer-matrix-data/get', 'AdeN\Api\Modules\Customer\MatrixData\Http\Controllers\CustomerMatrixDataController@show');
    Route::post('customer-matrix-data/save', 'AdeN\Api\Modules\Customer\MatrixData\Http\Controllers\CustomerMatrixDataController@store');
    Route::post('customer-matrix-data/delete', 'AdeN\Api\Modules\Customer\MatrixData\Http\Controllers\CustomerMatrixDataController@destroy');
    Route::post('customer-matrix-data/import', 'AdeN\Api\Modules\Customer\MatrixData\Http\Controllers\CustomerMatrixDataController@import');
    Route::post('customer-matrix-data/upload', 'AdeN\Api\Modules\Customer\MatrixData\Http\Controllers\CustomerMatrixDataController@upload');
    Route::match(['get', 'post'], 'customer-matrix-data', 'AdeN\Api\Modules\Customer\MatrixData\Http\Controllers\CustomerMatrixDataController@index');
	Route::match(['get', 'post'], 'customer-matrix-data/download', 'AdeN\Api\Modules\Customer\MatrixData\Http\Controllers\CustomerMatrixDataController@download');