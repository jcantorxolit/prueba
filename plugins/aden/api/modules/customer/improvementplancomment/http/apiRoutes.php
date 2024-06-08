<?php
    
	/**
     *Module: CustomerImprovementPlanComment
     */
    Route::get('customer-improvement-plan-comment/get', 'AdeN\Api\Modules\Customer\ImprovementPlanComment\Http\Controllers\CustomerImprovementPlanCommentController@show');
    Route::post('customer-improvement-plan-comment/save', 'AdeN\Api\Modules\Customer\ImprovementPlanComment\Http\Controllers\CustomerImprovementPlanCommentController@store');
    Route::post('customer-improvement-plan-comment/delete', 'AdeN\Api\Modules\Customer\ImprovementPlanComment\Http\Controllers\CustomerImprovementPlanCommentController@destroy');
    Route::post('customer-improvement-plan-comment/import', 'AdeN\Api\Modules\Customer\ImprovementPlanComment\Http\Controllers\CustomerImprovementPlanCommentController@import');
    Route::post('customer-improvement-plan-comment/upload', 'AdeN\Api\Modules\Customer\ImprovementPlanComment\Http\Controllers\CustomerImprovementPlanCommentController@upload');
    Route::match(['get', 'post'], 'customer-improvement-plan-comment', 'AdeN\Api\Modules\Customer\ImprovementPlanComment\Http\Controllers\CustomerImprovementPlanCommentController@index');
	Route::match(['get', 'post'], 'customer-improvement-plan-comment/download', 'AdeN\Api\Modules\Customer\ImprovementPlanComment\Http\Controllers\CustomerImprovementPlanCommentController@download');