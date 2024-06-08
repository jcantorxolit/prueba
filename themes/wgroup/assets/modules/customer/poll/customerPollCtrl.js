'use strict';
/**
  * controller for Customers
*/
app.controller('customerPollCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerPollCtrl!!! ");

    // default view
    $scope.poll_section = "list";
    $scope.currentPoll = 0;
    $scope.modeDsp = "edit";

    $scope.navToSection =  function(section, titlenav, currentPoll){
        $timeout(function(){
            $scope.poll_section  = section;
            $scope.modeDsp = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentPoll = currentPoll;
        });
    };

}]);