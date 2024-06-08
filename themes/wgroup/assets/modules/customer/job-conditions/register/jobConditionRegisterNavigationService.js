'use strict';
/**
 * Service for Job Conditions - Navigation in register
 */
app.factory('jobConditionRegisterNavigationService', function () {
    var dataFactory = {};

    // navigation
    var _jobConditionId = null;
    var _evaluationId = null;
    var _employeeTemp = null;
    var _isViewRegisterEdit = false;

    dataFactory.getJobConditionId = function () {
        return _jobConditionId;
    }

    dataFactory.setJobConditionId = function (jobConditionId) {
        _jobConditionId = jobConditionId;
    }

    dataFactory.getEvaluationId = function () {
        return _evaluationId;
    }

    dataFactory.setEvaluationId = function (evaluationId) {
        _evaluationId = evaluationId;
    }

    dataFactory.getEmployeeTemp = function () {
        return _employeeTemp;
    }

    dataFactory.setEmployeeTemp = function (employeeTemp) {
        _employeeTemp = employeeTemp;
    }

    dataFactory.isViewRegisterEdit = function () {
        return _isViewRegisterEdit;
    }

    dataFactory.setViewRegisterEdit = function (isView) {
        _isViewRegisterEdit = isView;
    }

    return dataFactory;
});
