'use strict';
/**
  * controller for Customers
*/
app.controller('customerConfigWizardProcessCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    
    // default view
    $scope.view_component = "list";
    $scope.currentId = 0;
    $scope.modeDsp = "edit";

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.view_component = section;
            $scope.modeDsp = titlenav;
            //$scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };

}]);