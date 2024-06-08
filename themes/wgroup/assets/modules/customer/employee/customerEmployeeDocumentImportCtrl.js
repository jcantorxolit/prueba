'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeDocumentImportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', 'SweetAlert', '$http', '$filter', '$document', 'FileUploader',
    '$uibModal', '$localStorage', '$aside', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
        $compile, toaster, $state, $rootScope, $timeout, SweetAlert, $http, $filter, $document,
        FileUploader, $uibModal, $localStorage, $aside, ListService) {


        var log = $log;
        var request = {};
        var attachmentUploadedId = [];

        log.info("loading..customerEmployeeDocumentListCtrl DAB");

        $scope.$storage = $localStorage.$default({
            hideCanceled: true
        });

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";
        $scope.canShowCheck = $rootScope.can("clientes_empleado_documentos_masivos_check");

        // parametros para seguimientos

        $scope.documentStatus = $rootScope.parameters("customer_document_status");
        $scope.isView = $scope.$parent.editMode == "view";
        $scope.isInvalidate = false;
        $scope.customerId = $stateParams.customerId;
        $scope.downloadUrl = "";

        getList();

        function getList() {

            var entities = [
                { name: 'customer_employee_document_type', value: $stateParams.customerId }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.requirements = response.data.data.customerEmployeeDocumentType;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var initialize = function () {
            $scope.attachment = {
                id: 0,
                customerId: $scope.customerId,
                requirement: null,
                status: $scope.documentStatus ? $scope.documentStatus[0] : null,
                version: 1,
                description: "",
                startDate: null,
                endDate: null,
                isApprove: false,
                created_at: null,
                toApplyAll: false,
                employeeList: []
            };

            $scope.employeeList = [];
        }

        initialize();

        $scope.master = $scope.attachment;

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

                    if ($scope.uploader.queue.length == 0) {
                        SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un anexo e Intentalo de nuevo.", "error");
                        return;
                    }
                    //your code for submit
                    save();
                }

            },
            reset: function (form) {

                $scope.attachment = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {

            $scope.attachment.employeeList = [];

            angular.forEach($scope.employeeList, function (v, k) {
                console.log(v);
                $scope.attachment.employeeList.push(v.id);
            });

            var req = {};
            var data = JSON.stringify($scope.attachment);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-employee/document/import',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    attachmentUploadedId = response.data.result.id;
                    uploader.uploadAll();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        var uploader = $scope.uploader = new FileUploader({
            url: 'api/customer-employee/document/upload-bulk',
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
            var formData = { id: attachmentUploadedId };
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
            initialize();
            SweetAlert.swal("Validación exitosa", "Se guardó satisfactoriamente", "success");
        };


        //----------------------------------------------------------------EMPLOYEE

        $scope.onAddEmployee = function () {

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/employee/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerEmployeeSearchListCtrl',
                scope: $scope,
                resolve: {
                    customer: function () {
                        return { id: $stateParams.customerId };
                    }
                }
            });
            modalInstance.result.then(function (employee) {
                initializeEmployee(employee);
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });

        };

        $scope.onRemoveEmployee = function (index) {
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
                            $scope.employeeList.splice(index, 1);
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        $scope.onUpload = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideImportEmployeeDocumentCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                $scope.showEmployeeLessImport(response.result)
            });

        };

        $scope.showEmployeeLessImport = function (documents) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/employee/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerEmployeeLessImportCtrl',
                scope: $scope,
                resolve: {
                    customer: function () {
                        return { id: $stateParams.customerId };
                    },
                    documents: function () {
                        return documents;
                    },
                }
            });
            modalInstance.result.then(function (employees) {
                employees.forEach(function (data) {
                    initializeEmployee(data);
                });
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        };


        var initializeEmployee = function (employee) {
            var result = $filter('filter')($scope.employeeList, { id: employee.id }, true);

            if (result.length == 0) {
                $scope.employeeList.push(employee);
            }
        }

        $scope.onToApplyAll = function () {
            console.log($scope.attachment.toApplyAll);
            if ($scope.attachment.toApplyAll) {
                $scope.employeeList = $scope.attachment.employeeList;
            } else {
                $scope.attachment.employeeList = $scope.employeeList;
            }


        };

        log.info("loading..customerEmployeeDocumentListCtrl DAB");

        $scope.afterEmployeeAdded = function (employee) {
            initializeEmployee(employee);
        }

        $scope.onCancel = function () {
            initialize();
        }

    }]);

app.controller('ModalInstanceDeniedCtrl', function ($scope, $uibModalInstance, document, action, $log, $timeout, SweetAlert, $http) {

    console.log($scope.$parent);
    if ($scope.attachment.toApplyAll) {
        $scope.employeeList = $scope.attachment.employeeList;
        console.log($scope.employeeList)


    } else {
        $scope.attachment.employeeList = $scope.employeeList;
        console.log($scope.attachment.employeeList)
    }

    $scope.document = document;

    $scope.document.tracking = {
        action: action,
        description: ""
    }

    $scope.onClose = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onSave = function () {
        save();
    };

    var save = function () {
        var req = {};

        var data = JSON.stringify($scope.document);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/customer-employee/document/denied',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $scope.onCancel();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };
});

app.controller('ModalInstanceSideCustomerEmployeeSearchListCtrl', function ($rootScope, $stateParams, $scope, customer, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    console.log($scope.$parent);
    $scope.title = 'Empleados';

    var request = {};

    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.employee.id != 0) {
            var req = {
                id: $scope.employee.id,
            };
            $http({
                method: 'GET',
                url: 'api/customer-employee',
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
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        //$scope.employee = response.data.result;
                        $scope.$parent.afterEmployeeAdded(response.data.result);
                    });

                }).finally(function () {
                    $timeout(function () {

                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }



    $scope.dtInstanceDisabilityEmployeeList = {};
    $scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customerId = customer.id;
                return JSON.stringify(d);
            },
            url: 'api/customer-employee-modal-basic',
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

    $scope.dtColumnsDisabilityEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                console.log(data);

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar empleado" tooltip-placement="right"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200),
        DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200),
        DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
        DTColumnBuilder.newColumn('workPlace').withTitle("Centro de Trabajo").withOption('width', 200),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200),
        DTColumnBuilder.newColumn('neighborhood').withTitle("Centro de Costos").withOption('width', 200),
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
                var text = 'Inactivo';

                if (data.isAuthorized != null || data.isAuthorized != undefined) {
                    if (data.isAuthorized == 'Autorizado') {
                        label = 'label label-success';
                        text = 'Autorizado';
                    } else if (data.isAuthorized == 'No Autorizado') {
                        label = 'label label-danger';
                        text = 'No Autorizado';
                    } else {
                        label = 'label label-info';
                        text = 'N/A';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            })
    ];

    var loadRow = function () {
        $("#dtDisabilityEmployeeList a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editDisabilityEmployee(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityEmployeeList.reloadData();
    };

    $scope.viewDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});



app.controller('ModalInstanceSideCustomerEmployeeLessImportCtrl', function ($rootScope, $q, $scope, customer, documents, $uibModalInstance, SweetAlert, $http, $filter, DTColumnBuilder, DTOptionsBuilder, $compile, $aside) {

    $scope.title = 'Empleados Importados';
    $scope.messageInfo = 'Al presionar "Cargar", solo se procesaran los registros correctos.';
    $scope.showProcessBtn = true;
    $scope.employees = [];

    $scope.onCloseModal = function () {
        var parse = [];
        $scope.employees.forEach(function (data, index) {
            if (data.id) {
                parse.push({
                    id: data.id,
                    customerId: data.customer_id,
                    entity: {
                        documentNumber: data.documentNumber,
                        fullName: data.firstName + " " + data.lastName,
                    },
                    workPlace: {
                        name: data.workPlace
                    }
                });
            }
        })
        $uibModalInstance.close(parse);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var request = {};
    request.operation = "restriction";
    request.customerId = customer.id;
    request.documentNumber = documents;

    var getTableData = function () {
        var deferred = $q.defer();
        if ($scope.employees.length) {
            deferred.resolve($scope.employees);
        } else {
            $http({
                method: 'POST',
                url: 'api/customer-employee-less',
                data: request
            }).then(function (response) {
                console.log(response);
                $scope.employees = response.data.data;
                deferred.resolve($scope.employees);
            });
        }
        return deferred.promise;
    };

    $scope.dtInstanceDisabilityEmployeeList = {};
    $scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.fromFnPromise(getTableData)
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('order', [[0, 'desc']])
        .withOption('processing', true)
        .withDataProp('data')
        .withOption('fnPreDrawCallback', function () {
            return true;
        })
        .withOption('fnDrawCallback', function () {
            loadRow();
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });

    $scope.dtColumnsDisabilityEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data) {
                var actions = "";
                var disabled = ""
                if (data.id == null) {
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar registro" tooltip-placement="right"  data-id="' + data.documentNumber + '"' + disabled + ' >' +
                        '   <i class="fa fa-pencil"></i></a> ';
                    actions += editTemplate;
                } else {
                    var checkTemplate = '<a class="btn btn-success btn-xs lnk" href="#" uib-tooltip="Registro correcto" tooltip-placement="right"  data-id="' + data.documentNumber + '"' + disabled + ' >' +
                        '   <i class="fa fa-check-circle"></i></a> ';
                    actions += checkTemplate;
                }
                var quitTemplate = '<a class="btn btn-danger btn-xs quitRow lnk" href="#" uib-tooltip="Quitar registro" tooltip-placement="right"  data-id="' + data.documentNumber + '"' + disabled + ' >' +
                    '   <i class="fa fa-trash"></i></a> ';
                actions += quitTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200),
        DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200),
        DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
        DTColumnBuilder.newColumn('workPlace').withTitle("Centro de Trabajo").withOption('width', 200),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200),
        DTColumnBuilder.newColumn('neighborhood').withTitle("Centro de Costos").withOption('width', 200),
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
                var label = '';
                var text = '';

                if (data.isAuthorized != null || data.isAuthorized != undefined) {
                    if (data.isAuthorized == 'Autorizado') {
                        label = 'label label-success';
                        text = 'Autorizado';
                    } else if (data.isAuthorized == 'No Autorizado') {
                        label = 'label label-danger';
                        text = 'No Autorizado';
                    } else {
                        label = 'label label-info';
                        text = 'N/A';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            })
    ];

    var loadRow = function () {
        $("#dtDisabilityEmployeeList a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.onEdit(id);
        });

        $("#dtDisabilityEmployeeList a.quitRow").on("click", function () {
            var id = $(this).data("id");
            $scope.employees.forEach(function (data, index) {
                if (data.documentNumber == id) {
                    $scope.employees.splice(index, 1);
                    $scope.reloadData();
                    return;
                }
            })
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityEmployeeList.reloadData();
    };

    $scope.onEdit = function (document) {
        $scope.documemtEmployee = document;
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/employee_create_modal.htm",
            placement: 'right',
            windowTopClass: 'top-modal',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideEmployeeLessImportEditCtrl',
            scope: $scope
        });
        modalInstance.result.then(function (response) {
            $scope.employees.forEach(function (data, index) {
                if (data.documentNumber == $scope.documemtEmployee) {
                    $scope.employees[index] = $scope.parseDataEmployee(response);
                    $scope.reloadData();
                    return;
                }
            })
        });
    };

    $scope.parseDataEmployee = function (data) {
        return {
            id: data.id,
            documentNumber: data.entity.documentNumber,
            firstName: data.entity.firstName,
            lastName: data.entity.lastName,
            workPlace: data.workPlace.name,
            job: data.job.name,
            neighborhood: data.entity.neighborhood,
            countAttachment: null,
            isActiveCode: null,
            isAuthorized: data.isAuthorized,
            employeeDocumentType: null,
            customerId: data.customerId,
            employeeId: data.entity.id,
            isActive: data.isActive
        }
    }

});

app.controller('ModalInstanceSideEmployeeLessImportEditCtrl',
    function ($scope, $stateParams, $state, $rootScope, $timeout, $http, SweetAlert, $uibModalInstance) {

        var $formInstance = null;
        $scope.genders = $rootScope.parameters("gender");
        $scope.documentTypes = $rootScope.parameters("employee_document_type");
        $scope.title = "EDITAR REGISTRO EMPLEADO";
        $scope.filter = true;

        var onInit = function () {
            $scope.employee = {
                id: 0,
                customerId: $stateParams.customerId,
                isActive: false,
                contractType: null,
                occupation: '',
                job: null,
                workPlace: null,
                salary: 0,
                isAuthorized: false,
                entity: {
                    id: 0,
                    documentType: null,
                    documentNumber: $scope.documemtEmployee,
                    expeditionPlace: "",
                    expeditionDate: "",
                    firstName: "",
                    lastName: "",
                    birthDate: "",
                    gender: null,
                    profession: null,
                    eps: null,
                    afp: null,
                    arl: null,
                    country: null,
                    state: null,
                    city: null,
                    rh: "",
                    riskLevel: 0,
                    neighborhood: "",
                    observation: "",
                    logo: "",
                    details: [],
                    isActive: false,
                    age: null
                },
                validityList: [],
            };


            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        };
        onInit();

        $scope.onValide = function () {
            var req = {
                documentNumber: $scope.employee.entity.documentNumber,
                customerId: $scope.employee.customerId
            };
            $http({
                method: 'GET',
                url: 'api/customer-employee-v3',
                params: req
            })
                .then(function (response) {
                    var res = response.data.result;
                    if (res.id) {
                        $scope.employee = res;
                    }
                }).catch(function (err) {
                    SweetAlert.swal("Empleado no existe!", "Debes digitar un documento válido para continuar.", "error");
                });
        }

        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        }

        $scope.onContinue = function () {
            $uibModalInstance.close($scope.employee);
        }

    }
);


app.controller('ModalInstanceSideImportEmployeeDocumentCtrl', function ($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $lastResponse = null;
    $scope.title = "- Adicionar Empleados Excel";
    console.log("entra 2")

    var uploader = $scope.uploader = new FileUploader({
        url: 'api/customer-employee-less/import',
        formData: []
    });

    $scope.download = function () {
        angular.element("#downloadDocument")[0].src = "api/customer-employee-less/download-template";
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
