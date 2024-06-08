'use strict';
/**
 * controller for Customers
 */
app.controller('customerProfileConfigSgsstTabsCtrl', ['$scope', '$location', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder',
    function ($scope, $location, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter) {

        
        $scope.views =
            [
                { name: 'workplace', url: $rootScope.app.views.urlRoot + 'modules/customer/sgsst/center/customer_profile_config_sgsst_work_center_tab.htm'},
                { name: 'macroprocess', url: $rootScope.app.views.urlRoot + 'modules/customer/sgsst/macro-processes/customer_profile_config_sgsst_macro_processes_tab.htm'},
                { name: 'process', url: $rootScope.app.views.urlRoot + 'modules/customer/sgsst/process/customer_profile_config_sgsst_process_tab.htm'},
                { name: 'activity', url: $rootScope.app.views.urlRoot + 'modules/customer/sgsst/activities/customer_profile_config_sgsst_acivity_tab.htm'},
                { name: 'job', url: $rootScope.app.views.urlRoot + 'modules/customer/sgsst/job/customer_profile_config_sgsst_job_tab.htm'},
                { name: 'hazard', url: $rootScope.app.views.urlRoot + 'modules/customer/sgsst/hazard/customer_profile_config_sgsst_job_activity_hazard_edit.htm'},
                { name: 'wizard', url: $rootScope.app.views.urlRoot + 'modules/customer/sgsst/massive/customer_profile_config_sgsst_wizard_tab.htm'},
            ];

        
        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";
    
        $scope.statusMode = "create";
        $scope.loading = false;

        $scope.tabsloaded = ["workplace"];
        $scope.tabname = "workplace";
        $scope.titletab = $scope.statusMode;

        $scope.getView = function(nameView) {
            var views = $filter('filter')($scope.views , {name: nameView}, true);
            return views[0];
        };

        $scope.switchTab = function (tab) {
            $log.info(tab);
            $timeout(function () {
                $scope.tabname = tab;
                $scope.tabsloaded.push(tab);                
            });
        };
    }
]);