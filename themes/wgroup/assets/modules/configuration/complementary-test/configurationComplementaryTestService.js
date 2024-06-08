'use strict';

app
    .factory('ConfigurationComplementaryTestService', ['$http', function($http) {

        var dataFactory = {};

        dataFactory.get = function(id) {
            var req = { id: id };

            return $http({
                method: 'GET',
                url: 'api/system-parameter/get',
                params: req
            })
        }

        return dataFactory;
    }]);