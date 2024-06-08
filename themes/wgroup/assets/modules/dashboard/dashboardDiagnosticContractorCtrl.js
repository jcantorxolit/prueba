'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticContractorCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ListService) {


        var log = $log;

        $scope.isAgent = $rootScope.isAgent();
        $scope.isAdmin = $rootScope.isAdmin();
        $scope.isCustomer = $rootScope.isCustomer();

        var customerId = $scope.isCustomer ? $rootScope.currentUser().company : null;

        $scope.filter = {
            customer: null,          
            contractor: null,            
            year: null,          
        };

        $scope.customerList = [];
        $scope.contractorList = [];
        $scope.yearList = [];

        getList();

        function getList() {

            var $criteria = {
                customerId: customerId
            }

            var entities = [
                { name: 'customer_employeer', value: null, criteria: $criteria },                
                { name: 'dashboard_year', value: null},                
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.customerList = response.data.data.customerEmployeerList;                    
                    $scope.yearList = response.data.data.yearList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getCustomerContractorListOnDemand() {

            var $criteria = {
                parentId: $scope.filter.customer.value
            }

            var entities = [
                { name: 'customer_contractor', value: null, criteria: $criteria },                
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.contractorList = response.data.data.customerContractorList;                    
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getEmployeerDashboard() {

            var req = {
                parent_id: $scope.filter.customer ? $scope.filter.customer.value : 0,
                year: $scope.filter.year ? $scope.filter.year.value : 0
            };

            return $http({
                method: 'POST',
                url: 'api/diagnostic/report-contracting-indicator',
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

        function getContractorDashboard() {

            var req = {
                parent_id: $scope.filter.customer ? $scope.filter.customer.value : 0,
                customer_id: $scope.filter.contractor ? $scope.filter.contractor.value : 0,
                year: $scope.filter.year ? $scope.filter.year.value : 0
            };

            return $http({
                method: 'POST',
                url: 'api/diagnostic/report-contracting-customer-indicator',
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
        $scope.onSelectCustomer = function () {
            if ($scope.filter.customer) {
                $scope.filter.contractor = null;
                getCustomerContractorListOnDemand();
                getEmployeerDashboard();
            }
        };

        $scope.onClearCustomer = function () {
            $scope.filter.customer = null;
            $scope.filter.contractor = null;
            $scope.filter.year = null;
            $scope.contractorList = []
            $scope.indicators = null
        }

        $scope.onSelectContractor = function () {
            getContractorDashboard()
        };

        $scope.onClearContractor = function () {
            $scope.filter.contractor = null;
            if ($scope.filter.customer != null) {
                getEmployeerDashboard();
            }
        }

        $scope.onSelectYear = function () {
            if ($scope.filter.contractor != null) {
                getContractorDashboard();
            } else {                
                getEmployeerDashboard();
            }
        };

        $scope.onClearYear = function () {
            $scope.filter.year = null;
            if ($scope.filter.contractor != null) {
                getContractorDashboard();
            } else {                
                getEmployeerDashboard();
            }
        }

    }
]);