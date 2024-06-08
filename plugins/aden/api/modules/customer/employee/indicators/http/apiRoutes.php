<?php

Route::post('customer-employee-indicators/consolidateStatusEmployees', 'AdeN\Api\Modules\Customer\Employee\Indicators\Http\Controllers\CustomerEmployeeIndicatorController@consolidateStatusEmployees');
Route::post('customer-employee-indicators/consolidateDemographic', 'AdeN\Api\Modules\Customer\Employee\Indicators\Http\Controllers\CustomerEmployeeIndicatorController@consolidateDemographic');
Route::post('customer-employee-indicators/consolidateSupportDocuments', 'AdeN\Api\Modules\Customer\Employee\Indicators\Http\Controllers\CustomerEmployeeIndicatorController@consolidateSupportDocuments');
