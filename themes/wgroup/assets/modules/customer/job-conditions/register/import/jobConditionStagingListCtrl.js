'use strict';
/**
 * controller for Customers
 */
app.controller('JobConditionStagingListCtrl', function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
    $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService, $localStorage) {

    var log = $log;
    var $exportUrl = '';

    function getList() {
        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function(response) {
                $exportUrl = response.data.data.exportUrl.item;
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }
    getList();


    var storeDatatable = 'jobConditionsStagingListCtrl-' + window.currentUser.id;
    $scope.dtInstanceJobConditionStaging = {};
    $scope.dtOptionsJobConditionStaging = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                    d.filter = {
                        filters: $scope.audit.filters.filter(function(filter) {
                            return filter != null && filter.field != null && filter.criteria != null;
                        }).map(function(filter, index, array) {
                            return {
                                field: filter.field.name,
                                operator: filter.criteria.value,
                                value: filter.value,
                                condition: filter.condition.value,
                            };
                        })
                    };
                }

                d.customerId = $stateParams.customerId;
                d.sessionId = $stateParams.sessionId;
                return JSON.stringify(d);
            },
            url: 'api/job-conditions-staging',
            type: 'POST',
            beforeSend: function() {},
            complete: function() {}
        })
        .withDataProp('data')
        .withOption('stateSave', true)
        .withOption('stateSaveCallback', function(settings, data) {
            $localStorage[storeDatatable] = data;
        })
        .withOption('stateLoadCallback', function() {
            return $localStorage[storeDatatable];
        })
        .withOption('order', [
            [1, 'asc']
        ])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function() {
            return true;
        })
        .withOption('fnDrawCallback', function() {
            loadRow();
        })
        .withOption('language', {})
        .withPaginationType('full_numbers')
        .withOption('createdRow', function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsJobConditionStagingDT = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
        .renderWith(function(data) {
            var disabled = "";
            var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                '   <i class="fa fa-edit"></i></a> ';
            var actions = data.isValid == 0 || !data.isValid ? editTemplate : '';
            return actions;
        }),
        DTColumnBuilder.newColumn(null).withTitle('Fila').withOption('width', 50)
        .renderWith(function(data, type, full, meta) {
            if (data.isValid == 1) {
                var $class = 'badge badge-success';
                var $icon = ' | <i class=" fa fa-check"></i>';
                var $info = "Es correcto";
            } else {
                var $class = 'badge badge-danger';
                var $icon = ' | <i class=" fa fa-ban"></i>';
                var $info = data.errors;
            }
            return '<span uib-tooltip="' + $info + '" class="' + $class + '">' + data.index + $icon + '</span>';
        }),

        DTColumnBuilder.newColumn('documentType').withTitle("Tipo Identificación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('document_number').withTitle("Número Identificación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('registrationDate').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('workmodel').withTitle("Modelo de trabajo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('location').withTitle("Lugar de trabajo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('workplace').withTitle("Puesto de trabajo").withOption('width', 200).withOption('defaultContent', ''),
    ];

    var loadRow = function() {
        angular.element("#dataJobConditionStagingDT a.editRow").on("click", function() {
            var id = angular.element(this).data("id");
            onEdit(id);
        });
    }

    $scope.reloadData = function() {
        $scope.dtInstanceJobConditionsRegister.reloadData();
    };

    $scope.onCreate = function() {
        if ($scope.$parent != null) {
            $scope.$parent.navToSection("edit", false, 0);
        }
    };

    $scope.dtInstanceEmployeeStagingDTCallback = function(instance) {
        $scope.dtInstanceJobConditionStaging = instance;
    };

    $scope.reloadData = function() {
        $scope.dtInstanceJobConditionStaging.reloadData();
    };

    var onEdit = function(id) {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/job-conditions/register/import/job_condition_staging_modal.htm",
            placement: 'right',
            windowTopClass: 'top-modal',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideJobConditionsStagingEditCtrl',
            scope: $scope,
            resolve: {
                dataItem: function() {
                    return { id: id ? id : 0 };
                },
                isView: function() {
                    return $scope.isView;
                }
            }
        });
        modalInstance.result.then(function() {
            $scope.reloadData();
        }, function() {
            $scope.reloadData();
        });
    };

    $scope.onCancel = function() {
        if ($scope.$parent != null) {
            $scope.$parent.navToSection("list", "list");
        }
    }

    $scope.onProcess = function() {
        SweetAlert.swal({
                title: "Confirma la importación de los registros?",
                text: "Se importarán los registros válidos. Una vez realizado este proceso no se podrán realizar cambios.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Si, confirmar!",
                cancelButtonText: "No, cancelar!",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function(isConfirm) {
                if (isConfirm) {

                    return $http({
                        method: 'POST',
                        url: $exportUrl + 'api/v1/job-conditions-import/confirm',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        data: $.param({
                            id: $stateParams.customerId,
                            sessionId: $stateParams.sessionId,
                            hasCustomerEmployeeId: $rootScope.hasCustomerEmployeeId
                        })
                    }).then(function(response) {
                        $timeout(function() {
                            SweetAlert.swal("Registro", "La información ha sido importada satisfactoriamente", "success");
                            $scope.onCancel();
                        });
                    }).catch(function(e) {
                        $log.error(e);
                        SweetAlert.swal("Error de guardado", e.data.message, "error");
                    }).finally(function() {});
                }
            });
    }

});

app.controller('ModalInstanceSideJobConditionsStagingEditCtrl', function($rootScope, $stateParams, $scope, dataItem, isView, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, $document, $filter, $aside, ListService) {

    $scope.onCloseModal = function() {
        $uibModalInstance.close(null);
    };

    $scope.onCancel = function() {
        $uibModalInstance.dismiss('cancel');
    };

    var log = $log;
    $scope.employee = [];
    $scope.workplaceList = [];
    $scope.documentTypes = $rootScope.parameters("employee_document_type");
    $scope.modelWork = $rootScope.parameters("wg_customer_job_conditions_work_model");
    $scope.location = $rootScope.parameters("wg_customer_job_conditions_location");
    $scope.jobs = [];
    $scope.showAuthorized = $rootScope.isAuthorizationTemplate && $rootScope.can('empleado_authorize');

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        format: "dd/MM/yyyy"
    };

    var init = function() {
        $scope.employee = {
            id: dataItem.id,
            index: null,
            customerId: null,
            documentType: null,
            documentNumber: null,
            registrationDate: null,
            workmodel: null,
            location: null,
            job: null,
            workplace: null,
            sessionId: null,
            isActive: null,
            isAuthorized: null,
            errors: null,
            isView: false
        };
    };
    init();

    function getList() {
        var $criteria = {
            customerId: $stateParams.customerId
        }

        var entities = [
            { name: 'customer_job', value: $stateParams.customerId, criteria: $criteria },
            { name: 'workplaces' }
        ];
        ListService.getDataList(entities)
            .then(function(response) {
                $scope.jobs = response.data.data.jobList;
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });

    }
    getList();

    $scope.onSearchEmployee = function() {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/employee_list_modal.htm",
            placement: 'right',
            windowTopClass: 'top-modal',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideJobConditionsStagingEmployeeListCtrl',
            jobConditios: '1',
            scope: $scope,
        });

        modalInstance.result.then(function(response) {
            $scope.employee.documentNumber = response.entity.documentNumber;
            $scope.employee.documentType = response.entity.documentType;
        });
    };

    $scope.onLoadRecord = function() {
        if ($scope.employee.id != 0) {
            $http({
                    method: 'GET',
                    url: 'api/job-conditions-staging/get',
                    params: {
                        id: $scope.employee.id
                    }
                })
                .catch(function(e, code) {})
                .then(function(response) {
                    $timeout(function() {
                        $scope.employee = response.data.result;
                    });
                }).finally(function() {});
        }
    }

    $scope.onLoadRecord();

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
                log.info($scope.employee);
                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                save();
            }

        },
        reset: function(form) {}
    };

    var save = function() {
        var req = {};
        var data = JSON.stringify($scope.employee);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/job-conditions-staging/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            $timeout(function() {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $scope.onCloseModal();
            });
        }).catch(function(e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function() {

        });
    };

});
