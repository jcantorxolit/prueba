app.controller('reportsPFgnTabsCtrl',
    function($scope, $stateParams, $localStorage) {
        var id = $stateParams.id;
        $scope.idReport = id;
        $scope.dataReport = $localStorage['pfgnInficatorActive' + id];
    });