'use strict';
/**
 * controller for Dashboard Top Management
 */
app.controller('dashboardTopManagementSummaryCtrl', function ($scope, $rootScope, $stateParams, $log, $http, $timeout,
                                                              calendarConfig, SweetAlert, $filter) {

    var date = new Date();
    var d = date.getDate();
    var m = date.getMonth();
    var y = date.getFullYear();

    $scope.IsEdited = false;


    $scope.events = [];

    $scope.calendarView = 'month';
    $scope.calendarDay = new Date();
    $scope.calendarDate = new Date();
    $scope.calendarTitle = null;

    $scope.currentFilteredYear = new Date().getFullYear();

    $scope.typeLists = $rootScope.parameters("project_type");

    load();

    $scope.onChangeTypeFilter = function () {
        load();
    };


    $scope.onChangeFilter = function () {
        if ($scope.calendarDate.getFullYear() != $scope.currentFilteredYear) {
            $scope.currentFilteredYear = $scope.calendarDate.getFullYear();
            load();
        }
    };


    function load() {
        var types = getTypes();

        var req = {
            year: $scope.calendarDate.getFullYear(),
            types: types
        }

        return $http({
            method: 'POST',
            url: 'api/dashboard/top-management/summary/calendar',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                response.data.data.map(function (entry) {
                    entry.startsAt = new Date(entry.startsAt);
                });

                $scope.events = response.data.data;
            });

        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error al cargar la información", "Error al cargar la información", "error");
        });
    }


    function getTypes() {
        var filtered = $filter('filter')($scope.typeLists, { isActive: true });

        var data = [];
        filtered.forEach(function (type) {
            data.push(type.value);
        });

        return data;
    }

});
