'use strict';
/**
  * controller for Customers
*/
app.controller('customerOccupationalReportIncidentPreviewCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','SweetAlert','$http', '$filter', '$document',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
          $compile, toaster, $state, $rootScope,$timeout, SweetAlert, $http, $filter, $document) {

    var log = $log;
    var request = {};
        log.info("loading..customerOccupationalReportIncidentPreviewCtrl.js ");

    $scope.agents = $rootScope.agents();

    $scope.currentURL =  "api/occupational-report-incident/preview?id=" + $scope.$parent.currentReport + "&rnd=" + Math.random();

    $scope.onCancel = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "edit", $scope.$parent.currentReport);
        }
    };

}]);