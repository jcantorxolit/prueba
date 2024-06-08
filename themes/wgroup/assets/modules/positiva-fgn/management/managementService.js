'use strict';
/**
 * controller for Customers
 */
app.factory('PFManagementService', function () {              
        var dataFactory = {};

        var _infoBasic = null;
        var _action = null;
       
        dataFactory.setInfoBasic = function (info) {
            _infoBasic = info;
        };

        dataFactory.getInfoBasic = function () {
            return _infoBasic;
        };
       
        dataFactory.setAction = function (action) {
            _action = action;
        };

        dataFactory.getAction = function () {
            return _action;
        };

        return dataFactory;
    }
);