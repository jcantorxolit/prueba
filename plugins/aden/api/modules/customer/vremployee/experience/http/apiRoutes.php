<?php
    
	/**
     *Module: CustomerVrEmployeeExperience
     */
    Route::get('customer-vr-employee-experience/get', 'AdeN\Api\Modules\Customer\VrEmployee\Experience\Http\Controllers\ExperienceController@show');
    Route::post('customer-vr-employee-experience/save', 'AdeN\Api\Modules\Customer\VrEmployee\Experience\Http\Controllers\ExperienceController@store');
    Route::post('customer-vr-employee-experience/delete', 'AdeN\Api\Modules\Customer\VrEmployee\Experience\Http\Controllers\ExperienceController@destroy');
    Route::post('customer-vr-employee-experience/get-question', 'AdeN\Api\Modules\Customer\VrEmployee\Experience\Http\Controllers\ExperienceController@getQuestion');
    Route::match(['get', 'post'], 'customer-vr-employee-experience', 'AdeN\Api\Modules\Customer\VrEmployee\Experience\Http\Controllers\ExperienceController@index');
	Route::match(['get', 'post'], 'customer-vr-employee-experience/export', 'AdeN\Api\Modules\Customer\VrEmployee\Experience\Http\Controllers\ExperienceController@export');