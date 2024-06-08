'use strict';
/**
 * controller for Dashboard Filter
 */
app.factory('DashboardFilterService', function() {
    var dataFactory = {};

    var _firstCustomer = null;
    var _secondCustomer = null;

    dataFactory.setFirstCustomer = function(customer) {
        _firstCustomer = customer;
    };

    dataFactory.getFirstCustomer = function() {
        return _firstCustomer;
    };

    dataFactory.setSecondCustomer = function(customer) {
        _secondCustomer = customer;
    };

    dataFactory.getSecondCustomer = function() {
        return _secondCustomer;
    };

    dataFactory.getCurrentCustomer = function() {
        if (!_firstCustomer) {
            return null;
        }

        if (!_secondCustomer) {
            return _firstCustomer;
        }

        return _secondCustomer;
    }

    return dataFactory;
});