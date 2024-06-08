<?php
    
	/**
     *Module: CustomerCovid
     */
    Route::get('customer-covid-daily/get', 'AdeN\Api\Modules\Customer\Covid\Daily\Http\Controllers\CustomerCovidDailyController@show');
    Route::post('customer-covid-daily/save', 'AdeN\Api\Modules\Customer\Covid\Daily\Http\Controllers\CustomerCovidDailyController@store');
    Route::match(['get', 'post'], 'customer-covid-daily', 'AdeN\Api\Modules\Customer\Covid\Daily\Http\Controllers\CustomerCovidDailyController@index');
    Route::match(['get', 'post'], 'customer-covid-daily-indicator', 'AdeN\Api\Modules\Customer\Covid\Daily\Http\Controllers\CustomerCovidDailyController@indexIndicator');
    Route::get('customer-covid-daily/export', 'AdeN\Api\Modules\Customer\Covid\Daily\Http\Controllers\CustomerCovidDailyController@export');