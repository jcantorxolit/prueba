<?php
    
	/**
     *Module: CustomerConfigWorkplaceShiftScheduleDetailEmployee
     */
    Route::get('customer-config-workplace-shift-schedule-detail-employee/get', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailEmployeeController@show');
    Route::post('customer-config-workplace-shift-schedule-detail-employee/save', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailEmployeeController@store');
    Route::post('customer-config-workplace-shift-schedule-detail-employee/change-shift', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailEmployeeController@changeShiift');
    Route::post('customer-config-workplace-shift-schedule-detail-employee/delete', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailEmployeeController@destroy');
    Route::post('customer-config-workplace-shift-schedule-detail-employee/import', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailEmployeeController@import');
    Route::post('customer-config-workplace-shift-schedule-detail-employee/upload', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailEmployeeController@upload');
    Route::match(['get', 'post'], 'customer-config-workplace-shift-schedule-detail-employee', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailEmployeeController@index');
    Route::match(['get', 'post'], 'customer-config-workplace-shift-schedule-detail-employee-basic', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailEmployeeController@indexBasic');
    Route::match(['get', 'post'], 'customer-config-workplace-shift-schedule-detail-employee-complete', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailEmployeeController@indexComplete');
	Route::match(['get', 'post'], 'customer-config-workplace-shift-schedule-detail-employee/download', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailEmployeeController@download');