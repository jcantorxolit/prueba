<?php

Route::post('customer-vr-employee/satisfaction-indicator', 'AdeN\Api\Modules\Customer\VrEmployee\Satisfactionindicators\Http\Controllers\SatisfactionIndicatorController@index');
Route::post('customer-vr-employee/satisfaction-indicator/valuation', 'AdeN\Api\Modules\Customer\VrEmployee\Satisfactionindicators\Http\Controllers\SatisfactionIndicatorController@valuationList');
Route::get('customer-vr-employee/satisfaction-indicator/export-template', 'AdeN\Api\Modules\Customer\VrEmployee\Satisfactionindicators\Http\Controllers\SatisfactionIndicatorController@downloadTemplate');

