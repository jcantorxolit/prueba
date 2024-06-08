'use strict';
/**
 * controller for Customers
 */
app.controller('customerManagementTabsCtrl', ['$scope', '$rootScope', '$timeout', '$filter',
    function ( $scope, $rootScope, $timeout, $filter ) {
        
        $scope.views =
            [
                { name: 'basic', url: $rootScope.app.views.urlRoot + 'modules/customer/management/customer_management.htm'},
                { name: 'indicator', url: $rootScope.app.views.urlRoot + 'modules/customer/management/indicators/customer_management_indicators.htm'},
            ];

        $scope.loading = false;        
        $scope.tabname = "basic";
     
        $scope.getView = function(viewName) {
            var views = $filter('filter')($scope.views , {name: viewName});
            return views[0];
        };

        $scope.switchTab = function (tab) {
            $timeout(function () {
                $scope.tabname = tab;
            });
        };

        $scope.switchSubTab = function (subtab) {
            $timeout(function () {
                $scope.subtab = subtab;
            });
        };                

    }
]);

