'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeDocumentListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', 'SweetAlert', '$http', '$filter', '$document', 'FileUploader', '$aside', '$localStorage',
    '$ngConfirm', 'ngNotify', 'ListService', '$uibModal', 'CustomerEmployeeService',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
        $compile, toaster, $state, $rootScope, $timeout, SweetAlert, $http, $filter, $document,
        FileUploader, $aside, $localStorage, $ngConfirm, ngNotify, ListService, $uibModal, CustomerEmployeeService) {

        var log = $log;
        var request = {};
        var attachmentUploadedId = 0;

        $scope.$storage = $localStorage.$default({
            hideCanceled: true
        });

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        // parametros para seguimientos

        $scope.isView = !$rootScope.canEditRoot || $scope.$parent.editMode == 'view';
        $scope.canValidateDocument = $rootScope.can("empleado_documento_approved_revised") || $rootScope.can("empleado_documento_reviewed_denied");
        $scope.showAuthorized = $rootScope.can("empleado_documento_authorize");

        $scope.documentStatus = $rootScope.parameters("customer_document_status");
        $scope.isInvalidate = false;
        $scope.customerId = $stateParams.customerId;
        $scope.downloadUrl = "";

        getList();

        function getList() {

            var entities = [
                { name: 'customer_employee_document_type', value: $stateParams.customerId }
            ];

            ListService.getDataList(entities)
                .then(function(response) {
                    $scope.requirements = response.data.data.customerEmployeeDocumentType;
                    $scope.requirements = $scope.requirements.filter(function(item) {
                        return item.isVisible == 1;
                    });
                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        var initialize = function() {
            $scope.attachment = {
                id: 0,
                customerEmployeeId: $scope.$parent.currentEmployee,
                requirement: null,
                status: $scope.documentStatus && $scope.documentStatus.length > 0 ? $scope.documentStatus[0] : null,
                version: 1,
                description: "",
                startDate: null,
                endDate: null,
            };

            $scope.employee = {
                id: $scope.$parent.currentEmployee,
                isAuthorized: CustomerEmployeeService.getAuthorization()
            }
        }

        initialize();

        $scope.$watch("$parent.currentEmployee", function() {
            initialize();
        });

        var onLoadRecord = function(id) {
            // se debe cargar primero la información actual del cliente..
            var req = {
                id: id
            };

            $http({
                    method: 'GET',
                    url: 'api/customer-employee/document',
                    params: req
                })
                .catch(function(response) {

                })
                .then(function(response) {

                    $timeout(function() {
                        $scope.attachment = response.data.result;
                        $scope.attachment.version = parseInt($scope.attachment.version) + 1;

                        initializeDates();
                    });

                }).finally(function() {
                    $timeout(function() {
                        $scope.loading = false;
                    }, 400);
                });


        };

        $scope.master = $scope.attachment;

        $scope.form = {

            submit: function(form) {
                var firstError = null;

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
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;

                } else {

                    if ($scope.uploader.queue.length == 0) {
                        SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un anexo e Intentalo de nuevo.", "error");
                        return;
                    }
                    //your code for submit
                    save();
                }

            },
            reset: function(form) {

                $scope.attachment = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function() {
            var req = {};
            var data = JSON.stringify($scope.attachment);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-employee/document/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {

                $timeout(function() {
                    $scope.attachment = response.data.result;
                    attachmentUploadedId = response.data.result.id;
                    uploader.uploadAll();
                });
            }).catch(function(e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function() {
                $scope.reloadData();
            });

        };

        var initializeDates = function() {
            if ($scope.attachment.startDate != null) {
                $scope.attachment.startDate = new Date($scope.attachment.startDate.date);
            }

            if ($scope.attachment.endDate != null) {
                $scope.attachment.endDate = new Date($scope.attachment.endDate.date);
            }
        }

        var uploader = $scope.uploader = new FileUploader({
            url: 'api/customer-employee/document/upload',
            formData: [],
            removeAfterUpload: true
        });

        uploader.filters.push({
            name: 'customFilter',
            fn: function(item /*{File|FileLikeObject}*/ , options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS

        uploader.onWhenAddingFileFailed = function(item /*{File|FileLikeObject}*/ , filter, options) {
            console.info('onWhenAddingFileFailed', item, filter, options);
        };
        uploader.onAfterAddingFile = function(fileItem) {
            console.info('onAfterAddingFile', fileItem);
        };
        uploader.onAfterAddingAll = function(addedFileItems) {
            console.info('onAfterAddingAll', addedFileItems);
        };
        uploader.onBeforeUploadItem = function(item) {
            console.info('onBeforeUploadItem', item);
            var formData = { id: attachmentUploadedId };
            item.formData.push(formData);
        };
        uploader.onProgressItem = function(fileItem, progress) {
            console.info('onProgressItem', fileItem, progress);
        };
        uploader.onProgressAll = function(progress) {
            console.info('onProgressAll', progress);
        };
        uploader.onSuccessItem = function(fileItem, response, status, headers) {
            console.info('onSuccessItem', fileItem, response, status, headers);
        };
        uploader.onErrorItem = function(fileItem, response, status, headers) {
            console.info('onErrorItem', fileItem, response, status, headers);
        };
        uploader.onCancelItem = function(fileItem, response, status, headers) {
            console.info('onCancelItem', fileItem, response, status, headers);
        };
        uploader.onCompleteItem = function(fileItem, response, status, headers) {
            console.info('onCompleteItem', fileItem, response, status, headers);
        };
        uploader.onCompleteAll = function() {
            console.info('onCompleteAll');
            $scope.reloadData();
            $scope.clear();
            SweetAlert.swal("Acción exitosa", "Se guardó satisfactoriamente", "success");
        };


        $scope.clear = function() {
            initialize();
            $scope.isInvalidate = false;
        };

        $scope.onAuthChange = function () {   
            CustomerEmployeeService.setAuthorization($scope.employee.isAuthorized);
            $scope.employee.reason = null; 
            if (CustomerEmployeeService.getLastAuthorization() != CustomerEmployeeService.getAuthorization()) {
                var modalInstance = $uibModal.open({                
                    templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/employee/customer_employee_authorization_reason_modal.htm',
                    controller: 'customerEmployeeAuthorizationModalCtrl',
                    windowTopClass: 'top-modal',
                    resolve: {
                       
                    }
                });
                modalInstance.result.then(function (reason) {                    
                    $scope.employee.reason = reason;

                    CustomerEmployeeService.saveAuth($scope.employee)
                        .then(function(response) {
                            CustomerEmployeeService.setLastAuthorization($scope.employee.isAuthorized);
                            CustomerEmployeeService.setAuthorization($scope.employee.isAuthorized);
                            $rootScope.$emit('isAuthorizedChangeInDocument', { newValue: $scope.employee.isAuthorized });
                        }, function(error) {
                            $scope.status = 'Unable to load customer data: ' + error.message;
                        });
                }, function() {
    
                });
            }            
        };

        var onDestroyIsAuthorized$ = $rootScope.$on('isAuthorizedChangeInBasic', function (event, args) {
            $scope.employee.isAuthorized = args.newValue;
        });
		
		
        $scope.$on("$destroy", function () {
            onDestroyIsAuthorized$();            
        });

        request.operation = "document";
        request.customer_employee_id = $scope.$parent.currentEmployee;
        request.customer_id = $scope.customerId;
        request.hideCanceled = $scope.$storage.hideCanceled ? 1 : 0;

        $scope.dtInstanceCustomerEmployeeDocument = {};
        $scope.dtOptionsCustomerEmployeeDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "document";
                    d.customerEmployeeId = $scope.$parent.currentEmployee;
                    if ($scope.$storage.hideCanceled) {
                        d.statusCode = '2'
                    }
                    return JSON.stringify(d);
                },
                url: 'api/customer-employee-document',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function() {
                    // Aqui inicia el loader indicator
                },
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function() {
                //log.info("fnDrawCallback");
                loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })

        .withPaginationType('full_numbers')
            .withOption('createdRow', function(row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });;

        $scope.dtColumnsCustomerEmployeeDocument = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 200).notSortable()
            .renderWith(function(data, type, full, meta) {
                var url = data.documentUrl != null ? data.documentUrl : "";
                var actions = "";
                var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                    '   <i class="fa fa-download"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-folder-open-o"></i></a> ';
                var deleteTemplate = '<a class="btn btn-light-red btn-xs delRow lnk" href="#" uib-tooltip="Anular anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-ban"></i></a> ';


                var isButtonVisible = !$scope.isView
                if ($rootScope.can("empleado_documento_open")) {
                    if (url != '') {
                        actions += viewTemplate;
                    }
                }

                if ($rootScope.can("empleado_documento_download")) {
                    if (url != '') {
                        actions += editTemplate;
                    }
                }

                if ($rootScope.can("empleado_documento_invalidate")) {
                    if (data.status == "Vigente" || data.status == "Vencido") {
                        actions += deleteTemplate;
                    }
                }

                if ($scope.isAdmin || $scope.isAgent || $scope.$parent.isCustomerContractor) {

                    var disabledA = "";
                    var disabledD = "";

                    if (data.isVerified == "Aprobado") {
                        disabledA = "disabled='true' "
                    } else if (data.isVerified == "Denegado") {
                        disabledD = "disabled='true' "
                    }

                    var approveTemplate = ' | <a class="btn btn-success btn-xs approve lnk" href="#" uib-tooltip="Revisado Aprobado" data-id="' + data.id + '"' + disabledA + ' >' +
                        '   <i class="fa fa-check"></i></a> ';
                    var noApproveTemplate = '<a class="btn btn-dark-yellow btn-xs noApprove lnk" href="#" uib-tooltip="Revisado Denegado" data-id="' + data.id + '"' + disabledD + '  >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("empleado_documento_approved_revised")) {
                        actions += approveTemplate;
                    }

                    if ($rootScope.can("empleado_documento_reviewed_denied")) {
                        actions += noApproveTemplate;
                    }
                }

                return isButtonVisible ? actions : viewTemplate + editTemplate;
            }),
            DTColumnBuilder.newColumn('requirement').withTitle("Tipo Documento").withOption('width', 200),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200),
            DTColumnBuilder.newColumn('startDate').withTitle("Fecha de Inicio Vigencia").withOption('width', 200),
            DTColumnBuilder.newColumn('endDate').withTitle("Fecha de Finalización Vigencia").withOption('width', 200),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200),
            DTColumnBuilder.newColumn(null).withTitle('Requerido').withOption('width', 200).notSortable()
            .renderWith(function(data, type, full, meta) {

                var actions = "";

                var checked = (data.isRequiredCode == '1') ? "checked" : ""

                var label = (data.isRequiredCode == '1') ? "Si" : "No"

                var editTemplate = '<div class="checkbox clip-check check-success ">' +
                    '<input class="editRow" ng-disabled="true" disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id + '">' + label + '</label></div>';

                actions += editTemplate;

                return actions;
            })
            .notSortable(),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
            .renderWith(function(data, type, full, meta) {
                var label = '';
                switch (data) {
                    case "Vigente":
                        label = 'label label-success';
                        break;

                    case "Anulado":
                        label = 'label label-danger';
                        break;

                    case "Vencido":
                        label = 'label label-warning';
                        break;
                }

                var status = '<span class="' + label + '">' + data + '</span>';

                return status;
            }),
            DTColumnBuilder.newColumn('isVerified').withTitle("Verificado").withOption('width', 200)
            .renderWith(function(data, type, full, meta) {
                var label = '';
                switch (data) {
                    case "Aprobado":
                        label = 'label label-success';
                        break;

                    case "Denegado":
                        label = 'label label-danger';
                        break;
                }

                var status = '<span class="' + label + '">' + data + '</span>';

                return status;
            }),
            DTColumnBuilder.newColumn('observation').withTitle("Motivo Denegado").withOption('width', 200),
        ];

        var loadRow = function() {

            $("#dtCustomerEmployeeDocument a.editRow").on("click", function() {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer-employee/document/download?id=" + id;
                }
            });

            $("#dtCustomerEmployeeDocument a.delRow").on("click", function() {
                var id = $(this).data("id");

                $ngConfirm({
                    boxWidth: '35%',
                    useBootstrap: false,
                    animation: 'bottom',
                    closeAnimation: 'scale',
                    title: 'Está seguro?',
                    content: '<strong>Anulará</strong> el anexo seleccionado',
                    scope: $scope,
                    type: 'red',
                    typeAnimated: true,
                    buttons: {
                        cancel: {
                            text: 'Anular y cambiar versión',
                            btnClass: 'btn-primary',
                            action: function(scope, button) {
                                $scope.attachment.id = id;
                                $scope.isView = false;
                                onLoadRecord(id);
                                toaster.pop('info', 'Anulación Anexo', 'El anexo se cargó satisfactoriamente, seleccione una nueva versión del documento');
                            }
                        },
                        fullCancel: {
                            text: 'Anular definitivamente',
                            btnClass: 'btn-red',
                            action: function(scope, button) {
                                var req = {};
                                req.id = id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-employee/document/delete',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    data: $.param(req)
                                }).then(function(response) {
                                    swal("Eliminado", "Anexo anulado satisfactoriamente", "info");
                                    $scope.reloadData();
                                }).catch(function(e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function() {});
                            }
                        },
                        closeAll: {
                            text: 'Cancelar',
                            btnClass: 'btn-default',
                            action: function(scope, button) {

                            }
                        },
                    }
                });

            });

            $("#dtCustomerEmployeeDocument a.approve").on("click", function() {
                var id = $(this).data("id");

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "El documento sera marcado como revisado aprobado.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Si, aprobar!",
                        cancelButtonText: "No, cancelar!",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function(isConfirm) {
                        if (isConfirm) {

                            var req = {};
                            var document = { id: id };
                            var data = JSON.stringify(document);

                            req.data = Base64.encode(data);
                            return $http({
                                method: 'POST',
                                url: 'api/customer-employee/document/approve',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function(response) {

                                $timeout(function() {
                                    SweetAlert.swal("Validación exitosa", "Documento revisado aprobado...", "success");
                                    $scope.reloadData();
                                });
                            }).catch(function(e) {
                                $log.error(e);
                                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                            }).finally(function() {

                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

            $("#dtCustomerEmployeeDocument a.noApprove").on("click", function() {

                var id = $(this).data("id");
                var url = $(this).data("url");

                var document = {
                    id: id,
                    tracking: {
                        action: "",
                        description: ""
                    }
                }

                var modalInstance = $uibModal.open({
                    templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/employee/document/customer_employee_tab_attachment_denied_modal.htm',
                    controller: 'ModalInstanceDeniedCtrl',
                    windowTopClass: 'top-modal',
                    resolve: {
                        document: function() {
                            return document;
                        },
                        action: function() {
                            return "Revisado Denegado";
                        }
                    }
                });

                modalInstance.result.then(function(selectedItem) {
                    $scope.selected = selectedItem;
                }, function() {
                    $log.info('Modal dismissed at: ' + new Date());
                    $scope.reloadData();

                });
            });
        };

        $scope.onValidateDocument = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/employee/document/customer_employee_tab_attachment_filter_modal.htm',
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: 'static',
                controller: 'ModalInstanceFilterdCtrl',
                resolve: {},
                scope: $scope,
            });

            modalInstance.result.then(function(selectedItem) {
                $scope.selected = selectedItem;
            }, function() {
                $log.info('Modal dismissed at: ' + new Date());
                //$scope.reloadData();
            });
        }

        $scope.dtInstanceCustomerEmployeeDocumentCallback = function(instance) {
            $scope.dtInstanceCustomerEmployeeDocument = instance;
        }

        $scope.reloadData = function() {
            $scope.dtInstanceCustomerEmployeeDocument.reloadData();
        };

        $scope.onShowCancelledChange = function() {
            request.hideCanceled = $scope.$storage.hideCanceled ? 1 : 0;
            $scope.reloadData();
        }

    }
]);

app.controller('ModalInstanceDeniedCtrl', function($scope, $uibModalInstance, document, action, $log, $timeout, SweetAlert, $http) {

    $scope.document = document;

    $scope.document.tracking = {
        action: action,
        description: ""
    }

    $scope.onClose = function() {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function() {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onSave = function() {
        save();
    };

    var save = function() {
        var req = {};

        var data = JSON.stringify($scope.document);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/customer-employee/document/denied',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {

            $timeout(function() {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $scope.onCancel();
            });
        }).catch(function(e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function() {

        });

    };
});

app.controller('ModalInstanceFilterdCtrl', function($rootScope, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, DTColumnBuilder, DTOptionsBuilder, $compile, CustomerEmployeeDocumentService, ListService) {

    var params = null;
    var $lastSearch = '';

    $scope.toggle = {
        isChecked: false,
        selectAll: false
    };

    $scope.records = {
        hasSelected: false,
        countSelected: 0,
        countSelectedAll: 0
    };

    var $selectedItems = {};
    var $uids = {};
    var $currentPageUids = {};

    var initialize = function() {
        $scope.filter = {
            status: null,
            required: null,
            verified: null,
        }
    }

    initialize();

    getList();

    function getList() {

        var entities = [
            { name: 'customer_document_status', value: null },
            { name: 'yes_no_options', value: null },
            { name: 'customer_document_verified', value: null },
        ];

        ListService.getDataList(entities)
            .then(function(response) {
                $scope.statusList = response.data.data.customer_document_status.filter(function(item, index, array) {
                    return item.value != 2 ;
                });

                $scope.requiredList = response.data.data.activeOptions.map(function(item, index, array) {
                    return {
                        item: item.name,
                        value: item.value
                    };
                });

                $scope.verifiedList = response.data.data.customer_document_verified.map(function(item, index, array) {
                    return {
                        item: item.name,
                        value: item.value
                    };
                });
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.onClose = function() {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function() {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClearFilter = function() {
        initialize();
        $scope.reloadData();
    };

    $scope.onContinue = function() {
        var documents = [];
        angular.forEach($selectedItems, function (uid, key) {
            if ($selectedItems[key].selected) {
                documents.push(key);
            }
        });

        CustomerEmployeeDocumentService.setDocuments(documents);
        $rootScope.$emit('employeeDocumentNavigate', { newValue: 'validate'});
    };

    $scope.dtOptionsCustomerEmployeeDocumentFilter = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.customerEmployeeId = $scope.$parent.currentEmployee;
                d.statusCode = '2'
                d.filter = {
                    filters: prepareFilters()
                };
                params = d;
                return JSON.stringify(d);
            },
            dataSrc: function (response) {
                $currentPageUids = response.data.map(function(item, index, array) {
                    return item.id;
                })

                $uids = response.extra;

                angular.forEach($uids, function (uid, key) {
                    if ($selectedItems[uid] === undefined || $selectedItems[uid] === null) {
                        $selectedItems[uid] = {
                            selected: false
                        };
                    }
                });

                $scope.records.currentPage = $currentPageUids.length;
                $scope.records.total = $uids.length;

                if ($lastSearch !== params.search.value) {
                    $scope.toggle.isChecked = false;
                    $scope.toggle.selectAll = false;
                    onCheck($uids, $scope.toggle.isChecked, true);
                    $lastSearch = params.search.value;
                }

                return response.data;
            },
            url: 'api/customer-employee-document-filter',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function() {
                // Aqui inicia el loader indicator
            },
            complete: function() {}
        })
        .withDataProp('data')
        .withOption('order', [
            [0, 'desc']
        ])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function() {
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function() {
            //log.info("fnDrawCallback");
            loadRow();
            //Pace.stop();

        })
        /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })

    .withPaginationType('full_numbers')
        .withOption('createdRow', function(row, data, dataIndex) {

            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });;

    $scope.dtColumnsCustomerEmployeeDocumentFilter = [
        DTColumnBuilder.newColumn(null).withOption('width', 30)
        .notSortable()
        .withClass("center")
        .renderWith(function (data, type, full, meta) {
            var checkTemplate = '';
            if (data.statusCode != "RT") {
                var isChecked = $selectedItems[data.id].selected;
                var checked = isChecked ? "checked" : ""

                checkTemplate = '<div class="checkbox clip-check check-danger ">' +
                    '<input class="selectedRow" type="checkbox" id="chk_participant_select_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label class="padding-left-10" for="chk_participant_select_' + data.id + '"> </label></div>';
            }

            return checkTemplate;
        }),
        DTColumnBuilder.newColumn('requirement').withTitle("Tipo Documento").withOption('width', 200),
        DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200),
        DTColumnBuilder.newColumn('startDate').withTitle("Fecha de Inicio Vigencia").withOption('width', 200),
        DTColumnBuilder.newColumn('endDate').withTitle("Fecha de Finalización Vigencia").withOption('width', 200),
        DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200),
        DTColumnBuilder.newColumn(null).withTitle('Requerido').withOption('width', 200).notSortable()
        .renderWith(function(data, type, full, meta) {

            var actions = "";

            var checked = (data.isRequiredCode == '1') ? "checked" : ""

            var label = (data.isRequiredCode == '1') ? "Si" : "No"

            var editTemplate = '<div class="checkbox clip-check check-success ">' +
                '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id + '">' + label + '</label></div>';

            actions += editTemplate;

            return actions;
        })
        .notSortable(),
        DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
        .renderWith(function(data, type, full, meta) {
            var label = '';
            switch (data) {
                case "Vigente":
                    label = 'label label-success';
                    break;

                case "Anulado":
                    label = 'label label-danger';
                    break;

                case "Vencido":
                    label = 'label label-warning';
                    break;
            }

            var status = '<span class="' + label + '">' + data + '</span>';

            return status;
        }),
        DTColumnBuilder.newColumn('isVerified').withTitle("Verificado").withOption('width', 200)
        .renderWith(function(data, type, full, meta) {
            var label = '';
            switch (data) {
                case "Aprobado":
                    label = 'label label-success';
                    break;

                case "Denegado":
                    label = 'label label-danger';
                    break;
            }

            var status = '<span class="' + label + '">' + data + '</span>';

            return status;
        }),
        DTColumnBuilder.newColumn('observation').withTitle("Motivo Denegado").withOption('width', 200),
    ];

    var loadRow = function() {
        angular.element("#dtCustomerEmployeeDocumentFilter input.selectedRow").on("change", function () {
            var id = angular.element(this).data("id");

            if (this.className == 'selectedRow') {
                $selectedItems[id].selected = this.checked;
            }

            $timeout(function () {
                var countSelected = 0;

                angular.forEach($selectedItems, function (value, key) {
                    countSelected += value.selected ? 1 : 0;
                });

                $scope.records.hasSelected = countSelected > 0;
                $scope.records.countSelected = countSelected;
            }, 100);
        });
    };

    var prepareFilters = function() {
        var filters = [];
        if ($scope.filter.status) {
            filters.push(prepareFilter('statusCode', 'eq', 'and', $scope.filter.status.value));
        }

        if ($scope.filter.required) {
            filters.push(prepareFilter('isRequiredCode', 'eq', 'and', $scope.filter.required.value));
        }

        if ($scope.filter.verified) {
            filters.push(prepareFilter('isVerified', 'eq', 'and', $scope.filter.verified.value));
        }

        return filters;
    };

    var prepareFilter = function(field, operator, condition, value) {
        return {
            field: field,
            operator: operator,
            condition: condition,
            value: value
        };
    }

    $scope.dtInstanceCustomerEmployeeDocumentFilter = {};
    $scope.dtInstanceCustomerEmployeeDocumentFilterCallback = function(instance) {
        $scope.dtInstanceCustomerEmployeeDocumentFilter = instance;
    }

    $scope.reloadData = function() {
        $scope.dtInstanceCustomerEmployeeDocumentFilter.reloadData();
    };

    $scope.onToggle = function () {
        $scope.toggle.isChecked = !$scope.toggle.isChecked;
        onCheck($currentPageUids, $scope.toggle.isChecked);
        //$scope.reloadData();
    };

    $scope.onSelectCurrentPage = function () {
        $scope.toggle.isChecked = true;
        if ($scope.toggle.selectAll) {
            onCheck($uids, false);
            $scope.toggle.selectAll = false;
        }
        onCheck($currentPageUids, $scope.toggle.isChecked);
    };

    $scope.onSelectAll = function () {
        $scope.toggle.isChecked = true;
        $scope.toggle.selectAll = true;
        onCheck($uids, $scope.toggle.selectAll);
    };

    $scope.onDeselectAll = function () {
        $scope.toggle.isChecked = false;
        $scope.toggle.selectAll = false;
        onCheck($uids, $scope.toggle.selectAll);
    };

    var onCheck = function($items, $isCheck, $forceUnCheck) {
        var countSelected = 0;

        angular.forEach($selectedItems, function (uid, key) {
            if ($forceUnCheck !== undefined && $forceUnCheck) {
                $selectedItems[key].selected = false;
            }

            if ($items.indexOf(parseInt(key)) !== -1) {
                $selectedItems[key].selected = $isCheck;
            }
            countSelected += $selectedItems[key].selected ? 1 : 0;
        });

        var $elements = angular.element('.selectedRow');
        angular.forEach($elements, function (elem, key) {
            ////console.log(key, elem);
            var $uid = angular.element(elem).data("id");
            angular.element(elem).prop( "checked", $selectedItems[$uid].selected);
        });

        $scope.records.hasSelected = countSelected > 0;
        $scope.records.countSelected = countSelected;
    }
});
