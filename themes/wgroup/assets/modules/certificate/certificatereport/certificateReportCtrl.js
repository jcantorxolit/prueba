'use strict';
/**
  * controller for Customers
*/
app.controller('certificateReportCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..certificateReportCtrl!!! ");

    // default view
    $scope.certificate_section = "list";
    $scope.currentId = 0;
    $scope.formMode = "edit";

    $scope.navToSection =  function(section, titlenav, currentTraking){
        $timeout(function(){
            $scope.certificate_section = section;
            $scope.formMode = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentTraking;
        });
    };
}]);