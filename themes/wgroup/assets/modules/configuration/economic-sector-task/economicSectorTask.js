'use strict';
/**
 * controller for Customers
 */
app.controller('economicSectorTask', ['$scope', '$aside', '$stateParams', '$log',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal',
    '$filter', '$document', 'ListService',
    function ($scope, $aside, $stateParams, $log, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, $filter, $document, ListService) {

        var $formInstance = null;

        getList();

        function getList() {
            var entities = [
                {name: 'economic_sector', value: null},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.economicSectorList = response.data.data.economicSectorList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        var init = function() {
            $scope.entity = {
                economicSector: null,
                taskList: null
            };

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        }

        init();

        $scope.form = {

            submit: function (form) {
                $formInstance = form;

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
                    save();
                }

            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        var save = function () {
            var req = {
                data : Base64.encode(JSON.stringify($scope.entity.taskList))
            } ;
            return $http({
                method: 'POST',
                url: 'api/economic-sector-task/batch',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.entity.taskList = response.data.result.data;
                    SweetAlert.swal("Validación exitosa", "Guardando información del cliente...", "success");
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.onSelectEconomicSector = function() {
            var req = {
                economicSectorId : $scope.entity.economicSector.id
            } ;
            return $http({
                method: 'POST',
                url: 'api/economic-sector-task',
                headers: {'Content-Type': 'aapplication/json'},
                data: req
            }).then(function (response) {
                $timeout(function () {
                    $scope.entity.taskList = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });
        }

        $scope.onAddTask = function (form) {
            if ($scope.entity.taskList == null) {
                $scope.entity.taskList = [];
            }

            $scope.entity.taskList.push(
                {
                    id: 0,
                    economicSectorId: $scope.entity.economicSector.id,
                    name: null,
                    isActive: true
                }
            );

            $timeout(function () {
                var lastError = null;
                if (form.$invalid) {
                    var field = null, firstError = null;
                    for (field in form) {
                        if (field[0] != '$') {
                            if (!form[field].$valid) {
                                lastError = form[field].$name;
                            }

                            if (form[field].$pristine) {
                                form[field].$dirty = true;
                            }
                        }
                    }
                    angular.element('.ng-invalid[name=' + lastError + ']').focus();
                    return;
                }
            });
        };

        $scope.onRemoveTask = function (index) {
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
                            var data = $scope.entity.taskList[index];

                            $scope.entity.taskList.splice(index, 1);

                            if (data.id != 0) {
                                var req = {
                                    id: data.id
                                };
                                $http({
                                    method: 'POST',
                                    url: 'api/economic-sector-task/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function (e) {

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

    }
]);
