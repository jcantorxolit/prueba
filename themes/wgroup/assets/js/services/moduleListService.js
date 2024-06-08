'use strict';

app.factory('ModuleListService', function($http) {
    var dataFactory = {};
    dataFactory.getDataList = function (endPoint, entities) {

        var data = JSON.stringify(entities);
        var req = { data: Base64.encode(data) };

        return $http({
            method: 'POST',
            url: 'api' + endPoint,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        });
    };

    return dataFactory;
});