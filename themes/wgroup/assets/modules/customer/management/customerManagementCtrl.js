'use strict';
/**
  * controller for Customers
*/
app.controller('customerManagementCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerManagementCtrl!!! ");

    // default view
    $scope.management_section = "list";
    $scope.currentManagement = 0;
    $scope.currentProgram = 0;
    
    $scope.navToSection =  function(section, titlenav, currentManagement, currentProgram){
        $timeout(function(){
            $scope.management_section = section;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentManagement = currentManagement;
            $scope.currentProgram = currentProgram;
        });
    };

}]);