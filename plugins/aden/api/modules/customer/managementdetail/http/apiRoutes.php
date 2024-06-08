<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-management-detail', 'AdeN\Api\Modules\Customer\ManagementDetail\Http\Controllers\CustomerManagementDetailController@show');
    Route::post('customer-management-detail/save', 'AdeN\Api\Modules\Customer\ManagementDetail\Http\Controllers\CustomerManagementDetailController@store');
    Route::post('customer-management-detail/update', 'AdeN\Api\Modules\Customer\ManagementDetail\Http\Controllers\CustomerManagementDetailController@update');
    Route::post('customer-management-detail/delete', 'AdeN\Api\Modules\Customer\ManagementDetail\Http\Controllers\CustomerManagementDetailController@destroy');
    Route::post('customer-management-detail/import', 'AdeN\Api\Modules\Customer\ManagementDetail\Http\Controllers\CustomerManagementDetailController@import');
    Route::post('customer-management-detail/upload', 'AdeN\Api\Modules\Customer\ManagementDetail\Http\Controllers\CustomerManagementDetailController@upload');
    Route::match(['post'], 'customer-management-detail', 'AdeN\Api\Modules\Customer\ManagementDetail\Http\Controllers\CustomerManagementDetailController@index');
    Route::match(['post'], 'customer-management-detail-question', 'AdeN\Api\Modules\Customer\ManagementDetail\Http\Controllers\CustomerManagementDetailController@indexQuestion');
	Route::match(['get', 'post'], 'customer-management-detail/download', 'AdeN\Api\Modules\Customer\ManagementDetail\Http\Controllers\CustomerManagementDetailController@download');
	Route::match(['get', 'post'], 'customer-management-detail/export-excel', 'AdeN\Api\Modules\Customer\ManagementDetail\Http\Controllers\CustomerManagementDetailController@exportExcel');