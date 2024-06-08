<?php
    
	/**
     *Module: CustomerCovidPersonInTouch
     */
    Route::get('customer-covid-person-near/get', 'AdeN\Api\Modules\Customer\Covid\DailyPersonNear\Http\Controllers\CustomerCovidDailyPersonNearController@show');
    Route::post('customer-covid-person-near/save', 'AdeN\Api\Modules\Customer\Covid\DailyPersonNear\Http\Controllers\CustomerCovidDailyPersonNearController@store');
    Route::post('customer-covid-person-near/delete', 'AdeN\Api\Modules\Customer\Covid\DailyPersonNear\Http\Controllers\CustomerCovidDailyPersonNearController@destroy');
    Route::match(['get', 'post'], 'customer-covid-person-near', 'AdeN\Api\Modules\Customer\Covid\DailyPersonNear\Http\Controllers\CustomerCovidDailyPersonNearController@index');
    Route::get('customer-covid-person-near/export', 'AdeN\Api\Modules\Customer\Covid\DailyPersonNear\Http\Controllers\CustomerCovidDailyPersonNearController@export');