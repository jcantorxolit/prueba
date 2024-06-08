'use strict';
/**
 * controller for Customers
 */
app.controller('customerImprovementPlanCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

        var log = $log;

        log.info("loading..customerImprovementPlanCtrl!!! ");

        // default view
        $scope.improvement_section = "list";
        $scope.currentId = 0;
        $scope.reload = false;
        $scope.statusMode = "create";

        $scope.navToSection = function (section, editMode, currentId) {
            $timeout(function () {
                $scope.improvement_section = section;
                $scope.editMode = editMode;
                $scope.$parent.switchSubTab(editMode);
                $scope.currentId = currentId;
            });
        };

        $scope.tabsloaded = ["profile"];
        $scope.tabname = "profile";
        $scope.titletab = '';
        
        $scope.switchTab = function (tab, titletab) {
            $timeout(function () {
                $scope.tabname = tab;
                $scope.titletab = titletab;
                $scope.tabsloaded.push(tab);
            });
        };

    }]);