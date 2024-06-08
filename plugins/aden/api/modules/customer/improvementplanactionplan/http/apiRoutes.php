<?php
    
	/**
     *Module: CustomerImprovementPlanActionPlan
     */
    Route::get('customer-improvement-plan-action-plan/get', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlan\Http\Controllers\CustomerImprovementPlanActionPlanController@show');
    Route::post('customer-improvement-plan-action-plan/save', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlan\Http\Controllers\CustomerImprovementPlanActionPlanController@store');
    Route::post('customer-improvement-plan-action-plan/update', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlan\Http\Controllers\CustomerImprovementPlanActionPlanController@update');
    Route::post('customer-improvement-plan-action-plan/delete', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlan\Http\Controllers\CustomerImprovementPlanActionPlanController@destroy');
    Route::post('customer-improvement-plan-action-plan/import', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlan\Http\Controllers\CustomerImprovementPlanActionPlanController@import');
    Route::post('customer-improvement-plan-action-plan/upload', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlan\Http\Controllers\CustomerImprovementPlanActionPlanController@upload');
    Route::match(['get', 'post'], 'customer-improvement-plan-action-plan', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlan\Http\Controllers\CustomerImprovementPlanActionPlanController@index');
	Route::match(['get', 'post'], 'customer-improvement-plan-action-plan/download', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlan\Http\Controllers\CustomerImprovementPlanActionPlanController@download');