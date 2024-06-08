<?php
    
	/**
     *Module: CustomerVrEmployeeExperience
     */
    Route::get('customer-vr-employee-experience-answer/get', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers\ExperienceAnswerController@show');
    Route::post('customer-vr-employee-experience-answer/save', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers\ExperienceAnswerController@store');
    Route::post('customer-vr-employee-experience-answer/delete', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers\ExperienceAnswerController@destroy');
    Route::post('customer-vr-employee-experience-answer/get-question', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers\ExperienceAnswerController@getQuestion');
    Route::post('customer-vr-employee-experience-answer/get-all', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers\ExperienceAnswerController@getAllExperiencesWithScenes');
    Route::post('customer-vr-employee-experience-answer/observations', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers\ExperienceAnswerController@getObservations');
    Route::post('customer-vr-employee-experience-answer/observations-count', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers\ExperienceAnswerController@getCountObservations');
    Route::post('customer-vr-employee-experience-answer/observations-detail', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers\ExperienceAnswerController@getObservationsDetail');
    Route::match(['get', 'post'], 'customer-vr-employee-experience-answer', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers\ExperienceAnswerController@index');
	Route::match(['get', 'post'], 'customer-vr-employee-experience-answer/observations-export', 'AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers\ExperienceAnswerController@export');