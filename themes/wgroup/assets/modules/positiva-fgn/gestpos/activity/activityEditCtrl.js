'use strict';
/**
 * controller for activityEditCtrl
 */
app.controller('activityEditCtrl',
    function($rootScope, $stateParams, $scope, $log, $timeout, SweetAlert, $http, $compile, ListService, $state, ModuleListService, $filter) {

        $scope.sectorList = $rootScope.parameters("positiva_fgn_gestpos_sector");
        $scope.programList = $rootScope.parameters("positiva_fgn_gestpos_program");
        $scope.planList = $rootScope.parameters("positiva_fgn_gestpos_plan");
        $scope.actionLineList = $rootScope.parameters("positiva_fgn_gestpos_action_line");
        $scope.activityTypeList = $rootScope.parameters("positiva_fgn_gestpos_activity_type");
        $scope.strategyList = $rootScope.parameters("positiva_fgn_consultant_strategy");

        var initialize = function() {
            $scope.entity = {
                id: $scope.$parent.currentId || 0,
                type: "main",
                task: null,
                isActive: true,
                isAutomatic: false,
                enableAutomatic: true,
                sector: null,
                program: null,
                plan: null,
                actionLine: null,
                consecutive: null,
                activityType: null,
                strategy: []
            }
        }
        initialize();


        $scope.form = {
            submit: function(form) {
                var firstError = null;
                $scope.Form = form;
                if (form.$invalid) {

                    var field = null,
                        firstError = null;
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
            reset: function() {
                $scope.Form.$setPristine(true);
                initialize();
            }
        };


        var save = function() {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/positiva-fgn-gestpos-activity/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                if ($scope.entity.enableAutomatic) {
                    $scope.$emit('realoadAssociatedTask');
                }
                $scope.entity.id = response.data.result.id;
                $stateParams.gestposId = $scope.entity.id;
                $scope.entity.strategy = response.data.result.strategy;
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
            }).catch(function(e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            });

        };


        $scope.onLoadRecord = function() {
            if ($scope.entity.id > 0) {
                var req = {
                    id: $scope.entity.id,
                };
                $http({
                        method: 'GET',
                        url: 'api/positiva-fgn-gestpos-activity/get',
                        params: req
                    })
                    .catch(function(e, code) {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
                    })
                    .then(function(response) {
                        $timeout(function() {
                            $scope.entity = response.data.result;
                        });
                    });
            }
        }
        $scope.onLoadRecord();


        $scope.onBack = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        $scope.onAddStrategy = function() {
            $timeout(function() {
                $scope.entity.strategy.push({
                    id: 0,
                    strategy: "",
                    isActive: true
                });
            });
        };

        $scope.onRemoveStrategy = function(index, id) {

            if (id == 0) {
                $scope.entity.strategy.splice(index, 1);
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
                function(isConfirm) {
                    if (isConfirm) {
                        $timeout(function() {
                            // eliminamos el registro en la posicion seleccionada
                            var contact = $scope.entity.strategy[index];
                            $scope.entity.strategy.splice(index, 1);

                            if (contact.id != 0) {
                                var req = {};
                                req.id = contact.id;
                                req.detail = "strategy";
                                $http({
                                    method: 'POST',
                                    url: 'api/positiva-fgn-gestpos-activity/delete',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    data: $.param(req)
                                }).then(function(response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function(e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function() {


                                });
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        $scope.valideSingle = function(item) {
            var currents = $filter('filter')($scope.entity.strategy, { strategy: item });
            if (currents.length) {
                return true;
            }
            return false;
        }

    });