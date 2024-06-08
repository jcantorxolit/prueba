<?php
    
	/**
     *Module: ProfessorDocument
     */
    Route::get('information-detail/get', 'AdeN\Api\Modules\EmployeeInformationDetail\Http\Controllers\EmployeeInformationDetailController@show');
    Route::post('information-detail/save', 'AdeN\Api\Modules\EmployeeInformationDetail\Http\Controllers\EmployeeInformationDetailController@store');
    //Route::post('information-detail/delete', 'AdeN\Api\Modules\EmployeeInformationDetail\Http\Controllers\EmployeeInformationDetailController@destroy');
    Route::post('customer-employee-contact/delete', 'AdeN\Api\Modules\EmployeeInformationDetail\Http\Controllers\EmployeeInformationDetailController@destroy');
    Route::post('information-detail/import', 'AdeN\Api\Modules\EmployeeInformationDetail\Http\Controllers\EmployeeInformationDetailController@import');
    Route::post('information-detail/upload', 'AdeN\Api\Modules\EmployeeInformationDetail\Http\Controllers\EmployeeInformationDetailController@upload');
    Route::match(['get', 'post'], 'information-detail', 'AdeN\Api\Modules\EmployeeInformationDetail\Http\Controllers\EmployeeInformationDetailController@index');
	Route::match(['get', 'post'], 'information-detail/download', 'AdeN\Api\Modules\EmployeeInformationDetail\Http\Controllers\EmployeeInformationDetailController@download');