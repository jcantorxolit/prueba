'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {
        
        $scope.covid_section = "list";
        $scope.currentId = 0;
        $scope.editMode = "create";

        $scope.navToSection =  function(section, title, currentId){
            $timeout(function(){
                $scope.covid_section = section;
                $scope.editMode = title;
                $scope.$parent.switchSubTab(title);
                $scope.currentId = currentId;
            });
        };        

    }
]);