'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerSafetyInspectionConfigListEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside',
    function ($scope, $stateParams, $log, $compile,  $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $aside) {

        var log = $log;

        var currentId = $scope.$parent.currentConfigListId;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        log.info("loading..customerTrackingEditCtrl con el id de tracking: ", currentId);
        $scope.customerId = $stateParams.customerId;

        $scope.dataTypes =  $rootScope.parameters("wg_data_type");

        $scope.dateConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        $scope.list = {
            id : 0,
            customerId : $scope.customerId,
            name : "",
            description : "",
            version : "",
            dateFrom : null,
            isActive : 0,
            groups: [],
            dangerousnessList: [],
            priorityList: [],
            existingControlList: [],
        };

        var modeDsp = ($scope.$parent.$parent.$parent.$parent.$parent.$parent.modeDsp).toString();
        if (modeDsp.toString() === "view"){
            $scope.isView = true;
        } else {
            $scope.isView = false;
        }

        $scope.cancelEdition = function (index) {
            if($scope.isView){
                if($scope.$parent != null){
                    $scope.$parent.navToSection("list", "list");
                }
            }else{
                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Perderá todos los cambios realizados en este formulario.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Si, cancelar!",
                        cancelButtonText: "No, continuar!",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function (isConfirm) {
                        if (isConfirm) {
                            $timeout(function () {
                                if($scope.$parent != null){
                                    $scope.$parent.navToSection("list", "list");
                                }
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        var onLoadRecord = function(currentId){
            // se debe cargar primero la información actual del cliente..

            if (currentId == 0) {
                return;
            }

            var req = {
                id: currentId
            };

            $http({
                method: 'GET',
                url: 'api/customer/safety-inspection-config-list',
                params: req
            })
                .catch(function(e, code){
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () { $state.go(messagered); }, 3000);
                    } else if (code == 404)
                    {
                        SweetAlert.swal("Información no disponible", "Seguimiento no encontrado", "error");
                        $timeout(function () {
                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del seguimiento", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function(){
                        $scope.list = response.data.result;

                        if ($scope.list.dateFrom != null && $scope.list.dateFrom.date != undefined) {
                            $scope.list.dateFrom =  new Date($scope.list.dateFrom.date);
                        }
                    });

                }).finally(function () {
                    $timeout(function(){
                        $scope.loading =  false;
                    }, 400);
                });
        };

        onLoadRecord(currentId);

        $scope.master = $scope.list;

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
                    log.info($scope.customer);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                    //your code for submit
                    log.info($scope.customer);
                    save();
                }

            },
            reset: function (form) {

                $scope.list = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.list);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer/safety-inspection-config-list/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function(){
                    $scope.list = response.data.result;

                    if($scope.$parent != null){
                        $scope.$parent.navToSection("list", "list");
                    }

                });
            }).catch(function(e){
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function(){

            });

        };

        $scope.onAddGroup = function ()
        {
            $timeout(function () {
                if ($scope.list.groups == null) {
                    $scope.list.groups = [];
                }
                $scope.list.groups.push(
                    {
                        id: 0,
                        customerSafetyInspectionHeaderId: currentId,
                        name: "",
                        dataType: null,
                        sort: 0,
                        isActive: true,
                    }
                );
            });
        };

        $scope.onRemoveGroup = function(index)
        {
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
                            var field = $scope.list.groups[index];

                            if (field.id != 0) {
                                var req = {};
                                req.id = field.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/safety-inspection-config-list-group/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");

                                    $scope.list.groups.splice(index, 1);

                                }).catch(function(e){
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function(){

                                });
                            } else {
                                $scope.list.groups.splice(index, 1);
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }


        $scope.onAddExistingControl = function ()
        {
            $timeout(function () {
                if ($scope.list.existingControlList == null) {
                    $scope.list.existingControlList = [];
                }
                $scope.list.existingControlList.push(
                    {
                        id: 0,
                        customerSafetyInspectionHeaderId: currentId,
                        type: "existingControl",
                        description: "",
                        value: 0,
                        isActive: true,
                    }
                );
            });
        };

        $scope.onRemoveExistingControl = function(index)
        {
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
                            var field = $scope.list.existingControlList[index];

                            if (field.id != 0) {
                                var req = {};
                                req.id = field.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/safety-inspection-config-list-validation/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");

                                    $scope.list.existingControlList.splice(index, 1);

                                }).catch(function(e){
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function(){

                                });
                            } else {
                                $scope.list.existingControlList.splice(index, 1);
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        $scope.onAddDangerousness = function ()
        {
            $timeout(function () {
                if ($scope.list.dangerousnessList == null) {
                    $scope.list.dangerousnessList = [];
                }
                $scope.list.dangerousnessList.push(
                    {
                        id: 0,
                        customerSafetyInspectionHeaderId: currentId,
                        type: "dangerousness",
                        description: "",
                        value: 0,
                        isActive: true,
                    }
                );
            });
        };

        $scope.onRemoveDangerousness = function(index)
        {
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
                            var field = $scope.list.dangerousnessList[index];

                            if (field.id != 0) {
                                var req = {};
                                req.id = field.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/safety-inspection-config-list-validation/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");

                                    $scope.list.dangerousnessList.splice(index, 1);

                                }).catch(function(e){
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function(){

                                });
                            } else {
                                $scope.list.dangerousnessList.splice(index, 1);
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        $scope.onAddPriority = function ()
        {
            $timeout(function () {
                if ($scope.list.priorityList == null) {
                    $scope.list.priorityList = [];
                }
                $scope.list.priorityList.push(
                    {
                        id: 0,
                        customerSafetyInspectionHeaderId: currentId,
                        type: "priority",
                        description: "",
                        value: 0,
                        isActive: true,
                    }
                );
            });
        };

        $scope.onRemovePriority = function(index)
        {
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
                            var field = $scope.list.priorityList[index];

                            if (field.id != 0) {
                                var req = {};
                                req.id = field.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/safety-inspection-config-list-validation/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");

                                    $scope.list.priorityList.splice(index, 1);

                                }).catch(function(e){
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function(){

                                });
                            } else {
                                $scope.list.priorityList.splice(index, 1);
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

    }]);
