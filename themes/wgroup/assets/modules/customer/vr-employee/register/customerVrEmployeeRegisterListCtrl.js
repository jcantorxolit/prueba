'use strict';
/**
  * controller for Customers
*/
app.controller('customerVrEmployeeRegisterListCtrl',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService, $uibModal, ngNotify, customerVrEmployeeService) {

        var log = $log;

        $scope.audit = {
            fields: [],
            filters: [],
        };

        function getList() {

            var entities = [
                { name: 'criteria_operators', value: null },
                { name: 'criteria_conditions', value: null },
                { name: 'customer_vr_employee_filter_field', value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.customerVrEmployeeFilterField;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();


        $scope.addFilter = function () {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }

            $scope.audit.filters.push({
                id: 0,
                field: null,
                criteria: $scope.criteria.length > 0 ? $scope.criteria[1] : null,
                condition: $scope.conditions.length > 0 ? $scope.conditions[0] : null,
                value: ""
            });
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

        $timeout(function () {
            $scope.mainGridOptions = {
                dataSource: {
                    type: "odata",
                    transport: {
                        read: {
                            url: "api/customer-vr-employee",
                            dataType: "json",
                            type: "POST",
                            data: function () {
                                var d = {};
                                d.customerId = $stateParams.customerId;
                                if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                                    d.filter = {
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
                                return d;
                            }
                        },
                        parameterMap: function (data, operation) {
                            return JSON.stringify(data);
                        }
                    },
                    schema: {
                        model: {
                            id: "id"
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
                    }
                },
                dataBound: function (e) {
                    //this.expandRow(this.tbody.find("tr.k-master-row"));
                    $scope.grid.tbody.find("tr").each(function () {

                        var model = $scope.grid.dataItem(this);

                        var $canViewTemplate = model !== undefined && (model.isActive == "Finalizado");
                        var $canViewGenerate = model !== undefined && (model.isActive == "En Progreso" && parseFloat(model.average) > 0);
                        var $canEditAndInvalidate = model !== undefined && (model.isActive != "Anulado" && model.isActive != "Finalizado");

                        if (!$canEditAndInvalidate) {
                            $(this).find(".btn-warning").remove();
                            $(this).find(".btn-primary").remove();
                        }

                        if (!$canViewTemplate) {
                            $(this).find(".btn-danger").remove();
                        }

                        if (!$canViewGenerate) {
                            $(this).find(".btn-dark-azure").remove();
                        }
                    });
                },
                columns: [
                    {
                        command: [
                            { text: " ", template: "<a class='btn btn-info btn btn-sm' ng-click='onView(dataItem)' uib-tooltip='Ver' tooltip-placement='right'><i class='fa fa-eye'></i></a> " },
                            { text: " ", template: "<a class='btn btn-primary btn btn-sm' ng-click='onEdit(dataItem)' uib-tooltip='Editar' tooltip-placement='right'><i class='fa fa-edit'></i></a> " },
                            { text: " ", template: "<a class='btn btn-warning btn btn-sm' ng-click='onInvalidate(dataItem)' uib-tooltip='Anular' tooltip-placement='right'><i class='fa fa-ban'></i></a> " },
                            { text: " ", template: "<a class='btn btn-danger btn btn-sm' ng-href='[[dataItem.documentUrl]]'  target='_blank' uib-tooltip='Certificado' tooltip-placement='right'><i class='fa fa-file-pdf-o'></i></a> " },
                            { text: " ", template: "<a class='btn btn-dark-azure btn btn-sm' ng-click='onGenerateCertificate(dataItem)' uib-tooltip='Generar Certificado' tooltip-placement='right'><i class='fa fa-share-square-o'></i></a> " }
                        ], width: "160px"
                    },
                    {
                        field: "registrationDate",
                        title: "Fecha",
                        width: "150px",
                        filterable: {
                            cell: {
                                operator: "contains",
                                suggestionOperator: "contains",
                            }
                        }
                    },
                    {
                        field: "documentType",
                        title: "Tipo Identificación",
                        filterable: {
                            cell: {
                                operator: "contains",
                                suggestionOperator: "contains",
                            }
                        }
                    },
                    {
                        field: "documentNumber",
                        title: "Num. Identificación",
                        filterable: {
                            cell: {
                                operator: "contains",
                                suggestionOperator: "contains",
                            }
                        }
                    },
                    {
                        field: "fullName",
                        title: "Nombre",
                        filterable: {
                            cell: {
                                operator: "contains",
                                suggestionOperator: "contains",
                            }
                        }
                    },
                    {
                        field: "dateRealization",
                        title: "Fecha Realización",
                        filterable: {
                            cell: {
                                operator: "contains",
                                suggestionOperator: "contains",
                            }
                        }
                    },
                    {
                        field: "average",
                        title: "% Completado",
                        filterable: {
                            cell: {
                                operator: "contains",
                                suggestionOperator: "contains",
                            }
                        },
                        template: function (dataItem) {
                            return '<span>' + dataItem.average + '%</span>';
                        },
                    },
                    {
                        field: "qtyExperience",
                        title: "# Experiencias",
                        filterable: {
                            cell: {
                                operator: "contains",
                                suggestionOperator: "contains",
                            }
                        }
                    },
                    {
                        title: "Estado",
                        width: "200px",
                        filterable: {
                            cell: {
                                operator: "contains",
                                suggestionOperator: "contains",
                            }
                        },
                        template: function (dataItem) {
                            var label = 'label label-default';
                            if (dataItem.isActive == 'Anulado') {
                                label = 'label label-warning';
                            } else if (dataItem.isActive == 'Finalizado') {
                                label = 'label label-inverse';
                            }

                            var status = '<span class="' + label + '">' + dataItem.isActive + '</span>';
                            return status;
                        },
                        attributes: {
                            style: "text-align: center;font-weight: bold;",
                        }
                    }
                ]
            };

            $scope.detailGridOptions = function (dataItem) {
                return {
                    dataSource: {
                        type: "odata",
                        transport: {
                            read: {
                                url: "api/customer-vr-employee/detail",
                                dataType: "json",
                                type: "POST",
                                data: function () {

                                    var param = {
                                        customerId: $stateParams.customerId,
                                        customerVrEmployeeId: dataItem.id
                                    };

                                    return param;
                                }
                            },
                            parameterMap: function (data, operation) {
                                return JSON.stringify(data);
                            }
                        },
                        requestEnd: function (e) {

                        },
                        schema: {
                            model: {
                                id: "id",
                                fields: {
                                    description: { editable: false, nullable: true },
                                    article: { editable: false, nullable: true },
                                    rate: { editable: false, defaultValue: { id: 0, text: null, code: null } },
                                }
                            },
                            data: function (result) {
                                return result.data || result;
                            },
                            total: function (result) {
                                return result.recordsTotal || result.data.length || 0;
                            }
                        },
                        serverPaging: false,
                        serverSorting: false,
                        serverFiltering: false,
                        filter: { field: "customerVrEmployeeId", operator: "eq", value: dataItem.id }
                    },
                    editable: 'incell',
                    edit: function (e) {
                        editedRow.model = e.model;
                    },
                    scrollable: false,
                    sortable: false,
                    pageable: false,
                    columns: [
                        {
                            field: "experience",
                            title: "Experiencia",
                        },
                        {
                            title: "Calificación",
                            template: function (dataItem) {
                                var label = '<h4 class="inline-block padding-top-10 ' + dataItem.colorPercent + '">' + dataItem.percent + '%</h4>';

                                return label;
                            },
                            attributes: {
                                style: "text-align: left;font-weight: bold;",
                            }
                        }
                    ]
                };
            };

        });

        $scope.$on("kendoWidgetCreated", function (event, widget) {
            if ($scope.grid === undefined || $scope.grid === null) {
                $scope.grid = widget;
            }
        });


        $scope.onCreate = function (id) {
            customerVrEmployeeService.setEntity(null);
            customerVrEmployeeService.setId(id);
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("form", "edit", 0);
            }
        };

        $scope.onEdit = function (dataItem) {
            customerVrEmployeeService.setEntity(null);
            customerVrEmployeeService.setId(dataItem.id);
            if ($scope.$parent != null) {
                if (dataItem.hasConfig) {
                    $scope.$parent.navToSection("metrics", "edit", dataItem.id);
                } else {
                    $scope.$parent.navToSection("form", "edit", dataItem.id);
                }
            }
        };

        $scope.onView = function (dataItem) {
            customerVrEmployeeService.setEntity(null);
            customerVrEmployeeService.setId(dataItem.id);
            if ($scope.$parent != null) {
                if (dataItem.hasConfig) {
                    $scope.$parent.navToSection("metrics", "view", dataItem.id);
                } else {
                    $scope.$parent.navToSection("form", "view", dataItem.id);
                }
            }
        };

        $scope.onInvalidate = function (dataItem) {
            SweetAlert.swal({
                title: "Está seguro?",
                text: "Anulará el registro seleccionado.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Si, anular!",
                cancelButtonText: "No, continuar!",
                closeOnConfirm: true,
                closeOnCancel: true
            },
                function (isConfirm) {
                    if (isConfirm) {
                        var req = {};
                        req.id = dataItem.id;
                        $http({
                            method: 'POST',
                            url: 'api/customer-vr-employee/cancel',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            data: $.param(req)
                        }).then(function (response) {
                            swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            $scope.reloadData();
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
        };

        $scope.reloadData = function () {
            $scope.grid.dataSource.read();
        }

        // $scope.onCancel = function (id, hasConfig) {
        //     customerVrEmployeeService.setEntity(null);
        //     customerVrEmployeeService.setId(id);
        //     if ($scope.$parent != null) {
        //         if (hasConfig) {
        //             $scope.$parent.navToSection("metrics", "view", id);
        //         } else {
        //             $scope.$parent.navToSection("form", "view", id);
        //         }
        //     }
        // };

        $scope.onGenerateCertificate = function (dataItem) {
            var data = JSON.stringify({ customerVrEmployeeId: dataItem.id })

            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-vr-employee-experience-evaluation/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $scope.reloadData();
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            });
        }

        $scope.onDownload = function (dataItem) {
            console.log(dataItem);
            angular.element("#downloadDocument")[0].src = dataItem.documentUrl;
        }

        $scope.onImport = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideImportCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                if (response && response.sessionId && !response.isValid) {
                    if ($scope.$parent != null) {
                        $scope.$parent.navToSection("staging", "staging", response.sessionId);
                    }
                } else {
                    $scope.reloadData();
                    destroyCertificates(response.sessionId);
                }                
            });

        };

        $scope.onImportEmployee = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideImportEmployeeCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                $scope.reloadData();
            });

        };

        $scope.onExport = function (type) {
            var param = {
                customerId: $stateParams.customerId,
                filter: {}
            };

            if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                param.filter = {
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

            angular.element("#downloadDocument")[0].src = "api/customer-vr-employee/export?data=" + Base64.encode(JSON.stringify(param));
        }

        $scope.onDownoladAllCertificate = function () {
            var modalInstance = $uibModal.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/vr-employee/register/customer_vr_employee_register_modal.htm",
                controller: 'ModalInstanceCustomerVrEmployeeCertificateCtrl',
                windowTopClass: 'top-modal'
            });

            modalInstance.result.then(function (selectedItem) {
                if (selectedItem) {
                    ngNotify.set('El archivo se está generando.', {
                        position: 'bottom',
                        sticky: true,
                        button: false,
                        html: true
                    });

                    var entity = {
                        customerId: $stateParams.customerId,
                        period: selectedItem.value
                    };

                    var req = {};
                    var data = JSON.stringify(entity);
                    req.data = Base64.encode(data);

                    $http({
                        method: 'POST',
                        url: 'api/customer-vr-employee/export-certificate',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        data: $.param(req)
                    }).then(function(response) {
                        if (response.data.message == 'ok') {
                            var $url = response.data.path + response.data.filename;
                            var $link = '<a class="btn btn-wide btn-default" href="' + $url + '" target="_self"><i class="glyphicon glyphicon-download"></i> Descargar zip</a>';
                            ngNotify.set($link, {
                                position: 'bottom',
                                sticky: true,
                                type: 'success',
                                button: true,
                                html: true
                            });
                        } else {
                            ngNotify.set('Ocurrio un error al generar los certificados', {
                                position: 'bottom',
                                sticky: true,
                                type: 'error',
                                button: true,
                                html: true
                            });
                        }
                    }).catch(function(response) {
                        ngNotify.set(response.data.message, {
                            position: 'bottom',
                            sticky: true,
                            type: 'error',
                            button: true,
                            html: true
                        });
                    }).finally(function() {

                    });
                }
            }, function () {

            });
        }

        $scope.onGeneratellCertificate = function () {
            ngNotify.set('Se está ejecutando el proceso. Por favor espere.', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var entity = {
                customerId: $stateParams.customerId,
            };

            var req = {};
            var data = JSON.stringify(entity);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: 'api/customer-vr-employee-experience-evaluation/generate-massive-certificates',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                if (response.data.message == 'ok') {
                    $scope.reloadData();
                    ngNotify.set('Certificados generados satisfactoriamente!.', {
                        position: 'bottom',
                        sticky: true,
                        button: true,
                        html: true,
                        type: 'success',
                    });
                } else {
                    ngNotify.set('No se encontraron certificados para generar', {
                        position: 'bottom',
                        sticky: true,
                        type: 'error',
                        button: true,
                        html: true
                    });
                }
            }).catch(function(response) {
                ngNotify.set(response.data.message, {
                    position: 'bottom',
                    sticky: true,
                    type: 'error',
                    button: true,
                    html: true
                });
            }).finally(function() {

            });
        }

        function destroyCertificates(sessionId) {
            var data = JSON.stringify({ sessionId: sessionId });
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-vr-employee-experience-evaluation/destroy-certificate',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                console.log('ha finalizado la eliminación de certificados desactualizados');
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error al guardar", e.data.message, "error");
            });
        }



    });

app.controller('ModalInstanceSideImportCtrl', function ($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;
    var $importUrl = 'api/v1/customer-vr-employee-import';
    $scope.title = "Realidad Virtual Empleados";

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + $importUrl,
        formData: []
    });

    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + $importUrl;
                $scope.uploader.url = $exportUrl + $importUrl;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    getList();

    $scope.download = function () {
        angular.element("#downloadDocument")[0].src = "api/customer-vr-employee/download-template?customerId=" + $stateParams.customerId;
    }

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item, options) {
            return this.queue.length < 10;
        }
    });

    uploader.onWhenAddingFileFailed = function (item, filter, options) {
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
        var formData = { id: $stateParams.customerId, user: $rootScope.currentUser().id };
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


app.controller('ModalInstanceSideImportEmployeeCtrl', function ($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;
    var $importUrl = 'api/v1/customer-vr-employee-head-import';
    $scope.title = "Realidad Virtual - Empleados";

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + $importUrl,
        formData: []
    });

    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + $importUrl;
                $scope.uploader.url = $exportUrl + $importUrl;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    getList();

    $scope.download = function () {
        angular.element("#downloadDocument")[0].src = "api/customer-vr-employee-head/download-template?customerId=" + $stateParams.customerId;
    }

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item, options) {
            return this.queue.length < 10;
        }
    });

    uploader.onWhenAddingFileFailed = function (item, filter, options) {
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
        var formData = { id: $stateParams.customerId, user: $rootScope.currentUser().id };
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

app.controller('ModalInstanceCustomerVrEmployeeCertificateCtrl', function ($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    $scope.periodList = [];

    function getList() {

        var entities = [
            { name: 'customer_employee_vr_period_list', criteria: { customerId: $stateParams.customerId} },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.periodList = response.data.data.employeeVrPeriodList;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    getList();

    var $formInstance = null;

    var init = function () {
        $scope.entity = {
            period: null,
        };

        if ($formInstance != null) {
            $formInstance.$setPristine(true);
        }
    }

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
    };

    init();

    $scope.form = {
        submit: function (form) {
            $formInstance = form;

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

            } else {
                onSelectPeriod();
            }
        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    var onSelectPeriod = function () {
        $uibModalInstance.close($scope.entity.period);
        init();
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss();
    };
});
