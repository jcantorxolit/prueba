<?php
    
	/**
     *Module: CustomerEvaluationMinimumStandard0312
     */
    Route::get('customer-evaluation-minimum-standard-0312/get', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@show');
    Route::post('customer-evaluation-minimum-standard-0312/save', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@store');
    Route::post('customer-evaluation-minimum-standard-0312/delete', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@destroy');
    Route::post('customer-evaluation-minimum-standard-0312/import', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@import');
    Route::post('customer-evaluation-minimum-standard-0312/upload', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@upload');
    Route::post('customer-evaluation-minimum-standard-0312/migrate', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@migrate');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-0312', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@index');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-0312/export-pdf', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@exportPdf');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-0312-summary', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@indexSummary');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-0312-summary/export-excel', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@exportSummary');
	Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-0312/download', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers\CustomerEvaluationMinimumStandard0312Controller@download');