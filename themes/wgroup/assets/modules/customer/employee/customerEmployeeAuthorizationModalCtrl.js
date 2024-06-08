'use strict';
/**
  * controller for Customers
*/
app.controller('customerEmployeeAuthorizationModalCtrl', ['$scope', '$stateParams', '$log','$compile', '$rootScope', '$timeout',
'$uibModalInstance',
function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $uibModalInstance) {

    
    $scope.onCloseModal = function () {
        $uibModalInstance.close();
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var init = function() {
        $scope.entity = {            
            reason: null,
        }
    }

    init();

    $scope.form = {

        submit: function (form) {
            var firstError = null;

            if (form.$invalid) {

                var field = null, firstError = null;
                for (field in form) {
                    if (field[0] != '$') {
                        if (firstError === null && !form[field].$valid) {
                            firstError = form[field].$name;
                        }

                        if (form[field].$pristine) {
                            form[field].$dirty = true;
                        }
                    }
                }

                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                onAccept();
            }

        },
        reset: function (form) {

        }
    };

    var onAccept = function () {        
        $uibModalInstance.close($scope.entity.description);
    };    

}]);
