'use strict';
/**
  * controller for Customers
*/
app.controller('customerTrackingCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerTrackingCtrl!!! ");

    // default view
    $scope.tracking_section = "list";
    $scope.currentTraking = 0;
    $scope.actionMode = "edit";

    $scope.navToSection =  function(section, titlenav, currentTraking){
        $timeout(function(){
            $scope.tracking_section = section;
            $scope.actionMode = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentTraking = currentTraking;
        });
    };

}]);