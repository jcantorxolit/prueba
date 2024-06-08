'use strict';
/**
 * controller for Customers
 */
app.controller('customerAbsenteeismTabsCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar',
    '$document', '$filter','$aside', 'FileUploader',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $document, $filter, $aside, FileUploader ) {

        var log = $log;
        $scope.views =
            [
                { name: 'basic', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability.htm'},
                { name: 'indicator', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/absenteeism/indicators/customer_absenteeism_indicators.htm'},
                { name: 'analysis', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/absenteeism/analysis/customer_absenteeism_analysis_tabs.htm'},
                { name: 'administrator', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/absenteeism/billing/customer_absenteeism_disability_billing.htm'},
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

