'use strict';
/**
 * controller for Customers
 */
app.controller('fishBoneCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout', '$state', '$filter', '$http', '$location',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $state, $filter, $http, $location) {

        var parameters = $location.path().split('/');

        var id = parameters[parameters.length - 2];

        $log.info(id);

        $scope.data = null;

        $scope.investigation = {
            id: id
        };

        $scope.onLoadRecord = function () {
            if ($scope.investigation.id != 0) {

                var req = {
                    id: $scope.investigation.id
                };

                $http({
                    method: 'GET',
                    url: '../../api/customer/investigation-al/factor/fish-bone',
                    params: req
                })
                    .catch(function (e, code) {
                    })
                    .then(function (response) {
                        $scope.data = response.data.result;
                    }).finally(function () {
                    });
            }
        }


        $scope.onLoadRecord();

    }]);