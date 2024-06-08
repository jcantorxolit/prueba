'use strict';

app
    .factory('ChartService', ['$http', function($http) {

        var urlBase = 'api/chart';
        var dataFactory = {};

        dataFactory.getDataChart = function (entities) {

            var data = JSON.stringify(entities);
            var req = { data: Base64.encode(data) };

            return $http({
                method: 'POST',
                url: urlBase,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            });
        };

        return dataFactory;
    }]);