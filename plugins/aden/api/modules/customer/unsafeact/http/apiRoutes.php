<?php
    
	/**
     *Module: CustomerUnsafeAct
     */
    Route::get('customer-unsafe-act/get', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@show');
    Route::get('customer-unsafe-act/migrate-files-api', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@migrateFilesApi');
    Route::post('customer-unsafe-act/save', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@store');
    Route::post('customer-unsafe-act/delete', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@destroy');
    Route::post('customer-unsafe-act/import', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@import');
    Route::post('customer-unsafe-act/upload', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@upload');
    Route::match(['get', 'post'], 'customer-unsafe-act', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@index');
    Route::match(['get', 'post'], 'customer-unsafe-act-massive', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@indexMassive');
    Route::match(['get', 'post'], 'customer-unsafe-act/export-excel', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@exportExcel');
    Route::match(['get', 'post'], 'customer-unsafe-act/export-report', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@exportReport');
    Route::match(['get', 'post'], 'customer-unsafe-act/export-zip', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@exportZip');
    Route::match(['get', 'post'], 'customer-unsafe-act/export-massive-zip', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@exportMassiveZip');
    Route::match(['get', 'post'], 'customer-unsafe-act/download', 'AdeN\Api\Modules\Customer\UnsafeAct\Http\Controllers\CustomerUnsafeActController@download');
    