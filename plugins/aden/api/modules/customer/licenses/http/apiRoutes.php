<?php

/**
 *Module: Customer - Licenses
 */
Route::post('customer-licenses', 'AdeN\Api\Modules\Customer\Licenses\Http\Controllers\LicenseController@index');
Route::post('customer-licenses/show', 'AdeN\Api\Modules\Customer\Licenses\Http\Controllers\LicenseController@show');
Route::post('customer-licenses/save', 'AdeN\Api\Modules\Customer\Licenses\Http\Controllers\LicenseController@store');

Route::post('customer-licenses/current-license', 'AdeN\Api\Modules\Customer\Licenses\Http\Controllers\LicenseController@currentLicense');
Route::post('customer-licenses/current-license/close-expire', 'AdeN\Api\Modules\Customer\Licenses\Http\Controllers\LicenseController@validateLicense');
Route::post('customer-licenses/finish', 'AdeN\Api\Modules\Customer\Licenses\Http\Controllers\LicenseController@finish');

Route::post('customer-licenses/logs', 'AdeN\Api\Modules\Customer\Licenses\Http\Controllers\LicenseController@logs');
