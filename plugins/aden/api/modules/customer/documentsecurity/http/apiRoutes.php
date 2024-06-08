<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-document-security', 'AdeN\Api\Modules\Customer\DocumentSecurity\Http\Controllers\CustomerDocumentSecurityController@show');
    Route::post('customer-document-security/get-relation', 'AdeN\Api\Modules\Customer\DocumentSecurity\Http\Controllers\CustomerDocumentSecurityController@showRelation');
    Route::post('customer-document-security/save', 'AdeN\Api\Modules\Customer\DocumentSecurity\Http\Controllers\CustomerDocumentSecurityController@store');
    Route::post('customer-document-security/update', 'AdeN\Api\Modules\Customer\DocumentSecurity\Http\Controllers\CustomerDocumentSecurityController@update');
    Route::post('customer-document-security/delete', 'AdeN\Api\Modules\Customer\DocumentSecurity\Http\Controllers\CustomerDocumentSecurityController@destroy');
    Route::post('customer-document-security/import', 'AdeN\Api\Modules\Customer\DocumentSecurity\Http\Controllers\CustomerDocumentSecurityController@import');
    Route::post('customer-document-security/upload', 'AdeN\Api\Modules\Customer\DocumentSecurity\Http\Controllers\CustomerDocumentSecurityController@upload');
    Route::match(['post'], 'customer-document-security', 'AdeN\Api\Modules\Customer\DocumentSecurity\Http\Controllers\CustomerDocumentSecurityController@index');
	Route::match(['get', 'post'], 'customer-document-security/download', 'AdeN\Api\Modules\Customer\DocumentSecurity\Http\Controllers\CustomerDocumentSecurityController@download');