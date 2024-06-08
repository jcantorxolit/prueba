'use strict';
/**
 * controller for Customers
 */
app.factory('CustomerCovidService', function () {              
        var dataFactory = {};

        var _id = null;
        var _dailyId = null;
       
        dataFactory.setId = function (id) {
            _id = id;
        };

        dataFactory.getId = function () {
            return _id;
        };
       
        dataFactory.setDailyId = function (id) {
            _dailyId = id;
        };

        dataFactory.getDailyId = function () {
            return _dailyId;
        };
       

        return dataFactory;
    }
);