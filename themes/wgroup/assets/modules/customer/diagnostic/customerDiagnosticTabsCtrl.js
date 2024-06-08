'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticTabsCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout', '$state', '$filter', 'flowFactory','$http',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $state, $filter, flowFactory, $http) {

        $scope.views =
            [
                { name: 'tab_minimum_standard', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-minimum-standard/customer_evaluation_minimum_standard.htm'},
                { name: 'tab_minimum_standard_0312', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-minimum-standard-0312/customer_evaluation_minimum_standard.htm'},
                { name: 'tab_road_safety', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-road-safety/customer_road_safety.htm'},
                { name: 'tab_matrix', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-matrix/customer_diagnostic_risk_matrix.htm'},
                { name: 'tab_express_matrix', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-express-matrix/customer_diagnostic_express_matrix.htm'},
                { name: 'tab_sg_sst', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/sg-sst/customer_diagnostic.htm'},
                { name: 'tab_observation', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/observation/customer_diagnostic_observation_list.htm'},
            ];

        $scope.section = $scope.views[0];

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.tabActive = $rootScope.can('estandares_minimos_0312_open') ? 0 : 1;

        $scope.tabname = "tab_minimum_standard";

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
