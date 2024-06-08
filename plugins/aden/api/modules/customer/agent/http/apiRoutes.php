<?php

	/**
     *Module: CustomerAgent
     */
    Route::get('customer-agent/get', 'AdeN\Api\Modules\Customer\Agent\Http\Controllers\CustomerAgentController@show');
    Route::post('customer-agent/save', 'AdeN\Api\Modules\Customer\Agent\Http\Controllers\CustomerAgentController@store');
    Route::post('customer-agent/bulk', 'AdeN\Api\Modules\Customer\Agent\Http\Controllers\CustomerAgentController@bulk');
    Route::post('customer-agent/delete', 'AdeN\Api\Modules\Customer\Agent\Http\Controllers\CustomerAgentController@destroy');
    Route::post('customer-agent/import', 'AdeN\Api\Modules\Customer\Agent\Http\Controllers\CustomerAgentController@import');
    Route::post('customer-agent/upload', 'AdeN\Api\Modules\Customer\Agent\Http\Controllers\CustomerAgentController@upload');
    Route::match(['get', 'post'], 'customer-agent/v2', 'AdeN\Api\Modules\Customer\Agent\Http\Controllers\CustomerAgentController@index');
    Route::match(['get', 'post'], 'customer-agent/available', 'AdeN\Api\Modules\Customer\Agent\Http\Controllers\CustomerAgentController@indexAvailable');
	Route::match(['get', 'post'], 'customer-agent/download', 'AdeN\Api\Modules\Customer\Agent\Http\Controllers\CustomerAgentController@download');
