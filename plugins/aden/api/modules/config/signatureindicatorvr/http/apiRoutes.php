<?php

Route::get('config/signature-indicator-vr/get', 'AdeN\Api\Modules\Config\SignatureIndicatorVr\Http\Controllers\SignatureIndicatorVrController@show');
Route::post('config/signature-indicator-vr/save', 'AdeN\Api\Modules\Config\SignatureIndicatorVr\Http\Controllers\SignatureIndicatorVrController@store');
Route::post('config/signature-indicator-vr/signature-upload', 'AdeN\Api\Modules\Config\SignatureIndicatorVr\Http\Controllers\SignatureIndicatorVrController@upload');
