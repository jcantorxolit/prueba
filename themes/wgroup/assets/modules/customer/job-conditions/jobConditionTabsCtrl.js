'use strict';
/**
 * controller for Customers - Job Conditions
 */
app.controller('customerJobConditionsRegisterTabsCtrl', ['$scope', '$rootScope', '$timeout', '$filter',
    function ($scope, $rootScope,  $timeout,  $filter) {

        $scope.loading = false;
        $scope.tabname = "tab1";
        $scope.activeTab = 1;
        $scope.loadedOther = ["tab1"];

        $scope.getView = function (viewName) {
            var views = $filter('filter')($scope.views, { name: viewName });
            return views[0];
        };

        $scope.switchTab = function (tab) {
            $timeout(function () {
                $scope.tabname = tab;
                $scope.loadedOther.push(tab);
            });
        };

        $scope.switchSubTab = function (subtab) {
            $timeout(function () {
                $scope.subtab = subtab;
            });
        };

    }
]);

