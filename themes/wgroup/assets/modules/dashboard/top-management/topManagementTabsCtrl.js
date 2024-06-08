app.controller('TopManagementTabsCtrl', function ($scope, $rootScope, $state) {

    if(!$rootScope.availableUserTopManagement) {
        $state.go('app.clientes.list', {reload: true});
    }

    $scope.switchTab = function (tab) {
        $scope.activeTab = tab;
    }

});
