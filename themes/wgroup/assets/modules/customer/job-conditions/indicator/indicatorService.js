'use strict';
/**
 * controller for Customer Job Conditions IndicatorService
 */
app.factory('CustomerJobConditionsIndicatorService', function ($stateParams, ListService) {
    var dataFactory = {};

    var _filters = null;
    var _view = null;

    var _evaluationId = null;
    var _classificationId = null;
    var _question = null;
    var _currentQuestion = null;

    var _customerAgentUser = [];

    dataFactory.setFilters = function (filters) {
        _filters = filters;
    };

    dataFactory.getFilters = function () {
        return _filters;
    };

    dataFactory.setEvaluationId = function (evaluationId) {
        _evaluationId = evaluationId;
    };

    dataFactory.getEvaluationId = function () {
        return _evaluationId;
    };

    dataFactory.setClassificationId = function (classificationId) {
        _classificationId = classificationId;
    };

    dataFactory.getClassificationId = function () {
        return _classificationId;
    };

    dataFactory.setQuestions = function (questions) {
        _question = questions;
    }

    dataFactory.getQuestions = function () {
        return _question;
    }

    dataFactory.setCurrentQuestion = function (question) {
        _currentQuestion = question;
    }

    dataFactory.getCurrentQuestion = function () {
        return _currentQuestion;
    }

    dataFactory.setView = function (view) {
        _view = view;
    }

    dataFactory.getView = function () {
        return _view;
    }

    dataFactory.getCustomerAgentUser = function () {
        if (_customerAgentUser.length > 0) {
            return _customerAgentUser;
        }

        var entities = [
            {  name: 'customer_related_agent_user',  value: $stateParams.customerId },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                _customerAgentUser = response.data.data.customerRelatedAgentAndUserList;
                return _customerAgentUser;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    return dataFactory;
});
