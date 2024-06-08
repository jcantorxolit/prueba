<?php
    
	/**
     *Module: CustomerCovidTemperature
     */
    Route::get('customer-covid-temperature/get', 'AdeN\Api\Modules\Customer\Covid\DailyTemperature\Http\Controllers\CustomerCovidDailyTemperatureController@show');
    Route::post('customer-covid-temperature/save', 'AdeN\Api\Modules\Customer\Covid\DailyTemperature\Http\Controllers\CustomerCovidDailyTemperatureController@store');
    Route::post('customer-covid-temperature/delete', 'AdeN\Api\Modules\Customer\Covid\DailyTemperature\Http\Controllers\CustomerCovidDailyTemperatureController@destroy');
    Route::post('customer-covid-temperature/import', 'AdeN\Api\Modules\Customer\Covid\DailyTemperature\Http\Controllers\CustomerCovidDailyTemperatureController@import');
    Route::post('customer-covid-temperature/upload', 'AdeN\Api\Modules\Customer\Covid\DailyTemperature\Http\Controllers\CustomerCovidDailyTemperatureController@upload');
    Route::match(['get', 'post'], 'customer-covid-temperature', 'AdeN\Api\Modules\Customer\Covid\DailyTemperature\Http\Controllers\CustomerCovidDailyTemperatureController@index');
	Route::match(['get', 'post'], 'customer-covid-temperature/download', 'AdeN\Api\Modules\Customer\Covid\DailyTemperature\Http\Controllers\CustomerCovidDailyTemperatureController@download');