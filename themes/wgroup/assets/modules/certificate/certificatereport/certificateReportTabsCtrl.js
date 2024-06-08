'use strict';
/**
 * controller for Customers
 */
app.controller('certificateReportTabsCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout', '$state', '$filter', 'flowFactory','$http',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $state, $filter, flowFactory, $http) {


        //Variables globales en el tab
        if ($state.is("app.certificate.report.view")) {
            $scope.certificate_report_title_tab = "view";
        } else if ($state.is("app.certificate.report.create")) {
            $scope.certificate_report_title_tab = "create";
        } else {
            $scope.certificate_report_title_tab = "edit";
        }

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";


        $scope.tabsloaded = ["certificate"];
        $scope.tabname = "certificate";
        $scope.titletab = $scope.certificate_report_title_tab;

        $scope.switchTab = function (tab, titletab) {
            $timeout(function () {
                $scope.tabname = tab;
                $scope.titletab = titletab;
                $scope.tabsloaded.push(tab);
            });
        };

        $scope.switchSubTab = function (subtab) {
            $timeout(function () {
                $scope.subtab = subtab;
            });
        };

    }]);