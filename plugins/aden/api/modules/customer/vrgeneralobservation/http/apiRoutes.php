<?php

	/**
     *Module: CustomeVrGeneralObservation
     */
    Route::get('customer-vr-general-observation/get', 'AdeN\Api\Modules\Customer\VrGeneralObservation\Http\Controllers\CustomerVrGeneralObservationController@show');
    Route::post('customer-vr-general-observation/save', 'AdeN\Api\Modules\Customer\VrGeneralObservation\Http\Controllers\CustomerVrGeneralObservationController@store');
    Route::post('customer-vr-general-observation/delete', 'AdeN\Api\Modules\Customer\VrGeneralObservation\Http\Controllers\CustomerVrGeneralObservationController@destroy');
    Route::post('customer-vr-general-observation/import', 'AdeN\Api\Modules\Customer\VrGeneralObservation\Http\Controllers\CustomerVrGeneralObservationController@import');
    Route::match(['get', 'post'], 'customer-vr-general-observation', 'AdeN\Api\Modules\Customer\VrGeneralObservation\Http\Controllers\CustomerVrGeneralObservationController@index');
    Route::match(['get', 'post'], 'customer-vr-general-observation/download-template', 'AdeN\Api\Modules\Customer\VrGeneralObservation\Http\Controllers\CustomerVrGeneralObservationController@downloadTemplate');
