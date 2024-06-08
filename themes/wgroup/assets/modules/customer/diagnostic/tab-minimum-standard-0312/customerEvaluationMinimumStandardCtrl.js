'use strict';
/**
  * controller for Customers
*/
app.controller('customerEvaluationMinimumStandard0312Ctrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    // default view
    $scope.standard_section = "list";
    $scope.currentId = 0;
    $scope.editMode = 'continue';

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.standard_section = section;            
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };

    $scope.setEditMode = function(mode) {
        $scope.editMode = mode;
    }
}]);