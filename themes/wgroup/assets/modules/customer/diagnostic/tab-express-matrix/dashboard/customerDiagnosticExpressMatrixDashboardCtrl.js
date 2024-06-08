'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticExpressMatrixDashboardCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {
              
        $scope.view_section = "hazard";
        $scope.currentHazardId = 0;
        $scope.editMode = "create";

        $scope.navToSection =  function(section, editMode, currentId) {
            $timeout(function(){
                $scope.view_section = section;
                $scope.editMode = editMode;                
                $scope.currentHazardId = currentId;
            });
        };

    }
]);