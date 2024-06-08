'use strict';
/**
 * controller for Customers
 */
app.factory('ExpressDashboardService', function () {              
        var dataFactory = {};

        var _workplace = null;
        var _hazard = null;
        var _question = null;
        var _view = null;
        var _isBack = null;
        var _hazardList = null;
        var _tabIndex = null;

        dataFactory.setWorkplace = function (workplace) {
            _workplace = workplace;
        };

        dataFactory.setHazard = function (hazard) {
            _hazard = hazard;
        };

        dataFactory.setQuestion = function (question) {
            _question = question;
        };

        dataFactory.setView = function (view) {
            _view = view;
        };

        dataFactory.setIsBack = function (isBack) {
            _isBack = isBack;
        };

        dataFactory.setHazardList = function (hazardList) {
            _hazardList = hazardList;
        };

        dataFactory.setTabIndex = function (tabIndex) {
            _tabIndex = tabIndex;
        };

        dataFactory.getWorkplace = function () {
            return _workplace;
        };

        dataFactory.getHazard = function () {
            return _hazard;
        };

        dataFactory.getQuestion = function () {
            return _question;
        };

        dataFactory.getView = function () {
            return _view;
        };

        dataFactory.getIsBack = function () {
            return _isBack;
        };

        dataFactory.getHazardList = function () {
            return _hazardList;
        };

        dataFactory.getTabIndex = function () {
            return _tabIndex;
        };

        return dataFactory;
    }
);