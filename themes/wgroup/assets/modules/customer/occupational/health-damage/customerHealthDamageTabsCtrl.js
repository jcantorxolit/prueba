'use strict';
/**
 * controller for Customers
 */
app.controller('customerHealthDamageTabsCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar',
    '$document', '$filter','$aside', 'FileUploader',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $document, $filter, $aside, FileUploader ) {

        var log = $log;
        $scope.views =
            [
                { name: 'disability', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/disability/customer_health_damage_disability.htm'},
                { name: 'diagnostic', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/diagnostic/customer_health_damage_diagnostic_source.htm'},
                { name: 'restriction', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/restriction/customer_health_damage_restriction.htm'},
                { name: 'qualification', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/qualification/customer_health_damage_qualification_source.htm'},
                { name: 'qualification_lost', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/qualification-lost/customer_health_damage_qualification_lost.htm'},
                { name: 'administrative', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/administrative/customer_health_damage_administrative_process.htm'},
                { name: 'observation', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/observation/customer_health_damage_observation.htm'},
                { name: 'analysis', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/analysis/customer_health_damage_analysis.htm'},
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

