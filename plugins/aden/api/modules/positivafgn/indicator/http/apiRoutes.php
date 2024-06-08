<?php

/**
 *Module: Indicator
 */

Route::post('positiva-fgn-fgn-indicator', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@index');
Route::post('positiva-fgn-fgn-indicator/consolidated', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@consolidated');
Route::post('positiva-fgn-fgn-indicator/indicators', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@indicators');

// PTA
Route::post('positiva-fgn-fgn-indicator/indicators/reports/activities-pta-compliance', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesPTACompliance');
Route::post('positiva-fgn-fgn-indicator/indicators/reports/activities-pta-compliance-details', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesPTAComplianceDetails');
Route::post('positiva-fgn-fgn-indicator/indicators/reports/activities-pta-compliance-axis', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesPTAComplianceAxis');
Route::get('positiva-fgn-fgn-indicator/indicators/reports/activities-pta-compliance-export', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesPTAComplianceExport');

/* Indicador Actividades Fallidas */
Route::post('positiva-fgn-fgn-indicator/indicators/reports/activities-failed-compliance', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@getActivitiesFailedCompliance');
Route::get('positiva-fgn-fgn-indicator/indicators/reports/activities-failed-compliance-export', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesFailedComplianceExport');

// Indicador Actividades por estrategia
Route::post('positiva-fgn-fgn-indicator/indicators/reports/activities-strategies', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesStrategyCompliance');
Route::get('positiva-fgn-fgn-indicator/indicators/reports/activities-strategies-export', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesByStrategyExport');

// Indicador Actividades por asesor
Route::post('positiva-fgn-fgn-indicator/indicators/reports/activities-consultant-compliance', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@getActivitiesConsultantCompliance');
Route::get('positiva-fgn-fgn-indicator/indicators/reports/activities-consultant-compliance-export', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesConsultantComplianceExport');

// Indicador Consolidado
Route::post('positiva-fgn-fgn-indicator/indicators/reports/activities-consolidated-compliance', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesConsolidatedCompliance');
Route::get('positiva-fgn-fgn-indicator/indicators/reports/activities-consolidated-compliance-export', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesConsolidatedExport');

// Indicadores consolidado cumplimiento por eje
Route::get('positiva-fgn-fgn-indicator/indicators/reports/activities-failed-compliance-axis-export', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesConsolidatedExport');

// Indicadores consolidado cobertura por eje
Route::get('positiva-fgn-fgn-indicator/indicators/reports/activities-failed-coverage-axis-export', 'AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers\IndicatorController@activitiesConsolidatedExport');
