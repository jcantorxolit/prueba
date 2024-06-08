<?php
    
	/**
     *Module: DisabilityDiagnostic
     */
    Route::match(['get', 'post'], 'disability-diagnostic', 'AdeN\Api\Modules\DisabilityDiagnostic\Http\Controllers\DisabilityDiagnosticController@index');
    Route::match(['get', 'post'], 'disability-diagnostic-employee', 'AdeN\Api\Modules\DisabilityDiagnostic\Http\Controllers\DisabilityDiagnosticController@indexEmployee');
    Route::match(['get', 'post'], 'disability-diagnostic-source-employee', 'AdeN\Api\Modules\DisabilityDiagnostic\Http\Controllers\DisabilityDiagnosticController@indexSourceEmployee');
	Route::match(['get', 'post'], 'disability-diagnostic/download', 'AdeN\Api\Modules\DisabilityDiagnostic\Http\Controllers\DisabilityDiagnosticController@download');