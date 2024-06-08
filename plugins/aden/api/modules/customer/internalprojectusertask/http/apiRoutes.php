<?php
    
	/**
     *Module: CustomerInternalProjectUserTask
     */
    Route::get('customer-internal-project-user-task/get', 'AdeN\Api\Modules\Customer\InternalProjectUserTask\Http\Controllers\CustomerInternalProjectUserTaskController@show');
    Route::post('customer-internal-project-user-task/save', 'AdeN\Api\Modules\Customer\InternalProjectUserTask\Http\Controllers\CustomerInternalProjectUserTaskController@store');
    Route::post('customer-internal-project-user-task/delete', 'AdeN\Api\Modules\Customer\InternalProjectUserTask\Http\Controllers\CustomerInternalProjectUserTaskController@destroy');
    Route::post('customer-internal-project-user-task/import', 'AdeN\Api\Modules\Customer\InternalProjectUserTask\Http\Controllers\CustomerInternalProjectUserTaskController@import');
    Route::post('customer-internal-project-user-task/upload', 'AdeN\Api\Modules\Customer\InternalProjectUserTask\Http\Controllers\CustomerInternalProjectUserTaskController@upload');
    Route::match(['get', 'post'], 'customer-internal-project-user-task', 'AdeN\Api\Modules\Customer\InternalProjectUserTask\Http\Controllers\CustomerInternalProjectUserTaskController@index');
    Route::match(['get', 'post'], 'customer-internal-project-user-task-agent', 'AdeN\Api\Modules\Customer\InternalProjectUserTask\Http\Controllers\CustomerInternalProjectUserTaskController@indexAgent');
	Route::match(['get', 'post'], 'customer-internal-project-user-task/download', 'AdeN\Api\Modules\Customer\InternalProjectUserTask\Http\Controllers\CustomerInternalProjectUserTaskController@download');