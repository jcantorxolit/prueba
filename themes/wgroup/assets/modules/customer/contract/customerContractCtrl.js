'use strict';
/**
  * controller for Customers
*/
app.controller('customerContractCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerContractCtrl!!! ");

    $scope.contract_section = "list";
    $scope.currentContract = 0;
    $scope.dataContract = [];

    $scope.navToSection =  function(section, titlenav, currentContract){
        $timeout(function(){
            $scope.contract_section = section;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentContract = currentContract;
        });
    };

    $scope.setDataSummary =  function(dataSummary){
        $scope.dataSummary = dataSummary;
    };

}]);