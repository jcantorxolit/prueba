'use strict';
/**
  * controller for Customers
*/
app.controller('configurationMinimumStandard0312Ctrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    // default view
    $scope.standard_section = "list";
    $scope.currentId = 0;
    
    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.standard_section = section;    
            $scope.currentId = currentId;
        });
    };

}]);