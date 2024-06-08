<?php

Route::post('dashboard/commercial/consolidate', 'AdeN\Api\Modules\Dashboard\Commercial\Http\Controllers\CommercialDashboardController@consolidate');
Route::post('dashboard/commercial/next-expired', 'AdeN\Api\Modules\Dashboard\Commercial\Http\Controllers\CommercialDashboardController@index');
