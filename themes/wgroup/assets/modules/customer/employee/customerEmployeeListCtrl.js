'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside',
    'ngNotify', 'ListService', '$translate', '$analytics', '$localStorage', '$uibModal',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $aside, ngNotify, ListService, $translate, $analytics, $localStorage, $uibModal) {

        var log = $log;
        var $exportUrl = '';

        $scope.showList = true;
        $scope.backupRecoveryEntities = [];

        $scope.currentTab = '';

        $scope.showImportOrganizationalStructure = false;

        $scope.audit = {
            fields: [],
            filters: [],
        };

        $scope.employee = {
            id: 0
        };

        $scope.canShowMoreActionButton = ($rootScope.can('empleado_export_excel') || $rootScope.can('empleado_export_pdf') || $rootScope.can('empleado_export_document')) && $rootScope.canEditRoot;

        getList();

        function getList() {
            var entities = [
                { name: 'criteria_operators', value: null },
                { name: 'criteria_conditions', value: null },
                { name: 'customer_employee_custom_filter_field', value: null },
                { name: 'customer_employee_backup_recovery', value: null },
                { name: 'export_url', value: null },
                { name: 'customer_parameter', criteria: { customerId: $stateParams.customerId, group: 'employeesOrganizationalStructure' } }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $exportUrl = response.data.data.exportUrl.item;
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.customerEmployeeCustomFilterField;
                    $scope.backupRecoveryEntities = response.data.data.customer_employee_backup_recovery.map(function (entity) {
                        return {
                            name: entity.item,
                            value: entity.value,
                            isActive: false
                        }
                    });

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        //------------------------------------------------------FILTERS
        $scope.addFilter = function () {
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

        var storeFilters = 'criteria-list-employe-' + window.currentUser.id + "-" + $stateParams.customerId;
        if ($localStorage[storeFilters]) {
            $scope.audit.filters = $localStorage[storeFilters];
        }

        $scope.onFilter = function () {
            $localStorage[storeFilters] = $scope.audit.filters;
            $localStorage.customerId = $stateParams.customerId;
            $scope.reloadData();
        }

        $scope.removeFilter = function (index) {
            $scope.audit.filters.splice(index, 1);
        }

        $scope.onCleanFilter = function () {
            $scope.audit.filters = [];
            $localStorage[storeFilters] = [];
            $scope.reloadData()
        }

        $scope.onCancel = function () {
            $scope.audit.filters = [];
            $scope.reloadData()
        }

        $scope.onToggleShowList = function () {
            $scope.showList = !$scope.showList;
        }

        // Datatable configuration
        var storeDatatable = 'customerEmployeeList-' + window.currentUser.id + "-" + $stateParams.customerId;
        $scope.dtInstanceEmployee = {};
        $scope.dtOptionsEmployee = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {

                    d.customerId = $stateParams.customerId;

                    if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                        d.filter = {
                            filters: $.map($scope.audit.filters, function (filter) {
                                return {
                                    field: filter.field.name,
                                    operator: filter.criteria.value,
                                    condition: filter.condition.value,
                                    value: filter.value
                                };
                            })
                        };
                    }
                    return JSON.stringify(d);
                },
                url: 'api/customer-employee-v2',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () { }
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

        $scope.dtColumnsEmployee = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 180).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';
                    var inactiveTemplate = '<a class="btn btn-danger btn-xs inactiveRow lnk" href="#" uib-tooltip="Inactivar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-ban"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-dark-red btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';                        

                    var uploadTemplate = '<a class="btn btn-success btn-xs uploadRow lnk" href="#"  uib-tooltip="Documentos de Soporte Obligatorios" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-paperclip"></i></a> ';

                    var downloadTemplate = ' | <a class="btn btn-dark-azure btn btn-xs downloadRow lnk" href="#" uib-tooltip="Descargar Anexos" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-download"></i></a>'

                    if ($rootScope.can("empleado_view")) {
                        actions += viewTemplate;
                    }

                    if ($rootScope.can("empleado_create") && $rootScope.canEditRoot) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("empleado_delete") && $rootScope.canEditRoot) {
                        actions += inactiveTemplate;
                    }

                    if ($rootScope.can("empleado_destroy") && $rootScope.canEditRoot) {
                        actions += deleteTemplate;
                    }

                    if ($rootScope.can("empleado_documento_open") && $rootScope.canEditRoot) {
                        actions += uploadTemplate;
                    }

                    return actions + (data.countAttachment > 0 ? downloadTemplate : '');
                }),

            //DTColumnBuilder.newColumn('entity.documentType.item').withTitle("Tipo Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
            DTColumnBuilder.newColumn('workPlace').withTitle($translate.instant('grid.employee.WORK-PLACE')).withOption('width', 200),
            DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200),
            DTColumnBuilder.newColumn('neighborhood').withTitle("Centro de Costos").withOption('width', 200),
            DTColumnBuilder.newColumn(null).withTitle("Anexos").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-warning';
                    var text = 0;

                    if (data.countAttachment != null || data.countAttachment != undefined) {
                        text = data.countAttachment;
                    }

                    var status = '<span class="' + label + '">' + text + ' Anexos </span>';

                    return status;
                }),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Inactivo';

                    if (data.isActiveCode != null || data.isActiveCode != undefined) {
                        if (data.isActiveCode == 'Activo') {
                            label = 'label label-success';
                            text = 'Activo';
                        } else {
                            label = 'label label-danger';
                            text = 'Inactivo';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),
            DTColumnBuilder.newColumn(null).withTitle("Autorización").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = data.isAuthorized;

                    if (data.isAuthorized != null || data.isAuthorized != undefined) {
                        if (data.isAuthorized == 'Autorizado') {
                            label = 'label label-success';
                        } else if (data.isAuthorized == 'No Autorizado') {
                            label = 'label label-danger';
                        } else {
                            label = 'label label-info';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
        ];

        var loadRow = function () {

            $("#dtCustomerEmployee a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editEmployee(id);
            });

            $("#dtCustomerEmployee a.viewRow").on("click", function () {
                var id = $(this).data("id");
                $scope.viewEmployee(id);
            });

            $("#dtCustomerEmployee a.uploadRow").on("click", function () {
                var id = $(this).data("id");

                $scope.employee.id = id;
                $scope.onAddDocument();
            });

            $("#dtCustomerEmployee a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onDownloadAttachment(id);
            });

            $("#dtCustomerEmployee a.inactiveRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

                SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Inactivará el empleado seleccionado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, inactivar!",
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
                                url: 'api/customer-employee/inactive',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Inactivado", "Registro inactivado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la inactivación", "Se ha presentado un error durante la inactivación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });


            $("#dtCustomerEmployee a.delRow").on("click", function () {
                var id = $(this).data("id");

                SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el empleado seleccionado.",
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
                                url: 'api/customer-employee/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                if (response.data.result.isSuccess) {
                                    swal("Eliminación", "Registro eliminado satisfactoriamente", "info");
                                } else {
                                    var message = response.data.result.messages.map(function(mesasge) {
                                        return "<li>" + mesasge + "</li>";
                                    });
                                    //SweetAlert.swal("Eliminación", message.join(''), "error");
                                    SweetAlert.swal({ html:true, type: "error", title:'No es posible eliminar el registro, tiene relación en los siguientes módulos:', text:message.join('')});
                                }
                                    
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });
        };

        $scope.dtInstanceEmployeeCallback = function (instance) {
            $scope.dtInstanceEmployee = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceEmployee.reloadData();
        };

        $scope.editEmployee = function (id) {
            if ($scope.$parent != null) {

                if ($rootScope.app.instance == "bolivar") {
                    $analytics.eventTrack('Clic módulo', { category: 'Clientes', label: 'Empleados', action: 'Editar Empleado' });
                }

                $scope.$parent.navToSection("edit", "edit", id);
            }
        };

        $scope.viewEmployee = function (id) {
            if ($scope.$parent != null) {

                if ($rootScope.app.instance == "bolivar") {
                    $analytics.eventTrack('Clic módulo', { category: 'Clientes', label: 'Empleados', action: 'Ver Empleado' });
                }

                $scope.$parent.navToSection("edit", "view", id);
            }
        };

        $scope.onCreateNew = function () {
            if ($scope.$parent != null) {

                if ($rootScope.app.instance == "bolivar") {
                    $analytics.eventTrack('Clic módulo', { category: 'Clientes', label: 'Empleados', action: 'Crear Empleado' });
                }

                $scope.$parent.navToSection("edit", "edit", 0);
            }
        };

        $scope.switchTab = function (tab, titletab, Module) {
            if ($rootScope.app.instance == "bolivar") {
                $analytics.eventTrack('Clic módulo', { category: 'Clientes', label: Module, action: 'Abre Pestaña' });
            }

            $scope.currentTab = tab;
        };

        $scope.onUpload = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/employee/customer_employee_import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideUploadEmployeeCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                if (response && response.sessionId) {
                    $rootScope.isAuthorizationTemplate = response.isAuthorizationTemplate;
                    $rootScope.hasCustomerEmployeeId = response.hasCustomerEmployeeId;
                    if ($scope.$parent != null) {
                        $scope.$parent.navToSection("stagingEmployee", "stagingEmployee", response.sessionId);
                    }
                }
            });

        };

        $scope.onUploadDemographic = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_customer_employee_import.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/employee/customer_employee_import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideUploadEmployeeDemographicCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                if (response && response.sessionId) {
                    if ($scope.$parent != null) {
                        $scope.$parent.navToSection("staging", "staging", response.sessionId);
                    }
                }
            });
        }

        $scope.onAddDocument = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_customer_employee_upload.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/employee/customer_employee_upload_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideUploadEmployeeDocumentCtrl',
                scope: $scope,
                resolve: {
                    employee: function () {
                        return $scope.employee;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        };

        $scope.onExportExcel = function () {
            //jQuery("#downloadDocument")[0].src = "api/customer-employee/export?id=" + $stateParams.customerId + "&data=" + Base64.encode(JSON.stringify($scope.audit));

            ngNotify.set('El archivo se está generando.', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var entity = {
                id: $stateParams.customerId,
                audit: $scope.audit
            };

            var req = {};
            var data = JSON.stringify(entity);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: $exportUrl + 'api/v1/customer-employee-export',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                var $url = $exportUrl + 'storage/' + response.data.filename;
                var $link = '<a class="btn btn-wide btn-default" href="' + $url + '" target="_self"><i class="glyphicon glyphicon-download"></i> Descargar el archivo</a>';
                ngNotify.set($link, {
                    position: 'bottom',
                    sticky: true,
                    type: 'success',
                    button: true,
                    html: true
                });

            }).catch(function (response) {
                ngNotify.set(response.data.message, {
                    position: 'bottom',
                    sticky: true,
                    type: 'error',
                    button: true,
                    html: true
                });
            }).finally(function () {

            });

        }

        $scope.onExportAuthorizationTemplate = function () {
            //jQuery("#downloadDocument")[0].src = "api/customer-employee/export?id=" + $stateParams.customerId + "&data=" + Base64.encode(JSON.stringify($scope.audit));

            ngNotify.set('El archivo se está generando.', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var entity = {
                id: $stateParams.customerId,
                audit: $scope.audit
            };

            var req = {};
            var data = JSON.stringify(entity);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: $exportUrl + 'api/v1/customer-employee-authorization-export',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                var $url = $exportUrl + 'storage/' + response.data.filename;
                var $link = '<a class="btn btn-wide btn-default" href="' + $url + '" target="_self"><i class="glyphicon glyphicon-download"></i> Descargar el archivo</a>';
                ngNotify.set($link, {
                    position: 'bottom',
                    sticky: true,
                    type: 'success',
                    button: true,
                    html: true
                });

            }).catch(function (response) {
                ngNotify.set(response.data.message, {
                    position: 'bottom',
                    sticky: true,
                    type: 'error',
                    button: true,
                    html: true
                });
            }).finally(function () {

            });

        }

        $scope.onExportTemplate = function () {
            ngNotify.set('El archivo se está generando. <uib-progressbar value="100" class="progress-striped progress-xs active" type="info"></uib-progressbar>', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var entity = {
                id: $stateParams.customerId,
                audit: $scope.audit
            };

            var req = {};
            var data = JSON.stringify(entity);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: $exportUrl + 'api/v1/customer-employee-export-template',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                var $url = $exportUrl + 'storage/' + response.data.filename;
                var $link = '<a class="btn btn-wide btn-default" href="' + $url + '" target="_self"><i class="glyphicon glyphicon-download"></i> Descargar el archivo</a>';
                ngNotify.set($link, {
                    position: 'bottom',
                    sticky: true,
                    type: 'success',
                    button: true,
                    html: true
                });

            }).catch(function (response) {
                ngNotify.set(response.data.message, {
                    position: 'bottom',
                    sticky: true,
                    type: 'error',
                    button: true,
                    html: true
                });
            }).finally(function () {

            });
        }

        $scope.onExportPdf = function () {
            angular.element("#downloadDocument")[0].src = "api/customer-employee/exportPdf?id=" + $stateParams.customerId + "&data=" + Base64.encode(JSON.stringify($scope.audit));
        }


        $scope.onExportDocument = function () {            
            var modalInstance = $uibModal.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/document_filter_modal.htm',
                controller: 'ModalInstanceSideCustomerEmployeeDownloadAttachmentCtrl',
                windowTopClass: 'top-modal',
                resolve: {
                    title: function () {
                        return "Descargar anexos de los empleados"
                    }
                }
            });
            modalInstance.result.then(function () {
                
            }, function () {

            });
        }

        $scope.onDownloadAttachment = function (id) {
            ngNotify.set('El archivo se está generando.', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var entity = {
                customerId: $stateParams.customerId,
                customerEmployeeId: id
            };

            var req = {};
            var data = JSON.stringify(entity);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: 'api/customer-employee-document/export',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                var $url = response.data.path + response.data.filename;
                var $link = '<div class="row"><div class="col-sm-5"> </div> <div class="col-sm-6 text-left">Por favor espere y verifique su correo y la bandeja de mensajes!</div> </div>';
                ngNotify.set($link, {
                    position: 'bottom',
                    sticky: true,
                    type: 'info',
                    button: true,
                    html: true
                });

            }).catch(function (response) {
                ngNotify.set(response.data.message, {
                    position: 'bottom',
                    sticky: true,
                    type: 'error',
                    button: true,
                    html: true
                });
            }).finally(function () {

            });
        }

        $scope.onImportOrganizationalStructure = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideImportEmployeeOrganizationalStructureCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function (response) {
                $scope.reloadData();
            });
        };

        $scope.onConfigDocumentType = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/employee/customer_employee_document_type_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerDocumentTypeCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function (response) {
                $scope.reloadData();
            });
        }
    }
]);

app.controller('ModalInstanceSideUploadEmployeeCtrl', function ($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;
    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/customer-employee-import',
        formData: []
    });

    getList();

    $scope.title = "Importar empleados";
    $scope.buttonDownloadTitle = "Plantilla Creación de Empleados";

    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + 'api/v1/customer-employee-import';
                $scope.uploader.url = $exportUrl + 'api/v1/customer-employee-import';
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function () {
        angular.element("#downloadDocument")[0].src = "api/customer-employee-document/download-template";
    }

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item /*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploader.onWhenAddingFileFailed = function (item /*{File|FileLikeObject}*/, filter, options) {
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
        var formData = { id: $stateParams.customerId, userId: $rootScope.currentUser().id };
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
        $lastResponse = response;
    };
    uploader.onCompleteAll = function () {
        $uibModalInstance.close($lastResponse);
    };

});

app.controller('ModalInstanceSideUploadEmployeeDemographicCtrl', function ($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {


    var $exportUrl = '';
    var $lastResponse = null;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/customer-employee-demographic-import',
        formData: []
    });

    $scope.title = "Importar Perfíl Sociodemográfico";
    $scope.buttonDownloadTitle = "Plantilla Perfíl Sociodemográfico";

    getList();

    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + 'api/v1/customer-employee-demographic-import';
                $scope.uploader.url = $exportUrl + 'api/v1/customer-employee-demographic-import';
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function () {
        angular.element("#downloadDocument")[0].src = "api/customer-employee-demographic-staging/download-template";
    }

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item /*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploader.onBeforeUploadItem = function (item) {
        var formData = { id: $stateParams.customerId };
        item.formData.push(formData);
    };
    uploader.onCompleteItem = function (fileItem, response, status, headers) {
        $lastResponse = response;
    };
    uploader.onCompleteAll = function () {
        $uibModalInstance.close($lastResponse);
    };

});


app.controller('ModalInstanceSideUploadEmployeeDocumentCtrl', function ($stateParams, $scope, FileUploader, $uibModalInstance, employee, DTOptionsBuilder, DTColumnBuilder, $compile, $log, $timeout, SweetAlert, $http, toaster) {

    var uploader = $scope.uploader = new FileUploader({
        url: 'api/customer-employee/import',
        formData: []
    });

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item /*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploader.onWhenAddingFileFailed = function (item /*{File|FileLikeObject}*/, filter, options) {
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
    };
    uploader.onCompleteAll = function () {
        console.info('onCompleteAll');
        $uibModalInstance.close(1);
    };

    var request = {};
    request.operation = "document";
    request.customer_employee_id = employee.id;
    $scope.dtInstanceUploadEmployeeDocument = {};
    $scope.dtOptionsUploadEmployeeDocument = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/customer-employee/document/required',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () { }
        })
        .withDataProp('data')
        .withOption('order', [
            [0, 'desc']
        ])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {
            //log.info("fnDrawCallback");

        })
        /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })

        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });;

    $scope.dtColumnsUploadEmployeeDocument = [
        DTColumnBuilder.newColumn('type').withTitle("Tipo Documento").withOption('width', 200),
        DTColumnBuilder.newColumn(null).withTitle('Requerido').withOption('width', 200).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";

                var checked = (data.isRequired == true) ? "checked" : ""

                var label = (data.isRequired == true) ? "Si" : "No"

                var editTemplate = '<div class="checkbox clip-check check-success ">' +
                    '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id + '">' + label + '</label></div>';

                actions += editTemplate;

                return actions;
            })
            .notSortable(),
        DTColumnBuilder.newColumn(null).withTitle('Diligenciado').withOption('width', 200).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";

                var checked = (data.isApproved == true) ? "checked" : ""

                var label = (data.isApproved == true) ? "Si" : "No"

                var editTemplate = '<div class="checkbox clip-check check-success ">' +
                    '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id + '">' + label + '</label></div>';

                actions += editTemplate;

                return actions;
            })
            .notSortable(),
        DTColumnBuilder.newColumn(null).withTitle("Anexos").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-warning';
                var text = 0;

                if (data.quantity != null || data.quantity != undefined) {
                    text = data.quantity;
                }

                var status = '<span class="' + label + '">' + text + ' Anexos </span>';

                return status;
            }),
    ];

});



app.controller('ModalInstanceSideImportEmployeeOrganizationalStructureCtrl', function ($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;

    $scope.showTemplate = true;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/customer-employee-import-organizational-structure',
        formData: [
            { customerId: $stateParams.customerId }
        ]
    });

    getList();

    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + 'api/v1/customer-employee-import-organizational-structure';
                $scope.uploader.url = $exportUrl + 'api/v1/customer-employee-import-organizational-structure';
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function () {
        jQuery("#downloadDocument")[0].src = "api/event-customer-employee/download-template-import-organizational-structure?customerId=" + $stateParams.customerId;
    }

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item /*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploader.onWhenAddingFileFailed = function (item /*{File|FileLikeObject}*/, filter, options) {
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

app.controller('ModalInstanceSideCustomerDocumentTypeCtrl', function ($rootScope, $state, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {


    $scope.currentUser = $rootScope.currentUser();
    $scope.isAgent = $scope.currentUser.wg_type == "agent";
    $scope.isAdmin = $scope.currentUser.wg_type == "system";
    $scope.isCustomerAdmin = $scope.currentUser.wg_type == "customerAdmin";
    $scope.isCustomerUser = $scope.currentUser.wg_type == "customerUser";

    $scope.canEdit = $scope.isCustomerAdmin || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
    $rootScope.canEditRoot = $scope.canEdit;

    $scope.isView = $state.is("app.clientes.view");
    $scope.isCreate = $state.is("app.clientes.create");

    $scope.onAddEmployeeDocumentType = function () {

        $timeout(function () {
            if ($scope.customer.employeeDocumentsTypeList == null) {
                $scope.customer.employeeDocumentsTypeList = [];
            }
            $scope.customer.employeeDocumentsTypeList.push(
                {
                    id: 0,
                    customerId: 0,
                    namespace: "wgroup",
                    group: "employeeDocumentType",
                    isActive: false,
                    isVisible: false,
                    value: ""
                }
            );
        });
    };

    $scope.onRemoveEmployeeDocumentType = function (index) {
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
                        var $data = $scope.customer.employeeDocumentsTypeList[index];

                        $scope.customer.employeeDocumentsTypeList.splice(index, 1);

                        if ($data.id != 0) {
                            var req = {};
                            req.id = $data.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer-parameter/delete',
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
                log.info($scope.customer);
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

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var save = function () {

        var $form = {
            id: $scope.customer.id,
            employeeDocumentsTypeList: $scope.customer.employeeDocumentsTypeList
        }

        var req = {};
        var data = JSON.stringify($form);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/customer/save-document-type',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.customer.employeeDocumentsTypeList = response.data.result.employeeDocumentsTypeList;
                $rootScope.$emit('dataCustomer', { newValue: $scope.customer, id: $scope.customer.id, message: 'Data Customer has been changed!' });

                if ($rootScope.app.supportHelp) {
                    $rootScope.app.supportHelp.hasNotificationUser = $scope.customer.userNotificationList.length > 0;
                }

                SweetAlert.swal("Validación exitosa", "Guardando información del cliente...", "success");
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };
});

app.controller('ModalInstanceSideCustomerEmployeeDownloadAttachmentCtrl', function ($rootScope, $stateParams, $scope, $uibModal, $uibModalInstance, title, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile, ListService, ngNotify) {

    var log = $log;

    $scope.loading = true;
    $scope.title = title;

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onSelectYear = function () {
        getList();
    };

    var init = function () {
        $scope.filter = {
            id: $stateParams.customerId
        }
    }

    init();

    getList();

    function getList() {

        var $year = $scope.filter.year ? $scope.filter.year.value : null;

        var entities = [
            { name: 'customer_employee_document_type', value: $stateParams.customerId },
            { name: 'customer_employee_document_periods', value: $stateParams.customerId, year:  $year}
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.documentType = response.data.data.customerEmployeeDocumentType;
                $scope.years = response.data.data.customerEmployeeDocumentPeriod.years;
                $scope.months = response.data.data.customerEmployeeDocumentPeriod.months;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                if (!$scope.filter.type && !$scope.filter.year && !$scope.filter.month) {
                    SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione al menos un filtro e intentelo nuevamente.", "error");
                    return;
                }
                onFIlter();
            }

        },
        reset: function (form) {

        }
    };

    var onFIlter = function () {

        ngNotify.set('El archivo se está generando.', {
            position: 'bottom',
            sticky: true,
            button: false,
            html: true
        });

        var entity = {
            customerId: $stateParams.customerId,
            audit: $scope.filter
        };

        var req = {};
        var data = JSON.stringify(entity);
        req.data = Base64.encode(data);

        $http({
            method: 'POST',
            url: 'api/customer-employee-document/export',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            var $url = response.data.path + response.data.filename;
            var $link = '<div class="row"><div class="col-sm-12 text-center">Por favor espere y verifique su correo y la bandeja de mensajes!</div> </div>';
            ngNotify.set($link, {
                position: 'bottom',
                sticky: true,
                type: 'success',
                button: true,
                html: true
            });

            $scope.onCloseModal();

        }).catch(function (response) {
            ngNotify.set(response.data.message, {
                position: 'bottom',
                sticky: true,
                type: 'error',
                button: true,
                html: true
            });
        }).finally(function () {

        });

    };

});