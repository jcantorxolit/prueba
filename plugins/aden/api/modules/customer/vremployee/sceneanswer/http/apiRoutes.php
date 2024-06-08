<?php
    

    Route::post('customer-vr-employee-scene-answer/summary', 'AdeN\Api\Modules\Customer\VrEmployee\SceneAnswer\Http\Controllers\SceneAnswerController@getSummary');
    Route::post('customer-vr-employee-scene-answer/summary-all', 'AdeN\Api\Modules\Customer\VrEmployee\SceneAnswer\Http\Controllers\SceneAnswerController@getAllSummary');

    Route::post('customer-vr-employee-scene-answer/grid/{experience}', 'AdeN\Api\Modules\Customer\VrEmployee\SceneAnswer\Http\Controllers\SceneAnswerController@getExperienceByEmployee');