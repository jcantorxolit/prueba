'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeIndicatorsEmployeeIndicatorsTabsCtrl', function ($scope) {

        $scope.section = "summary";

        $scope.navToSection = function (section) {
            $scope.section = section;
        };
    }
);
