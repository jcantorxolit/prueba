'use strict';
/**
 * controller for Customers
 */
app.controller('tabsCtrl',
    function ($scope, $rootScope,  $stateParams, $timeout,  $filter) {

        $scope.loading = false;
        $scope.activeTab = 1;
        $scope.loadedOther = [1];

        $scope.getView = function (viewName) {
            var views = $filter('filter')($scope.views, { name: viewName });
            return views[0];
        };

        $scope.switchTab = function (tab) {
            $timeout(function () {
                $scope.activeTab = tab;
                $scope.loadedOther.push(tab);
            });
        };

    }
);

