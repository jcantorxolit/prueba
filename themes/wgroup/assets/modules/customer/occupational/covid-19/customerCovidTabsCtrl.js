'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidTabsCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar',
    '$document', '$filter','$aside', 'FileUploader',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $document, $filter, $aside, FileUploader ) {

        var log = $log;
        $scope.views =
            [
                { name: 'basic', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/covid-19/customer_covid.htm'},                
                { name: 'indicatorTabs', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/covid-19/indicator/customer_covid_indicator_tabs.htm'},                                
            ];

        $scope.loading = false;        
        $scope.tabname = "basic";
        $scope.loadedIndicator = false;
     
        $scope.getView = function(viewName) {
            var views = $filter('filter')($scope.views , {name: viewName});
            return views[0];
        };

        $scope.switchTab = function (tab) {
            $timeout(function () {
                $scope.tabname = tab;
                if (tab == "indicatorTabs") {
                    $scope.loadedIndicator = true;
                }
            });
        };

        $scope.switchSubTab = function (subtab) {
            $timeout(function () {
                $scope.subtab = subtab;
            });
        };                

    }
]);

