<?php
    
	/**
     *Module: CustomerEmployee
     */
    Route::get('customer-config-activity-hazard', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@show');
    Route::post('customer-config-activity-hazard/save', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@store');
    Route::post('customer-config-activity-hazard/update', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@update');
    Route::post('customer-config-activity-hazard/delete', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@destroy');
    Route::post('customer-config-activity-hazard/import', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@import');
    Route::post('customer-config-activity-hazard/upload', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@upload');
    Route::match(['post'], 'customer-config-activity-hazard', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@index');
    Route::match(['post'], 'customer-config-activity-hazard-intervention', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@indexIntervention');
    Route::match(['post'], 'customer-config-activity-hazard-historical', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@indexHistorical');
    Route::match(['post'], 'customer-config-activity-hazard-historical-reason', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@indexHistoricalReason');
    Route::match(['post'], 'customer-config-activity-hazard-characterization', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@indexCharacterization');
    Route::match(['post'], 'customer-config-activity-hazard-characterization-detail', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@indexCharacterizationDetail');
    Route::match(['post'], 'customer-config-activity-hazard-priorization', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@indexPriorization');
    Route::match(['get', 'post'], 'customer-config-activity-hazard/download', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@download');
    Route::match(['get'], 'customer-config-activity-hazard/export', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@export');
    Route::match(['get'], 'customer-config-activity-hazard-priorization/export', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@exportPriorization');
    Route::match(['get'], 'customer-config-activity-hazard-historical/export', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@exportHistorical');
    Route::match(['get'], 'customer-config-activity-hazard-characterization/export', 'AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers\CustomerConfigActivityHazardController@exportCharacterization');