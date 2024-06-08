'use strict';
/**
  * controller for Customers
*/
app.controller('customerAuditCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;

    // default view
    $scope.audit_section = "list";
    $scope.currentAudit= 0;
    $scope.modeDsp = "edit";

    $scope.navToSection =  function(section, titlenav, currentAudit){
        $timeout(function(){
            $scope.audit_section = section;
            $scope.modeDsp = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentAudit = currentAudit;
        });
    };

}]);