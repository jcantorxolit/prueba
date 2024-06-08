'use strict';
/**
  * controller for Customers
*/
app.controller('customerVrEmployeeCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerVrEmployeeCtrl!!! ");

    $scope.vr_employee_section = "list";
    $scope.currentId = 0;
    $scope.editMode = "create";

    $scope.navToSection =  function(section, title, currentId){
        $timeout(function(){
            $scope.vr_employee_section = section;
            $scope.editMode = title;
            $scope.$parent.switchSubTab(title);
            $scope.currentId = currentId;
        });
    };

}]);