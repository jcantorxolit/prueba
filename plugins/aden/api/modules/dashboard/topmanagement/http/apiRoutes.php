<?php

Route::post('dashboard/top-management/consolidate', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@consolidate');
Route::post('dashboard/top-management/performance-level', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@index');

Route::post('dashboard/top-management/summary/calendar', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@calendar');


Route::post('dashboard/top-management/sales-historical', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getHistoricalCosts');

Route::post('dashboard/top-management/get-customers', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getCustomers');
Route::post('dashboard/top-management/get-administrators', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getAdministrators');


// grids
Route::post('dashboard/top-management/total-sales', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getTotalSales');
Route::post('dashboard/top-management/sales-by-type', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getSalesByType');
Route::post('dashboard/top-management/sales-by-concept', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getSalesByConcept');
Route::post('dashboard/top-management/sales-by-classification', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getSalesByClassification');

Route::post('dashboard/top-management/experiencies-by-months', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getExperienciesByMonths');
Route::post('dashboard/top-management/amount-by-satistaction-level', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@amountBySatisfactionGrid');
Route::post('dashboard/top-management/registered-vs-participants', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getRegisteredVsParticipants');
Route::post('dashboard/top-management/performance-by-consultant', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getPerformanceByConsultant');

Route::post('dashboard/top-management/programmed-vs-executed-sales', 'AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers\TopManagementController@getProgrammedVsExecutedSales');