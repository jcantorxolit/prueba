'use strict';
/**
 * Controller for Actividad
 *
 * @author David Blandon <david.blandon@gmail.com>
 */
app.controller('ConfigurationPrioritizationFactorCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout', '$filter', '$state',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $filter, $state) {

        var log = $log;

        $scope.isCreate = $state.is("app.configuration.prioritization-factor");

        $scope.views =
            [
                { name: 'basic', url: $rootScope.app.views.urlRoot + 'modules/configuration/prioritization-factor/_form.htm'}
            ];

        // default view
        $scope.section = $scope.views[0];
        $scope.currentId = 0;
        $scope.modeView = "form";

        $scope.tabsLoaded = ["basic"];
        $scope.tabName = $scope.tabsLoaded[0];

        $scope.navToSection =  function(section, title, currentId){
            $timeout(function(){
                $scope.section = $scope.getView(section);
                $scope.modeView = title;
                //$scope.$parent.switchSubTab(title);
                $scope.currentId = currentId;
            });
        };

        $scope.getView = function(nameView) {
            var views = $filter('filter')($scope.views , {name: nameView});
            return views[0];
        }

        $scope.switchTab = function (tab) {
            $timeout(function () {
                $scope.tabName = tab;
                $scope.tabsLoaded.push(tab);
                $scope.section = $scope.getView(tab);
            });
        };

    }]);
