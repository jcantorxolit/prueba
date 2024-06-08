<?php
    
	/**
     *Module: CustomerEmployee
     */
    // Route::get('customer-health-damage-administrative-process', 'AdeN\Api\Modules\Customer\HealthDamageAdministrativeProcess\Http\Controllers\CustomerHealthDamageAdministrativeProcessController@show');
    // Route::post('customer-health-damage-administrative-process/save', 'AdeN\Api\Modules\Customer\HealthDamageAdministrativeProcess\Http\Controllers\CustomerHealthDamageAdministrativeProcessController@store');
    // Route::post('customer-health-damage-administrative-process/update', 'AdeN\Api\Modules\Customer\HealthDamageAdministrativeProcess\Http\Controllers\CustomerHealthDamageAdministrativeProcessController@update');
    // Route::post('customer-health-damage-administrative-process/delete', 'AdeN\Api\Modules\Customer\HealthDamageAdministrativeProcess\Http\Controllers\CustomerHealthDamageAdministrativeProcessController@destroy');
    // Route::post('customer-health-damage-administrative-process/import', 'AdeN\Api\Modules\Customer\HealthDamageAdministrativeProcess\Http\Controllers\CustomerHealthDamageAdministrativeProcessController@import');
    // Route::post('customer-health-damage-administrative-process/upload', 'AdeN\Api\Modules\Customer\HealthDamageAdministrativeProcess\Http\Controllers\CustomerHealthDamageAdministrativeProcessController@upload');
    // Route::match(['post'], 'customer-health-damage-administrative-process', 'AdeN\Api\Modules\Customer\HealthDamageAdministrativeProcess\Http\Controllers\CustomerHealthDamageAdministrativeProcessController@index');
    // Route::match(['get', 'post'], 'customer-health-damage-administrative-process/download', 'AdeN\Api\Modules\Customer\HealthDamageAdministrativeProcess\Http\Controllers\CustomerHealthDamageAdministrativeProcessController@download');
    
    Route::match(['post'], 'customer-health-damage-administrative-process', 'AdeN\Api\Modules\Customer\HealthDamageAdministrativeProcess\Http\Controllers\CustomerHealthDamageAdministrativeProcessController@indexHealthDamageAdministrativeProcess');
