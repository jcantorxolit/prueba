'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticRiskMatrixSummaryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside', '$filter',
    '$translate', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside, $filter, $translate, ListService) {

        var log = $log;

        var pager = {
            refresh: true,
            index: 0
        };

        $scope.audit = {
            fields: [],
            filters: [],
        };

        $scope.isView = $scope.customer.matrixType != 'G';

        getList();

        function getList() {
            var entities = [
                {name: 'criteria_operators', value: null},
                {name: 'criteria_conditions', value: null},
                {name: 'customer_config_acitivty_hazard_filter_field', value: null}
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.customerAbsenteeismDisabilityFilterField;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $scope.addFilter = function () {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }
            $scope.audit.filters.push(
                {
                    id: 0,
                    field: null,
                    criteria: $scope.criteria.length > 0 ? $scope.criteria[1] : null,
                    condition: $scope.conditions.length > 0 ? $scope.conditions[0] : null,
                    value: ""
                }
            );
        };

        $scope.onFilter = function () {
            $scope.grid.dataSource.read();
        }

        $scope.removeFilter = function (index) {
            $scope.audit.filters.splice(index, 1);
        }

        $scope.onCleanFilter = function () {
            $scope.audit.filters = [];
            $scope.grid.dataSource.read();
        }


        $timeout(function () {

            var kendoGridColumns = function()
            {
                var $columns = [
                    {
                        command: [
                            { text: " ", template: "<a class='btn btn-info btn btn-xs' ng-click='onAddHazard(dataItem)' uib-tooltip='Ver' tooltip-placement='right'><i class='fa fa-eye'></i></a> " },
                            { text: " ", template: "<a class='btn btn-success btn btn-xs' ng-click='onAddHazard(dataItem)' uib-tooltip='Editar' tooltip-placement='right'><i class='fa fa-edit'></i></a> " },
                            { text: " ", template: "<a class='btn btn-dark-azure btn btn-xs' ng-click='onAddHazard(dataItem)' uib-tooltip='Adicionar Nuevo Peligro' tooltip-placement='right'><i class='fa fa-plus-circle'></i></a> " },
                            { text: " ", template: "<a class='btn btn-warning btn btn-xs' ng-click='onConfigHazard(dataItem)' uib-tooltip='Adicionar Peligro Existente' tooltip-placement='right'><i class='fa fa-cog'></i></a> " },
                        ],
                        width: "80px"
                    }
                ];

                $columns.push(buildKendoGridColumn('workPlace', $translate.instant('grid.matrix.WORK-PLACE'), '250px'));
                $columns.push(buildKendoGridColumn('macroProcess', $translate.instant('grid.matrix.MACROPROCESS'), '200px'));
                $columns.push(buildKendoGridColumn('process', $translate.instant('grid.matrix.PROCESS'), '200px'));
                $columns.push(buildKendoGridColumn('job', 'Cargo', '200px'));
                $columns.push(buildKendoGridColumn('activity', $translate.instant('grid.matrix.ACTIVITY'), '200px'));
                $columns.push(buildKendoGridColumn('isRoutine', 'Rutinaria', '200px'));
                $columns.push(buildKendoGridColumn('classification', 'Clasificación', '200px'));
                $columns.push(buildKendoGridColumn('type', 'Tipo Peligro', '200px'));
                $columns.push(buildKendoGridColumn('description', 'Descripción Peligro', '200px'));
                $columns.push(buildKendoGridColumn('effect', 'Efectos a la Salud', '200px'));
                $columns.push(buildKendoGridColumn('timeExposure', 'T. Expuesto', '200px'));
                $columns.push(buildKendoGridColumn('controlMethodSourceText', $translate.instant('grid.matrix.CONTROL-METHOD-SOURCE'), '200px'));
                $columns.push(buildKendoGridColumn('controlMethodMediumText', $translate.instant('grid.matrix.CONTROL-METHOD-MEDIUM'), '200px'));
                $columns.push(buildKendoGridColumn('controlMethodPersonText', $translate.instant('grid.matrix.CONTROL-METHOD-PERSON'), '200px'));
                if ($rootScope.app.instance == 'isa') {
                    $columns.push(buildKendoGridColumn('controlMethodAdministrativeText', 'M. Control Señalización / Control Administrativo', '200px'));
                }
                $columns.push(buildKendoGridColumn('measureND', 'N. Deficiencia', '200px'));
                $columns.push(buildKendoGridColumn('measureNE', 'N. Exposición', '200px'));
                $columns.push(buildKendoGridColumn('measureNC', 'N. Consecuencia', '200px'));
                $columns.push(buildKendoGridColumn('levelP', 'N. Probabilidad', '200px'));
                $columns.push(buildKendoGridColumn('levelIP', 'Interp N. Probabilidad', '200px'));
                $columns.push(buildKendoGridColumn('levelR', 'Nivel Riesgo', '200px'));
                $columns.push(buildKendoGridColumn(null, 'Interp Riesgo', '200px', false, levelIRTemplate));
                $columns.push(buildKendoGridColumn(null, 'Valoracion riesgo', '200px', false, riskValueTemplate));
                $columns.push(buildKendoGridColumn('exposed', 'Trabajadores Vinculados o en Misión', '200px'));
                $columns.push(buildKendoGridColumn('contractors', 'Trabajadores Contratistas', '200px'));
                $columns.push(buildKendoGridColumn('visitors', 'Visitantes', '200px'));
                $columns.push(buildKendoGridColumn('status', 'Verificado', '200px', false, statusTemplate));
                $columns.push(buildKendoGridColumn('reason', 'Motivo', '200px'));

                return $columns;
            };

            var buildKendoGridColumn = function(field, title, width, filterable, templateCallback)
            {
                return {
                    field: field,
                    title: title,
                    width: width,
                    headerAttributes: {
                        class: "text-bold",
                    },
                    filterable: filterable !== undefined ? filterable : false,
                    template: (typeof templateCallback == 'function') ? templateCallback : null
                };
            }

            var levelIRTemplate = function(dataItem) {
                return buildColumnTemplateRisk(dataItem.levelIR, dataItem.levelIR);
            }

            var riskValueTemplate = function(dataItem) {
                return buildColumnTemplateRisk(dataItem.riskValue, dataItem.levelIR);
            }

            var statusTemplate = function(dataItem) {
                var label = '';
                var text = dataItem.status != null ? dataItem.status : '';
                switch (dataItem.status) {
                    case "Denegado":
                        label = 'label label-danger';
                        break;

                    case "Pendiente":
                        label = 'label label-warning';
                        break;

                    case "Aprobado":
                        label = 'label label-success';
                        break;
                }

                return '<span class="' + label + '">' + text + '</span>';
            };

            var buildColumnTemplateRisk = function(text, levelIR) {
                var label = '';
                var text = text != null ? text : '';
                switch (levelIR) {
                    case "I":
                        label = 'label label-danger';
                        break;

                    case "II":
                        label = 'label label-warning';
                        break;

                    case "III":
                        label = 'label label-info';
                        break;

                    case "IV":
                        label = 'label label-success';
                        break;
                }

                return '<span class="' + label + '">' + text + '</span>';;
            }

            $scope.mainGridOptions = {
                dataSource: {
                    type: "odata",
                    transport: {
                        read: {
                            url: "api/customer-config-activity-hazard",
                            dataType: "json",
                            type: "POST",
                            data: function() {

                                var param = {
                                    customerId: $stateParams.customerId
                                };

                                if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                                    param.filter =
                                    {
                                        filters: $.map($scope.audit.filters, function (filter) {
                                            if (filter.field != null) {
                                                return {
                                                    field: filter.field.name,
                                                    operator: filter.criteria.value,
                                                    condition: filter.condition.value,
                                                    value: filter.value
                                                };
                                            }
                                        })
                                    };
                                }

                                return param;
                            }
                        },
                        parameterMap: function (data, operation) {
                            return JSON.stringify(data);
                        }
                    },
                    schema: {
                        model: {
                            id: "id",
                            fields: {}
                        },
                        data: function (result) {
                            return result.data || result;
                        },
                        total: function (result) {
                            return result.recordsTotal || result.data.length || 0;
                        }
                    },
                    pageSize: 10,
                    serverPaging: true,
                    serverFiltering: true,
                    serverSorting: true
                },
                scrollable: true,
                sortable: {
                    mode: "multiple"
                },
                pageable: {
                    change: function (e) {
                        pager.index = e.index;
                        log.info('page.index', pager.index);
                    }
                },
                filterable: false,
                dataBinding: function(e) {
                    $log.info("dataBinding");
                },
                dataBound: function (e) {
                    $log.info("dataBound");

                    //this.expandRow(this.tbody.find("tr.k-master-row"));

                    $scope.grid.tbody.find("tr").each(function () {

                        var model = $scope.grid.dataItem(this);

                        var $canViewEdit = model !== undefined && (model.id != null && model.id != "");
                        var $canViewAdd = model !== undefined  && (model.id == null)  && (model.activityId != null);
                        var $canViewAddExists = model !== undefined  && (model.activityId != null) && (model.hasHazards > 0);

                        if (!$canViewEdit) {
                            $(this).find(".btn-info").remove();
                            $(this).find(".btn-success").remove();
                        } else {
                            if ($scope.isView) {
                                $(this).find(".btn-success").remove();
                            } else {
                                $(this).find(".btn-info").remove();
                            }
                        }

                        if (!$canViewAdd || $scope.isView) {
                            $(this).find(".btn-dark-azure").remove();
                        }

                        if (!$canViewAddExists || $scope.isView) {
                            $(this).find(".btn-warning").remove();
                        }
                    });
                },
                columns: kendoGridColumns()
            };

            $scope.detailGridOptions = function(dataItem) {
                return {
                    dataSource: {
                        type: "odata",
                        transport: {
                            read: {
                                url: "api/customer-config-activity-hazard-intervention",
                                dataType: "json",
                                type: "POST",
                                data: function() {

                                    var param = {
                                        jobActivityHazardId: dataItem.id,
                                    };

                                    return param;
                                }
                            },
                            parameterMap: function (data, operation) {
                                return JSON.stringify(data);
                            }
                        },
                        requestEnd: function(e) {

                        },
                        schema: {
                            model:{
                                id: "id",
                                fields: {}
                            },
                            data: function(result) {
                                return result.data || result;
                            },
                            total: function(result) {
                                return result.recordsTotal || result.data.length || 0;
                            }
                        },
                        serverPaging: true,
                        serverSorting: true,
                        serverFiltering: false,
                        pageSize: 10,
                        filter: { field: "jobActivityHazardId", operator: "eq", value: dataItem.id }
                    },
                    noRecords: true,
                    scrollable: false,
                    sortable: true,
                    pageable: false,
                    dataBound: function (e) {
                        $log.info("dataBound child");
                        this.wrapper.css({
                            width: this.wrapper.closest("div.k-parent")[0].offsetWidth - 90 + 'px'
                        })
                    },
                    columns: [
                        {
                            command: [
                                { text: " ", template: "<a class='btn btn-warning btn btn-xs' ng-click='onAddImprovementPlan(dataItem)' uib-tooltip='Plan Mejoramiento' tooltip-placement='right'><i class='fa fa-plus-square'></i></a> " },
                            ],
                            width: "50px"
                        },
                        buildKendoGridColumn('type', 'Medida de Intervención', '300px'),
                        buildKendoGridColumn('description', 'Descripción Medida de Intervención', '300px'),
                        buildKendoGridColumn('tracking', 'Seguimiento y Medición', '300px'),
                        buildKendoGridColumn('observation', 'Observación', '300px')
                    ]
                };
            };

        });

        $scope.$on("kendoWidgetCreated", function(event, widget) {
            if ($scope.grid === undefined || $scope.grid === null) {
                $scope.grid = widget;
            }
        });

        $scope.onSummaryExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/customer-config-activity-hazard/export?id=" + $stateParams.customerId;
        }

        $scope.onAddHazard = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_config_sgsst_activity_hazard_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideConfigMatrixJobActivityHazardCtrl',
                scope: $scope,
                resolve: {
                    activity: function () {
                        return {
                            activityHazardId: dataItem.id,
                            activityId: dataItem.activityId,
                            jobActivityId: dataItem.jobActivityId
                        };
                    },
                    isView : function() {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.grid.dataSource.read();
            }, function() {
                $scope.grid.dataSource.read();
            });
        };

        $scope.onConfigHazard = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/sgsst/hazard/customer_profile_config_sgsst_job_activity_hazard_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerConfigJobActivityHazardCtrl',
                scope: $scope,
                resolve: {
                    activity: function () {
                        return {
                            id: dataItem.jobActivityId
                        };
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.grid.dataSource.read();
            }, function() {
                $scope.grid.dataSource.read();
            });
        };

        //----------------------------------------------------------------------------IMPROVEMENT PLAN
        $scope.onAddImprovementPlan = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_improvement_plan_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerMatrixDetailImprovementPlanCtrl',
                scope: $scope,
                resolve: {
                    entity: function () {
                        return dataItem;
                    },
                    isView: function () {
                        return $scope.isView;
                    },
                    program: function () {
                        return { code: 'Matri' };
                    }
                }
            });
            modalInstance.result.then(function () {

            }, function() {

            });
        };

    }
]);


app.controller('ModalInstanceSideConfigMatrixJobActivityHazardCtrl', function ($rootScope, $scope, $location, $uibModalInstance, activity, isView, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document, $stateParams, $translate, ListService) {

    var attachmentUploadedId = 0;

    $scope.isView = isView;

    getList();

    function getList() {

        var entities = [
            { name: 'customer_config_activity_hazard_reason', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.reasonList = response.data.data.customer_config_activity_hazard_reason;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    $scope.controlMethods = $rootScope.parameters("config_control_method");
    $scope.typesMeasure = $rootScope.parameters("config_type_measure");
    $scope.trackingList = $rootScope.parameters("hazard_tracking");

    $scope.activity = {
        id: activity.activityId
    };

    var url = $location.absUrl();

    if ($rootScope.app.instance == 'isa') {
        $scope.matrixTabLabel1 = "Grupo Ocupacional o Instalación";
        $scope.matrixTabLabel2 = "Subestación";
        $scope.matrixTabLabel3 = "Ubicación, Sitio o Área";
        $scope.matrixTabLabel4 = "Labor / Tarea";

        $scope.controlMethodSourceLabel = "Eliminación / Sustitución";
        $scope.controlMethodMediumLabel = "Control de Ingeniería";
        $scope.controlMethodAdministrativeLabel = "Señalización / Control Administrativo";
        $scope.controlMethodPersonLabel = "Equipos de Protección Personal";
    } else {
        $scope.matrixTabLabel1 = "Centros de Trabajo";
        $scope.matrixTabLabel2 = "Macroprocesos";
        $scope.matrixTabLabel3 = "Procesos";
        $scope.matrixTabLabel4 = "Actividades";

        $scope.controlMethodSourceLabel = "Fuente";
        $scope.controlMethodMediumLabel = "Medio";
        $scope.controlMethodAdministrativeLabel = "";
        $scope.controlMethodPersonLabel = "Persona";
    }

    var init = function() {
        $scope.hazard = {
            id: activity.activityHazardId ? activity.activityHazardId : 0,
            jobActivityId: $scope.activity.id,
            customerConfigJobActivityId: activity.jobActivityId,
            type: null,
            classification: null,
            description: null,
            health: null,
            exposure: 0,
            controlMethodSourceText: "",
            controlMethodMediumText: "",
            controlMethodPersonText: "",
            measureND: null,
            measureNE: null,
            measureNC: null,
            levelP: null,
            levelIP: null,
            levelR: null,
            riskValue: null,
            riskText: null,
            interventions: [],
            reason: null,
            reasonObservation: null
        };
    }

    init();

    $scope.types = [];

    $scope.onLoadRecordHazard = function () {
        if ($scope.hazard.id != 0) {
        //if ($stateParams.customerId != 0) {

            // se debe cargar primero la información actual del cliente..
            // log.info("editando cliente con código: " + $scope.danger.id);
            var req = {
                id: $scope.hazard.id
            };
            $http({
                method: 'GET',
                url: 'api/customer/config-sgsst/job-activity-hazard/get',
                params: req
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.hazard = response.data.result;
                        $scope.reloadDataReason();
                    });

                }).finally(function () {

                });
        } else {

        }
    };

    $scope.onLoadRecordActivity = function () {
        if ($scope.activity.id != 0) {
            var req = {
                id: $scope.activity.id
            };
            $http({
                method: 'GET',
                url: 'api/customer/config-sgsst/activity/get',
                params: req
            })
                .catch(function (e, code) {
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.activity = response.data.result;
                    });

                }).finally(function () {
                });
        } else {
            $scope.loading = false;
        }
    };

    $scope.onLoadRecordHazard();
    $scope.onLoadRecordActivity();

    var loadList = function () {

        var req = {};
        req.operation = "diagnostic";
        req.customerId = $scope.customerId;

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/wizard/listClassification',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.classifications = response.data.data;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var loadListLevel = function () {

        var req = {};
        req.operation = "diagnostic";
        req.customerId = $scope.customerId;

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/wizard/listLevel',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.measuresND = response.data.data.ND;
                $scope.measuresNE = response.data.data.NE;
                $scope.measuresNC = response.data.data.NC;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    loadList();
    loadListLevel();

    $scope.$watch("hazard.classification", function () {
        //console.log('new result',result);
        if ($scope.hazard.classification != null) {
            var req = {};
            req.operation = "diagnostic";
            req.classificationId = $scope.hazard.classification.id;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/wizard/listType',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.types = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });
        }
    });

    $scope.$watch("hazard.type", function () {
        //console.log('new result',result);
        if ($scope.hazard.classification != null) {
            var req = {};
            req.operation = "diagnostic";
            req.typeId = $scope.hazard.type.id;

            $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/wizard/listDescription',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.descriptions = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });

            $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/wizard/listEffect',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.healthEffects = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });
        }
    });

    var calculateRisk = function () {
        $scope.hazard.riskValue = null;
        $scope.hazard.riskText = null;

        if ($scope.hazard.measureND != null && $scope.hazard.measureNE != null) {
            $scope.hazard.levelP = parseFloat($scope.hazard.measureND.value) * parseFloat($scope.hazard.measureNE.value);

            if ($scope.hazard.levelP > 20) {
                $scope.hazard.levelIP = "Muy Alto";
            } else if ($scope.hazard.levelP >= 10 && $scope.hazard.levelP <= 20) {
                $scope.hazard.levelIP = "Alto";
            } else if ($scope.hazard.levelP >= 6 && $scope.hazard.levelP <= 8) {
                $scope.hazard.levelIP = "Medio";
            } else if ($scope.hazard.levelP >= 1 && $scope.hazard.levelP <= 4) {
                $scope.hazard.levelIP = "Bajo";
            } else {
                $scope.hazard.levelIP = '';
            }

            if ($scope.hazard.measureNC != null) {
                $scope.hazard.levelR = parseFloat($scope.hazard.levelP) * parseFloat($scope.hazard.measureNC.value);

                if ($scope.hazard.levelR >= 600 && $scope.hazard.levelR <= 4000) {
                    $scope.hazard.riskValue = "No Aceptable";
                    $scope.hazard.riskText = "Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente";
                } else if ($scope.hazard.levelR >= 150 && $scope.hazard.levelR <= 500) {
                    $scope.hazard.riskValue = "No Aceptable o Aceptable con control especifico";
                    $scope.hazard.riskText = "Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360";
                } else if ($scope.hazard.levelR >= 40 && $scope.hazard.levelR <= 120) {
                    $scope.hazard.riskValue = "Mejorable";
                    $scope.hazard.riskText = "Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad";
                } else if ($scope.hazard.levelR >= 10 && $scope.hazard.levelR <= 39) {
                    $scope.hazard.riskValue = "Aceptable";
                    $scope.hazard.riskText = "Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable";
                }
            }
        }
    };

    $scope.$watch("hazard.measureND", function () {
        calculateRisk();
    });

    $scope.$watch("hazard.measureNE", function () {
        calculateRisk();
    });

    $scope.$watch("hazard.measureNC", function () {
        calculateRisk();
    });

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {
        init();
        $scope.hazard.id = 0;
    }


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

                $timeout(function () {
                    toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                }, 500);

                return;

            } else {
                $scope.onSave();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.onSave = function () {

        var req = {};
        var data = JSON.stringify($scope.hazard);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/job-activity-hazard/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $scope.onClear();
                $scope.reloadData();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var buildDTColumns = function() {
        var $columns = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.document != null ? data.document.path : "";
                var actions = "";
                var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.customerConfigJobActivityHazardId + '" data-url="' + url + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.customerConfigJobActivityHazardId + '" >' +
                    '   <i class="fa fa-ban"></i></a> ';

                actions += editTemplate;

                actions += deleteTemplate;

                return !$scope.isView ? actions : '';
            })
        ];

        $columns.push(buildDTColumn('classification', 'Clasificación', '', 200));
        $columns.push(buildDTColumn('type', 'Tipo Peligro', '', 200));
        $columns.push(buildDTColumn('description', 'Descripción Peligro', '', 200));
        $columns.push(buildDTColumn('effect', 'Efectos a la salud', '', 200));
        $columns.push(buildDTColumn('timeExposure', 'T Expuesto', '', 200));

        $columns.push(buildDTColumn('controlMethodSourceText', $translate.instant('grid.matrix.CONTROL-METHOD-SOURCE'), '', 200));
        $columns.push(buildDTColumn('controlMethodMediumText', $translate.instant('grid.matrix.CONTROL-METHOD-MEDIUM'), '', 200));
        $columns.push(buildDTColumn('controlMethodPersonText', $translate.instant('grid.matrix.CONTROL-METHOD-PERSON'), '', 200));
        if ($rootScope.app.instance == 'isa') {
            $columns.push(buildDTColumn('controlMethodAdministrativeText', 'M Control Señalización / Control Administrativo', '', 200));
        }
        $columns.push(buildDTColumn('measureND', 'ND', '', 200));
        $columns.push(buildDTColumn('measureNE', 'NE', '', 200));
        $columns.push(buildDTColumn('measureNC', 'NC', '', 200));

        return $columns;
    }

    var buildDTColumn = function(field, title, defaultContent, width) {
        return DTColumnBuilder.newColumn(field)
            .withTitle(title)
            .withOption('defaultContent', defaultContent)
            .withOption('width', width);
    };

    $scope.dtInstanceConfigJobActivityHazard = {};
    $scope.dtOptionsConfigJobActivityHazard = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customerConfigJobActivityId = activity.jobActivityId;
                return JSON.stringify(d);
            },
            url: 'api/customer-config-job-activity-hazard-relation',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {
            //log.info("fnDrawCallback");
            loadRow();
            //Pace.stop();

        })
        /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })

        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {

            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsConfigJobActivityHazard = buildDTColumns();

    var loadRow = function () {

        $("#dtConfigJobActivityHazard a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.hazard.id = id;
            $scope.onLoadRecordHazard();
        });

        $("#dtConfigJobActivityHazard a.delRow").on("click", function () {
            var id = $(this).data("id");

            // Aqui se debe hacer la redireccion al formulario de edicion del customer
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Anularás el anexo seleccionado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, anular!",
                    cancelButtonText: "No, cancelar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {

                    if (isConfirm) {
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/customer/config-sgsst/job-activity-hazard/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function (e) {
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        }).finally(function () {

                            $scope.reloadData();
                        });

                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });

    };

    $scope.reloadData = function () {
        $scope.dtInstanceConfigJobActivityHazard.reloadData();
    };

    $scope.onAddIntervention = function () {

        $timeout(function () {
            if ($scope.hazard.interventions == null) {
                $scope.hazard.interventions = [];
            }
            $scope.hazard.interventions.push(
                {
                    id: 0,
                    hazardId: $scope.hazard.id,
                    type: null,
                    description: ''
                }
            );
        });
    };

    $scope.onRemoveIntervention = function (index) {
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
                        var date = $scope.hazard.interventions[index];

                        if (date.id != 0) {
                            var req = {};
                            req.id = date.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/config-sgsst/job-activity-hazard/intervention/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                $scope.hazard.interventions.splice(index, 1);
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                            });
                        } else {
                            $scope.hazard.interventions.splice(index, 1);
                        }
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    }


    //---------------------------------------------------------------REASONS
    var buildDTReasonColumns = function() {
        var $columns = [];

        $columns.push(buildDTColumn('createdAt', 'Fecha', '', 200));
        $columns.push(buildDTColumn('name', 'Usuario', '', 200));
        $columns.push(buildDTColumn('reason', 'Motivo', '', 200));
        $columns.push(buildDTColumn('reasonObservation', 'Observación', '', 200));

        return $columns;
    }

    $scope.dtOptionsConfigJobActivityHazardReason = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.jobActivityHazardId = $scope.hazard.id;
                return JSON.stringify(d);
            },
            url: 'api/customer-config-activity-hazard-historical-reason',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {

        })
        /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })

        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {

            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsConfigJobActivityHazardReason = buildDTReasonColumns();

    $scope.dtInstanceConfigJobActivityHazardReasonCallback = function (instance) {
        $scope.dtInstanceConfigJobActivityHazardReason = instance;
    };

    $scope.reloadDataReason = function () {
        $scope.dtInstanceConfigJobActivityHazardReason.reloadData();
    };


});


app.controller('ModalInstanceSideCustomerMatrixDetailImprovementPlanCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, entity, program,
                                                                                     $log, $timeout, SweetAlert, isView, $filter, FileUploader,
                                                                                     $http, DTOptionsBuilder, DTColumnBuilder, $compile) {

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.responsibleList = [];

    $scope.typesAlert = $filter('orderBy')($rootScope.parameters("tracking_alert_type"), 'id', false);
    $scope.typesTime = $rootScope.parameters("tracking_alert_timeType");
    $scope.statusAlert = $rootScope.parameters("tracking_alert_status");
    $scope.preferencesAlert = $rootScope.parameters("tracking_alert_preference");

    $scope.typeList = $rootScope.parameters("improvement_plan_type");

    var init = function () {
        $scope.improvement = {
            id: 0,
            customerId: $stateParams.customerId,
            classificationName: program.code,
            classificationId: program.code,
            entityName: 'MT',
            entityId: entity.id,
            type: null,
            endDate: null,
            description: '',
            observation: '',
            responsible: null,
            isRequiresAnalysis: false,
            status: {
                id: 0,
                value: 'CR',
                item: 'Creada'
            },
            trackingList: [],
            alertList: []
        };
    }

    init();

    $scope.onLoadRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id
            };
            $http({
                method: 'GET',
                url: 'api/customer/improvement-plan',
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
                        if (response.data.result != null && response.data.result != '') {
                            $scope.improvement = response.data.result;

                            initializeDates();
                        }
                    }, 400);

                }).finally(function () {

                });
        } else {
            $scope.loading = false;
        }
    }

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {

    }

    var loadList = function () {

        var req = {
            customer_id: $stateParams.customerId
        };

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan/list-data',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.responsibleList = response.data.data.responsible;
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    loadList();

    $scope.master = $scope.improvement;

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

            $scope.improvement = angular.copy($scope.master);
            form.$setPristine(true);

        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.improvement);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                init();
            });
        }).catch(function (e) {

            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.reloadData();
        });
    };

    var initializeDates = function () {
        if ($scope.improvement.endDate != null) {
            $scope.improvement.endDate = new Date($scope.improvement.endDate.date);
        }

        angular.forEach($scope.improvement.trackingList, function (model, key) {
            if (model.startDate != null) {
                model.startDate = new Date(model.startDate.date);
            }
        });
    }

    //----------------------------------------------------------------TRACKING
    $scope.onAddTracking = function () {

        $timeout(function () {
            if ($scope.improvement.trackingList == null) {
                $scope.improvement.trackingList = [];
            }
            $scope.improvement.trackingList.push(
                {
                    id: 0,
                    customerImprovementPlanId: 0,
                    responsible: null,
                    startDate: null,
                }
            );
        });
    };

    $scope.onRemoveTracking = function (index) {
        SweetAlert.swal({
                title: "Está seguro?",
                text: "Desea confirmar la eliminación de este registro ?",
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
                        var date = $scope.improvement.trackingList[index];

                        $scope.improvement.trackingList.splice(index, 1);

                        if (date.id != 0) {
                            var req = {};
                            req.id = date.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/improvement-plan-tracking/delete',
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
    $scope.onAddAlert = function () {

        $timeout(function () {
            if ($scope.improvement.alertList == null) {
                $scope.improvement.alertList = [];
            }
            $scope.improvement.alertList.push(
                {
                    id: 0,
                    customerImprovementPlanId: 0,
                    type: null,
                    preference: null,
                    time: 0,
                    timeType: null,
                    status: null,
                }
            );
        });
    };

    $scope.onRemoveAlert = function (index) {
        SweetAlert.swal({
                title: "Está seguro?",
                text: "Desea confirmar la eliminación de este registro ?",
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
                        var date = $scope.improvement.alertList[index];

                        $scope.improvement.alertList.splice(index, 1);

                        if (date.id != 0) {
                            var req = {};
                            req.id = date.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/improvement-plan-alert/delete',
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


    //----------------------------------------------------------------IMPROVEMENT PLAN LIST
    $scope.dtInstanceImprovementPlan = {};
    $scope.dtOptionsImprovementPlan = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customerId = $scope.improvement.customerId;
                d.entityId = $scope.improvement.entityId;
                d.entityName = $scope.improvement.entityName;

                return JSON.stringify(d);
            },
            url: 'api/customer-improvement-plan-entity',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {
            //log.info("fnDrawCallback");
            loadRow();
            //Pace.stop();

        })
        /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })

        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {

            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsImprovementPlan = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if ($rootScope.can('cliente_plan_mejoramiento_edit')) {
                    actions += editTemplate;
                }

                if ($rootScope.can('cliente_plan_mejoramiento_delete')) {
                    actions += deleteTemplate;
                }

				return !$scope.isView ? actions : null;
            }),
        DTColumnBuilder.newColumn('origin').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('description').withTitle("Hallazgo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('responsibleName').withTitle("Responsable").withOption('defaultContent', ''),
        DTColumnBuilder.newColumn(null).withTitle("Fecha Cierre").withOption('width', 200)
        .renderWith(function (data, type, full, meta) {
            if (typeof data.endDate == 'object' && data.endDate != null) {
                return moment(data.endDate.date).format('DD/MM/YYYY');
            }
            return data.endDate != null ? moment(data.endDate).format('DD/MM/YYYY') : '';
        }),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200).withOption('defaultContent', '')
        .renderWith(function (data, type, full, meta) {
            var label = 'label label-success';
            var text = data.status;

            switch (data.statusCode) {
                case "AB":
                    label = 'label label-info'
                    break;

                case "CO":
                    label = 'label label-success'
                    break;

                case "CA":
                    label = 'label label-danger'
                    break;
            }

            return '<span class="' + label + '">' + text + '</span>';
        })
    ];

    var loadRow = function () {

        $("#dtImprovementPlan a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.onLoadRecord(id);
        });

        $("#dtImprovementPlan a.delRow").on("click", function () {
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
                            url: 'api/customer/improvement-plan/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function (e) {
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        }).finally(function () {

                            $scope.reloadData();
                        });

                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });

    };

    $scope.dtInstanceImprovementPlanCallback = function (dtInstance) {
        $scope.dtInstanceImprovementPlan = dtInstance;
    }

    $scope.reloadData = function () {
        $scope.dtInstanceImprovementPlan.reloadData();
    };

});
