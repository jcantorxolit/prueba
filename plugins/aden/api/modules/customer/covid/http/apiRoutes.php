<?php
    
	/**
     *Module: CustomerCovid
     */
    Route::get('customer-covid/get', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@show');
    Route::post('customer-covid/save', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@store');
    Route::post('customer-covid/delete', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@destroy');
    Route::post('customer-covid/import', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@import');
    Route::post('customer-covid/upload', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@upload');
    Route::match(['get', 'post'], 'customer-covid', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@index');
    Route::match(['get', 'post'], 'customer-covid-indicator', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@indexIndicator');
	Route::match(['get', 'post'], 'customer-covid/download', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@download');
    Route::match(['get', 'post'], 'customer-covid/download-template', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@downloadTemplate');
    Route::match(['get', 'post'], 'customer-covid/export', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@export');
    Route::match(['get', 'post'], 'customer-covid/exportemployee', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@exportEmployee');
    Route::match(['get', 'post'], 'customer-covid/exportexternal', 'AdeN\Api\Modules\Customer\Covid\Http\Controllers\CustomerCovidController@exportExternal');