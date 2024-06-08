<?php
    
	/**
     *Module: CustomerImprovementPlanActionPlanComment
     */
    Route::get('customer-improvement-plan-action-plan-comment/get', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlanComment\Http\Controllers\CustomerImprovementPlanActionPlanCommentController@show');
    Route::post('customer-improvement-plan-action-plan-comment/save', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlanComment\Http\Controllers\CustomerImprovementPlanActionPlanCommentController@store');
    Route::post('customer-improvement-plan-action-plan-comment/delete', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlanComment\Http\Controllers\CustomerImprovementPlanActionPlanCommentController@destroy');
    Route::post('customer-improvement-plan-action-plan-comment/import', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlanComment\Http\Controllers\CustomerImprovementPlanActionPlanCommentController@import');
    Route::post('customer-improvement-plan-action-plan-comment/upload', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlanComment\Http\Controllers\CustomerImprovementPlanActionPlanCommentController@upload');
    Route::match(['get', 'post'], 'customer-improvement-plan-action-plan-comment', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlanComment\Http\Controllers\CustomerImprovementPlanActionPlanCommentController@index');
	Route::match(['get', 'post'], 'customer-improvement-plan-action-plan-comment/download', 'AdeN\Api\Modules\Customer\ImprovementPlanActionPlanComment\Http\Controllers\CustomerImprovementPlanActionPlanCommentController@download');