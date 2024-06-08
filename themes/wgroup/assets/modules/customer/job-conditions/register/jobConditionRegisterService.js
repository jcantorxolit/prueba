'use strict';
/**
 * Service for Job Conditions
 */
app.factory('jobConditionRegisterService', function () {
    var dataFactory = {};

    var _currentClassification = null;
    var _classifications = [];

    dataFactory.getCurrentClassification = function () {
        return _currentClassification;
    }

    dataFactory.setCurrentClassification = function (classification) {
        _currentClassification = classification;
    }

    dataFactory.setClassifications = function (classifications) {
        _classifications = classifications;
    }

    dataFactory.getNextClassification = function () {
        var index = _currentClassification.index;
        _currentClassification = _classifications[index + 1];
        return _currentClassification;
    }

    dataFactory.getBeforeClassification = function () {
        var index = _currentClassification.index;
        _currentClassification = _classifications[index - 1];
        return _currentClassification;
    }

    dataFactory.isLastClassification = function () {
        return _currentClassification.index == _classifications.length - 1;
    }


    return dataFactory;
});
