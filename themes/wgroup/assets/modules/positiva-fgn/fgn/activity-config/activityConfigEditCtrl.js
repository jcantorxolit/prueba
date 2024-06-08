'use strict';
/**
 * controller for PFFactivityConfigEditCtrl
 */
app.controller('PFFactivityConfigEditCtrl',
    function($scope, $stateParams, $log, $compile, $state, $rootScope, $timeout, $http, SweetAlert, $document, $aside, $localStorage) {

        var log = $log;
        $scope.modalityList = $rootScope.parameters("positiva_fgn_activity_modality");
        $scope.executionTypeList = $rootScope.parameters("positiva_fgn_activity_execution_type");
        $scope.isView = $localStorage.isView;

        $scope.details = [];
        var initialize = function() {
            $scope.entity = {
                id: $stateParams.activityId,
                axis: null,
                action: null,
                code: null,
                name: null,
                hasCoverage: false,
                hasCompliance: false,
                details: { indicator: [], strategy: [] }
            };

            $scope.gestpos = {
                id: 0,
                fgnActivityId: $stateParams.activityId,
                strategy: null,
                modality: null,
                executionType: null,
                activity: null,
                task: null,
                providesCoverage: false,
                providesCompliance: false
            }

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
            var data = JSON.stringify($scope.gestpos);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/positiva-fgn-fgn-activity-config/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $scope.gestpos.activity = null;
                $scope.gestpos.task = null;
                $scope.gestpos.strategy = null;
                $scope.gestpos.modality = null;
                $scope.gestpos.executionType = null;
                $scope.grid.dataSource.read();
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

        $scope.onBack = function() {
            $state.go("app.positiva-fgn.fgn-activity-list", { "configId": $stateParams.configId });
        }

        $scope.onCancel = function() {
            $scope.Form = {
                strategy: '',
                modality: '',
                executionType: '',
                activityName: '',
                taskName: '',
            };
            $scope.gestpos = {
                id: 0,
                fgnActivityId: $stateParams.activityId,
                providesCoverage: '',
                providesCompliance: '',
            }
        }

        $scope.onAddActivity = function() {
            if (!$scope.gestpos.strategy) {
                SweetAlert.swal("Valor requerido", "Debe seleccionar una estrategia para abrir la ventana de actividades.", "warning");
                return;
            }
            $scope.strategy = $scope.gestpos.strategy.strategy.value;
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/fgn/activity-config/activity_task_list.htm",
                placement: 'right',
                backdrop: 'static',
                size: 'lg',
                controller: 'PFFactivityTaskListCtrl',
                scope: $scope
            });
            modalInstance.result.then(function(params) {
                $scope.gestpos.activity = params.activity;
                $scope.gestpos.task = params.task;

                console.log("response", params)

            });
        }

        $timeout(function() {
            $scope.mainGridOptions = {
                dataSource: {
                    type: "odata",
                    transport: {
                        read: {
                            url: "api/positiva-fgn-fgn-activity-config",
                            dataType: "json",
                            type: "POST",
                            data: function() {

                                var param = {
                                    fgnActivityId: $stateParams.activityId,
                                    sizeOf: $stateParams.activityId
                                };

                                return param;
                            }
                        },
                        parameterMap: function(data) {
                            return JSON.stringify(data);
                        }
                    },
                    schema: {
                        model: {
                            id: "id"
                        },
                        data: function(result) {
                            return result.data || result;
                        },
                        total: function(result) {
                            return result.recordsTotal || result.data.length || 0;
                        }
                    },
                    pageSize: 10,
                    serverPaging: true,
                    serverFiltering: true,
                    serverSorting: true
                },
                sortable: {
                    mode: "multiple"
                },
                pageable: {
                    change: function(e) {
                        pager.index = e.index;
                        log.info('page.index', pager.index);
                    }
                },
                filterable: {
                    mode: "row",
                },
                columns: [{
                        field: null,
                        title: "Acciones",
                        filterable: false,
                        command: [
                            { text: " ", template: "<a class='btn btn-warning btn btn-xs' ng-click='onEditConfig(dataItem)' uib-tooltip='Editar' tooltip-placement='right'><i class='fa fa-pencil'></i></a> " },
                            { text: " ", template: "<a class='btn btn-light-red btn btn-xs' ng-click='onRemoveConfig(dataItem)' uib-tooltip='Eliminar' tooltip-placement='right'><i class='fa fa-trash'></i></a> " }
                        ],
                        width: "80px"
                    },
                    {
                        field: "strategy",
                        title: "Estrategia",
                        filterable: false
                    },
                    {
                        field: "modality",
                        title: "Modalidad",
                        filterable: false
                    },
                    {
                        field: "executionType",
                        title: "Tipo Ejecución",
                        filterable: false
                    },
                    {
                        field: "activity",
                        title: "Actividad",
                        filterable: false
                    },
                    {
                        field: "task",
                        title: "Tarea",
                        filterable: false
                    }
                ]
            };

            $scope.detailGridOptions = function(dataItem) {
                return {
                    dataSource: {
                        type: "odata",
                        transport: {
                            read: {
                                url: "api/positiva-fgn-fgn-activity-config-subtask",
                                dataType: "json",
                                type: "POST",
                                data: function() {

                                    var param = {
                                        activityConfigId: dataItem.id,
                                        sizeOf: $stateParams.activityId
                                    };

                                    return param;
                                }
                            },
                            parameterMap: function(data, operation) {
                                return JSON.stringify(data);
                            }
                        },
                        requestEnd: function(e) {

                        },
                        schema: {
                            model: {
                                id: "id"
                            },
                            data: function(result) {
                                return result.data || result;
                            },
                            total: function(result) {
                                return result.recordsTotal || result.data.length || 0;
                            }
                        },
                        serverPaging: false,
                        serverSorting: true,
                        serverFiltering: false,
                        filter: false
                    },
                    editable: 'incell',
                    edit: function(e) {
                        editedRow.model = e.model;
                    },
                    scrollable: false,
                    sortable: true,
                    pageable: false,
                    columns: [{
                        title: "Acciones",
                        command: [
                            { text: " ", template: "<a class='btn btn-blue btn btn-xs' ng-click='onConfigSubtask(dataItem)' uib-tooltip='Configurar' tooltip-placement='right'><i class='fa fa-cog'></i></a> " },
                        ],
                        width: "80px",
                    }, {
                        field: "subtask",
                        title: "Tarea"
                    }, {
                        field: "executionType",
                        title: "Tipo Ejecución"
                    }, {
                        field: "providesCoverage",
                        title: "Aportar Cobertura"
                    }, {
                        field: "providesCompliance",
                        title: "Aporta Cumplimiento"
                    }]
                };
            };

        });

        $scope.$on("kendoWidgetCreated", function(widget) {
            if ($scope.grid === undefined || $scope.grid === null) {
                $scope.grid = widget;
            }
        });

        $scope.onRemoveConfig = function(index) {
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
                            var req = {};
                            req.id = index.id;
                            $http({
                                method: 'POST',
                                url: 'api/positiva-fgn-fgn-activity-config/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function(response) {
                                $scope.grid.dataSource.read();
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function(e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", e.data.message, "error");
                            })
                        });
                    }
                });
        }

        $scope.onEditConfig = function(item) {
            var req = {
                id: item.id,
            };
            $http({
                    method: 'GET',
                    url: 'api/positiva-fgn-fgn-activity-config/get',
                    params: req
                })
                .catch(function(e, code) {
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
                })
                .then(function(response) {
                    $timeout(function() {
                        $scope.gestpos = response.data.result;
                    });
                });
        }

        $scope.onConfigSubtask = function(item) {
            $scope.configSubtaskId = item.id;
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/fgn/activity-config/activity_subtask_edit.htm",
                placement: 'right',
                backdrop: 'static',
                size: 'md',
                controller: 'PFFactivitySubtaskEditCtrl',
                scope: $scope
            });
            modalInstance.result.then(function() {
                $scope.grid.dataSource.read();
            });
        }

        $scope.onEdit = function() {
            $rootScope.isView = false;
            $state.go("app.positiva-fgn.fgn-activity-edit", { "configId": $stateParams.configId, "activityId": $scope.entity.id });
        }

        $scope.onConfigSectional = function() {
            $state.go("app.positiva-fgn.fgn-activity-config-sectional", { "configId": $stateParams.configId, "activityId": $scope.entity.id });
        }

    });

app.controller('PFFactivitySubtaskEditCtrl', function($rootScope, $stateParams, $scope, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder,
    DTColumnDefBuilder, $log, $timeout, SweetAlert, $http, $compile, ListService) {

    $scope.executionTypeList = $rootScope.parameters("positiva_fgn_activity_execution_type");
    var initialize = function() {
        $scope.entity = {
            id: $scope.configSubtaskId,
            axis: null,
            action: null,
            code: null,
            name: null,
            mainTask: null,
            dependenTask: null,
            hasCoverage: false,
            hasCompliance: false,
            providesCoverage: false,
            providesCompliance: false,
            executionType: null
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
            url: 'api/positiva-fgn-fgn-activity-config/saveSubtask',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
            $uibModalInstance.close(1);
        }).catch(function(e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", e.data.message, "error");
        });

    };

    $scope.onLoadRecord = function() {
        if ($scope.entity.id > 0) {
            var req = {
                id: $scope.entity.id,
                activityId: $stateParams.activityId
            };
            $http({
                    method: 'GET',
                    url: 'api/positiva-fgn-fgn-activity-config/getSubtask',
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

    $scope.onCloseModal = function() {
        $uibModalInstance.dismiss('cancel');
    }

});
