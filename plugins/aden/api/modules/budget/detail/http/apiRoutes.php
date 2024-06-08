<?php
    
	/**
     *Module: BudgetDetail
     */
    Route::get('budget-detail/get', 'AdeN\Api\Modules\Budget\Detail\Http\Controllers\BudgetDetailController@show');
    Route::post('budget-detail/save', 'AdeN\Api\Modules\Budget\Detail\Http\Controllers\BudgetDetailController@store');
    Route::post('budget-detail/delete', 'AdeN\Api\Modules\Budget\Detail\Http\Controllers\BudgetDetailController@destroy');
    Route::post('budget-detail/import', 'AdeN\Api\Modules\Budget\Detail\Http\Controllers\BudgetDetailController@import');
    Route::post('budget-detail/upload', 'AdeN\Api\Modules\Budget\Detail\Http\Controllers\BudgetDetailController@upload');
    Route::match(['get', 'post'], 'budget-detail', 'AdeN\Api\Modules\Budget\Detail\Http\Controllers\BudgetDetailController@index');
	Route::match(['get', 'post'], 'budget-detail/download', 'AdeN\Api\Modules\Budget\Detail\Http\Controllers\BudgetDetailController@download');