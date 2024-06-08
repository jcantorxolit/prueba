'use strict';
/**
  * controller for Customers
*/
app.controller('customerDiagnosticRiskMatrixCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout', '$filter',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $filter) {

    var log = $log;
    var log = $log;
    $scope.views =
        [
            { name: 'summary', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-matrix/customer_diagnostic_risk_matrix_summary.htm'},
            { name: 'priorization', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-matrix/customer_diagnostic_risk_matrix_priorization.htm'},
            { name: 'historical', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-matrix/customer_diagnostic_risk_matrix_historical.htm'},
            { name: 'characterization', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/tab-matrix/customer_diagnostic_risk_matrix_characterization.htm'},
        ];

    $scope.loading = false;        
    $scope.tabname = "basic";
    
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