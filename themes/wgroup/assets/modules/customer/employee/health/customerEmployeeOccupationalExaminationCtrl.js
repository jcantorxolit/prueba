'use strict';
/**
  * controller for Customers
*/
app.controller('customerEmployeeOccupationalExaminationCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;

    log.info("loading..customerEmployeeOccupationalExaminationCtrl!!! ");

    // default view
    $scope.employee_section = "list";
    $scope.currentId = 0;
    $scope.editMode = "create";

    $scope.navToSection =  function(section, title, currentId){
        $timeout(function(){
            $scope.employee_section = section;
            $scope.editMode = title;
            $scope.$parent.switchSubTab(title);
            $scope.currentId = currentId;
        });
    };

}]);