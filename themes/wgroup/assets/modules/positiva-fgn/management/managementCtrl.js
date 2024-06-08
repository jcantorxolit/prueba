'use strict';

app.controller('PFManagement', 
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

    $scope.pf_management_section = "all";
    $scope.currentId = 0;
    $scope.editMode = "create";

    $scope.navToSection =  function(section, title, currentId){
        $timeout(function(){
            $scope.pf_management_section = section;
            $scope.editMode = title;
            $scope.currentId = currentId;
        });
    };

});