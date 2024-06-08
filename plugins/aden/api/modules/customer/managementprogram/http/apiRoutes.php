<?php
    
	/**
     *Module: CustomerManagementProgram
     */
    Route::get('customer-management-program/get', 'AdeN\Api\Modules\Customer\ManagementProgram\Http\Controllers\CustomerManagementProgramController@show');
    Route::post('customer-management-program/save', 'AdeN\Api\Modules\Customer\ManagementProgram\Http\Controllers\CustomerManagementProgramController@store');
    Route::post('customer-management-program/delete', 'AdeN\Api\Modules\Customer\ManagementProgram\Http\Controllers\CustomerManagementProgramController@destroy');
    Route::post('customer-management-program/import', 'AdeN\Api\Modules\Customer\ManagementProgram\Http\Controllers\CustomerManagementProgramController@import');
    Route::post('customer-management-program/upload', 'AdeN\Api\Modules\Customer\ManagementProgram\Http\Controllers\CustomerManagementProgramController@upload');
    Route::match(['get', 'post'], 'customer-management-program', 'AdeN\Api\Modules\Customer\ManagementProgram\Http\Controllers\CustomerManagementProgramController@index');
	Route::match(['get', 'post'], 'customer-management-program/download', 'AdeN\Api\Modules\Customer\ManagementProgram\Http\Controllers\CustomerManagementProgramController@download');