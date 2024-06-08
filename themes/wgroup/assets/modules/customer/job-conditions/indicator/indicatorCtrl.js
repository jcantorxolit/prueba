'use strict';
/**
 * controller for Customers
 */
app.controller('CustomerJobConditionsIndicatorCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout',
    function($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

        $scope.isView = false;
        $scope.section = "dashboard";
        $scope.currentId = 0;

        $scope.navToSection = function(section, title, currentId) {
            $timeout(function() {
                $scope.section = section;
                $scope.isView = title;
                $scope.currentId = currentId;
            });
        };
    }
]);