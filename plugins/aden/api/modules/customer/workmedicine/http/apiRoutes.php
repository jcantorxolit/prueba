<?php
    
	/**
     *Module: CustomerWorkMedicine
     */
    Route::get('customer-work-medicine/get', 'AdeN\Api\Modules\Customer\WorkMedicine\Http\Controllers\CustomerWorkMedicineController@show');
    Route::post('customer-work-medicine/save', 'AdeN\Api\Modules\Customer\WorkMedicine\Http\Controllers\CustomerWorkMedicineController@store');
    Route::post('customer-work-medicine/delete', 'AdeN\Api\Modules\Customer\WorkMedicine\Http\Controllers\CustomerWorkMedicineController@destroy');
    Route::post('customer-work-medicine/import', 'AdeN\Api\Modules\Customer\WorkMedicine\Http\Controllers\CustomerWorkMedicineController@import');
    Route::post('customer-work-medicine/upload', 'AdeN\Api\Modules\Customer\WorkMedicine\Http\Controllers\CustomerWorkMedicineController@upload');
    Route::match(['get', 'post'], 'customer-work-medicine', 'AdeN\Api\Modules\Customer\WorkMedicine\Http\Controllers\CustomerWorkMedicineController@index');
	Route::match(['get', 'post'], 'customer-work-medicine/download', 'AdeN\Api\Modules\Customer\WorkMedicine\Http\Controllers\CustomerWorkMedicineController@download');

    Route::match(['get', 'post'], 'customer-work-medicine/export-excel', 'AdeN\Api\Modules\Customer\WorkMedicine\Http\Controllers\CustomerWorkMedicineController@dowloadExcel');
    Route::match(['get', 'post'], 'customer-work-medicine/download-template', 'AdeN\Api\Modules\Customer\WorkMedicine\Http\Controllers\CustomerWorkMedicineController@downloadTemplate');
