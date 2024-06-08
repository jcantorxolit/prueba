<?php

Route::get('config/signature-certificate-vr/get', 'AdeN\Api\Modules\Config\SignatureCertificateVr\Http\Controllers\SignatureCertificateVrController@show');
Route::post('config/signature-certificate-vr/save', 'AdeN\Api\Modules\Config\SignatureCertificateVr\Http\Controllers\SignatureCertificateVrController@store');
Route::post('config/signature-certificate-vr/signature-upload', 'AdeN\Api\Modules\Config\SignatureCertificateVr\Http\Controllers\SignatureCertificateVrController@upload');
Route::post('config/signature-certificate-vr/logo-upload', 'AdeN\Api\Modules\Config\SignatureCertificateVr\Http\Controllers\SignatureCertificateVrController@uploadLogo');
