'use strict';
/**
  * controller for Customers
*/
app.controller('customerDiagnosticExoressMatrixCtrl', ['$scope', '$stateParams', '$log','$compile', 
'$rootScope', '$timeout', '$filter', 'ExpressMatrixService',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $filter, ExpressMatrixService) {

    $scope.views =
        [
            { name: 'workplace', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-express-matrix/work-place/customer_diagnostic_express_matrix_work_place.htm'},
            { name: 'priorization', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-matrix/customer_diagnostic_risk_matrix_priorization.htm'},
            { name: 'historical', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-matrix/customer_diagnostic_risk_matrix_historical.htm'},
            { name: 'characterization', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-matrix/customer_diagnostic_risk_matrix_characterization.htm'},
        ];

    $scope.loading = false;            
    $scope.currentStep = 0;
    
    $scope.getView = function(viewName) {
        var views = $filter('filter')($scope.views , {name: viewName});
        return views[0];
    };

    $scope.switchTab = function (tab) {
        $timeout(function () {
            $scope.tabname = tab;
        });
    };

    var onDestroyWizardGoTo$ = $rootScope.$on('wizardGoTo', function (event, args) {         
        goToStep(args.newValue);
    });

    $scope.$on("$destroy", function() {
        onDestroyWizardGoTo$();
    });


    $scope.form = {
        next: function (form) {
            $scope.toTheTop();
            nextStep();
        },
        prev: function (form) {
            $scope.toTheTop();
            prevStep();
        },
        goTo: function (form, i) {
            if (parseInt($scope.currentStep) > parseInt(i)) {
                $scope.toTheTop();     
                ExpressMatrixService.setWorkplaceId(null);           
                goToStep(i);
            } else {
                if (form.$valid) {
                    $scope.toTheTop();       
                    ExpressMatrixService.setWorkplaceId(null);                                     
                    goToStep(i);
                } else {
                    errorMessage();
                }
            }
        },
        reset: function (form) {            
            form.$setPristine(true);
        }
    };

    var nextStep = function () {
        var $oldValue = $scope.currentStep;
        $scope.currentStep++;
        $rootScope.$emit('wizardNavigate', { newValue: $scope.currentStep, oldValue:  $oldValue});
    };
    var prevStep = function () {
        var $oldValue = $scope.currentStep;
        $scope.currentStep--;
        $rootScope.$emit('wizardNavigate', { newValue: $scope.currentStep, oldValue:  $oldValue });
    };
    var goToStep = function (i) {  
        var $oldValue = $scope.currentStep;      
        $scope.currentStep = i;
        $rootScope.$emit('wizardNavigate', { newValue: $scope.currentStep, oldValue:  $oldValue });
    };

}]);