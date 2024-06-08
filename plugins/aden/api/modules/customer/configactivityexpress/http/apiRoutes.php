<?php
    
	/**
     *Module: CustomerConfigActivityExpress
     */
    Route::get('customer-config-activity-express/get', 'AdeN\Api\Modules\Customer\ConfigActivityExpress\Http\Controllers\CustomerConfigActivityExpressController@show');
    Route::post('customer-config-activity-express/save', 'AdeN\Api\Modules\Customer\ConfigActivityExpress\Http\Controllers\CustomerConfigActivityExpressController@store');
    Route::post('customer-config-activity-express/delete', 'AdeN\Api\Modules\Customer\ConfigActivityExpress\Http\Controllers\CustomerConfigActivityExpressController@destroy');
    Route::post('customer-config-activity-express/import', 'AdeN\Api\Modules\Customer\ConfigActivityExpress\Http\Controllers\CustomerConfigActivityExpressController@import');
    Route::post('customer-config-activity-express/upload', 'AdeN\Api\Modules\Customer\ConfigActivityExpress\Http\Controllers\CustomerConfigActivityExpressController@upload');
    Route::match(['get', 'post'], 'customer-config-activity-express', 'AdeN\Api\Modules\Customer\ConfigActivityExpress\Http\Controllers\CustomerConfigActivityExpressController@index');
	Route::match(['get', 'post'], 'customer-config-activity-express/download', 'AdeN\Api\Modules\Customer\ConfigActivityExpress\Http\Controllers\CustomerConfigActivityExpressController@download');