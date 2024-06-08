<?php
    
	/**
     *Module: CustomerDocumentSecurityUser
     */
    Route::get('customer-document-security-user/get', 'AdeN\Api\Modules\Customer\DocumentSecurityUser\Http\Controllers\CustomerDocumentSecurityUserController@show');
    Route::post('customer-document-security-user/save', 'AdeN\Api\Modules\Customer\DocumentSecurityUser\Http\Controllers\CustomerDocumentSecurityUserController@store');
    Route::post('customer-document-security-user/delete', 'AdeN\Api\Modules\Customer\DocumentSecurityUser\Http\Controllers\CustomerDocumentSecurityUserController@destroy');
    Route::post('customer-document-security-user/import', 'AdeN\Api\Modules\Customer\DocumentSecurityUser\Http\Controllers\CustomerDocumentSecurityUserController@import');
    Route::post('customer-document-security-user/upload', 'AdeN\Api\Modules\Customer\DocumentSecurityUser\Http\Controllers\CustomerDocumentSecurityUserController@upload');
    Route::match(['get', 'post'], 'customer-document-security-user', 'AdeN\Api\Modules\Customer\DocumentSecurityUser\Http\Controllers\CustomerDocumentSecurityUserController@index');
	Route::match(['get', 'post'], 'customer-document-security-user/download', 'AdeN\Api\Modules\Customer\DocumentSecurityUser\Http\Controllers\CustomerDocumentSecurityUserController@download');