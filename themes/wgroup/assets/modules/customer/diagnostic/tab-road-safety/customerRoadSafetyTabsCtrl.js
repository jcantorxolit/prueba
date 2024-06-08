'use strict';
/**
 * controller for Customers
 */
app.controller('customerRoadSafetyTabsCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout', '$state', '$filter', 'flowFactory','$http',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $state, $filter, flowFactory, $http) {

        $scope.views =
            [
                { name: 'tab_road_safety_1565', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-road-safety/1565/customer_road_safety.htm'},
                { name: 'tab_road_safety_40595', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-road-safety/40595/customer_road_safety.htm'},
            ];

        $scope.section = $scope.views[0];

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.tabActive = $rootScope.can('road_safety_40595_open') ? 0 : 1;

        $scope.tabname = "tab_road_safety_40595";

        $scope.matrixType = $scope.customer.matrixType;

        $scope.getView = function(nameView) {
            var views = $filter('filter')($scope.views , {name: nameView});
            return views[0];
        };

        $scope.switchTab = function (tab, titletab) {
            $timeout(function () {
                $scope.tabname = tab;
                $scope.titletab = titletab;
                $scope.tabsloaded.push(tab);
                $scope.section = $scope.getView(tab);
            });
        };

        $scope.switchSubTab = function (subtab) {
            $timeout(function () {
                $scope.subtab = subtab;
            });
        };

    }]);
