'use strict';
/**
  * controller for Customers
*/
app.controller('customerSafetyInspectionCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;

    log.info("loading..customerSafetyInspectionCtrl!!! ");

    // default view
    $scope.tracking_section = "list";
    $scope.currentSafetyInspectionId = 0;
    $scope.modeDsp = "edit";

    $scope.navToSection =  function(section, titlenav, currentSafetyInspectionId){
        $timeout(function(){
            $scope.tracking_section = section;
            $scope.modeDsp = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentSafetyInspectionId = currentSafetyInspectionId;
        });
    };

}]);