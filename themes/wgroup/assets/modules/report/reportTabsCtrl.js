'use strict';
/**
 * controller for Reports
 */
app.controller('reportTabsCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout', '$state', '$filter', 'flowFactory','$http',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $state, $filter, flowFactory, $http) {

        var log = $log;
        var request = {};

        //Variables globales en el tab
        if ($state.is("app.report.generate")) {
            $scope.report_title_tab = "generate";
        } else if ($state.is("app.report.create")) {
            $scope.report_title_tab = "create";
        } else if ($state.is("app.report.dynamically")) {
            $scope.report_title_tab = "dynamically";
        } else {
            $scope.report_title_tab = "edit";
        }

        var $reportId = $rootScope.currentUser().company;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.report = {};
        $scope.report.id = $scope.iscreate ? 0 : $stateParams.reportId;

        $scope.tabsloaded = ["profile"];
        $scope.tabname = "profile";
        $scope.titletab = $scope.report_title_tab;

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