<?php
    
	/**
     *Module: CustomerConfigProcessExpressRelation
     */
    Route::get('customer-config-process-express-relation/get', 'AdeN\Api\Modules\Customer\ConfigProcessExpressRelation\Http\Controllers\CustomerConfigProcessExpressRelationController@show');
    Route::post('customer-config-process-express-relation/save', 'AdeN\Api\Modules\Customer\ConfigProcessExpressRelation\Http\Controllers\CustomerConfigProcessExpressRelationController@store');
    Route::post('customer-config-process-express-relation/copy', 'AdeN\Api\Modules\Customer\ConfigProcessExpressRelation\Http\Controllers\CustomerConfigProcessExpressRelationController@copy');
    Route::post('customer-config-process-express-relation/delete', 'AdeN\Api\Modules\Customer\ConfigProcessExpressRelation\Http\Controllers\CustomerConfigProcessExpressRelationController@destroy');
    Route::post('customer-config-process-express-relation/import', 'AdeN\Api\Modules\Customer\ConfigProcessExpressRelation\Http\Controllers\CustomerConfigProcessExpressRelationController@import');
    Route::post('customer-config-process-express-relation/upload', 'AdeN\Api\Modules\Customer\ConfigProcessExpressRelation\Http\Controllers\CustomerConfigProcessExpressRelationController@upload');
    Route::match(['get', 'post'], 'customer-config-process-express-relation', 'AdeN\Api\Modules\Customer\ConfigProcessExpressRelation\Http\Controllers\CustomerConfigProcessExpressRelationController@index');
	Route::match(['get', 'post'], 'customer-config-process-express-relation/download', 'AdeN\Api\Modules\Customer\ConfigProcessExpressRelation\Http\Controllers\CustomerConfigProcessExpressRelationController@download');