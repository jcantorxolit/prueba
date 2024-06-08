<?php
    
	/**
     *Module: CustomerConfigJobExpressRelation
     */
    Route::get('customer-config-job-express-relation/get', 'AdeN\Api\Modules\Customer\ConfigJobExpressRelation\Http\Controllers\CustomerConfigJobExpressRelationController@show');
    Route::post('customer-config-job-express-relation/save', 'AdeN\Api\Modules\Customer\ConfigJobExpressRelation\Http\Controllers\CustomerConfigJobExpressRelationController@store');
    Route::post('customer-config-job-express-relation/delete', 'AdeN\Api\Modules\Customer\ConfigJobExpressRelation\Http\Controllers\CustomerConfigJobExpressRelationController@destroy');
    Route::post('customer-config-job-express-relation/import', 'AdeN\Api\Modules\Customer\ConfigJobExpressRelation\Http\Controllers\CustomerConfigJobExpressRelationController@import');
    Route::post('customer-config-job-express-relation/upload', 'AdeN\Api\Modules\Customer\ConfigJobExpressRelation\Http\Controllers\CustomerConfigJobExpressRelationController@upload');
    Route::match(['get', 'post'], 'customer-config-job-express-relation', 'AdeN\Api\Modules\Customer\ConfigJobExpressRelation\Http\Controllers\CustomerConfigJobExpressRelationController@index');
	Route::match(['get', 'post'], 'customer-config-job-express-relation/download', 'AdeN\Api\Modules\Customer\ConfigJobExpressRelation\Http\Controllers\CustomerConfigJobExpressRelationController@download');