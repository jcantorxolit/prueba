<?php
    
	/**
     *Module: ExperienceEvaluation
     */
    
    Route::post('customer-vr-employee-experience-evaluation/save', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceEvaluation\Http\Controllers\ExperienceEvaluationController@store');
    Route::post('customer-vr-employee-experience-evaluation/upload', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceEvaluation\Http\Controllers\ExperienceEvaluationController@upload');
    Route::get('customer-vr-employee-experience-evaluation/generate-certificate', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceEvaluation\Http\Controllers\ExperienceEvaluationController@store');
    Route::post('customer-vr-employee-experience-evaluation/destroy-certificate', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceEvaluation\Http\Controllers\ExperienceEvaluationController@destroyCertificate');
    Route::post('customer-vr-employee-experience-evaluation/generate-massive-certificates', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceEvaluation\Http\Controllers\ExperienceEvaluationController@generateMassiveCertificates');