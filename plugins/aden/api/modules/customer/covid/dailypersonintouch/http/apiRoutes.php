<?php
    
	/**
     *Module: CustomerCovidPersonInTouch
     */
    Route::get('customer-covid-person-in-touch/get', 'AdeN\Api\Modules\Customer\Covid\DailyPersonInTouch\Http\Controllers\CustomerCovidDailyPersonInTouchController@show');
    Route::post('customer-covid-person-in-touch/save', 'AdeN\Api\Modules\Customer\Covid\DailyPersonInTouch\Http\Controllers\CustomerCovidDailyPersonInTouchController@store');
    Route::post('customer-covid-person-in-touch/delete', 'AdeN\Api\Modules\Customer\Covid\DailyPersonInTouch\Http\Controllers\CustomerCovidDailyPersonInTouchController@destroy');
    Route::post('customer-covid-person-in-touch/import', 'AdeN\Api\Modules\Customer\Covid\DailyPersonInTouch\Http\Controllers\CustomerCovidDailyPersonInTouchController@import');
    Route::post('customer-covid-person-in-touch/upload', 'AdeN\Api\Modules\Customer\Covid\DailyPersonInTouch\Http\Controllers\CustomerCovidDailyPersonInTouchController@upload');
    Route::match(['get', 'post'], 'customer-covid-person-in-touch', 'AdeN\Api\Modules\Customer\Covid\DailyPersonInTouch\Http\Controllers\CustomerCovidDailyPersonInTouchController@index');
	Route::match(['get', 'post'], 'customer-covid-person-in-touch/download', 'AdeN\Api\Modules\Customer\Covid\DailyPersonInTouch\Http\Controllers\CustomerCovidDailyPersonInTouchController@download');