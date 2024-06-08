'use strict';
/**
 * controller for Customers
 */
app.controller('customerInternalCertificateAdminTabsCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout', '$state', '$filter', 'flowFactory','$http',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $state, $filter, flowFactory, $http) {


        //Variables globales en el tab
        if ($state.is("app.certificate.admin.view")) {
            $scope.certificate_admin_title_tab = "view";
        } else if ($state.is("app.certificate.admin.create")) {
            $scope.certificate_admin_title_tab = "create";
        } else {
            $scope.certificate_admin_title_tab = "edit";
        }

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";


        $scope.tabsloaded = ["program"];
        $scope.tabname = "program";
        $scope.titletab = $scope.certificate_admin_title_tab;

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