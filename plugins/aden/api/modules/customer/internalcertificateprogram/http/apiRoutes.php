<?php
    
	/**
     *Module: CustomerInternalCertificateProgram
     */
    /*Route::get('customer-internal-certificate-program/get', 'AdeN\Api\Modules\Customer\InternalCertificateProgram\Http\Controllers\CustomerInternalCertificateProgramController@show');
    Route::post('customer-internal-certificate-program/save', 'AdeN\Api\Modules\Customer\InternalCertificateProgram\Http\Controllers\CustomerInternalCertificateProgramController@store');
    Route::post('customer-internal-certificate-program/delete', 'AdeN\Api\Modules\Customer\InternalCertificateProgram\Http\Controllers\CustomerInternalCertificateProgramController@destroy');
    Route::post('customer-internal-certificate-program/import', 'AdeN\Api\Modules\Customer\InternalCertificateProgram\Http\Controllers\CustomerInternalCertificateProgramController@import');
    Route::post('customer-internal-certificate-program/upload', 'AdeN\Api\Modules\Customer\InternalCertificateProgram\Http\Controllers\CustomerInternalCertificateProgramController@upload');*/
    Route::match(['get', 'post'], 'customer-internal-certificate-program/v2', 'AdeN\Api\Modules\Customer\InternalCertificateProgram\Http\Controllers\CustomerInternalCertificateProgramController@index');
	Route::match(['get', 'post'], 'customer-internal-certificate-program/download', 'AdeN\Api\Modules\Customer\InternalCertificateProgram\Http\Controllers\CustomerInternalCertificateProgramController@download');