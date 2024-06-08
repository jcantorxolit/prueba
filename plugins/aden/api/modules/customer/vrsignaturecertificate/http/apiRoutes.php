<?php

Route::get('customer/signature-certificate-vr/get/{customerId}', 'AdeN\Api\Modules\Customer\VrSignatureCertificate\Http\Controllers\SignatureCertificateVrController@show');
Route::post('customer/signature-certificate-vr/save', 'AdeN\Api\Modules\Customer\VrSignatureCertificate\Http\Controllers\SignatureCertificateVrController@store');
Route::post('customer/signature-certificate-vr/signature-upload/{customerId}', 'AdeN\Api\Modules\Customer\VrSignatureCertificate\Http\Controllers\SignatureCertificateVrController@upload');
Route::post('customer/signature-certificate-vr/logo-upload/{customerId}', 'AdeN\Api\Modules\Customer\VrSignatureCertificate\Http\Controllers\SignatureCertificateVrController@uploadLogo');
