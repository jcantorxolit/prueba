<?php
    
	/**
     *Module: CustomerConfigWorkplace
     */
    Route::get('customer-config-workplace/get', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@show');
    Route::get('customer-config-workplace/get-gtc', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@showGTC45');
    Route::get('customer-config-workplace/get-shift-condition', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@showShiftCondition');
    Route::get('customer-config-workplace/get-elegible-employee', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@showElegibleEmployee');
    Route::post('customer-config-workplace/save', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@store');
    Route::post('customer-config-workplace/save-gtc', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@storeGTC45');
    Route::post('customer-config-workplace/save-shift-condition', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@storeShiftCondition');
    Route::post('customer-config-workplace/copy', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@copy');
    Route::post('customer-config-workplace/delete', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@destroy');
    Route::post('customer-config-workplace/import', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@import');
    Route::post('customer-config-workplace/upload', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@upload');
    Route::match(['get', 'post'], 'customer-config-workplace', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@index');
    Route::match(['get', 'post'], 'customer-config-workplace-shift', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@indexShift');
    Route::match(['get', 'post'], 'customer-config-workplace-shift-available', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@indexShiftAvailable');
    Route::match(['get', 'post'], 'customer-config-workplace-express', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@indexExpress');
	Route::match(['get', 'post'], 'customer-config-workplace/download', 'AdeN\Api\Modules\Customer\ConfigWorkplace\Http\Controllers\CustomerConfigWorkplaceController@download');