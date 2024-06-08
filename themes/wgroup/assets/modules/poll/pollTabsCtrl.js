'use strict';
/**
 * controller for Reports
 */
app.controller('pollTabsCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout', '$state', '$filter', 'flowFactory','$http',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $state, $filter, flowFactory, $http) {

        var log = $log;
        var request = {};

        //Variables globales en el tab
        if ($state.is("app.poll.generate")) {
            $scope.poll_title_tab = "generate";
        } else if ($state.is("app.poll.create")) {
            $scope.poll_title_tab = "create";
        } else if ($state.is("app.poll.dynamically")) {
            $scope.poll_title_tab = "dynamically";
        } else {
            $scope.poll_title_tab = "edit";
        }

        var $pollId = $rootScope.currentUser().company;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.poll = {};
        $scope.poll.id = $scope.iscreate ? 0 : $stateParams.pollId;

        $scope.tabsloaded = ["profile"];
        $scope.tabname = "profile";
        $scope.titletab = $scope.poll_title_tab;

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