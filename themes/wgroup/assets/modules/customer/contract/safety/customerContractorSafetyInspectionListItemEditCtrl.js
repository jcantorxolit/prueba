'use strict';
/**
 * controller for Customers
 */
app.controller('customerContractorSafetyInspectionListItemEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar',
    '$document', '$filter', '$aside', 'FileUploader', 'ListService', 'ngNotify',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $document, $filter, $aside,
              FileUploader, ListService, ngNotify) {

        $scope.currentStep = 0;

        $scope.criteria = {
            period: null,
            list: null
        }

        $scope.actions = $rootScope.parameters("wg_safety_inspection_action");
        $scope.dangerousnessList = [];
        $scope.existingControlList = [];

        var pager = {
            refresh: true,
            index: 0
        };

        var currentList = {
            id: 0,
            code: null
        };

        var editedRow = {
            model: null
        }

        var currentId = $scope.$parent.currentContract;

        getList();

        function getList() {
            var entities = [
                {name: 'customer_contract_safety_inspection_list', value: currentId},
                {name: 'customer_contract_safety_inspection_header_fields', value: currentId},
                {name: 'customer_contract_safety_inspection_period', value: currentId},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.lists = response.data.data.customerContractSafetyInspectionList;
                    $scope.headerFields = response.data.data.customerContractSafetyInspectionHeaderFields;
                    $scope.periodList = response.data.data.customerContractSafetyInspectionPeriod;

                    if (!$scope.criteria.period && $scope.periodList.length > 0) {
                        $scope.criteria.period = $scope.periodList[0];
                    }

                    if (!$scope.criteria.list && $scope.lists.length > 0) {
                        $scope.criteria.list = $scope.lists[0];
                    }

                    initializeDates();

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getHeaderFields() {
            var entities = [
                {name: 'customer_contract_safety_inspection_header_fields', value: currentId},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.headerFields = response.data.data.customerContractSafetyInspectionHeaderFields;
                    initializeDates();
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.actionOptions = {
            dataSource: $scope.actions,
            dataTextField: "item",
            dataValueField: "value",
            select: function(e) {
                var dataItem = this.dataItem(e.item);
                $timeout(function () {
                    editedRow.model.set("action", dataItem);
                    editedRow.model.set("actionValue", dataItem.value);
                    displayNotify();
                });

            },
        };

        var $isNotifyVisible = false;

        var displayNotify = function() {
            if (!$isNotifyVisible) {
                ngNotify.set('<strong>Recuerde guardar los cambios.</strong>', {
                    position: 'bottom',
                    sticky: false,
                    button: false,
                    duration: 6000,
                    html: true,
                    type: 'info'
                }, function() {
                    $isNotifyVisible = false;
                    $log.info('Callback triggered after message fades.');
                });

                $isNotifyVisible = true;
            }
        };

        var initializeDates = function() {
            angular.forEach($scope.headerFields, function(field, key) {
                if (field.dataType == "date" && field.dateValue) {
                    field.dateValue = new Date(field.dateValue.date);
                }
            });
        };

        var calculateResult = function(item) {
            if (item && item.existingControl != null && item.dangerousness != null) {
                var existingControl = parseFloat(item.existingControl.value);
                var dangerousness = parseFloat(item.dangerousness.value);

                return existingControl * dangerousness;
            }

            return 0;
        };

        var refreshOnChange = function() {
            getList();
            $scope.grid.dataSource.read();
        }

        $timeout(function () {
            $scope.mainGridOptions = {
                dataSource: {
                    type: "odata",
                    transport: {
                        read: {
                            url: "api/customer-contract-safety-inspection-list-item",
                            dataType: "json",
                            type: "POST",
                            data: function() {

                                var param = {
                                    customerContractorId: currentId,
                                    customerSafetyInspectionConfigListId: currentList.id,
                                    period: $scope.criteria.period ? $scope.criteria.period.value : 0,
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
                    batch: true,
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
                toolbar: kendo.template('<a class="k-button k-button-icontext k-grid-custom-save-changes" href="\\#"><span class="k-icon k-update"></span>Guardar Cambios</a>'),
                dataBound: function (e) {
                    $scope.existingControlOptions = {
                        dataSource: $scope.existingControlList,
                        dataTextField: "description",
                        dataValueField: "id",
                        select: function(e) {
                            var dataItem = this.dataItem(e.item);
                            $timeout(function () {
                                editedRow.model.set("existingControl", dataItem);
                                editedRow.model.set("calculate", calculateResult(editedRow.model));
                                displayNotify();
                            });
                        },
                    };

                    $scope.dangerousnessOptions = {
                        dataSource: $scope.dangerousnessList,
                        dataTextField: "description",
                        dataValueField: "id",
                        select: function(e) {
                            var dataItem = this.dataItem(e.item);
                            $timeout(function () {
                                editedRow.model.set("dangerousness", dataItem);
                                editedRow.model.set("calculate", calculateResult(editedRow.model));
                                displayNotify();
                            });
                        },
                    };

                    this.expandRow(this.tbody.find("tr.k-master-row"));
                },
                columns: [
                    {
                        field: "description",
                        title: "Grupo",
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
                            $template +=  dataItem.answers + ' de ' + dataItem.questions;
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
                                url: "api/customer-contract-safety-inspection-list-item-question",
                                dataType: "json",
                                type: "POST",
                                data: function() {

                                    var param = {
                                        customerContractorId: currentId,
                                        customerSafetyInspectionConfigListId: currentList.id,
                                        groupId: dataItem.id,
                                        period: $scope.criteria.period ? $scope.criteria.period.value : 0,
                                    };

                                    return param;
                                }
                            },
                            parameterMap: function (data, operation) {
                                return JSON.stringify(data);
                            }
                        },
                        schema: {
                            model:{
                                id: "id",
                                fields: {
                                    description: { editable: false, nullable: true },
                                    existingControl: { editable: true, defaultValue: { id: 0, text: null, code: null} },
                                    dangerousness: { editable: true, defaultValue: { id: 0, text: null, code: null} },
                                    action: { editable: true, defaultValue: { id: 0, item: null, value: null} },
                                    calculate: { editable: true, nullable: true },
                                    actionValue: { editable: true,  nullable: true },
                                }
                            },
                            data: function(result) {
                                return result.data || result;
                            },
                            total: function(result) {
                                return result.recordsTotal || result.data.length || 0;
                            }
                        },
                        batch: true,
                        serverPaging: false,
                        serverSorting: true,
                        serverFiltering: false,
                        filter: { field: "groupId", operator: "eq", value: dataItem.id }
                    },
                    editable: 'incell',
                    edit: function(e) {
                        var $name = e.container.find("[name]").first().attr("name");
                        if ($name == 'calculate' || $name == 'actionValue') {
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
                                { text: " ", template: "<a class='btn btn-dark-azure btn btn-sm' ng-click='onSave(dataItem)' uib-tooltip='Guardar Cambios' tooltip-placement='right'><i class='fa fa-save'></i></a> " },
                                { text: " ", template: "<a class='btn btn-light-red btn btn-sm' ng-click='onAddComment(dataItem)' uib-tooltip='Comentarios' tooltip-placement='right'><i class='fa fa-comments'></i></a> " },
                                { text: " ", template: "<a class='btn btn-warning btn btn-sm' ng-click='onAddImprovementPlan(dataItem)' uib-tooltip='Plan Mejoramiento' tooltip-placement='right'><i class='fa fa-plus-square'></i></a> " },
                                { text: " ", template: "<a class='btn btn-info btn btn-sm' ng-click='onAddAttachment(dataItem)' uib-tooltip='Anexos' tooltip-placement='right'><i class='fa fa-paperclip'></i></a> " }
                            ], width: "160px",
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
                            field: "existingControl",
                            title: "Control",
                            width: "220px",
                            editor: '<input kendo-drop-down-list k-value-primitive="true" k-options="existingControlOptions"  />',
                            template: function(dataItem)
                            {
                                return dataItem.existingControl ? dataItem.existingControl.description : '-Seleccionar-';
                            },
                            headerAttributes: {
                                style: "display: none"
                            },
                            attributes: {
                                style: "text-align: center;font-weight: bold;",
                                class: 'text-capitalize text-dark',
                            }
                        }, {
                            field: "dangerousness",
                            title: "Peligrosidad",
                            width: "220px",
                            editor: '<input kendo-drop-down-list k-value-primitive="true" k-options="dangerousnessOptions"  />',
                            template: function(dataItem)
                            {
                                return dataItem.dangerousness ? dataItem.dangerousness.description : '-Seleccionar-';
                            },
                            headerAttributes: {
                                style: "display: none"
                            },
                            attributes: {
                                style: "text-align: center;font-weight: bold;",
                                class: 'text-capitalize text-dark',
                            }
                        }, {
                            field: "calculate",
                            title: "icon",
                            width: "70px",
                            headerAttributes: {
                                style: "display: none"
                            },
                            template: function(dataItem)
                            {
                                var label = dataItem.calculate ? dataItem.calculate : 0;
                                return '<span class="badge badge-success text-extra-large">' + label + '</span>';
                            },
                            attributes: {
                                style: "text-align: center;font-weight: bold;",
                            }
                        }, {
                            field: "action",
                            title: "Acción",
                            width: "220px",
                            editor: '<input kendo-drop-down-list k-value-primitive="true" k-options="actionOptions"  />',
                            template: function(dataItem)
                            {
                                return dataItem.action ? dataItem.action.item : '-Seleccionar-';
                            },
                            headerAttributes: {
                                style: "display: none"
                            },
                            attributes: {
                                style: "text-align: center;font-weight: bold;",
                                class: 'text-capitalize text-dark',
                            }
                        }, {
                            field: "actionValue",
                            title: "icon",
                            width: "70px",
                            headerAttributes: {
                                style: "display: none"
                            },
                            template: function(dataItem)
                            {
                                var label = '';
                                switch (dataItem.actionValue) {
                                    case "s-o":
                                        label = '<i class="fa fa-ban text-yellow text-extra-extra-large"></i>';
                                    break;

                                    case "s-p":
                                        label = '<i class="fa fa-minus-circle text-azure-blue text-extra-extra-large"></i>';
                                        break;

                                    case "s-a":
                                        label = '<i class="fa fa-circle-o text-red text-extra-extra-large"></i>';
                                        break;

                                    case "s-t":
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

                $scope.grid.wrapper.find(".k-grid-toolbar").on("click", ".k-grid-custom-save-changes", function (e) {
                    var $filteredChanges = [];
                    $scope.grid.wrapper.find(".hide-grid-header").each(function ($el, $index) {
                        var $childGrid = angular.element(this).data("kendoGrid");
                        if ($childGrid.dataSource.hasChanges()) {
                            angular.forEach($childGrid.dataSource.data(), function ( $entity, $key ) {
                                if ($entity.dirty) {
                                    $filteredChanges.push($entity);
                                }
                            });
                        }
                    });

                    onSaveBatch($filteredChanges);
                });
            }
        });

        $scope.form = {
            next: function (form) {
                nextStep();
            },
            prev: function (form) {
                prevStep();
            },
            goTo: function (form, i) {
                goToStep(i);
            },
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
                        SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    }, 500);

                    return;

                } else {
                    onSaveObservation();
                }
            },
            reset: function () {

            }
        };

        var nextStep = function () {
            $scope.currentStep++;
            initializeStep();
        };

        var prevStep = function () {
            $scope.currentStep--;
            initializeStep();
        };

        var goToStep = function (i) {
            $scope.currentStep = i;
            initializeStep();
        };

        var initializeStep = function() {
            if ($scope.currentStep > 0 && $scope.currentStep < ($scope.lists.length + 1)) {
                currentList.id = $scope.lists[$scope.currentStep - 1].id;
                $scope.dangerousnessList = $scope.lists[$scope.currentStep - 1].dangerousnessList;
                $scope.existingControlList = $scope.lists[$scope.currentStep - 1].existingControlList;
                $scope.grid.dataSource.page(1);
            }else if ($scope.currentStep == ($scope.lists.length + 1)) {
                reloadObservationData();
                reloadDangerousnessData();
                reloadActionData();
            }
        }

        $scope.onSelectPeriod = function() {
            $scope.grid.dataSource.page(1);
            reloadObservationData();
            reloadDangerousnessData();
            reloadActionData();
        }

        $scope.onSelectList = function() {
            reloadDangerousnessData();
            reloadActionData();
        }

        $scope.onCancel = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", currentId);
            }
        };

        $scope.onSave = function (dataItem) {
            onSaveBatch([dataItem]);
        };

        var onSaveBatch = function (models) {
            var req = {};
            var data = JSON.stringify(models);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-contract-safety-inspection-list-item/batch',
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

        //----------------------------------------------------------------------------HEADER
        $scope.onSaveHeader = function (dataItem) {
            $scope.onSaveHeaderBatch([dataItem]);
        };

        $scope.onSaveHeaderBatch = function (models) {
            var req = {};
            var data = JSON.stringify(models);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-contract-safety-inspection-header-field/batch',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    toaster.pop('success', 'Actualización', 'La información ha sido actualizada satisfactoriamente.');

                    getHeaderFields();
                });

            }).catch(function (e) {
                $log.error(e);
                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
            }).finally(function () {

            });
        };

        //----------------------------------------------------------------------------OBSERVATION
        var init = function() {
            $scope.entity = {
                id: 0,
                customerContractorId: currentId,
                customerSafetyInspectionList: null,
                observation: null,
                period: $scope.criteria.period
            }
        }

        init();

        var onSaveObservation = function() {
            $scope.entity.period = $scope.criteria.period;

            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-contract-safety-inspection-list-observation/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    toaster.pop('success', 'Actualización', 'La información ha sido actualizada satisfactoriamente.');
                    reloadObservationData();
                    init();
                });

            }).catch(function (e) {
                $log.error(e);
                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
            }).finally(function () {

            });
        }

        $scope.dtInstanceCustomerContractSafetyInspectionObservation = {};
		$scope.dtOptionsCustomerContractSafetyInspectionObservation = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerContractorId = currentId;
                    d.period = $scope.criteria.period ? $scope.criteria.period.value : 0;

                    return JSON.stringify(d);
                },
                url: 'api/customer-contract-safety-inspection-list-observation',
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

        $scope.dtColumnsCustomerContractSafetyInspectionObservation = [
            DTColumnBuilder.newColumn('period').withTitle("Periodo").withOption('width', 300).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('list').withTitle("Lista").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observation').withTitle("Observación").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('user').withTitle("Uusario").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('createdAt').withTitle("Fecha Creación").withOption('width', 150).withOption('defaultContent', "")
        ];

        $scope.dtInstanceCustomerContractSafetyInspectionObservationCallback = function(instance) {
            $scope.dtInstanceCustomerContractSafetyInspectionObservation = instance;
        }

        var reloadObservationData = function() {
            $scope.dtInstanceCustomerContractSafetyInspectionObservation.reloadData();
        }


        //----------------------------------------------------------------------------DANGEROUSNESS
		$scope.dtOptionsCustomerContractSafetyInspectionDangerousness = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerContractorId = currentId;
                    d.customerSafetyInspectionListId = $scope.criteria.list ? $scope.criteria.list.id : 0;                    ;
                    d.period = $scope.criteria.period ? $scope.criteria.period.value : 0;

                    return JSON.stringify(d);
                },
                url: 'api/customer-contract-safety-inspection-list-item-dangerousness',
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

        $scope.dtColumnsCustomerContractSafetyInspectionDangerousness = [
            DTColumnBuilder.newColumn('period').withTitle("Periodo").withOption('width', 150).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Peligrosidad").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('quantity').withTitle("Cantidad").withOption('defaultContent', ''),
        ];

        $scope.dtInstanceCustomerContractSafetyInspectionDangerousnessCallback = function(instance) {
            $scope.dtInstanceCustomerContractSafetyInspectionDangerousness = instance;
        }

        var reloadDangerousnessData = function() {
            $scope.dtInstanceCustomerContractSafetyInspectionDangerousness.reloadData();
        }


        //----------------------------------------------------------------------------ACTIONS
		$scope.dtOptionsCustomerContractSafetyInspectionActions = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerContractorId = currentId;
                    d.customerSafetyInspectionListId = $scope.criteria.list ? $scope.criteria.list.id : 0;                    ;
                    d.period = $scope.criteria.period ? $scope.criteria.period.value : 0;

                    return JSON.stringify(d);
                },
                url: 'api/customer-contract-safety-inspection-list-item-action',
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

        $scope.dtColumnsCustomerContractSafetyInspectionActions = [
            DTColumnBuilder.newColumn('period').withTitle("Periodo").withOption('width', 150).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Acciones").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('quantity').withTitle("Cantidad").withOption('defaultContent', ''),
        ];

        $scope.dtInstanceCustomerContractSafetyInspectionActionsCallback = function(instance) {
            $scope.dtInstanceCustomerContractSafetyInspectionActions = instance;
        }

        var reloadActionData = function() {
            $scope.dtInstanceCustomerContractSafetyInspectionActions.reloadData();
        }


        //----------------------------------------------------------------------------COMMENT
        $scope.onAddComment= function (dataItem) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_sgsst_comment.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_questions_comment_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerContractorSafetyInspectionListItemCommentCtrl',
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

        //----------------------------------------------------------------------------IMPROVEMENT PLAN
        $scope.onAddImprovementPlan = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_improvement_plan_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerContractorSafetyInspectionListItemImprovementPlanCtrl',
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

        //----------------------------------------------------------------------------DOCUMENT
        $scope.onAddAttachment = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/contract/safety/customer_contractor_safety_inspection_attachment_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerContractorSafetyInspectionListItemAttachmentCtrl',
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

    }
]);

app.controller('ModalInstanceSideCustomerContractorSafetyInspectionListItemCommentCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, question, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document) {

    $scope.isView = false;

    var initialize = function() {
        $scope.entity = {
            id: 0,
            customerContractorSafetyInspectionListItemId: question.id,
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
            url: 'api/customer-contract-safety-inspection-list-item-comment/save',
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
                d.customerContractorSafetyInspectionListItemId = question.id

                return JSON.stringify(d);
            },
            url: 'api/customer-contract-safety-inspection-list-item-comment',
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

app.controller('ModalInstanceSideCustomerContractorSafetyInspectionListItemImprovementPlanCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, question,
                                                                                         $log, $timeout, SweetAlert, isView, $filter, FileUploader,
                                                                                         $http, DTOptionsBuilder, DTColumnBuilder, $compile) {

    $scope.isView = false;

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
            entityName: 'CS',
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

app.controller('ModalInstanceSideCustomerContractorSafetyInspectionListItemAttachmentCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, $log, $timeout, SweetAlert, isView, question, $filter, FileUploader, $http, DTColumnBuilder, DTOptionsBuilder, $compile, ListService, toaster) {

    var attachmentUploadedId = 0;

    $scope.documentType = $rootScope.parameters("contract_detail_document_type");
    $scope.documentStatus = $rootScope.parameters("customer_document_status");
    $scope.isView = false;


    var init = function() {
        $scope.attachment = {
            id: 0,
            customerContractorSafetyInspectionListItemId: question.id,
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
        url: 'api/customer-contract-safety-inspection-list-item-document/upload',
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
        toaster.pop('success', 'Operación Exitosa', 'La información ha sido guardada satisfactoriamente');
        $scope.reloadData();
        $scope.onClear();
    };

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
            url: 'api/customer-contract-safety-inspection-list-item-document/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                if (uploader.queue.length > 0) {
                    attachmentUploadedId = response.data.result.id;
                    uploader.uploadAll();
                } else {
                    toaster.pop('success', 'Operación Exitosa', 'La información ha sido guardada satisfactoriamente');
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

    $scope.dtInstanceContractSafetyInspectionDocument = {};
    $scope.dtOptionsContractfetyInspectionDocument = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerContractorSafetyInspectionListItemId = question.id;
                d.statusCode = '2'
                return JSON.stringify(d);
            },
            url: 'api/customer-contract-safety-inspection-list-item-document',
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

    $scope.dtColumnsContractfetyInspectionDocument = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.documentUrl ? data.documentUrl : '';
                var downloadUrl = "api/customer-contract-safety-inspection-list-item-document/download?id=" + data.id;
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

        $("#dtContractfetyInspectionDocument a.editRow").on("click", function () {

        });
    };

    $scope.dtInstanceContractSafetyInspectionDocumentCallback = function (instance) {
        $scope.dtInstanceContractSafetyInspectionDocument = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceContractSafetyInspectionDocument.reloadData();
    };

});
