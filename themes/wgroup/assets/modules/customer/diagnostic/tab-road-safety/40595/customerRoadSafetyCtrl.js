'use strict';
/**
  * controller for Customers
*/
app.controller('customerRoadSafety40595Ctrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    // default view
    $scope.safety_road_section = "summary";
    $scope.currentId = 0;
    $scope.editMode = 'continue';

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.safety_road_section = section;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };

    $scope.setEditMode = function(mode) {
        $scope.editMode = mode;
    }
}]);
