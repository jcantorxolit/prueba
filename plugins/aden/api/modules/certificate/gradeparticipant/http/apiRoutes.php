<?php
    
	/**
     *Module: CertificateGradeParticipant
     */
    /*Route::get('certificate-grade-participant/get', 'AdeN\Api\Modules\Certificate\GradeParticipant\Http\Controllers\CertificateGradeParticipantController@show');
    Route::post('certificate-grade-participant/save', 'AdeN\Api\Modules\Certificate\GradeParticipant\Http\Controllers\CertificateGradeParticipantController@store');
    Route::post('certificate-grade-participant/delete', 'AdeN\Api\Modules\Certificate\GradeParticipant\Http\Controllers\CertificateGradeParticipantController@destroy');
    Route::post('certificate-grade-participant/import', 'AdeN\Api\Modules\Certificate\GradeParticipant\Http\Controllers\CertificateGradeParticipantController@import');
    Route::post('certificate-grade-participant/upload', 'AdeN\Api\Modules\Certificate\GradeParticipant\Http\Controllers\CertificateGradeParticipantController@upload');
    Route::match(['get', 'post'], 'certificate-grade-participant', 'AdeN\Api\Modules\Certificate\GradeParticipant\Http\Controllers\CertificateGradeParticipantController@index');*/
    Route::match(['get', 'post'], 'certificate-grade-participant/search-v2', 'AdeN\Api\Modules\Certificate\GradeParticipant\Http\Controllers\CertificateGradeParticipantController@indexSearch');
    Route::match(['get', 'post'], 'certificate-grade-participant/expiration-v2', 'AdeN\Api\Modules\Certificate\GradeParticipant\Http\Controllers\CertificateGradeParticipantController@indexExpiration');
	Route::match(['get', 'post'], 'certificate-grade-participant/download', 'AdeN\Api\Modules\Certificate\GradeParticipant\Http\Controllers\CertificateGradeParticipantController@download');