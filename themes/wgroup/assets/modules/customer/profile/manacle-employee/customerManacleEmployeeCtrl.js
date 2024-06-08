'use strict';
/**
 * controller for Customers
 */
app.controller('customerManacleEmployeeCtrl',
    function ($scope, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document) {

        $scope.isView = $scope.$parent.editMode == "view";
        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        var init = function() {
            $scope.entity = {
                id: 0,
                registrationDate: new Date(),
                customerId: $stateParams.customerId,
                manacleNumber: null,
                manacleId: null,
                isActive: true,
                customerEmployeeId: null,
                firstName: null,
                lastName: null,
                documentNumber: null,
                documentType: null
            }

            $scope.entity.registrationDate = $scope.maxDate;
        }
        init();

        $scope.onLoadRecord = function () {
            if ($scope.entity.id != 0) {
                var req = {
                    id: $scope.entity.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer-manacle-employee/get',
                    params: req
                })
                .catch(function (response) {})
                .then(function (response) {
                    $timeout(function () {
                        $scope.entity = response.data.result;
                        if($scope.entity.registrationDate) {
                            $scope.entity.registrationDate = new Date($scope.entity.registrationDate.date)
                        }
                    });
                }).finally(function () {});
            }
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
                    save();
                }
            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-manacle-employee/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.entity = response.data.result;
                    init();
                    $scope.reloadDataManacle();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message , "error");
            }).finally(function () {});
        };

        $scope.cancelEdition = function (index) {
            init();
        };

        $scope.dtInstanceManacle = {};
        $scope.dtOptionsManacle = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-manacle-employee',
                type: 'POST',
                beforeSend: function () {
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withOption('language', {
            })
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsManacle = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if(!$scope.isView) {
                        actions += editTemplate;
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('registrationDate').withTitle("Fecha").withOption('width', 200),
            DTColumnBuilder.newColumn('manacleNumber').withTitle("Id Manilla").withOption('width', 200),
            DTColumnBuilder.newColumn('documentType').withTitle("Tipo Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('documentNumber').withTitle("Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('firstName').withTitle("Nombre(s)").withOption('width', 200),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
            DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 200)
            .renderWith(function (data) {
                var label = 'label label-success';
                if (data == "Inactivo") {
                    label = 'label label-danger';
                }
                var status = '<span class="' + label +'">' + data + '</span>';
                return status;
            }),
        ];

        var loadRow = function () {

            angular.element("#dtManacleEmployee a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onEdit(id);
            });

            angular.element("#dtManacleEmployee a.delRow").on("click", function () {
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
                                url: 'api/customer-manacle-employee/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error de guardado", e.data.message , "error");
                            }).finally(function () {
                                $scope.reloadDataManacle();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.onEdit = function (id) {
            $scope.entity.id = id;
            $scope.onLoadRecord();
        };

        $scope.dtInstanceManacleCallback = function (instance) {
            $scope.dtInstanceManacle = instance;
        };

        $scope.reloadDataManacle = function () {
            $scope.dtInstanceManacle.reloadData();
        };

        $scope.onSearchManacle = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceManacleCtrl',
                scope: $scope,
                windowTopClass: 'top-modal',
                resolve: {
                }
            });
            modalInstance.result.then(function (manacle) {
                $scope.entity.manacleId = manacle.id;
                $scope.entity.manacleNumber = manacle.number;
            });
        };

        $scope.onSearchEmployee = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideManacleEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (employee) {
                $scope.entity.documentType = employee.entity.documentType ? employee.entity.documentType.item : null;
                $scope.entity.documentNumber = employee.entity.documentNumber;
                $scope.entity.firstName = employee.entity.firstName;
                $scope.entity.lastName = employee.entity.lastName;
                $scope.entity.customerEmployeeId = employee.id;
            });
        };

        $scope.onImport = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/manacle-employee/customer_profile_manacle_employee_import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerManacleEmployeeImportCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                $scope.reloadDataManacle()
            }, function() {});
        };

});


app.controller('ModalInstanceManacleCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'MANILLAS DISPONIBLES';
    $scope.entity = {};
    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.entity);
    };
    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var onLoadRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id,
            };
            $http({
                method: 'GET',
                url: 'api/customer-manacle/get',
                params: req
            })
            .catch(function (e, code) {
                SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información de la manilla", "error");
            })
            .then(function (response) {
                $timeout(function () {
                    $scope.entity = response.data.result;
                });
            }).finally(function () {
                $timeout(function () {
                    $scope.onCloseModal();
                });
            });
        } else {
            $scope.loading = false;
        }
    }

    $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.customerId = $stateParams.customerId;
                d.isActive = "Activo";
                return JSON.stringify(d);
            },
            url: 'api/customer-manacle',
            contentType: 'application/json',
            type: 'POST',
            beforeSend: function () {},
            complete: function () {}
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
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
    ;

    $scope.dtColumnsCommonDataTableList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var disabled = "";
                var addTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar manilla"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';
                actions += addTemplate;
                return actions;
            }),

        DTColumnBuilder.newColumn('registrationDate').withTitle("Fecha").withOption('width', 200),
        DTColumnBuilder.newColumn('number').withTitle("Id Manilla").withOption('width', 200),
        DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 200)
            .renderWith(function (data) {
                var label = 'label label-success';
                var status = '<span class="' + label +'">' + data + '</span>';
                return status;
            }),
    ];

    var loadRow = function () {
        angular.element("#dtCommonDataTableList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            onLoadRecord(id);
        });
    };

    $scope.dtInstanceCommonDataTableListCallback = function (instance) {
        $scope.dtInstanceCommonDataTableList = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceCommonDataTableList.reloadData();
    };

});

app.controller('ModalInstanceSideManacleEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.employee = {};
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
                SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del empleado", "error");
            })
            .then(function (response) {
                $timeout(function () {
                    $scope.employee = response.data.result;
                });
            }).finally(function () {
                $timeout(function () {
                    $scope.onCloseModal();
                });
            });
        } else {
            $scope.loading = false;
        }
    }

    $scope.dtInstanceDisabilityEmployeeList = {};
    $scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.customerId = $stateParams.customerId;
                d.isActive = 1;
                return JSON.stringify(d);
            },
            url: 'api/customer-employee-modal-basic',
            contentType: 'application/json',
            type: 'POST',
            beforeSend: function () {},
            complete: function () {}
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return true;
        })
        .withOption('fnDrawCallback', function () {
            loadRow();
        })
        .withOption('language', {
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });
    ;

    $scope.dtColumnsDisabilityEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
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
        angular.element("#dtDisabilityEmployeeList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
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


app.controller('ModalInstanceSideCustomerManacleEmployeeImportCtrl', function ($rootScope, ngNotify, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/customer-manacle-employee-import',
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
                uploader.url = $exportUrl + 'api/v1/customer-manacle-employee-import';
                $scope.uploader.url = $exportUrl + 'api/v1/customer-manacle-employee-import';
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function () {
        angular.element("#downloadDocument")[0].src = "api/customer-manacle-employee/download-template?customerId=" + $stateParams.customerId;
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
