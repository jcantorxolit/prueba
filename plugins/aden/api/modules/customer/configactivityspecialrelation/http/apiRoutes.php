<?php
    
	/**
     *Module: CustomerConfigActivitySpecialRelation
     */
    Route::get('customer-config-activity-special-relation/get', 'AdeN\Api\Modules\Customer\ConfigActivitySpecialRelation\Http\Controllers\CustomerConfigActivitySpecialRelationController@show');
    Route::post('customer-config-activity-special-relation/save', 'AdeN\Api\Modules\Customer\ConfigActivitySpecialRelation\Http\Controllers\CustomerConfigActivitySpecialRelationController@store');
    Route::post('customer-config-activity-special-relation/delete', 'AdeN\Api\Modules\Customer\ConfigActivitySpecialRelation\Http\Controllers\CustomerConfigActivitySpecialRelationController@destroy');
    Route::post('customer-config-activity-special-relation/import', 'AdeN\Api\Modules\Customer\ConfigActivitySpecialRelation\Http\Controllers\CustomerConfigActivitySpecialRelationController@import');
    Route::post('customer-config-activity-special-relation/upload', 'AdeN\Api\Modules\Customer\ConfigActivitySpecialRelation\Http\Controllers\CustomerConfigActivitySpecialRelationController@upload');
    Route::match(['get', 'post'], 'customer-config-activity-special-relation', 'AdeN\Api\Modules\Customer\ConfigActivitySpecialRelation\Http\Controllers\CustomerConfigActivitySpecialRelationController@index');
	Route::match(['get', 'post'], 'customer-config-activity-special-relation/download', 'AdeN\Api\Modules\Customer\ConfigActivitySpecialRelation\Http\Controllers\CustomerConfigActivitySpecialRelationController@download');