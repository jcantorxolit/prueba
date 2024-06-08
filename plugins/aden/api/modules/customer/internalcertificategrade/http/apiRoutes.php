<?php
    
	/**
     *Module: CustomerInternalCertificateGrade
     */
    /*Route::get('customer-internal-certificate-grade/get', 'AdeN\Api\Modules\Customer\InternalCertificateGrade\Http\Controllers\CustomerInternalCertificateGradeController@show');
    Route::post('customer-internal-certificate-grade/save', 'AdeN\Api\Modules\Customer\InternalCertificateGrade\Http\Controllers\CustomerInternalCertificateGradeController@store');
    Route::post('customer-internal-certificate-grade/delete', 'AdeN\Api\Modules\Customer\InternalCertificateGrade\Http\Controllers\CustomerInternalCertificateGradeController@destroy');
    Route::post('customer-internal-certificate-grade/import', 'AdeN\Api\Modules\Customer\InternalCertificateGrade\Http\Controllers\CustomerInternalCertificateGradeController@import');
    Route::post('customer-internal-certificate-grade/upload', 'AdeN\Api\Modules\Customer\InternalCertificateGrade\Http\Controllers\CustomerInternalCertificateGradeController@upload');*/
    Route::match(['get', 'post'], 'customer-internal-certificate-grade/v2', 'AdeN\Api\Modules\Customer\InternalCertificateGrade\Http\Controllers\CustomerInternalCertificateGradeController@index');
	Route::match(['get', 'post'], 'customer-internal-certificate-grade/download', 'AdeN\Api\Modules\Customer\InternalCertificateGrade\Http\Controllers\CustomerInternalCertificateGradeController@download');