<?php
    
	/**
     *Module: CustomerConfigMacroProcess
     */
    Route::get('customer-config-macro-process/get', 'AdeN\Api\Modules\Customer\ConfigMacroProcess\Http\Controllers\CustomerConfigMacroProcessController@show');
    Route::post('customer-config-macro-process/save', 'AdeN\Api\Modules\Customer\ConfigMacroProcess\Http\Controllers\CustomerConfigMacroProcessController@store');
    Route::post('customer-config-macro-process/delete', 'AdeN\Api\Modules\Customer\ConfigMacroProcess\Http\Controllers\CustomerConfigMacroProcessController@destroy');
    Route::post('customer-config-macro-process/import', 'AdeN\Api\Modules\Customer\ConfigMacroProcess\Http\Controllers\CustomerConfigMacroProcessController@import');
    Route::post('customer-config-macro-process/upload', 'AdeN\Api\Modules\Customer\ConfigMacroProcess\Http\Controllers\CustomerConfigMacroProcessController@upload');
    Route::match(['get', 'post'], 'customer-config-macro-process', 'AdeN\Api\Modules\Customer\ConfigMacroProcess\Http\Controllers\CustomerConfigMacroProcessController@index');
	Route::match(['get', 'post'], 'customer-config-macro-process/download', 'AdeN\Api\Modules\Customer\ConfigMacroProcess\Http\Controllers\CustomerConfigMacroProcessController@download');
	Route::match(['get', 'post'], 'customer-config-macro-process/download-template', 'AdeN\Api\Modules\Customer\ConfigMacroProcess\Http\Controllers\CustomerConfigMacroProcessController@downloadTemplate');