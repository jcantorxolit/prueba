'use strict';
/**
  * controller for Customers
*/
app.controller('customerEvaluationMinimumStandardCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerEvaluationMinimumStandard!!! ");

    // default view
    $scope.standard_section = "summary";
    $scope.currentId = 0;

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.standard_section = section;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };
}]);