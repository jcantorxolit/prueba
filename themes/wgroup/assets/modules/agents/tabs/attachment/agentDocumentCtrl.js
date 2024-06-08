'use strict';
/**
  * controller for Customers
*/
app.controller('agentDocumentCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..customerDocumentCtrl!!! ");

    // default view
    $scope.attachment_section = "list";
    $scope.currentAttachment = 0;
    $scope.modeDsp = "edit";

    $scope.navToSection =  function(section, titlenav, currentAttachment){
        $timeout(function(){
            $scope.attachment_section = section;
            $scope.modeDsp = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentAttachment = currentAttachment;
        });
    };

}]);