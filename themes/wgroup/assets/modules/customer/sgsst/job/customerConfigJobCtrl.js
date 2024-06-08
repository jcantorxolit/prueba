'use strict';
/**
  * controller for Customers
*/
app.controller('customerConfigJobCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerTrackingCtrl!!! ");

    // default view
    $scope.job_section = "list";
    $scope.currentId = 0;
    $scope.editMode = "edit";
    $scope.controllerName = "customerConfigJobCtrl";

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.job_section = section;
            $scope.editMode = titlenav;
            //$scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };

}]);