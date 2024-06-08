'use strict';
/**
  * controller for Customers
*/
app.controller('customerOccupationalReportIncidentCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerOccupationalReportIncidentCtrl!!! ");

    // default view
    $scope.tracking_section = "list";
    $scope.currentReport = 0;
    $scope.modeDsp = "edit";

    $scope.navToSection =  function(section, titlenav, currentReport){
        $timeout(function(){
            $scope.tracking_section = section;
            $scope.modeDsp = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentReport = currentReport;
        });
    };

}]);