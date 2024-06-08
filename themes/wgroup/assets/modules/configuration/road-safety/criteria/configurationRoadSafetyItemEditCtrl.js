'use strict';
/**
 * controller for Customers
 */
app.controller('configurationRoadSafetyItemEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'FileUploader', '$localStorage', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, FileUploader, $localStorage, $aside) {

        var log = $log;
        var request = {};
        var roadSafetyUploadedId = 0;

        $scope.loading = true;
        $scope.isCreate = $scope.$parent.currentId == 0;

        $scope.parentList = [];
        $scope.roadSafetyList = [];
        $scope.roadSafetyListAll = [];

        var loadList = function () {

            var req = {};

            return $http({
                method: 'POST',
                url: 'api/road-safety/list-data',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.parentList = response.data.data.parent;
                    $scope.roadSafetyListAll = response.data.data.roadSafety;
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        loadList();


        var initialize = function () {
            $scope.roadSafety = {
                id: $scope.$parent.currentId ? $scope.$parent.currentId : 0,
                roadSafety: null,
                roadSafetyParent: null,
                numeral: "",
                description: "",
                value: 0,
                criterion: '',
                isActive: true,
                legalFrameworkList: [],
                verificationModeList: []
            };
        };

        initialize();

        $scope.$watch("roadSafety.roadSafetyParent", function (newValue, oldValue, scope) {

            $scope.roadSafetyList = [];

            if (oldValue != null && !angular.equals(newValue, oldValue)) {
                $scope.roadSafety.roadSafety = null;
            }

            if ($scope.roadSafety.roadSafetyParent != null) {
                $scope.roadSafetyList = $filter('filter')($scope.roadSafetyListAll, {parentId: $scope.roadSafety.roadSafetyParent.id});
            }

        });

        var loadRecord = function () {
            // se debe cargar primero la información actual del cliente..

            if ($scope.roadSafety.id) {
                var req = {
                    id: $scope.roadSafety.id
                };

                $http({
                    method: 'GET',
                    url: 'api/road-safety-item',
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
                            SweetAlert.swal("Información no disponible", "Anexo no encontrado", "error");
                            $timeout(function () {

                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder al registro", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.roadSafety = response.data.result;
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);
                    });
            }
        };

        loadRecord();

        $scope.master = $scope.roadSafety;

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

                $scope.roadSafety = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.roadSafety);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/road-safety-item/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.roadSafety = response.data.result;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.reloadData();
            });
        };

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", 0);
            }
        };

        //----------------------------------------------------------------LEGAL FRAMEWORK
        $scope.onAddLegalFramework = function () {

            $timeout(function () {
                if ($scope.roadSafety.legalFrameworkList == null) {
                    $scope.roadSafety.legalFrameworkList = [];
                }
                $scope.roadSafety.legalFrameworkList.push(
                    {
                        id: 0,
                        roadSafetyItemId: 0,
                        type: 'legal-framework',
                        description: "",
                    }
                );
            });
        };

        $scope.onRemoveLegalFramework = function (index) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Confirma que desea elimintar el registro seleccionado?",
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
                            var date = $scope.roadSafety.legalFrameworkList[index];

                            $scope.roadSafety.legalFrameworkList.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/road-safety-item-detail/delete',
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

        //----------------------------------------------------------------VERIFICATION MODE
        $scope.onAddVerificationMode = function () {

            $timeout(function () {
                if ($scope.roadSafety.verificationModeList == null) {
                    $scope.roadSafety.verificationModeList = [];
                }
                $scope.roadSafety.verificationModeList.push(
                    {
                        id: 0,
                        roadSafetyItemId: 0,
                        type: 'verification-mode',
                        description: "",
                    }
                );
            });
        };

        $scope.onRemoveVerificationMode = function (index) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Confirma que desea elimintar el registro seleccionado?",
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
                            var date = $scope.roadSafety.verificationModeList[index];

                            $scope.roadSafety.verificationModeList.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/road-safety-item-detail/delete',
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


    }]);
