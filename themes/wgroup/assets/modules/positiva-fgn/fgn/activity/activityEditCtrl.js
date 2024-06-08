'use strict';
/**
 * controller for PFFactivityEditCtrl
 */
app.controller('PFFactivityEditCtrl',
    function($scope, $stateParams, $log, $compile, $state, $rootScope, $timeout, $http, SweetAlert, $document, $filter, $localStorage) {

        var log = $log;
        $scope.axisList = $rootScope.parameters("positiva_fgn_activity_axis");
        $scope.actionList = $rootScope.parameters("positiva_fgn_activity_action");
        $scope.activityTypeList = $rootScope.parameters("positiva_fgn_gestpos_activity_type");
        $scope.strategyList = $rootScope.parameters("positiva_fgn_consultant_strategy");
        $scope.typeList = $rootScope.parameters("positiva_fgn_activity_type");
        $scope.groupList = $rootScope.parameters("positiva_fgn_activity_group");
        $scope.periodicityList = $rootScope.parameters("positiva_fgn_activity_periodicity");
        $scope.isView = $localStorage.isView;

        var initialize = function() {
            $scope.entity = {
                id: $stateParams.activityId || 0,
                configId: $stateParams.configId,
                axis: null,
                action: null,
                code: null,
                name: null,
                goalAnnual: null,
                type: null,
                group: null,
                details: { indicator: [], strategy: [] }
            };
        };
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
                url: 'api/positiva-fgn-fgn-activity/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $scope.entity.id = response.data.result.id;
                $scope.entity.details = response.data.result.details;
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
            }).catch(function(e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message, "error");
            });

        };

        $scope.onLoadRecord = function() {
            if ($scope.entity.id > 0) {
                var req = {
                    id: $scope.entity.id,
                };

                $http({
                        method: 'GET',
                        url: 'api/positiva-fgn-fgn-activity/get',
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

        $scope.onAddStrategy = function() {
            $timeout(function() {
                $scope.entity.details.strategy.push({
                    id: 0,
                    strategy: "",
                    isActive: true
                });
            });
        };

        $scope.onRemoveStrategy = function(index, id) {

            if (id == 0) {
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
                function(isConfirm) {
                    if (isConfirm) {
                        $timeout(function() {
                            // eliminamos el registro en la posicion seleccionada
                            var contact = $scope.entity.details.strategy[index];
                            var req = {};
                            req.id = contact.id;
                            req.detail = "strategy";
                            $http({
                                method: 'POST',
                                url: 'api/positiva-fgn-fgn-activity/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function(response) {
                                $scope.entity.details.strategy.splice(index, 1);
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function(e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", e.data.message, "error");
                            })
                        });
                    }
                });
        }

        $scope.onAddIndicator = function() {
            $timeout(function() {
                $scope.entity.details.indicator.push({
                    id: 0,
                    type: null,
                    periodicity: null,
                    formulation: null,
                    goal: 0
                });
            });
        };


        $scope.onRemoveIndicator = function(index, id) {

            if (id == 0) {
                $scope.entity.details.indicator.splice(index, 1);
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
                            var contact = $scope.entity.details.indicator[index];
                            var req = {};
                            req.id = contact.id;
                            req.detail = "indicator";
                            $http({
                                method: 'POST',
                                url: 'api/positiva-fgn-fgn-activity/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function(response) {
                                $scope.entity.details.indicator.splice(index, 1);
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function(e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", e.data.message, "error");
                            })
                        });
                    }
                });
        }

        $scope.calculateGoal = function(indicator) {
            indicator.goal = 0;
            indicator.goal += indicator.jan ? parseInt(indicator.jan) : 0;
            indicator.goal += indicator.feb ? parseInt(indicator.feb) : 0;
            indicator.goal += indicator.mar ? parseInt(indicator.mar) : 0;
            indicator.goal += indicator.apr ? parseInt(indicator.apr) : 0;
            indicator.goal += indicator.may ? parseInt(indicator.may) : 0;
            indicator.goal += indicator.jun ? parseInt(indicator.jun) : 0;
            indicator.goal += indicator.jul ? parseInt(indicator.jul) : 0;
            indicator.goal += indicator.aug ? parseInt(indicator.aug) : 0;
            indicator.goal += indicator.sep ? parseInt(indicator.sep) : 0;
            indicator.goal += indicator.oct ? parseInt(indicator.oct) : 0;
            indicator.goal += indicator.nov ? parseInt(indicator.nov) : 0;
            indicator.goal += indicator.dec ? parseInt(indicator.dec) : 0;
        }


        $scope.onBack = function() {
            $state.go("app.positiva-fgn.fgn-activity-list", { "configId": $stateParams.configId });
        }

        $scope.valideSingle = function(item) {
            var currents = $filter('filter')($scope.entity.details.strategy, { strategy: item });
            if (currents.length) {
                return true;
            }
            return false;
        }

        $scope.valideSingleIndicator = function(item) {
            var currents = $filter('filter')($scope.entity.details.indicator, { type: item });
            if (currents.length) {
                return true;
            }
            return false;
        }

        $scope.onConfig = function() {
            $state.go("app.positiva-fgn.fgn-activity-config", { "configId": $stateParams.configId, "activityId": $scope.entity.id });
        }

        $scope.onConfigSectional = function() {
            $state.go("app.positiva-fgn.fgn-activity-config-sectional", { "configId": $stateParams.configId, "activityId": $scope.entity.id });;
        }

    });