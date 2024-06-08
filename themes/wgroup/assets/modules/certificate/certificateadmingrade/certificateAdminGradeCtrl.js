'use strict';
/**
  * controller for Customers
*/
app.controller('certificateAdminGradeCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    var log = $log;
    var request = {};

    log.info("loading..certificateAdminGradeCtrl!!! ");

    // default view
    $scope.grade_section = "list";
    $scope.currentId = 0;
    $scope.formMode = "edit";

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.grade_section = section;
            $scope.formMode = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };


}]);