<?php
    
	/**
     *Module: CustomerConfigMacroProcessStaging
     */
    Route::get('customer-config-macro-process-staging/get', 'AdeN\Api\Modules\Customer\ConfigMacroProcessStaging\Http\Controllers\CustomerConfigMacroProcessStagingController@show');
    Route::post('customer-config-macro-process-staging/save', 'AdeN\Api\Modules\Customer\ConfigMacroProcessStaging\Http\Controllers\CustomerConfigMacroProcessStagingController@store');
    Route::post('customer-config-macro-process-staging/delete', 'AdeN\Api\Modules\Customer\ConfigMacroProcessStaging\Http\Controllers\CustomerConfigMacroProcessStagingController@destroy');
    Route::post('customer-config-macro-process-staging/import', 'AdeN\Api\Modules\Customer\ConfigMacroProcessStaging\Http\Controllers\CustomerConfigMacroProcessStagingController@import');
    Route::post('customer-config-macro-process-staging/upload', 'AdeN\Api\Modules\Customer\ConfigMacroProcessStaging\Http\Controllers\CustomerConfigMacroProcessStagingController@upload');
    Route::match(['get', 'post'], 'customer-config-macro-process-staging', 'AdeN\Api\Modules\Customer\ConfigMacroProcessStaging\Http\Controllers\CustomerConfigMacroProcessStagingController@index');
	Route::match(['get', 'post'], 'customer-config-macro-process-staging/download', 'AdeN\Api\Modules\Customer\ConfigMacroProcessStaging\Http\Controllers\CustomerConfigMacroProcessStagingController@download');