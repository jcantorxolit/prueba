<?php
    
	/**
     *Module: CustomerConfigWorkplaceShiftCondition
     */
    Route::get('customer-config-workplace-shift-condition/get', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftCondition\Http\Controllers\CustomerConfigWorkplaceShiftConditionController@show');
    Route::post('customer-config-workplace-shift-condition/save', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftCondition\Http\Controllers\CustomerConfigWorkplaceShiftConditionController@store');
    Route::post('customer-config-workplace-shift-condition/delete', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftCondition\Http\Controllers\CustomerConfigWorkplaceShiftConditionController@destroy');
    Route::post('customer-config-workplace-shift-condition/import', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftCondition\Http\Controllers\CustomerConfigWorkplaceShiftConditionController@import');
    Route::post('customer-config-workplace-shift-condition/upload', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftCondition\Http\Controllers\CustomerConfigWorkplaceShiftConditionController@upload');
    Route::match(['get', 'post'], 'customer-config-workplace-shift-condition', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftCondition\Http\Controllers\CustomerConfigWorkplaceShiftConditionController@index');
	Route::match(['get', 'post'], 'customer-config-workplace-shift-condition/download', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftCondition\Http\Controllers\CustomerConfigWorkplaceShiftConditionController@download');