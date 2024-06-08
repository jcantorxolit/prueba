<?php
    
	/**
     *Module: CustomerEconomicGroup
     */
    Route::get('customer-economic-group/get', 'AdeN\Api\Modules\Customer\EconomicGroup\Http\Controllers\CustomerEconomicGroupController@show');
    Route::post('customer-economic-group/save', 'AdeN\Api\Modules\Customer\EconomicGroup\Http\Controllers\CustomerEconomicGroupController@store');
    Route::post('customer-economic-group/delete', 'AdeN\Api\Modules\Customer\EconomicGroup\Http\Controllers\CustomerEconomicGroupController@destroy');
    Route::post('customer-economic-group/import', 'AdeN\Api\Modules\Customer\EconomicGroup\Http\Controllers\CustomerEconomicGroupController@import');
    Route::post('customer-economic-group/upload', 'AdeN\Api\Modules\Customer\EconomicGroup\Http\Controllers\CustomerEconomicGroupController@upload');
    //Route::match(['get', 'post'], 'customer-economic-group', 'AdeN\Api\Modules\Customer\EconomicGroup\Http\Controllers\CustomerEconomicGroupController@index');
    Route::match(['get', 'post'], 'customer-economic-group-available', 'AdeN\Api\Modules\Customer\EconomicGroup\Http\Controllers\CustomerEconomicGroupController@indexAvailable');
	Route::match(['get', 'post'], 'customer-economic-group/download', 'AdeN\Api\Modules\Customer\EconomicGroup\Http\Controllers\CustomerEconomicGroupController@download');
