<?php
    
	/**
     *Module: CustomeManacleEmployee
     */
    Route::get('customer-manacle-employee/get', 'AdeN\Api\Modules\Customer\ManacleEmployee\Http\Controllers\CustomerManacleEmployeeController@show');
    Route::post('customer-manacle-employee/save', 'AdeN\Api\Modules\Customer\ManacleEmployee\Http\Controllers\CustomerManacleEmployeeController@store');
    Route::post('customer-manacle-employee/delete', 'AdeN\Api\Modules\Customer\ManacleEmployee\Http\Controllers\CustomerManacleEmployeeController@destroy');
    Route::post('customer-manacle-employee/import', 'AdeN\Api\Modules\Customer\ManacleEmployee\Http\Controllers\CustomerManacleEmployeeController@import');
    Route::match(['get', 'post'], 'customer-manacle-employee', 'AdeN\Api\Modules\Customer\ManacleEmployee\Http\Controllers\CustomerManacleEmployeeController@index');
    Route::match(['get', 'post'], 'customer-manacle-employee/download-template', 'AdeN\Api\Modules\Customer\ManacleEmployee\Http\Controllers\CustomerManacleEmployeeController@downloadTemplate');