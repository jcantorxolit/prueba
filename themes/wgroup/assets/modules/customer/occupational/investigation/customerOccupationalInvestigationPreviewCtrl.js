'use strict';
/**
  * controller for Customers
*/
app.controller('customerOccupationalInvestigationPreviewCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','SweetAlert','$http', '$filter', '$document',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
          $compile, toaster, $state, $rootScope,$timeout, SweetAlert, $http, $filter, $document) {

    $scope.agents = $rootScope.agents();

    $scope.isView = $scope.$parent.modeDsp == "view" || $scope.$parent.editMode == "view";

    $scope.currentURL =  "api/customer-occupational-investigation-al/stream-pdf?id=" + $scope.$parent.currentId + "&rnd=" + Math.random();

    $scope.onCancel = function(id){
        if ($scope.$parent != null) {            
            console.log("onCancel preview!!")     
            $scope.$parent.navToSection("form", $scope.isView ? 'view' : "edit", $scope.$parent.currentId);
        }
    };

}]);