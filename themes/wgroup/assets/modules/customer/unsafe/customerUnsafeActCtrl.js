'use strict';
/**
  * controller for Customers
*/
app.controller('customerUnsafeActCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerUnsafeActCtrl!!! ");

    // default view
    $scope.matrix_section = "list";
    $scope.action = "list";
    $scope.currentId = 0;

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.matrix_section = section;
            $scope.action = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };

}]);