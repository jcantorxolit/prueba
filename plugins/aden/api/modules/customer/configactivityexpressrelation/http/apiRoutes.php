<?php
    
	/**
     *Module: CustomerConfigActivityExpressRelation
     */
    Route::get('customer-config-activity-express-relation/get', 'AdeN\Api\Modules\Customer\ConfigActivityExpressRelation\Http\Controllers\CustomerConfigActivityExpressRelationController@show');
    Route::post('customer-config-activity-express-relation/save', 'AdeN\Api\Modules\Customer\ConfigActivityExpressRelation\Http\Controllers\CustomerConfigActivityExpressRelationController@store');
    Route::post('customer-config-activity-express-relation/delete', 'AdeN\Api\Modules\Customer\ConfigActivityExpressRelation\Http\Controllers\CustomerConfigActivityExpressRelationController@destroy');
    Route::post('customer-config-activity-express-relation/import', 'AdeN\Api\Modules\Customer\ConfigActivityExpressRelation\Http\Controllers\CustomerConfigActivityExpressRelationController@import');
    Route::post('customer-config-activity-express-relation/upload', 'AdeN\Api\Modules\Customer\ConfigActivityExpressRelation\Http\Controllers\CustomerConfigActivityExpressRelationController@upload');
    Route::match(['get', 'post'], 'customer-config-activity-express-relation', 'AdeN\Api\Modules\Customer\ConfigActivityExpressRelation\Http\Controllers\CustomerConfigActivityExpressRelationController@index');
	Route::match(['get', 'post'], 'customer-config-activity-express-relation/download', 'AdeN\Api\Modules\Customer\ConfigActivityExpressRelation\Http\Controllers\CustomerConfigActivityExpressRelationController@download');