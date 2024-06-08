'use strict';
/**
 * controller for Customers
 */
app.controller('customerAbsenteeismAnalysisTabsCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar',
    '$document', '$filter','$aside', 'FileUploader',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $document, $filter, $aside, FileUploader ) {

        var log = $log;
        $scope.views =
            [
                { name: 'resolution_1111', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/absenteeism/analysis/1111/customer_absenteeism_analysis.htm'},
                { name: 'resolution_0312', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/absenteeism/analysis/0312/customer_absenteeism_analysis.htm'},
            ];

        $scope.loading = false;        
        $scope.tabname = "resolution_1111";
     
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

