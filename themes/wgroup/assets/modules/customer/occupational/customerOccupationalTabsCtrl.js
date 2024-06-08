'use strict';
/**
 * controller for Customers
 */
app.controller('customerOccupationalTabsCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar',
    '$document', '$filter','$aside', 'FileUploader',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $document, $filter, $aside, FileUploader ) {

        var log = $log;
        $scope.views =
            [
                { name: 'absenteeism', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/absenteeism/customer_absenteeism_tabs.htm'},
                { name: 'health_damage', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/customer_health_damage_tabs.htm'},
                { name: 'occupational_report_al', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/report/customer_occupational_report_al.htm'},
                { name: 'occupational_investigation_al', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/investigation/customer_occupational_investigation_al.htm'},
                { name: 'occupational_report_incident', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/report-incident/customer_occupational_report_incident.htm'},
                { name: 'occupational_covid_19', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/covid-19/customer_covid_tabs.htm'},
                { name: 'occupational_covid_19_bolivar', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/covid-19-bolivar/customer_covid_bolivar_tabs.htm'},
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

