'use strict';
/**
  * controller for Customers
*/
app.controller('customerWorkMedicineCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerWorkMedicineCtrl!!! ");

    // default view
    $scope.tracking_section = "list";
    $scope.currentId = 0;
    $scope.modeDsp = "edit";

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.tracking_section = section;
            $scope.modeDsp = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };

}]);