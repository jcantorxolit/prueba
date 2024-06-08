'use strict';
/**
  * controller for Customers
*/
app.controller('customerSafetyInspectionConfigListCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerTrackingCtrl!!! ");

    // default view
    $scope.tracking_section = "list";
    $scope.currentConfigListId = 0;
    $scope.modeDsp = "edit";

    $scope.navToSection =  function(section, titlenav, currentConfigHeaderId){
        $timeout(function(){
            $scope.tracking_section = section;
            $scope.modeDsp = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentConfigListId = currentConfigHeaderId;
        });
    };

}]);