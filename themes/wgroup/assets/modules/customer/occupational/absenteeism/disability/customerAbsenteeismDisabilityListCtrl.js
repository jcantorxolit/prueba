'use strict';
/**
 * controller for Customers
 */
app.controller('customerAbsenteeismDisabilityListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter', '$aside', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService) {

        var log = $log;

        $scope.audit = {
            fields: [],
            filters: [],
        };

        $scope.filter = {
            selectedYear: null,
            selectedMonth: null,
        }

        getList();

        function getList() {
            var entities = [
                {name: 'criteria_operators', value: null},
                {name: 'criteria_conditions', value: null},
                {name: 'month_options', value: null},
                {name: 'current_month', value: null},
                {name: 'customer_absenteeism_disability_filter_field', value: null},
                {name: 'customer_absenteeism_disability_filter_years', value: $stateParams.customerId}
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.customerAbsenteeismDisabilityFilterField;
                    $scope.yearList = response.data.data.customerAbsenteeismDisabilityFilterYears;
                    $scope.monthList = response.data.data.monthOptions;
                    $scope.currentMonth = response.data.data.currentMonth;

                    if ($scope.yearList.length > 0) {
                        $scope.filter.selectedYear = $scope.yearList[0];
                    }

                    $scope.filter.selectedMonth = $scope.monthList.find(function(item) {
                        return item.value == $scope.currentMonth
                    });

                    $scope.reloadData();
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
            $scope.reloadData();
        }

        $scope.removeFilter = function (index) {
            $scope.audit.filters.splice(index, 1);
        }

        $scope.onCleanFilter = function () {
            $scope.audit.filters = [];
            $scope.reloadData()
        }

        $scope.clearMonth = function () {
            $scope.filter.selectedMonth = null;
            $scope.reloadData()
        }

        $scope.dtInstanceDiagnosticDisabilityDT = {};
        $scope.dtOptionsDiagnosticDisabilityDT = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "absenteeism";
                    d.customerId = $stateParams.customerId;

                    d.year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 1;
                    d.month = $scope.filter.selectedMonth ? $scope.filter.selectedMonth.value : null;

                    if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                        d.filter =
                        {
                            filters: $scope.audit.filters.filter(function (filter) {
                                return filter != null && filter.field != null && filter.criteria != null;
                            }).map(function (filter, index, array) {
                                return {
                                    field: filter.field.name,
                                    operator: filter.criteria.value,
                                    value: filter.value,
                                    condition: filter.condition.value,
                                };
                            })
                        };
                    }

                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-disability',
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
                loadRow();
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

        $scope.dtColumnsDiagnosticDisabilityDT = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 220).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    var uploadTemplate = '<a class="btn btn-success btn-xs uploadRow lnk" href="#"  uib-tooltip="Adicionar anexo" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-paperclip"></i></a> ';

                    var reportTemplate = '<a class="btn btn-purple btn-xs reportRow lnk" href="#"  uib-tooltip="Adicionar reporte AT" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-ambulance"></i></a> ';

                    var actionPlanTemplate = '<a class="btn btn-dark-orange btn-xs actionPlanRowDisability lnk" href="#" tooltip-placement="right" uib-tooltip="Plan de mejoramiento"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-plus-square"></i></a> ';

                    if ($rootScope.can("clientes_view")) {
                        actions += viewTemplate;
                    }

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_edit")) {
                        actions += deleteTemplate;
                    }


                    if ($rootScope.can("clientes_edit")) {
                        actions += uploadTemplate;
                    }


                    /*if (data.category != null && data.category.item == 'Incapacidad' && data.cause != null && data.cause.item == "AT") {
                     actions += actionPlanTemplate;
                     actions += reportTemplate;
                     }*/

                    if (data.category != null && data.category == 'Incapacidad' && data.causeValue != null && (data.causeValue == "AT" || data.causeValue == "AL")) {
                        actions += actionPlanTemplate;
                        actions += reportTemplate;
                    }

                    return actions;
                }),

            DTColumnBuilder.newColumn('documentNumber').withTitle("Nro Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('firstName').withTitle("Nombres").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('contractType').withTitle("Tipo Contrato").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('workplace').withTitle("Centro de Trabajo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('category').withTitle("Tipo Ausentismo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('typeText').withTitle("Tipo Incapacidad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('causeItem').withTitle("Causa Incapacidad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('startDateFormat').withTitle("F Inicial").withOption('width', 180).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('endDateFormat').withTitle("F Final").withOption('width', 180).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle('Reporte EPS').notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    if (data.category != null && data.category == 'Incapacidad' && data.causeValue != null && (data.causeValue == "AT" || data.causeValue == "AL")) {
                        var checked = (data.hasReportEps == true || data.hasReportEps == '1') ? "checked" : ""
                        var label = (data.hasReportEps == true || data.hasReportEps == '1') ? "Si" : "No"

                        var editTemplate = '<div class="checkbox clip-check check-success ">' +
                            '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id + '">' + label + '</label></div>';

                        actions += editTemplate;
                    } else {
                        actions = "NA";
                    }

                    return actions;
                })
                .notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Reporte ARL').notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    if (data.category != null && data.category == 'Incapacidad' && data.causeValue != null && (data.causeValue == "AT" || data.causeValue == "AL")) {
                        var checked = (data.hasReport == true || data.hasReport == '1') ? "checked" : ""
                        var label = (data.hasReport == true || data.hasReport == '1') ? "Si" : "No"

                        var editTemplate = '<div class="checkbox clip-check check-success ">' +
                            '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id + '">' + label + '</label></div>';

                        actions += editTemplate;
                    } else {
                        actions = "NA";
                    }

                    return actions;
                })
                .notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Reporte Ministerio').notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    if (data.category != null && data.category == 'Incapacidad' && data.causeValue != null && (data.causeValue == "AT" || data.causeValue == "AL")) {
                        var checked = (data.hasReportMin == true || data.hasReportMin == '1') ? "checked" : ""
                        var label = (data.hasReportMin == true || data.hasReportMin == '1') ? "Si" : "No"

                        var editTemplate = '<div class="checkbox clip-check check-success ">' +
                            '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id + '">' + label + '</label></div>';

                        actions += editTemplate;
                    } else {
                        actions = "NA";
                    }

                    return actions;
                })
                .notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Incapacidad').notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    if (data.category != null && data.category == 'Incapacidad') {

                        var checked = (data.hasInhability == true || data.hasInhability == '1') ? "checked" : ""

                        var label = (data.hasInhability == true || data.hasInhability == '1') ? "Si" : "No"

                        var editTemplate = '<div class="checkbox clip-check check-success ">' +
                            '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id + '">' + label + '</label></div>';

                        actions += editTemplate;
                    } else {
                        actions = "NA";
                    }

                    return actions;
                })
                .notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Investigación AT').notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    if (data.category != null && data.category == 'Incapacidad' && data.causeValue != null && (data.causeValue == "AT" || data.causeValue == "AL")) {
                        var checked = (data.hasInvestigation == true || data.hasInvestigation == '1') ? "checked" : ""

                        var label = (data.hasInvestigation || data.hasInvestigation == '1') ? "Si" : "No"

                        var editTemplate = '<div class="checkbox clip-check check-success ">' +
                            '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id + '">' + label + '</label></div>';

                        actions += editTemplate;
                    } else {
                        actions = "NA";
                    }

                    return actions;
                })
                .notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Plan Mejoramiento').notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    if (data.category != null && data.category == 'Incapacidad' && data.causeValue != null && (data.causeValue == "AT" || data.causeValue == "AL")) {
                        var checked = (data.hasImprovementPlan == true || data.hasImprovementPlan == '1') ? "checked" : ""

                        var label = (data.hasImprovementPlan == true || data.hasImprovementPlan == '1') ? "Si" : "No"

                        var editTemplate = '<div class="checkbox clip-check check-success ">' +
                            '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id + '">' + label + '</label></div>';

                        actions += editTemplate;
                    } else {
                        actions = "NA";
                    }

                    return actions;
                })
                .notSortable()
        ];

        var loadRow = function () {
            angular.element("#dataDiagnosticDisabilityDT a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                onEdit(id);
            });

            angular.element("#dataDiagnosticDisabilityDT a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                onView(id);
            });

            angular.element("#dataDiagnosticDisabilityDT a.uploadRow").on("click", function () {
                var id = angular.element(this).data("id");
                onAddDocument(id);
            });

            angular.element("#dataDiagnosticDisabilityDT a.reportRow").on("click", function () {
                var id = angular.element(this).data("id");
                onAddReportAL(id);
            });

            angular.element("#dataDiagnosticDisabilityDT a.actionPlanRowDisability").on("click", function () {
                var id = angular.element(this).data("id");
                onAddImprovementPlan(id);
            });

            angular.element("#dataDiagnosticDisabilityDT a.delRow").on("click", function () {
                var id = angular.element(this).data("id");

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
                                url: 'api/absenteeism-disability/delete',
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
        }

        $scope.dtInstanceDiagnosticDisabilityDTCallback = function(instance) {
            $scope.dtInstanceDiagnosticDisabilityDT = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceDiagnosticDisabilityDT.reloadData();
        };

        $scope.onCreate = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", 0);
            }
        };

        $scope.onExportExcel = function()
        {
            var data = JSON.stringify({
                customerId: $stateParams.customerId,
                year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 1,
                month: $scope.filter.selectedMonth ? $scope.filter.selectedMonth.value : null,

            });
            angular.element("#downloadDocument")[0].src = "api/customer-absenteeism-disability/export-excel?data=" + Base64.encode(data);
        }

        var onEdit = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", id);
            }
        };

        var onView = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "view", id);
            }
        };

        //--------------------------------------------------ADD DOCUMENT TO DISABILITY
        var onAddDocument = function (id) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_document_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideDisabilityDocumentCtrl',
                scope: $scope,
                resolve: {
                    disability: function () {
                        return { id: id ? id : 0 };
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {
                $scope.reloadData();
            });
        };

        //--------------------------------------------------ADD REPORT (AL) RELATION TO DISABILITY
        var onAddReportAL = function (id) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_reportAL_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideDisabilityReportALCtrl',
                scope: $scope,
                resolve: {
                    disability: function () {
                        return { id: id ? id : 0 };
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {

            });
        };

        //----------------------------------------------------------------------------IMPROVEMENT PLAN
        var onAddImprovementPlan = function (id) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_improvement_plan_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerAbsenteeismDisabilitymprovementPlanCtrl',
                scope: $scope,
                resolve: {
                    item: function () {
                        return { id: id };
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function () {
                $scope.reloadData();
            });
        };


        $scope.onUpload = function () {

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_customer_employee_import.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideUploadDisabilityCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                if (response && response.sessionId) {
                    if ($scope.$parent != null) {
                        $scope.$parent.navToSection("staging", "staging", response.sessionId);
                    }
                }
                //$scope.reloadData();
            }, function() {

            });

        };
    }
]);

app.controller('ModalInstanceSideDisabilityDocumentCtrl', function ($rootScope, $scope, $uibModalInstance, disability, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document) {

    var attachmentUploadedId = 0;

    $scope.disabilityDocumentType = $rootScope.parameters("absenteeism_disability_document_type");

    $scope.disability = disability;
    $scope.disabledType = false;

    $scope.attachment = {
        id: 0,
        created_at: $filter('date')(new Date(), "dd/MM/yyyy HH:mm"),
        customerDisabilityId: disability.id,
        agent: null,
        type: null,
        classification: null,
        status: null,
        version: 1,
        description: ""
    };

    $scope.onLoadRecordDisability = function () {
        if ($scope.disability.id != 0) {

            // se debe cargar primero la información actual del cliente..
            // log.info("editando cliente con código: " + $scope.disability.id);
            var req = {
                id: $scope.disability.id
            };
            $http({
                method: 'GET',
                url: 'api/absenteeism-disability',
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
                        $scope.disability = response.data.result;

                        if ($scope.disability.cause != null && $scope.disability.cause.value == "EG") {
                            var types = $filter('filter')($scope.disabilityDocumentType, {value: "INC"});
                            if (types.length > 0) {
                                $scope.attachment.type = types[0];
                                $scope.disabledType = true;
                            }
                        }
                    });

                }).finally(function () {

                });
        }
    }

    $scope.onLoadRecordDisability();


    var uploader = $scope.uploader = new FileUploader({
        url: 'api/absenteeism-disability-document/upload',
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
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancelDocument = function () {
        $uibModalInstance.dismiss('cancel');
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

        }
    };

    var save = function () {

        var req = {};
        var data = JSON.stringify($scope.attachment);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/absenteeism-disability-document/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                $scope.attachment = response.data.result;
                attachmentUploadedId = response.data.result.id;
                uploader.uploadAll();
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var request = {};
    request.operation = "document";
    request.disability_id = $scope.disability.id;

    $scope.dtInstanceDisabilityAttachment = {};
    $scope.dtOptionsDisabilityAttachment = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/absenteeism-disability-document',
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

    $scope.dtColumnsDisabilityDocumentAttachment = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.document != null ? data.document.path : "";
                var actions = "";
                var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                    '   <i class="fa fa-download"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-folder-open-o"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Anular anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-ban"></i></a> ';


                if ($rootScope.can("seguimiento_view")) {
                    //actions += viewTemplate;
                }

                if ($rootScope.can("seguimiento_edit")) {
                    actions += editTemplate;
                }

                if ($rootScope.can("seguimiento_delete")) {
                    //actions += deleteTemplate;
                }

                return actions;
            }),
        DTColumnBuilder.newColumn('documentType').withTitle("Tipo de documento").withOption('width', 200),

        DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200),

        DTColumnBuilder.newColumn('date').withTitle("Fecha Creación").withOption('width', 200),
        DTColumnBuilder.newColumn(null).withTitle("Usuario").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var name = "";

                if (data.agent != null) {
                    name = data.agent;
                }

                return name;
            })
    ];

    var loadRow = function () {

        $("#dtDisabilityDocumentAttachment a.editRow").on("click", function () {
            var id = $(this).data("id");
            var url = $(this).data("url");
            //$scope.editTracking(id);
            if (url == "") {
                SweetAlert.swal("Error en la descarga", "No existe un anexo para descargar", "error");
            }
            else {
                jQuery("#downloadDocument")[0].src = "api/absenteeism-disability-document/download?id=" + id;
            }
        });

        $("#dtDisabilityDocumentAttachment a.delRow").on("click", function () {
            var id = $(this).data("id");

            // Aqui se debe hacer la redireccion al formulario de edicion del customer
            log.info("intenta eliminar el registro: " + id);

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
                        //
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });

    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityAttachment.reloadData();
    };

});

app.controller('ModalInstanceSideDisabilityReportALCtrl', function ($rootScope, $scope, $uibModalInstance, disability, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document) {

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.dtInstanceReportAL = {};
    $scope.dtOptionsReportAL = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.operation = "document";
                d.disability_id = disability.id;
                return d;
            },
            url: 'api/absenteeism-disability-report-al/available',
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

    $scope.dtColumnsReportAL = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.document != null ? data.document.path : "";
                var actions = "";
                var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                    '   <i class="fa fa-download"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-folder-open-o"></i></a> ';
                var deleteTemplate = '<a class="btn btn-primary btn-xs delRow lnk" href="#" uib-tooltip="Adicionar reporte" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-plus"></i></a> ';


                if ($rootScope.can("seguimiento_view")) {
                    //actions += viewTemplate;
                }

                if ($rootScope.can("seguimiento_edit")) {
                    // actions += editTemplate;
                }

                if ($rootScope.can("seguimiento_delete")) {
                    //actions += deleteTemplate;
                }

                actions += deleteTemplate;

                return actions;
            }),
        DTColumnBuilder.newColumn('accident_date').withTitle("Fecha de accidente").withOption('width', 200),

        DTColumnBuilder.newColumn('accident_description').withTitle("Descripción accidente").withOption('width', 200),

        DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 100)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                switch (data) {
                    case "abierto":
                        label = 'label label-success';
                        break;

                    case "Cancelado":
                        label = 'label label-danger';
                        break;

                    case "Retirado":
                        label = 'label label-warning';
                        break;
                }

                var status = '<span class="' + label + '">' + data + '</span>';


                return status;
            })
    ];

    var loadRow = function () {

        $("#dtReportAL a.delRow").on("click", function () {
            var id = $(this).data("id");

            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Adicionará el reporte al diagnóstico.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, adicionar!",
                    cancelButtonText: "No, cancelar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {

                        var reportAl = {
                            id: 0,
                            customerDisabilityId: disability.id,
                            reportAL: {
                                id: id
                            }
                        }

                        var req = {};
                        var data = JSON.stringify(reportAl);
                        req.data = Base64.encode(data);

                        return $http({
                            method: 'POST',
                            url: 'api/absenteeism-disability-report-al/save',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {

                            $timeout(function () {
                                toaster.pop('success', 'Operación Exitosa', 'Registro eliminado');
                                $scope.reloadDataReportALRelation();
                                $scope.reloadData();
                            });
                        }).catch(function (e) {
                            $log.error(e);
                            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
                        }).finally(function () {

                        });


                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });

    };

    $scope.reloadData = function () {
        $scope.dtInstanceReportAL.reloadData();
    };


    $scope.dtInstanceReportALRelation = {};
    $scope.dtOptionsReportALRelation = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.operation = "document";
                d.disability_id = disability.id;
                return d;
            },
            url: 'api/absenteeism-disability-report-al',
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
            loadRowReportALRelation();
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

    $scope.dtColumnsReportALRelation = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.document != null ? data.document.path : "";
                var actions = "";
                var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                    '   <i class="fa fa-download"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-folder-open-o"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar reporte" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash"></i></a> ';


                if ($rootScope.can("seguimiento_view")) {
                    //actions += viewTemplate;
                }

                if ($rootScope.can("seguimiento_edit")) {
                    //actions += editTemplate;
                }

                if ($rootScope.can("seguimiento_delete")) {
                    //actions += deleteTemplate;
                }

                actions += deleteTemplate;

                return actions;
            }),
        DTColumnBuilder.newColumn('accident_date').withTitle("Fecha de accidente").withOption('width', 200),

        DTColumnBuilder.newColumn('accident_description').withTitle("Descripción accidente").withOption('width', 200),

        DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 100)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                switch (data) {
                    case "abierto":
                        label = 'label label-success';
                        break;

                    case "Cancelado":
                        label = 'label label-danger';
                        break;

                    case "Retirado":
                        label = 'label label-warning';
                        break;
                }

                var status = '<span class="' + label + '">' + data + '</span>';


                return status;
            })
    ];

    var loadRowReportALRelation = function () {

        $("#dtReportALRelation a.delRow").on("click", function () {
            var id = $(this).data("id");


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
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/absenteeism-disability-report-al/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function (e) {
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        }).finally(function () {
                            $scope.reloadDataReportALRelation();
                            $scope.reloadData();
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });

    };

    $scope.reloadDataReportALRelation = function () {
        $scope.dtInstanceReportALRelation.reloadData();
    };

});

app.controller('ModalInstanceSideDisabilityActionPlanCtrl', function ($rootScope, $scope, $uibModalInstance, actionPlan, $log, $timeout, SweetAlert, isView, $filter, FileUploader, $http) {

    $scope.typesAlert = $filter('orderBy')($rootScope.parameters("tracking_alert_type"), 'id', false);
    $scope.typesTime = $rootScope.parameters("tracking_alert_timeType");
    $scope.statusAlert = $rootScope.parameters("tracking_alert_status");
    $scope.perferencesAlert = $rootScope.parameters("tracking_alert_preference");

    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy hh:mm tt"
        //value: $scope.project.deliveryDate.date
    };

    $scope.actionPlan = actionPlan;
    $scope.isView = isView;


    $scope.onClose = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.saveActionPlan = function () {
        var req = {};

        if ($scope.actionPlan.closeDateTime == undefined || $scope.actionPlan.closeDateTime == null) {
            SweetAlert.swal("Error de guardado", "La fecha de cierre es requerida!", "error");
            return;
        }

        $scope.actionPlan.closeDateTime = $scope.actionPlan.closeDateTime.toISOString();

        var data = JSON.stringify($scope.actionPlan);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/absenteeism-disability/action-plan/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $uibModalInstance.close(1);
            });
        }).catch(function (e) {
            $uibModalInstance.close(1);
        }).finally(function () {

        });

    };

    $scope.addActionPlanAlert = function () {

        if ($scope.actionPlan.alerts == null) {
            $scope.actionPlan.alerts = [];
        }

        $timeout(function () {
            $scope.actionPlan.alerts.push(
                {
                    id: 0,
                    type: null,
                    timeType: null,
                    time: 0,
                    preference: null,
                    sent: 0,
                    status: null
                }
            );
        }, 500);
    };

    $scope.removeAlert = function (index) {

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
                        $scope.actionPlan.alerts.splice(index, 1);
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    };

});

app.controller('ModalInstanceSideCustomerAbsenteeismDisabilitymprovementPlanCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, item,
                                                                                         $log, $timeout, SweetAlert, isView, $filter, FileUploader,
                                                                                         $http, DTOptionsBuilder, DTColumnBuilder, $compile) {

    var classification = 'AT';

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
            classificationName: classification,
            classificationId: classification,
            entityName: 'AD',
            entityId: item.id,
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
                SweetAlert.swal("Registro", "La información se ha guardado satisfactoriamente", "success");
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

app.controller('ModalInstanceSideUploadDisabilityCtrl', function ($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/customer-absenteeism-import',
        formData: []
    });

    getList();

    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + 'api/v1/customer-absenteeism-import';
                $scope.uploader.url = $exportUrl + 'api/v1/customer-absenteeism-import';
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function () {
        jQuery("#downloadDocument")[0].src = "api/customer-absenteeism-disability/download-template";
    }

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item/*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

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
        var formData = { id: $stateParams.customerId };
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
        $lastResponse = response;
    };
    uploader.onCompleteAll = function () {
        console.info('onCompleteAll');
        $uibModalInstance.close($lastResponse);
    };

});
