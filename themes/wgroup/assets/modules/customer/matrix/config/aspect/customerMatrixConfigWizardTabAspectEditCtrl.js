'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerMatrixConfigWizardTabAspectEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', '$document', 'FileUploader', '$localStorage', 'toaster',
    function ($scope, $stateParams, $log, $compile, $state,
              SweetAlert, $rootScope, $http, $timeout, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $uibModal, flowFactory,
              cfpLoadingBar, $filter, $aside, $document, FileUploader, $localStorage, toaster) {

        var log = $log;

        var currentId = $scope.$parent.currentId;
        var currentParentId = $scope.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.currentId;
        console.log($scope.$parent);
        console.log(currentParentId);

        $scope.isRecordLoaded = false;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.impactList = [];
        $scope.isView = $scope.$parent.modeDsp == "view";
        $scope.minDateCurrent = new Date();

        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy HH:mm"
        };

        $scope.onLoadRecord = function () {
            if ($scope.aspect.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.aspect.id);
                var req = {
                    id: $scope.aspect.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/matrix-aspect',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Información no disponible", "Centro de trabajo no encontrado", "error");

                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del centro de trabajo", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.aspect = response.data.result;

                            if ($scope.aspect.impacts == null || $scope.aspect.impacts.length == 0) {
                                $scope.aspect.impacts = [
                                    {
                                        id: 0,
                                        impact: null
                                    }
                                ]
                            }

                            $scope.isRecordLoaded = true;
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            $document.scrollTop(40, 2000);
                        });

                    });
            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        }

        var init = function () {
            $scope.aspect = {
                id: currentId,
                customerMatrixId: currentParentId,
                name: null,
                isActive: true,
                impacts: [
                    {
                        id: 0,
                        impact: null
                    }
                ]
            };
        }


        var loadList = function () {

            var req = {};
            req.operation = "diagnostic";
            req.customerMatrixId = currentParentId;

            return $http({
                method: 'POST',
                url: 'api/customer/matrix-impact/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                console.log(response);
                $timeout(function () {
                    $scope.impactList = response.data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        init();
        loadList();

        $scope.onLoadRecord();

        $scope.master = $scope.aspect;

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
                    SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                    //your code for submit
                    save();
                }

            },
            reset: function (form) {

                $scope.aspect = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};

            var data = JSON.stringify($scope.aspect);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/matrix-aspect/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.aspect = response.data.result;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.$parent.navToSection("list", "list");
            });
        };

        $scope.cancelEdition = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        //----------------------------------------------------------------IMPACTS
        $scope.onAddImpact = function () {

            $timeout(function () {
                if ($scope.aspect.impacts == null) {
                    $scope.aspect.impacts = [];
                }
                $scope.aspect.impacts.push(
                    {
                        id: 0,
                        impact: null
                    }
                );
            });
        };

        $scope.onRemoveImpact = function (index) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, eliminar!",
                    cancelButtonText: "No, cancelar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        $timeout(function () {
                            // eliminamos el registro en la posicion seleccionada
                            var date = $scope.aspect.impacts[index];

                            $scope.aspect.impacts.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/matrix-aspect-impact/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function (e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function () {

                                });
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        $scope.onRefreshImpact = function () {
            loadList();
        }


    }]);
