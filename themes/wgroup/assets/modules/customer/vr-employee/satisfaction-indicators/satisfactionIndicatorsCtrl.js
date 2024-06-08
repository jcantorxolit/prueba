'use strict';
/**
 * controller for Customers
 */
app.controller('customerVrEmployeeSatisfactionIndicatorsCtrl', ['$scope', function ($scope) {

    $scope.isView = false;
    $scope.section = "list";
    $scope.date = null;
    $scope.participants = 0;

    $scope.navToSection = function (section, date, participants) {
        $scope.section = section;

        if (section == 'list') {
            $scope.date = null;
            $scope.participants = 0;
        } else {
            $scope.date = date;
            $scope.participants = participants;
        }
    };

}
]);