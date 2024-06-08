'use strict';
/**
  * controller for Customers
*/
app.controller('customerEmployeeCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    // default view
    $scope.employee_section = "list";
    $scope.currentEmployee = 0;
    $scope.editMode = "create";

    $scope.navToSection =  function(section, title, currentEmployee){
        $timeout(function(){
            $scope.employee_section = section;
            $scope.editMode = title;
            $scope.$parent.switchSubTab(title);
            $scope.currentEmployee = currentEmployee;
        });
    };

}]);
