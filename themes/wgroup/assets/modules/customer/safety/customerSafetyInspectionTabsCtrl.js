'use strict';
/**
 * controller for Customers
 */
app.controller('customerSafetyInspectionTabsCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout', '$state', '$filter', 'flowFactory','$http',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $state, $filter, flowFactory, $http) {

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.tabname = "program";

        $scope.switchTab = function (tab, titletab) {
            $timeout(function () {
                $scope.tabname = tab;
                $scope.titletab = titletab;
                $scope.tabsloaded.push(tab);
            });
        };

        $scope.switchSubTab = function (subtab) {
            $timeout(function () {
                $scope.subtab = subtab;
            });
        };

    }]);