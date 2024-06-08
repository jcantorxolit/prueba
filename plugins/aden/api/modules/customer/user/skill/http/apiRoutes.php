<?php
    
	/**
     *Module: CustomerUserSkill
     */
    Route::get('customer-user-skill/get', 'AdeN\Api\Modules\Customer\User\Skill\Http\Controllers\CustomerUserSkillController@show');
    Route::post('customer-user-skill/save', 'AdeN\Api\Modules\Customer\User\Skill\Http\Controllers\CustomerUserSkillController@store');
    Route::post('customer-user-skill/delete', 'AdeN\Api\Modules\Customer\User\Skill\Http\Controllers\CustomerUserSkillController@destroy');
    Route::post('customer-user-skill/import', 'AdeN\Api\Modules\Customer\User\Skill\Http\Controllers\CustomerUserSkillController@import');
    Route::post('customer-user-skill/upload', 'AdeN\Api\Modules\Customer\User\Skill\Http\Controllers\CustomerUserSkillController@upload');
    Route::match(['get', 'post'], 'customer-user-skill', 'AdeN\Api\Modules\Customer\User\Skill\Http\Controllers\CustomerUserSkillController@index');
	Route::match(['get', 'post'], 'customer-user-skill/download', 'AdeN\Api\Modules\Customer\User\Skill\Http\Controllers\CustomerUserSkillController@download');