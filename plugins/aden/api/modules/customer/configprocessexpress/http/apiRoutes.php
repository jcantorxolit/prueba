<?php
    
	/**
     *Module: CustomerConfigProcessExpress
     */
    Route::get('customer-config-process-express/get', 'AdeN\Api\Modules\Customer\ConfigProcessExpress\Http\Controllers\CustomerConfigProcessExpressController@show');
    Route::post('customer-config-process-express/save', 'AdeN\Api\Modules\Customer\ConfigProcessExpress\Http\Controllers\CustomerConfigProcessExpressController@store');
    Route::post('customer-config-process-express/delete', 'AdeN\Api\Modules\Customer\ConfigProcessExpress\Http\Controllers\CustomerConfigProcessExpressController@destroy');
    Route::post('customer-config-process-express/import', 'AdeN\Api\Modules\Customer\ConfigProcessExpress\Http\Controllers\CustomerConfigProcessExpressController@import');
    Route::post('customer-config-process-express/upload', 'AdeN\Api\Modules\Customer\ConfigProcessExpress\Http\Controllers\CustomerConfigProcessExpressController@upload');
    Route::match(['get', 'post'], 'customer-config-process-express', 'AdeN\Api\Modules\Customer\ConfigProcessExpress\Http\Controllers\CustomerConfigProcessExpressController@index');
	Route::match(['get', 'post'], 'customer-config-process-express/download', 'AdeN\Api\Modules\Customer\ConfigProcessExpress\Http\Controllers\CustomerConfigProcessExpressController@download');