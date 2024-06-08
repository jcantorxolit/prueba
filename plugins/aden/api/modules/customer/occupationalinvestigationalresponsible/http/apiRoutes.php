<?php
    
	/**
     *Module: CustomerOccupationalInvestigationAlResponsible
     */
    Route::get('customer-occupational-investigation-al-responsible/get', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible\Http\Controllers\CustomerOccupationalInvestigationAlResponsibleController@show');
    Route::get('customer-occupational-investigation-al-responsible/get-relation', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible\Http\Controllers\CustomerOccupationalInvestigationAlResponsibleController@showRelation');
    Route::post('customer-occupational-investigation-al-responsible/save', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible\Http\Controllers\CustomerOccupationalInvestigationAlResponsibleController@store');
    Route::post('customer-occupational-investigation-al-responsible/delete', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible\Http\Controllers\CustomerOccupationalInvestigationAlResponsibleController@destroy');
    Route::post('customer-occupational-investigation-al-responsible/import', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible\Http\Controllers\CustomerOccupationalInvestigationAlResponsibleController@import');
    Route::post('customer-occupational-investigation-al-responsible/upload', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible\Http\Controllers\CustomerOccupationalInvestigationAlResponsibleController@upload');
    Route::match(['get', 'post'], 'customer-occupational-investigation-al-responsible', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible\Http\Controllers\CustomerOccupationalInvestigationAlResponsibleController@index');
    Route::match(['get', 'post'], 'customer-occupational-investigation-al-responsible-available', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible\Http\Controllers\CustomerOccupationalInvestigationAlResponsibleController@indexAvailable');
	Route::match(['get', 'post'], 'customer-occupational-investigation-al-responsible/download', 'AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible\Http\Controllers\CustomerOccupationalInvestigationAlResponsibleController@download');