'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticEconomicGroupCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ListService) {


        var log = $log;

        $scope.isAgent = $rootScope.isAgent();
        $scope.isAdmin = $rootScope.isAdmin();
        $scope.isCustomer = $rootScope.isCustomer();

        var customerId = $scope.isCustomer ? $rootScope.currentUser().company : null;

        $scope.filter = {
            economicGroup: null,            
            customer: null,          
            year: null,          
        };

        $scope.economicGroupList = [];
        $scope.customerList = [];
        $scope.yearList = [];

        $scope.indicators = null;
                
        getList();

        function getList() {

            var $criteria = {
                customerId: customerId
            }

            var entities = [
                { name: 'customer_has_economic_group', value: null, criteria: $criteria },                
                { name: 'dashboard_year', value: null},                
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.economicGroupList = response.data.data.customerHasEconomicGroupList;                    
                    $scope.yearList = response.data.data.yearList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getCustomerListOnDemand() {

            var $criteria = {
                parentId: $scope.filter.economicGroup.value
            }

            var entities = [
                { name: 'customer_economic_group', value: null, criteria: $criteria },                
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.customerList = response.data.data.customerEconomicGroupList;                    
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getEconomicGroupDashboard() {

            var req = {
                parent_id: $scope.filter.economicGroup ? $scope.filter.economicGroup.value : 0,
                year: $scope.filter.year ? $scope.filter.year.value : 0
            };
            
            return $http({
                method: 'POST',
                url: 'api/diagnostic/report-economic-group-indicator',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.indicators = response.data.result;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error del servidor", "Ha ocurrido un error cargando los datos!", "error");
            }).finally(function () {

            });

        };
        
        function getCustomerDashboard() {

            var req = {
                parent_id: $scope.filter.economicGroup ? $scope.filter.economicGroup.value : 0,
                customer_id: $scope.filter.customer ? $scope.filter.customer.value : 0,
                year: $scope.filter.year ? $scope.filter.year.value : 0
            };

            return $http({
                method: 'POST',
                url: 'api/diagnostic/report-economic-group-customer-indicator',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.indicators = response.data.result;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error del servidor", "Ha ocurrido un error cargando los datos!", "error");
            }).finally(function () {

            });

        };        

        //-----------------------------------------------------------EVENTS
        $scope.onSelectEconomicGroup = function () {
            if ($scope.filter.economicGroup) {
                $scope.filter.customer = null;
                getCustomerListOnDemand();
                getEconomicGroupDashboard();
            }
        };

        $scope.onClearEconomicGroup = function () {
            $scope.filter.economicGroup = null;
            $scope.filter.customer = null;
            $scope.filter.year = null;
            $scope.customerList = []
            $scope.indicators = null
        }

        $scope.onSelectCustomer = function () {
            getCustomerDashboard()
        };

        $scope.onClearCustomer = function () {
            $scope.filter.customer = null;
            if ($scope.filter.economicGroup != null) {
                getEconomicGroupDashboard();
            }
        }

        $scope.onSelectYear = function () {
            if ($scope.filter.customer != null) {
                getCustomerDashboard();
            } else {                
                getEconomicGroupDashboard();
            }
        };

        $scope.onClearYear = function () {
            $scope.filter.year = null;
            if ($scope.filter.customer != null) {
                getCustomerDashboard();
            } else {                
                getEconomicGroupDashboard();
            }
        }
    }
]);