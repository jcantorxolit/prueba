<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-diagnostic', 'AdeN\Api\Modules\Customer\Diagnostic\Http\Controllers\CustomerDiagnosticController@show');
    Route::post('customer-diagnostic/save', 'AdeN\Api\Modules\Customer\Diagnostic\Http\Controllers\CustomerDiagnosticController@store');
    Route::post('customer-diagnostic/update', 'AdeN\Api\Modules\Customer\Diagnostic\Http\Controllers\CustomerDiagnosticController@update');
    Route::post('customer-diagnostic/delete', 'AdeN\Api\Modules\Customer\Diagnostic\Http\Controllers\CustomerDiagnosticController@destroy');
    Route::post('customer-diagnostic/import', 'AdeN\Api\Modules\Customer\Diagnostic\Http\Controllers\CustomerDiagnosticController@import');
    Route::post('customer-diagnostic/upload', 'AdeN\Api\Modules\Customer\Diagnostic\Http\Controllers\CustomerDiagnosticController@upload');
    Route::match(['post'], 'customer-diagnostic', 'AdeN\Api\Modules\Customer\Diagnostic\Http\Controllers\CustomerDiagnosticController@index');
    Route::match(['post'], 'customer-diagnostic-summary', 'AdeN\Api\Modules\Customer\Diagnostic\Http\Controllers\CustomerDiagnosticController@indexSummary');
	Route::match(['get', 'post'], 'customer-diagnostic/download', 'AdeN\Api\Modules\Customer\Diagnostic\Http\Controllers\CustomerDiagnosticController@download');