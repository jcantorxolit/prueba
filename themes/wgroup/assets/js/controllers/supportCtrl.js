'use strict';
/** 
  * controller for Wizard Form example
*/
app.controller('SupportCtrl', ['$scope', '$rootScope', '$state', 'toaster', 'SupportService',
function ($scope, $rootScope, $state, toaster, SupportService) {
    
    

    $scope.goTo = function (step) {
        SupportService.setCurrentStep(step);
        SupportService.setShouldRedirect(true);

        if (!SupportService.inCustomerState()) {            
            $state.go("app.clientes.edit", { customerId:$rootScope.currentUser().company });          
        }

        $rootScope.$emit('navigateToSupport');
    };

}]);
