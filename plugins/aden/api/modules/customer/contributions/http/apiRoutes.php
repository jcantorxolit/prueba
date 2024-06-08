<?php

/**
 *Module: Customer Contributions
 */
Route::post('customer-contributions/get-general-balanace', 'AdeN\Api\Modules\Customer\Contributions\Http\Controllers\ContributionController@getGeneralBalance');
Route::post('customer-contributions/get-detail-balanace', 'AdeN\Api\Modules\Customer\Contributions\Http\Controllers\ContributionController@getDetailBalance');
Route::match(['get', 'post'], 'customer-contributions/generate-report-pdf', 'AdeN\Api\Modules\Customer\Contributions\Http\Controllers\ContributionController@generateReportPdf');
