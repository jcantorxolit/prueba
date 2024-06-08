'use strict';
/**
 * controller for Customers
 */
app.factory('CustomerEmployeeService',  ['$http', function($http) {
        var urlBase = 'api/customer-employee/save-auth';
        var dataFactory = {};
        
        var _isAuthorized = null;
        var _lastAuthorized = null;
        var _canAuthorize = null;

        dataFactory.setCanAuthorize = function (canAuthorize) {
            _canAuthorize = canAuthorize            
        };

        dataFactory.getCanAuthorize = function () {
            return _isAuthorized;
        };

        dataFactory.setAuthorization = function (isAuthorized) {
            _isAuthorized = isAuthorized            
        };

        dataFactory.getAuthorization = function () {
            return _isAuthorized;
        };

        dataFactory.setLastAuthorization = function (isAuthorized) {
            _lastAuthorized = isAuthorized            
        };

        dataFactory.getLastAuthorization = function () {
            return _lastAuthorized;
        };

        dataFactory.saveAuth = function (entity) {

            var data = JSON.stringify(entity);
            var req = { data: Base64.encode(data) };

            return $http({
                method: 'POST',
                url: urlBase,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            });
        };
       
        return dataFactory;
    }]
);
