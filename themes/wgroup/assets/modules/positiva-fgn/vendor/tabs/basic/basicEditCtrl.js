'use strict';
/**
  * controller for Customers
*/
app.controller('vendorEditCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
          $rootScope,$timeout, $http, SweetAlert, $aside, $document,  $filter) {

    var log = $log;
    $scope.entity = {};
    $scope.documentTypeList = $rootScope.parameters("employee_document_type");
    $scope.contactInformationList = $rootScope.parameters("extrainfo");
    $scope.strategyList = $rootScope.parameters("positiva_fgn_consultant_strategy");
    $scope.townList = [];
    $scope.departmentList = [];

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
    };

    var initialize = function() {
        $scope.entity = {
            id: $stateParams.vendorId || 0,
            isActive: null,
            documentType: null,
            documentNumber: null,
            name: null,
            legalRepresentative: null,
            department: null,
            town: null,
            telephone: null,
            email: null,
            details: { contact: [], strategy: []}
        };

    };
    initialize();

    $scope.form = {
        submit: function (form) {
            var firstError = null;
            $scope.Form = form;
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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
                return;
            } else {
                save();
            }
        },
        reset: function () {
            $scope.Form.$setPristine(true);
            initialize();
        }
    };


    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/positiva-fgn-vendor/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
            if($scope.entity.id == 0){
                $timeout(function () {
                    $state.go("app.positiva-fgn.vendor-edit", { "vendorId": response.data.result.id});
                }, 1000)
            }
            $scope.entity.details = response.data.result.details;
        }).catch(function(e){
            $log.error(e);
            SweetAlert.swal("Error de guardado", e.data.message , "error");
        });

    };

    $scope.getDepartments = function () {
        var req = {
            cid: 68
        };
        $http({
            method: 'GET',
            url: 'api/states',
            params: req
        }).then(function (response) {
            $scope.departmentList = response.data.result;
        });
    };
    $scope.getDepartments();

    $scope.changeDepartment = function (item, refreshTown) {
        $scope.townList = [];

        if(refreshTown){
            $scope.entity.town = null;
        }

        var req = {
            sid: item.id
        };

        $http({
            method: 'GET',
            url: 'api/towns',
            params: req
        }).then(function (response) {
            $scope.townList = response.data.result;
        });

    };

    $scope.onLoadRecord = function (){
        if($scope.entity.id > 0) {
            var req = {
                id: $scope.entity.id,
            };

            $http({
                method: 'GET',
                url: 'api/positiva-fgn-vendor/get',
                params: req
            })
            .catch(function(e, code){
                SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
            })
            .then(function (response) {
                $timeout(function(){
                    $scope.entity = response.data.result;
                    $scope.changeDepartment($scope.entity.department, false)
                });
            });
        }
    }
    $scope.onLoadRecord();

    $scope.onAddInfoDetailContact = function () {
        $timeout(function () {
            $scope.entity.details.contact.push({
                id: 0,
                value: "",
                type: null
            });
        });
    };

    $scope.onRemoveInfoDetailContact = function (index, id) {

        if(id == 0) {
            $scope.entity.details.contact.splice(index, 1);
            return;
        }

        SweetAlert.swal({
            title: "Está seguro?",
            text: "Eliminará el registro seleccionado",
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
                        var contact = $scope.entity.details.contact[index];
                        $scope.entity.details.contact.splice(index, 1);

                        if (contact.id != 0) {
                            var req = {};
                            req.id = contact.id;
                            req.detail = "contact";
                            $http({
                                method: 'POST',
                                url: 'api/positiva-fgn-vendor-detail/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

    $scope.onAddInfoDetailStrategy = function () {
        $timeout(function () {
            $scope.entity.details.strategy.push({
                id: 0,
                strategy: "",
                isActive: true
            });
        });
    };

    $scope.onRemoveInfoDetailStrategy = function (index, id) {

        if(id == 0) {
            $scope.entity.details.strategy.splice(index, 1);
            return;
        }

        SweetAlert.swal({
            title: "Está seguro?",
            text: "Eliminará el registro seleccionado",
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
                        var contact = $scope.entity.details.strategy[index];
                        $scope.entity.details.strategy.splice(index, 1);

                        if (contact.id != 0) {
                            var req = {};
                            req.id = contact.id;
                            req.detail = "strategy";
                            $http({
                                method: 'POST',
                                url: 'api/positiva-fgn-vendor-detail/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

    $scope.onBack = function() {
        $state.go("app.positiva-fgn.vendor-list");
    }

    $scope.valideSingle = function(item) {
        var currents = $filter('filter')($scope.entity.details.strategy, { strategy: item});
        if(currents.length) {
            return true;
        }
        return false;
    }

});
