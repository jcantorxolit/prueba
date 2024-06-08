'use strict';
/**
 * controller for Customers
 */
app.controller('certificateCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', '$timeout',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http, $timeout) {


        $scope.showIframe = false;
        $scope.currentURL = "";

        $scope.certificate = {
            validateCode: "",
        };

        $scope.onValidateCode = function()
        {
            if ($scope.certificate.validateCode == "") {
                toaster.pop('error', 'Error', 'Por favor digite el código de validacion');
                return ;
            }
            var req = {};
            req.id = $scope.certificate.validateCode;

            $http({
                method: 'POST',
                url: 'api/certificate-grade-participant/validate',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            })
                .catch(function (e, code) {
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.asesores.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Código no encontrada", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                    }
                })
                .then(function (response) {

                    if (response != null){
                        $timeout(function () {
                            $scope.showIframe = true;
                            $scope.currentURL = "";
                            $scope.currentURL = "api/certificate-grade-participant-certificate/stream?id=" + response.data.result.id;
                        });
                    }

                }).finally(function () {

                });
        }

        $scope.onDownloadCertificate = function()
        {
            if ($scope.certificate.validateCode == "") {
                toaster.pop('error', 'Error', 'Por favor digite la cedula de ciudadania');
                return ;
            }
            var req = {};
            req.id = $scope.certificate.validateCode;

            $http({
                method: 'POST',
                url: 'api/certificate-grade-participant/download',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            })
                .catch(function (e, code) {
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.asesores.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta informaci�n.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Cedula no encontrada", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del certificado", "error");
                    }
                })
                .then(function (response) {

                    if (response != null){
                        $timeout(function () {
                            $scope.showIframe = true;
                            $scope.currentURL = "api/certificate-grade-participant-certificate/stream?id=" + response.data.result.id;
                        });
                    }

                }).finally(function () {

                });
        }

        $scope.onCancel = function()
        {
            $scope.showIframe = false;
            $scope.currentURL = "";
        }

    }]);
