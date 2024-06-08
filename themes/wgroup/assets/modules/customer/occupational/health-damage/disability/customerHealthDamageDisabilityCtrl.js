'use strict';
/**
  * controller for Customers
*/
app.controller('customerHealthDamageDisabilityCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout', '$filter',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $filter) {

    var log = $log;
    var request = {};

    $scope.views =
    [
        { name: 'disability_edit', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/disability/customer_health_damage_disability_edit.htm'},
        { name: 'disability_list', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/disability/customer_health_damage_disability_list.htm'},
        { name: 'disability_person', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/health-damage/disability/customer_health_damage_disability_person.htm'},
    ];

    // default view
    $scope.tracking_section = "list";
    $scope.currentId = 0;
    $scope.modeDsp = "edit";
    $scope.tabname = "disability_edit";

    $scope.navToSection =  function(section, titlenav, currentId){
        $timeout(function(){
            $scope.tracking_section = section;
            $scope.modeDsp = titlenav;
            $scope.$parent.switchSubTab(titlenav);
            $scope.currentId = currentId;
        });
    };

    $scope.getView = function(viewName) {
        var views = $filter('filter')($scope.views , {name: viewName});
        return views[0];
    };

    $scope.switchTab = function (tab) {
        $timeout(function () {
            $scope.tabname = tab;
        });
    };    

}]);