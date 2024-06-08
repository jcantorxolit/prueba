'use strict';
/**
 * controller for Customers
 */
app.controller('customerContractEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar',
    '$document', '$filter', '$aside', 'FileUploader', 'ChartService', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $document, $filter, $aside,
              FileUploader, ChartService, ListService) {

        var log = $log;

        $scope.criteria = {
            year: null
        }

        var pager = {
            refresh: true,
            index: 0
        };

        var editedRow = {
            model: null
        }

        var currentId = $scope.$parent.currentContract;

        $scope.rateList = $rootScope.rates();

        $scope.chart = {
            bar: { options: null },
            doughnut: { options: null },
            programs: { data: null },
            progress: {
                data: null,
                total: 0
            }
        };

        getCharts();

        function getCharts() {
            var $criteria = {
                contractorId: currentId
            };

            var entities = [
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_doughnut_options', criteria: null},
                { name: 'customer_contract', criteria: $criteria }
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.doughnut.options = response.data.data.chartLineOptions;
                    $scope.chart.programs.data = response.data.data.customerContractPeriod;
                    $scope.chart.progress.data = response.data.data.customerContractProgress;
                    $scope.chart.progress.total = response.data.data.customerContractAverage;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();

        function getList() {
            var entities = [
                {name: 'customer_contract_year', value: currentId},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.yearList = response.data.data.customerContractYear;

                    if (!$scope.criteria.year && $scope.yearList.length > 0) {
                        $scope.criteria.year = $scope.yearList[0];
                        if ($scope.grid !== undefined) {
                            $scope.grid.dataSource.read();
                        }
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var refreshOnChange = function() {
            $log.info("refreshOnChange");
            //getList();
            getCharts();
            $scope.grid.dataSource.read();
        }

        $scope.rateOptions = {
            dataSource: $scope.rateList,
            dataTextField: "text",
            dataValueField: "id",
            change: function() {
                //log.info('change event');
            },
            select: function(e) {
                var dataItem = this.dataItem(e.item);
                if (editedRow.model && editedRow.model.rateId != dataItem.id) {
                    editedRow.model.rate = dataItem;
                    $scope.onSave(editedRow.model);
                }
            },
        };

        $timeout(function () {
            $scope.mainGridOptions = {
                dataSource: {
                    type: "odata",
                    transport: {
                        read: {
                            url: "api/customer-contract-detail",
                            dataType: "json",
                            type: "POST",
                            data: function() {

                                var param = {
                                    contractorId: currentId,
                                    year: $scope.criteria.year ? $scope.criteria.year.value : 0
                                };

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
                            fields: {
                                description: {editable: false, nullable: true},
                                documentNumber: {editable: false, nullable: true},
                                businessName: {editable: false, nullable: true},
                                isActive: {editable: false, nullable: true, type: "boolean"},
                            }
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
                sortable: {
                    mode: "multiple"
                },
                pageable: {
                    change: function (e) {
                        pager.index = e.index;
                        log.info('page.index', pager.index);
                    }
                },
                filterable: {
                    mode: "row",
                },
                dataBound: function (e) {
                    /*if ($state.is("app.contract.customer")) {
                        if (pager.refresh) {
                            if ($rootScope.app.grid["customer"] != undefined && $rootScope.app.grid["customer"] != "") {
                                $scope.grid.dataSource.page($rootScope.app.grid["customer"])
                                pager.refresh = false;
                            }
                        }
                    }*/
                    this.expandRow(this.tbody.find("tr.k-master-row"));
                },
                columns: [
                    {
                        field: "description",
                        title: "Periodo",
                        attributes: {
                            class: "text-orange text-large",
                        },
                        filterable: {
                            cell: {
                                operator: "contains",
                                suggestionOperator: "contains",
                            }
                        }
                    }, {
                        field: null,
                        title: "Avance",
                        width: "150px",
                        attributes: {
                            style: "text-align: center",
                        },
                        filterable: false,
                        template: function (dataItem) {
                            var $template = '<h4 class="no-margin">' + dataItem.advance + '%</h4>';
                            $template += '<uib-progressbar value="' + dataItem.advance + '" class="progress-xs no-radius no-margin" type="success"></uib-progressbar>';
                            $template +=  dataItem.answers + ' de ' + dataItem.items;
                            return $template;
                        }
                    }, {
                        field: null,
                        title: "Promedio",
                        width: "150px",
                        attributes: {
                            style: "text-align: center",
                        },
                        filterable: false,
                        template: function (dataItem) {
                            var $template = '<h4 class="no-margin">' + dataItem.average + '%</h4>';
                            $template += '<uib-progressbar value="' + dataItem.total + '" class="progress-xs no-radius no-margin" type="success"></uib-progressbar>';
                            $template +=  'Promedio Total';
                            return $template;
                        }
                    }
                ]
            };

            $scope.detailGridOptions = function(dataItem) {
                return {
                    dataSource: {
                        type: "odata",
                        transport: {
                            read: {
                                url: "api/customer-contract-detail-question",
                                dataType: "json",
                                type: "POST",
                                data: function() {

                                    var param = {
                                        period: dataItem.description,
                                        contractorId: currentId,
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
                                fields: {
                                    description: { editable: false, nullable: true },
                                    article: { editable: false, nullable: true },
                                    rate: { editable: true, defaultValue: { id: 0, text: null, code: null} },
                                }
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
                        filter: { field: "period", operator: "eq", value: dataItem.description }
                    },
                    editable: 'incell',
                    edit: function(e) {
                        if (!e.model.isActive) {
                            e.sender.closeCell();
                        }
                        editedRow.model = e.model;
                    },
                    scrollable: false,
                    sortable: true,
                    pageable: false,
                    columns: [
                        {
                            command: [
                                { text: " ", template: "<a class='btn btn-light-red btn btn-sm' ng-click='onAddComment(dataItem)' uib-tooltip='Comentarios' tooltip-placement='right'><i class='fa fa-comments'></i></a> " },
                                { text: " ", template: "<a class='btn btn-warning btn btn-sm' ng-click='onAddImprovementPlan(dataItem)' uib-tooltip='Plan Mejoramiento' tooltip-placement='right'><i class='fa fa-plus-square'></i></a> " },
                                { text: " ", template: "<a class='btn btn-info btn btn-sm' ng-click='onAddAttachment(dataItem)' uib-tooltip='Anexos' tooltip-placement='right'><i class='fa fa-paperclip'></i></a> " }
                            ], width: "130px",
                            headerAttributes: {
                                style: "display: none"
                            }
                        }, {
                            field: "description",
                            title: "Descripción",
                            headerAttributes: {
                                style: "display: none"
                            }
                        }, {
                            field: "rate",
                            title: "Calificación",
                            width: "220px",
                            // editor: '<input kendo-drop-down-list k-value-primitive="true" k-data-text-field="\'text\'" k-data-text-value="\'id\'" k-on-change="onSelect" k-data-source="rateList" />',
                            editor: '<input kendo-drop-down-list k-value-primitive="true" k-options="rateOptions"  />',
                            template: function(dataItem)
                            {
                                var $defaultText = dataItem.isActive ? '-Seleccionar-' : '<span class="text-light">Deshabilitado</span>';
                                return dataItem.rate ? dataItem.rate.text : $defaultText;
                            },
                            headerAttributes: {
                                style: "display: none"
                            },
                            attributes: {
                                style: "text-align: center;font-weight: bold;",
                                class: 'text-capitalize text-dark',
                            }
                        }, {
                            title: "icon",
                            width: "70px",
                            headerAttributes: {
                                style: "display: none"
                            },
                            template: function(dataItem)
                            {
                                var label = '';
                                switch (dataItem.rateCode) {
                                    case "na":
                                        label = '<i class="fa fa-ban text-yellow text-extra-extra-large"></i>';
                                    break;

                                    case "cp":
                                        label = '<i class="fa fa-minus-circle text-muted text-extra-extra-large"></i>';
                                        break;

                                    case "nc":
                                        label = '<i class="fa fa-circle-o text-red text-extra-extra-large"></i>';
                                        break;

                                    case "c":
                                        label = '<i class="fa fa-check-circle-o text-green text-extra-extra-large"></i>';
                                        break;

                                    default:
                                        label = '<i class="fa fa-question-circle text-orange text-extra-extra-large"></i>';;
                                        break;
                                }

                                return label;
                            },
                            attributes: {
                                style: "text-align: center;font-weight: bold;",
                            }
                        }
                    ]
                };
            };

        });

        $scope.$on("kendoWidgetCreated", function(event, widget) {
            if ($scope.grid === undefined || $scope.grid === null) {
                $scope.grid = widget;
            }
        });


        $scope.onSave = function (question) {

            var req = {};
            var data = JSON.stringify(question);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-contract-detail/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    toaster.pop('success', 'Actualización', 'La información ha sido actualizada satisfactoriamente.');

                    refreshOnChange();
                });

            }).catch(function (e) {
                $log.error(e);
                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
            }).finally(function () {

            });

        };

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", currentId);
            }
        };

        $scope.onSelectYear = function() {
            $scope.grid.dataSource.page(1);
        }

        //----------------------------------------------------------------------------COMMENT
        $scope.onAddComment= function (dataItem) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_sgsst_comment.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_questions_comment_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerContractDetailCommentCtrl',
                scope: $scope,
                resolve: {
                    question: function () {
                        return dataItem;
                    }
                }
            });
            modalInstance.result.then(function () {

            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        };


        //----------------------------------------------------------------------------DOCUMENT
        $scope.onAddAttachment = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/contract/customer_contract_detail_attachment_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerContractDetailAttachmentCtrl',
                scope: $scope,
                resolve: {
                    isView: function () {
                        return $scope.isView;
                    },
                    question: function() {
                        return dataItem;
                    }
                }
            });
            modalInstance.result.then(function () {

            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        };

        //----------------------------------------------------------------------------IMPROVEMENT PLAN
        $scope.onAddImprovementPlan = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_improvement_plan_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerContractDetailImprovementPlanCtrl',
                scope: $scope,
                resolve: {
                    question: function () {
                        return dataItem;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function () {

            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        };


        $scope.onSummaryExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/management/export-excel?management_id=" + $scope.$parent.currentManagement +"&program_id=" + $scope.program_id + "&rate_id=" + $scope.rate_id;
        }

    }
]);

app.controller('ModalInstanceSideCustomerContractDetailCommentCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, question, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document) {

    $scope.isView = !question.isActive;

    var initialize = function() {
        $scope.entity = {
            id: 0,
            customerContractDetailId: question.id,
            comment: '',
        };
    }

    initialize();

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {
        initialize();
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
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-contract-detail-comment/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                toaster.pop('success', 'Operación Exitosa', 'Actualización exitosa.');
                $scope.onClear();
                $scope.reloadData();
            });
        }).catch(function (e) {
            toaster.pop('Error', 'Error inesperado', e);
        }).finally(function () {

        });

    };

    $scope.dtInstanceQuestionComment = {};
    $scope.dtOptionsQuestionComment = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerContractDetailId = question.id

                return JSON.stringify(d);
            },
            url: 'api/customer-contract-detail-comment',
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

    $scope.dtColumnsQuestionComment = [
        DTColumnBuilder.newColumn('comment')
            .withTitle("Comentario"),

        DTColumnBuilder.newColumn('user')
            .withTitle("Usuario")
            .withOption('width', 200)
            .withOption('defaultContent', 200),

        DTColumnBuilder.newColumn('createdAt')
            .withTitle("Fecha")
            .withOption('width', 200)
    ];

    $scope.dtInstanceQuestionCommentCallback = function (instance) {
        $scope.dtInstanceQuestionComment = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceQuestionComment.reloadData();
    };

});

app.controller('ModalInstanceSideCustomerContractDetailImprovementPlanCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, question,
                                                                                         $log, $timeout, SweetAlert, isView, $filter, FileUploader,
                                                                                         $http, DTOptionsBuilder, DTColumnBuilder, $compile) {

    $scope.isView = !question.isActive;

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
            classificationName: question.period,
            classificationId: question.period,
            entityName: 'CT',
            entityId: question.id,
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

app.controller('ModalInstanceSideCustomerContractDetailAttachmentCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, $log, $timeout, SweetAlert, isView, question, $filter, FileUploader, $http, DTColumnBuilder, DTOptionsBuilder, $compile, ListService) {

    var attachmentUploadedId = 0;

    $scope.documentType = $rootScope.parameters("contract_detail_document_type");
    $scope.documentStatus = $rootScope.parameters("customer_document_status");
    $scope.isView = !question.isActive;


    var init = function() {
        $scope.attachment = {
            id: 0,
            customerContractDetailId: question.id,
            type: null,
            classification: null,
            status: $scope.documentStatus ? $scope.documentStatus[0] : null,
            version: 1,
            description: ""
        };
    }

    init();

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {
        init();
    };

    var uploader = $scope.uploader = new FileUploader({
        url: 'api/customer/contract-detail-document/upload',
        formData: [],
        removeAfterUpload: true
    });

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item/*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    // CALLBACKS

    uploader.onWhenAddingFileFailed = function (item/*{File|FileLikeObject}*/, filter, options) {
        console.info('onWhenAddingFileFailed', item, filter, options);
    };
    uploader.onAfterAddingFile = function (fileItem) {
        console.info('onAfterAddingFile', fileItem);
    };
    uploader.onAfterAddingAll = function (addedFileItems) {
        console.info('onAfterAddingAll', addedFileItems);
    };
    uploader.onBeforeUploadItem = function (item) {
        console.info('onBeforeUploadItem', item);
        var formData = {id: attachmentUploadedId};
        item.formData.push(formData);
    };
    uploader.onProgressItem = function (fileItem, progress) {
        console.info('onProgressItem', fileItem, progress);
    };
    uploader.onProgressAll = function (progress) {
        console.info('onProgressAll', progress);
    };
    uploader.onSuccessItem = function (fileItem, response, status, headers) {
        console.info('onSuccessItem', fileItem, response, status, headers);
    };
    uploader.onErrorItem = function (fileItem, response, status, headers) {
        console.info('onErrorItem', fileItem, response, status, headers);
    };
    uploader.onCancelItem = function (fileItem, response, status, headers) {
        console.info('onCancelItem', fileItem, response, status, headers);
    };
    uploader.onCompleteItem = function (fileItem, response, status, headers) {
        console.info('onCompleteItem', fileItem, response, status, headers);
    };
    uploader.onCompleteAll = function () {
        console.info('onCompleteAll');
        $scope.reloadData();
        $scope.onClear();
    };

    $scope.form = {

        submit: function (form) {
            console.log("GUARDANDO...")
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
                save();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.attachment);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-contract-detail-document/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                if (uploader.queue.length > 0) {
                    attachmentUploadedId = response.data.result.id;
                    uploader.uploadAll();
                } else {
                    $scope.reloadData();
                    $scope.onClear();
                }
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.reloadData();
        });

    };

    $scope.dtInstanceContractDocumentAtt = {};
    $scope.dtOptionsContractDocumentAtt = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerContractDetailId = question.id;
                d.statusCode = '2'
                return JSON.stringify(d);
            },
            url: 'api/customer-contract-detail-document',
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

    $scope.dtColumnsContractDocumentAtt = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.documentUrl ? data.documentUrl : '';
                var downloadUrl = "api/customer/contract-detail-document/download?id=" + data.id;
                var actions = "";
                var editTemplate = '<a target="_self" class="btn btn-primary btn-xs downloadDocumentRow lnk" href="' + downloadUrl + '" uib-tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                    '   <i class="fa fa-download"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs openDocumentRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-folder-open-o"></i></a> ';

                if (url != '') {
                    actions += viewTemplate;
                    actions += editTemplate;
                }

                return actions;
            }),
        DTColumnBuilder.newColumn('documentType').withTitle("Tipo de documento").withOption('width', 200),
        DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200),
        DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200),
        DTColumnBuilder.newColumn('createdAt').withTitle("Fecha Creación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                switch (data) {
                    case "Vigente":
                        label = 'label label-success';
                        break;

                    case "Anulado":
                        label = 'label label-danger';
                        break;
                }

                var status = '<span class="' + label + '">' + data + '</span>';

                return status;
            })
    ];

    var loadRow = function () {

        $("#dtContractDocumentAtt a.editRow").on("click", function () {

        });
    };

    $scope.dtInstanceContractDocumentAttCallback = function (instance) {
        $scope.dtInstanceContractDocumentAtt = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceContractDocumentAtt.reloadData();
    };

});
