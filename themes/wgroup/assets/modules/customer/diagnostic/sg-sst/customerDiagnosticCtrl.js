'use strict';
/**
  * controller for Customers
*/
app.controller('customerDiagnosticCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
   
    // default view
    $scope.diagnostic_section = "list";
    $scope.currentDiagnostic = 0;
    
    $scope.navToSection =  function(section, titlenav, currentDiagnostic){
        log.info("cambiando la vista a... section:", section, " titlenav:", titlenav, " currentDiagnostic:", currentDiagnostic);
        $timeout(function(){
            $scope.diagnostic_section = section;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentDiagnostic = currentDiagnostic;
        });
    };
    
}]);