'use strict';
/**
  * controller for Customers
*/
app.controller('customerDiagnosticPreventionDocumentCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};


    // default view
    $scope.diagnostic_section = "list";
    $scope.currentId = 0;
    
    $scope.navToSection =  function(section, titlenav, currentId){
        log.info("cambiando la vista a... section:", section, " titlenav:", titlenav, " currentId:", currentId);
        $timeout(function(){
            $scope.diagnostic_section = section;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };

}]);