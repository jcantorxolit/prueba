app.controller('PFManagementTabsCtrl',
    function ($scope, $state) {

        
        $scope.activeTab = $state.is("app.positiva-fgn.fgn-management-axis-programming") ? 1 : 2;

        $scope.switchTab = function(tab) {
            $scope.activeTab = tab;
            if(tab==1){
                $state.go("app.positiva-fgn.fgn-management-axis-programming");
            } else {
                $state.go("app.positiva-fgn.fgn-management-axis-execution");
            }
        }


    });