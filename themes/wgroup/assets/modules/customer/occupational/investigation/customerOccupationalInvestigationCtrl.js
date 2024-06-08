'use strict';
/**
  * controller for Customers
*/
app.controller('customerOccupationalInvestigationCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerOccupationalInvestigationCtrl!!! ");

    // default view
    $scope.investigation_section = "list";
    $scope.currentId = 0;
    $scope.modeDsp = "edit";

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.investigation_section = section;
            $scope.modeDsp = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };

}]);