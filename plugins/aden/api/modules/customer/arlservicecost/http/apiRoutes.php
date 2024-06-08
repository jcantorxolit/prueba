<?php

	/**
     *Module: CustomeArlServiceCost
     */
    Route::get('customer-arl-service-cost/get', 'AdeN\Api\Modules\Customer\ArlServiceCost\Http\Controllers\CustomerArlServiceCostController@show');
    Route::post('customer-arl-service-cost/save', 'AdeN\Api\Modules\Customer\ArlServiceCost\Http\Controllers\CustomerArlServiceCostController@store');
    Route::post('customer-arl-service-cost/delete', 'AdeN\Api\Modules\Customer\ArlServiceCost\Http\Controllers\CustomerArlServiceCostController@destroy');
    Route::post('customer-arl-service-cost/import', 'AdeN\Api\Modules\Customer\ArlServiceCost\Http\Controllers\CustomerArlServiceCostController@import');
    Route::match(['get', 'post'], 'customer-arl-service-cost', 'AdeN\Api\Modules\Customer\ArlServiceCost\Http\Controllers\CustomerArlServiceCostController@index');
    Route::match(['get', 'post'], 'customer-arl-service-cost/download-template', 'AdeN\Api\Modules\Customer\ArlServiceCost\Http\Controllers\CustomerArlServiceCostController@downloadTemplate');
