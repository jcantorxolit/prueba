'use strict';
/**
 * controller for Customers
 */
app.controller('customerAbsenteeismBillingCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document', '$filter',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter) {


        var log = $log;
        var request = {};

        log.info("loading..customerAbsenteeismIndicatorsCtrl ");

        $scope.disabilities = [];

        var loadDisabilities = function () {
            var req = {};
            req.customer_id = $stateParams.customerId;
            $http({
                method: 'POST',
                url: 'api/absenteeism-disability/billing',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.disabilities = response.data.data;
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error en la eliminaci�n", "Se ha presentado un error durante la eliminaci�n del registro por favor intentelo de nuevo", "error");
            }).finally(function () {
            });
        }

        loadDisabilities();

        $scope.onSaveBilling = function () {
            var req = {};

            var disabilityData = {
                isValid: true,
                disabilities: []
            }

            var result = true;

            angular.forEach($scope.disabilities, function(disability) {
                if (disability.charged && disability.amountPaid == '') {
                    result = false;
                } else if (disability.charged && disability.amountPaid != '') {
                    disabilityData.disabilities.push(disability);
                }
            });

            if (result) {

                if (disabilityData.disabilities.length > 0) {
                    var data = JSON.stringify(disabilityData);

                    req.data = Base64.encode(data);

                    return $http({
                        method: 'POST',
                        url: 'api/absenteeism-disability/update',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param(req)
                    }).then(function (response) {

                        $timeout(function () {
                            toaster.pop('success', 'Operación Exitosa', 'Pagos administradoras ingresados');
                            loadDisabilities();
                        });
                    }).catch(function (e) {
                        $log.error(e);
                        toaster.pop("error", "Error", "Ha ocurrido un error ingresando las facturas");
                    }).finally(function () {

                    });
                } else {
                    toaster.pop("error", "Error", "No hay proyectos marcados para facturar");
                }
            } else {
                toaster.pop("error", "Error", "El número de factura es requerido para los proyectos marcados como facturado");
            }

        }

    }]);