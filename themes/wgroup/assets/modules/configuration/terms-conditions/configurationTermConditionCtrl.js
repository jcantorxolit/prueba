'use strict';
/**
  * controller for Customers
*/
app.controller('configurationTermConditionCtrl', ['$scope', '$stateParams', '$log', '$state', '$rootScope',
    '$timeout', '$http', 'SweetAlert', '$aside', '$document',
    function ($scope, $stateParams, $log, $state,
        $rootScope, $timeout, $http, SweetAlert, $aside, $document) {

        $scope.loading = true;
        $scope.isView = false;

        $scope.terms = $rootScope.parameters("wg_term_condition");

        var onInit = function () {
            $scope.parameter = {
                id: $scope.terms.length > 0 ? $scope.terms[0].id : 0,
                namespace: "wgroup",
                group: "wg_term_condition",
                item: "",
                value: ""
            };
        };

        onInit();

        $scope.onLoadRecord = function () {
            $http({
                method: 'GET',
                url: 'api/system-parameter/term-condition'
            })
                .then(function (response) {
                    $timeout(function () {
                        $scope.parameter = response.data.result;
                    });
                })
                .catch(function (e) {
                    toaster.pop('Error', 'Error inesperado', e);
                })
                .finally(function () {
                    $timeout(function () {
                        $document.scrollTop(40, 2000);
                    });
                })
        }

        $scope.onLoadRecord();

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
                    log.info($scope.parameter);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("Error guardando", "Se han encontrado errores en el proceso de validación " +
                        "por favor verifique los datos del formulario y vuelva a intentarlo", "error");
                    return;

                } else {
                    save();
                }

            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        $scope.onCancel = function () {
            $timeout(function () {
                initialize();
            });
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.parameter);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/system-parameter/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {                
                $scope.parameter = response.data.result;
                SweetAlert.swal("Operación Exitosa", "La información ha sido guardada satisfactoriamente", "success");
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el proceso por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

    }
]);