<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-improvement-plan', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@show');
    Route::post('customer-improvement-plan/save', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@store');
    Route::post('customer-improvement-plan/update', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@update');
    Route::post('customer-improvement-plan/delete', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@destroy');
    Route::post('customer-improvement-plan/import', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@import');
    Route::post('customer-improvement-plan/upload', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@upload');
    Route::match(['post'], 'customer-improvement-plan', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@index');
    Route::match(['post'], 'customer-improvement-plan-matrix', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@indexMatrix');
    Route::match(['post'], 'customer-improvement-plan-entity', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@indexEntity');
    Route::match(['get', 'post'], 'customer-improvement-plan/download', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@download');
    Route::match(['get', 'post'], 'customer-improvement-plan/export-excel', 'AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers\CustomerImprovementPlanController@exportExcel');