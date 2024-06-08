<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-audit', 'AdeN\Api\Modules\Customer\Audit\Http\Controllers\CustomerAuditController@show');
    Route::post('customer-audit/save', 'AdeN\Api\Modules\Customer\Audit\Http\Controllers\CustomerAuditController@store');
    Route::post('customer-audit/update', 'AdeN\Api\Modules\Customer\Audit\Http\Controllers\CustomerAuditController@update');
    Route::post('customer-audit/delete', 'AdeN\Api\Modules\Customer\Audit\Http\Controllers\CustomerAuditController@destroy');
    Route::post('customer-audit/import', 'AdeN\Api\Modules\Customer\Audit\Http\Controllers\CustomerAuditController@import');
    Route::post('customer-audit/upload', 'AdeN\Api\Modules\Customer\Audit\Http\Controllers\CustomerAuditController@upload');
    Route::match(['post'], 'customer-audit', 'AdeN\Api\Modules\Customer\Audit\Http\Controllers\CustomerAuditController@index');
	Route::match(['get', 'post'], 'customer-audit/download', 'AdeN\Api\Modules\Customer\Audit\Http\Controllers\CustomerAuditController@download');