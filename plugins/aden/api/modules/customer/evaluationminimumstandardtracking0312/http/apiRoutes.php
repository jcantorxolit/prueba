<?php
    
	/**
     *Module: CustomerEvaluationMinimumStandardTracking0312
     */
    Route::get('customer-evaluation-minimum-standard-tracking-0312/get', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@show');
    Route::post('customer-evaluation-minimum-standard-tracking-0312/save', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@store');
    Route::post('customer-evaluation-minimum-standard-tracking-0312/delete', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@destroy');
    Route::post('customer-evaluation-minimum-standard-tracking-0312/import', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@import');
    Route::post('customer-evaluation-minimum-standard-tracking-0312/upload', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@upload');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-tracking-0312', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@index');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-tracking-0312-summary-cycle', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@indexSummaryCycle');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-tracking-0312-summary-cycle-detail', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@indexSummaryCycleDetail');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-tracking-0312-summary-indicator', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@indexSummaryIndicator');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-tracking-0312-summary-cycle/export-excel', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@exportSummaryCycle');
    Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-tracking-0312-summary-indicator/export-excel', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@exportSummaryIndicator');
	Route::match(['get', 'post'], 'customer-evaluation-minimum-standard-tracking-0312/download', 'AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\Http\Controllers\CustomerEvaluationMinimumStandardTracking0312Controller@download');