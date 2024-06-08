<?php
    
	/**
     *Module: CustomerConfigWorkplaceShiftScheduleDetail
     */
    Route::get('customer-config-workplace-shift-schedule-detail/get', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailController@show');
    Route::post('customer-config-workplace-shift-schedule-detail/save', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailController@store');
    Route::post('customer-config-workplace-shift-schedule-detail/allocation', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailController@allocation');
    Route::post('customer-config-workplace-shift-schedule-detail/bulk-allocation', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailController@bulkAllocation');
    Route::post('customer-config-workplace-shift-schedule-detail/delete', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailController@destroy');
    Route::post('customer-config-workplace-shift-schedule-detail/import', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailController@import');
    Route::post('customer-config-workplace-shift-schedule-detail/upload', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailController@upload');
    Route::match(['get', 'post'], 'customer-config-workplace-shift-schedule-detail', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailController@index');
	Route::match(['get', 'post'], 'customer-config-workplace-shift-schedule-detail/download', 'AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\Http\Controllers\CustomerConfigWorkplaceShiftScheduleDetailController@download');