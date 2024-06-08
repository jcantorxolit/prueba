<?php
    
	/**
     *Module: CustomeManacle
     */
    Route::get('customer-manacle/get', 'AdeN\Api\Modules\Customer\Manacle\Http\Controllers\CustomerManacleController@show');
    Route::post('customer-manacle/save', 'AdeN\Api\Modules\Customer\Manacle\Http\Controllers\CustomerManacleController@store');
    Route::post('customer-manacle/delete', 'AdeN\Api\Modules\Customer\Manacle\Http\Controllers\CustomerManacleController@destroy');
    Route::post('customer-manacle/import', 'AdeN\Api\Modules\Customer\Manacle\Http\Controllers\CustomerManacleController@import');
    Route::match(['get', 'post'], 'customer-manacle', 'AdeN\Api\Modules\Customer\Manacle\Http\Controllers\CustomerManacleController@index');
    Route::match(['get', 'post'], 'customer-manacle/download-template', 'AdeN\Api\Modules\Customer\Manacle\Http\Controllers\CustomerManacleController@downloadTemplate');