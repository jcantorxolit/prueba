'use strict';
/**
 * controller for PFFconfigConsultantEditCtrl
 */
app.controller('PFFconfigConsultantEditCtrl',
    function ($scope, $rootScope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, $localStorage, $compile, $state, $timeout, $uibModalInstance, $http, SweetAlert, ModuleListService) {

        var log = $log;
        $scope.activityList = [];
        $scope.taskList = [];
        $scope.strategyList = [];
        $scope.modalityList = [];
        $scope.consultantList = [];
        $scope.initialEntity = {};
        $scope.isView = $localStorage.isView;

        var initialize = function () {
            $scope.entity = {
                id: 0,
                sectionalRelationId: $scope.sectionalRelationId,
                axis: null,
                action: null,
                code: null,
                name: null,
                goalCoverage: 0,
                goalCompliance: 0,
                assignmentCoverage: 0,
                assignmentCompliance: 0,
                regional: null,
                sectional: null,
                activity: null,
                gestposTask: null,
                modality: null,
                executionType: null,
                activityConfigId: 0,
                isOccasional: false,
                consultant: null,
                details: {indicator: [], strategy: []}
            };
        };
        initialize();

        function getParams() {
            var entities = [
                {name: 'strategy_list', value: $scope.sectionalRelationId},
                {name: 'consultant_list', value: $scope.sectionalRelationId}
            ];

            ModuleListService.getDataList("/positiva-fgn-fgn-activity-config-consultant/config", entities)
                .then(function (response) {
                    $scope.strategyList = response.data.result.strategyList;
                    $scope.consultantList = response.data.result.consultantList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getParams();

        $scope.filterActivity = function () {
            $scope.entity.activity = null;
            $scope.entity.gestposTask = null;
            $scope.entity.modality = null;
            $scope.entity.executionType = null;
            $scope.entity.activityConfigId = 0;
            var entities = [
                {name: 'activity_list', value: $scope.sectionalRelationId, strategy: $scope.entity.strategy.value},
            ];

            ModuleListService.getDataList("/positiva-fgn-fgn-activity-config-consultant/config", entities)
                .then(function (response) {
                    $scope.activityList = response.data.result.activityList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.filterTask = function () {
            $scope.entity.gestposTask = null;
            $scope.entity.modality = null;
            $scope.entity.executionType = null;
            $scope.entity.activityConfigId = 0;

            var entities = [
                {
                    name: 'task_list',
                    value: $scope.sectionalRelationId,
                    activity: $scope.entity.activity.value,
                    strategy: $scope.entity.strategy.value
                },
            ];

            ModuleListService.getDataList("/positiva-fgn-fgn-activity-config-consultant/config", entities)
                .then(function (response) {
                    $scope.taskList = response.data.result.taskList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.filterModality = function () {
            var entities = [{
                name: 'modality_list',
                value: $scope.sectionalRelationId,
                activity: $scope.entity.activity.value,
                task: $scope.entity.gestposTask.value,
                strategy: $scope.entity.strategy.value
            },];

            ModuleListService.getDataList("/positiva-fgn-fgn-activity-config-consultant/config", entities)
                .then(function (response) {
                    $scope.modalityList = response.data.result.modalityList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.getConfig = function () {
            var entities = [{
                name: 'activity_config',
                value: $scope.sectionalRelationId,
                strategy: $scope.entity.strategy.value,
                activity: $scope.entity.activity.value,
                task: $scope.entity.gestposTask.value,
                modality: $scope.entity.modality.value
            },];

            ModuleListService.getDataList("/positiva-fgn-fgn-activity-config-consultant/config", entities)
                .then(function (response) {
                    $scope.entity.executionType = response.data.result.executionType;
                    $scope.entity.activityConfigId = response.data.result.activityConfigId;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.form = {
            submit: function (form) {
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
            reset: function () {
                $scope.Form.$setPristine(true);
                $scope.entity.id = 0;
                $scope.entity.strategy = null;
                $scope.entity.activity = null;
                $scope.entity.gestposTask = null;
                $scope.entity.modality = null;
                $scope.entity.executionType = null;
                $scope.entity.activityConfigId = 0;
                $scope.entity.consultant = null;
                $scope.entity.isOccasional = false;
                $scope.entity.details = angular.copy($scope.initialEntity);
            }
        };


        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/positiva-fgn-fgn-activity-config-consultant/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
                $scope.entity.assignmentCompliance = response.data.result.assignmentCompliance;
                $scope.entity.assignmentCoverage = response.data.result.assignmentCoverage;
                $scope.form.reset();
                $scope.reloadData();
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message, "error");
            });

        };

        $scope.onLoadRecordActivity = function () {
            var req = {
                id: $stateParams.activityId,
            };
            $http({
                method: 'GET',
                url: 'api/positiva-fgn-fgn-activity/getClear',
                params: req
            })
                .catch(function (e, code) {
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
                })
                .then(function (response) {
                    $timeout(function () {
                        $scope.entity.axis = response.data.result.axis;
                        $scope.entity.action = response.data.result.action;
                        $scope.entity.code = response.data.result.code;
                        $scope.entity.name = response.data.result.name;
                    });
                });
        }
        $scope.onLoadRecordActivity();

        $scope.onLoadRecordSectional = function () {
            var req = {
                id: $scope.sectionalRelationId
            };
            $http({
                method: 'GET',
                url: 'api/positiva-fgn-fgn-activity-config-sectional/getClear',
                params: req
            })
                .catch(function (e, code) {
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
                })
                .then(function (response) {
                    $timeout(function () {
                        $scope.entity.regional = response.data.result.regional;
                        $scope.entity.sectional = response.data.result.sectional;
                        $scope.entity.details = response.data.result.details;
                        $scope.entity.goalCoverage = response.data.result.goalCoverage;
                        $scope.entity.goalCompliance = response.data.result.goalCompliance;
                        $scope.entity.assignmentCoverage = response.data.result.assignmentCoverage;
                        $scope.entity.assignmentCompliance = response.data.result.assignmentCompliance;
                        $scope.initialEntity = angular.copy(response.data.result.details);
                    });
                });
        }
        $scope.onLoadRecordSectional();

        $scope.calculateGoal = function (indicator) {
            indicator.goal = 0;

            if (parseInt(indicator.jan) > 0) {
                indicator.goal += parseInt(indicator.jan);
            }
            if (parseInt(indicator.feb) > 0) {
                indicator.goal += parseInt(indicator.feb);
            }
            if (parseInt(indicator.mar) > 0) {
                indicator.goal += parseInt(indicator.mar);
            }
            if (parseInt(indicator.apr) > 0) {
                indicator.goal += parseInt(indicator.apr);
            }
            if (parseInt(indicator.may) > 0) {
                indicator.goal += parseInt(indicator.may);
            }
            if (parseInt(indicator.jun) > 0) {
                indicator.goal += parseInt(indicator.jun);
            }
            if (parseInt(indicator.jul) > 0) {
                indicator.goal += parseInt(indicator.jul);
            }
            if (parseInt(indicator.aug) > 0) {
                indicator.goal += parseInt(indicator.aug);
            }
            if (parseInt(indicator.sep) > 0) {
                indicator.goal += parseInt(indicator.sep);
            }
            if (parseInt(indicator.oct) > 0) {
                indicator.goal += parseInt(indicator.oct);
            }
            if (parseInt(indicator.nov) > 0) {
                indicator.goal += parseInt(indicator.nov);
            }
            if (parseInt(indicator.dec) > 0) {
                indicator.goal += parseInt(indicator.dec);
            }
        }

        $scope.onClose = function () {
            $uibModalInstance.close(1);
        }

        $scope.onCancel = function () {
            $scope.form.reset();
        }

        var storeDatatable = 'configConsultantEdit-' + window.currentUser.id;
        $scope.dtInstanceActivityConfigSectional = {};
        $scope.dtOptionsActivityConfigSectional = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.sectionalRelationId = $scope.sectionalRelationId;
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-activity-config-consultant',
                type: 'POST',
                beforeSend: function () {
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function (settings, data) {
                $localStorage[storeDatatable] = data;
            })
            .withOption('stateLoadCallback', function () {
                return $localStorage[storeDatatable];
            })
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);

            });

        $scope.dtColumnsActivityConfigSectional = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
                .renderWith(function (data) {
                    var actions = "";
                    var disabled = ""

                    if ($rootScope.isView) {
                        var edit = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Ver"  data-id="' + data.id + '"' + disabled + ' >' +
                            '   <i class="fa fa-eye"></i></a> ';
                    } else {
                        var edit = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                            '   <i class="fa fa-edit"></i></a> ';
                    }

                    if (!$rootScope.isView) {
                        var drop = '<a class="btn btn-danger btn-xs deleteSectional lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                            '   <i class="fa fa-trash"></i></a> ';
                    }

                    actions += edit;
                    actions += drop || '';
                    return actions;
                }),

            DTColumnBuilder.newColumn('strategy').withTitle("Estrategia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activity').withTitle("Actividad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('task').withTitle("Tarea").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('consultant').withTitle("Asesor").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('consultantType').withTitle("Tipo Asesor").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('providesCoverage').withTitle("Aporta Cob.").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCoverage').withTitle("Meta Cob.").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('providesCompliance').withTitle("Aporta Cump.").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCompliance').withTitle("Meta Cump.").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRow = function () {

            $("#dtActivityConfigSectionalConsultant a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onEditConfig(id);
            });

            $("#dtActivityConfigSectionalConsultant a.deleteSectional").on("click", function () {
                var id = $(this).data("id");
                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminará el registro seleccionado.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Si, eliminar!",
                        cancelButtonText: "No, continuar!",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function (isConfirm) {
                        if (isConfirm) {
                            var req = {};
                            req.id = id;
                            $http({
                                method: 'POST',
                                url: 'api/positiva-fgn-fgn-activity-config-consultant/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
                                $scope.entity.assignmentCompliance = response.data.result.assignmentCompliance;
                                $scope.entity.assignmentCoverage = response.data.result.assignmentCoverage;
                                SweetAlert.swal("Proceso Exitoso", "Se ha eliminado correctamente el registro.", "success");
                            }).catch(function (e) {
                                SweetAlert.swal("Error en la eliminación", e.data.message, "error");
                            }).finally(function () {
                                $scope.reloadData();
                                $scope.onLoadRecord();
                            });

                        }
                    });
            });

            $("#dtActivityConfigSectional a.configUser").on("click", function () {
                var id = $(this).data("id");
                $state.go("app.positiva-fgn.fgn-activity-config-sectional", {
                    "configId": $stateParams.configId,
                    "activityId": id
                });
            });
        };

        $scope.reloadData = function () {
            $scope.dtInstanceActivityConfigSectional.reloadData();
        };


        $scope.onEditConfig = function (id) {
            var req = {
                id: id,
            };
            $http({
                method: 'GET',
                url: 'api/positiva-fgn-fgn-activity-config-consultant/get',
                params: req
            })
                .catch(function (e, code) {
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
                })
                .then(function (response) {
                    $timeout(function () {
                        $scope.entity.id = response.data.result.id;
                        $scope.entity.strategy = response.data.result.strategy;
                        $scope.entity.activity = response.data.result.activity;
                        $scope.entity.gestposTask = response.data.result.gestposTask;
                        $scope.entity.modality = response.data.result.modality;
                        $scope.entity.executionType = response.data.result.executionType;
                        $scope.entity.activityConfigId = response.data.result.activityConfigId;
                        $scope.entity.isOccasional = response.data.result.isOccasional;
                        $scope.entity.consultant = response.data.result.consultant;
                        $scope.entity.details.indicator = response.data.result.details.indicator;

                        $scope.refreshConfigToEdit();
                    });
                });
        }


        $scope.refreshConfigToEdit = function () {
            var entities = [
                {name: 'activity_list', value: $scope.sectionalRelationId, strategy: $scope.entity.strategy.value},
                {
                    name: 'task_list',
                    value: $scope.sectionalRelationId,
                    activity: $scope.entity.activity.value,
                    strategy: $scope.entity.strategy.value
                },
                {
                    name: 'modality_list',
                    value: $scope.sectionalRelationId,
                    activity: $scope.entity.activity.value,
                    task: $scope.entity.gestposTask.value,
                    strategy: $scope.entity.strategy.value
                },
                {
                    name: 'activity_config',
                    value: $scope.sectionalRelationId,
                    strategy: $scope.entity.strategy.value,
                    activity: $scope.entity.activity.value,
                    task: $scope.entity.gestposTask.value,
                    modality: $scope.entity.modality.value
                }
            ];

            ModuleListService.getDataList("/positiva-fgn-fgn-activity-config-consultant/config", entities)
                .then(function (response) {
                    $scope.activityList = response.data.result.activityList;
                    $scope.taskList = response.data.result.taskList;
                    $scope.modalityList = response.data.result.modalityList;

                    $scope.entity.executionType = response.data.result.executionType;
                    $scope.entity.activityConfigId = response.data.result.activityConfigId;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        };


    });