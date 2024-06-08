'use strict';
/**
  * controller for Customers
*/
app.controller('configurationMinimumStandardItem0312Ctrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..configurationMinimumStandardItemCtrl!!! ");

    // default view
    $scope.standard_section = "list";
    $scope.currentId = 0;
    $scope.dataSummary = [];

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.standard_section = section;
            //$scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };

}]);