<?php
    
	/**
     *Module: CustomerConfigWorkplaceShiftSchedule
     */
    Route::get('customer-config-workplace-shift-schedule/get', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\Http\Controllers\CustomerConfigWorkplaceShiftScheduleController@show');
    Route::post('customer-config-workplace-shift-schedule/save', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\Http\Controllers\CustomerConfigWorkplaceShiftScheduleController@store');
    Route::post('customer-config-workplace-shift-schedule/delete', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\Http\Controllers\CustomerConfigWorkplaceShiftScheduleController@destroy');
    Route::post('customer-config-workplace-shift-schedule/update-scheduled-status', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\Http\Controllers\CustomerConfigWorkplaceShiftScheduleController@updateStatus');
    Route::post('customer-config-workplace-shift-schedule/import', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\Http\Controllers\CustomerConfigWorkplaceShiftScheduleController@import');
    Route::post('customer-config-workplace-shift-schedule/upload', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\Http\Controllers\CustomerConfigWorkplaceShiftScheduleController@upload');
    Route::match(['get', 'post'], 'customer-config-workplace-shift-schedule', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\Http\Controllers\CustomerConfigWorkplaceShiftScheduleController@index');
    Route::match(['get', 'post'], 'customer-config-workplace-shift-schedule/export-excel', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\Http\Controllers\CustomerConfigWorkplaceShiftScheduleController@exportExcel');
	Route::match(['get', 'post'], 'customer-config-workplace-shift-schedule/download', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\Http\Controllers\CustomerConfigWorkplaceShiftScheduleController@download');