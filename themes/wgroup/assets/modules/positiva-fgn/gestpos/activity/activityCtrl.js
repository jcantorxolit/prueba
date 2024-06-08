'use strict';
/**
 * controller for activityCtrl
 */
app.controller('activityCtrl', 
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {
        $scope.activity_section = "list";
        $scope.currentId = 0;
        $scope.editMode = "create";

        $scope.navToSection =  function(section, title, currentId){
            $timeout(function(){
                $scope.activity_section = section;
                $scope.editMode = title;
                // $scope.$parent.switchSubTab(title);
                $scope.currentId = currentId;
            });
        };
    }
);