'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidIndicatorTabsCtrl', ['$scope', '$rootScope', '$timeout', '$filter',
    function ($scope, $rootScope,  $timeout,  $filter) {

        $scope.views =[
            { name: 'indicatorEmployee', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/covid-19/indicator/employee/customer_covid_indicator_employee.htm' },
            { name: 'indicatorExternal', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/covid-19/indicator/external/customer_covid_indicator_external.htm' },
        ];

        $scope.loading = false;
        $scope.tabname = "indicatorEmployee";
        $scope.loadedExternal = false;

        $scope.getView = function (viewName) {
            var views = $filter('filter')($scope.views, { name: viewName });
            return views[0];
        };

        $scope.switchTab = function (tab) {
            $timeout(function () {
                $scope.tabname = tab;
                if (tab=="indicatorExternal") $scope.loadedExternal = true;
            });
        };

        $scope.switchSubTab = function (subtab) {
            $timeout(function () {
                $scope.subtab = subtab;
            });
        };

    }
]);

