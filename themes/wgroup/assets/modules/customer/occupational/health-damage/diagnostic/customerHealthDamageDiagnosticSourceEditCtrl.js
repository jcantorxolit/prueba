'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerHealthDamageDiagnosticSourceEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', '$document',
    function ($scope, $stateParams, $log, $compile, $state,
              SweetAlert, $rootScope, $http, $timeout, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $uibModal, flowFactory, cfpLoadingBar, $filter, $aside, $document) {

        var log = $log;
        $scope.invalid = false;



        var request = {};
        var currentId = $scope.$parent.currentId;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        log.info("loading..customerWorkMedicineEditCtrl con el id de tracking: ", currentId);
        $scope.canShow = false;
        // parametros para seguimientos
        $scope.presumedSources = $rootScope.parameters("work_health_damage_presumed_diagnostic_source");
        $scope.codeCIE10s = $rootScope.parameters("work_health_damage_code_cie_10");

        $scope.arl = $rootScope.parameters("arl");
        $scope.documentType =  $rootScope.parameters("customer_document_type");
        $scope.tiposdoc = $rootScope.parameters("tipodoc");


        $scope.isView = $scope.$parent.modeDsp == "view";
        console.log($scope.isView);
        $scope.minDateCurrent = new Date();
        $scope.customerId = $stateParams.customerId;

        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy HH:mm"
        };

        $scope.employees = [];
        $scope.diagnostics = [];

        $scope.onLoadRecord = function () {
            if ($scope.diagnostic.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.diagnostic.id);
                var req = {
                    id: $scope.diagnostic.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/diagnostic-source',
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
                            $scope.diagnostic = response.data.result;
                            $scope.canShow = true;
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            $document.scrollTop(40, 2000);
                        });

                    });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        }

        $scope.diagnostic = {
            id: currentId,
            employee: null,
            arl: null,
        };

        var init = function () {
            $scope.detail = {
                id: 0,
                customerHealthDamageDiagnosticSourceId: $scope.diagnostic.id,
                codeCIE10: null,
                presumedSource: null,
                diagnostic: "",
            };
        };

        init();

        $scope.onLoadRecord();

        $scope.master = $scope.diagnostic;

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
                    SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                    //your code for submit
                    log.info($scope.customer);
                    save();
                }

            },
            reset: function (form) {

                $scope.diagnostic = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};

            var data = JSON.stringify($scope.diagnostic);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/diagnostic-source/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.diagnostic = response.data.result;
                    request.customer_health_damage_id = $scope.diagnostic.id;
                    $scope.canShow = true;
                    init();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        };

        $scope.cancelEdition = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        //------------------------------------------------------------------------HealthDamageDiagnosticSourceDetail
        request.customer_health_damage_id = currentId;

        $scope.dtInstanceHealthDamageDiagnosticSourceDetail = {};
		$scope.dtOptionsHealthDamageDiagnosticSourceDetail = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.HealthDamageDiagnosticSourceId = $scope.diagnostic.id;;
                    return JSON.stringify(d);
                    },
                url: 'api/customer-health-damage-diagnostic-source-detail',
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

        $scope.dtColumnsHealthDamageDiagnosticSourceDetail = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("seguimiento_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("seguimiento_delete")) {
                        actions += deleteTemplate;
                    }

                    return $scope.isView ? '' : actions;
                }),
            DTColumnBuilder.newColumn('codeCIE10').withTitle("Código CIE10").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Diagnóstico").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('item').withTitle("Presunto Origen del Diagnóstico").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRow = function () {

            $("#dtHealthDamageDiagnosticSourceDetail a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageDiagnosticSourceDetail(id);
            });

            $("#dtHealthDamageDiagnosticSourceDetail a.delRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

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
                                url: 'api/customer/health-damage/diagnostic-source-detail/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageDiagnosticSourceDetail();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageDiagnosticSourceDetailCallback = function(dtInstance) {
            $scope.dtInstanceHealthDamageDiagnosticSourceDetail = dtInstance;
        }

        $scope.reloadDataHealthDamageDiagnosticSourceDetail = function () {
            $scope.dtInstanceHealthDamageDiagnosticSourceDetail.reloadData();
        };

        $scope.editHealthDamageDiagnosticSourceDetail = function (id) {
            if (id) {
                var req = {id: id};
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/diagnostic-source-detail',
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
                            $scope.detail = response.data.result;
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            $document.scrollTop(40, 2000);
                        });

                    });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        };

        $scope.saveHealthDamageDiagnosticSourceDetail = function(){

            if ($scope.detail.codeCIE10 != null && $scope.detail.presumedSource != null){
                var req = {};

                $scope.invalid = false;


                //$scope.diagnostic.examinationDate = $scope.diagnostic.examinationDate.toISOString();

                var data = JSON.stringify($scope.detail);
                req.data = Base64.encode(data);
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/diagnostic-source-detail/save',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        $scope.clearHealthDamageDiagnosticSourceDetail()
                        $scope.reloadDataHealthDamageDiagnosticSourceDetail()
                    });
                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {
                });
            }else{
                $scope.invalid = true;

            }
        }

        $scope.clearHealthDamageDiagnosticSourceDetail = function () {
            init();
        };



        //----------------------------------------------------------------EMPLOYEE

        $scope.onAddEmployee = function() {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/diagnostic/customer_absenteeism_disability_employee_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideHealthDamageSourceEmployeeCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function () {
                //loadEmployees();
            });
        };

        $scope.onAddDisabilityEmployeeList = function() {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/diagnostic/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideHealthDamageEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (employee) {
                //loadEmployees();
                var result = $filter('filter')($scope.employees, {id: employee.id});

                if (result.length == 0) {
                    $scope.employees.push(employee);
                }

                $scope.diagnostic.employee = employee;
            });
        };


        //----------------------------------------------------------------DIAGNOSTICS

        $scope.onAddDisabilityDiagnostic = function(index) {
            if ($scope.diagnostic.employee == null) {
                toaster.pop("error", "Error", "Debe seleccionar primero un empleado");
                return;
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_diagnostic.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/diagnostic/customer_absenteeism_disability_diagnostic_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                resolve: {
                    employee: function () {
                        return $scope.diagnostic.employee;
                    }
                },
                controller: 'ModalInstanceSideHealthDamageDiagnosticListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (diagnostic) {
                //loadEmployees();

                var result = $filter('filter')($scope.diagnostics, {id: diagnostic.id});

                if (result.length == 0) {
                    $scope.diagnostics.push(diagnostic);
                }

                $scope.detail.codeCIE10 = diagnostic;
            });
        };

    }]);

app.controller('ModalInstanceSideHealthDamageSourceEmployeeCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.contractTypes = $rootScope.parameters("employee_contract_type");
    $scope.documentTypes = $rootScope.parameters("employee_document_type");

    var initialize = function() {
        $scope.employee = {
            id: 0,
            customerId:$stateParams.customerId,
            isActive: true,
            contractType: null,
            job: null,
            workPlace: null,
            salary: 0,
            entity: {
                id: 0,
                documentType: null,
                documentNumber: "",
                firstName: "",
                lastName: "",
                isActive: true
            }
        };
    };

    initialize();

    var loadWorkPlace = function()
    {

        var req = {};
        req.operation = "diagnostic";
        req.customerId = $stateParams.customerId;;


        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/workplace/listProcess',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.workPlaces = response.data.data;
            });
        }).catch(function (e) {

        }).finally(function () {

        });

    };

    loadWorkPlace();

    var loadJobs = function()
    {
        if ($scope.employee.workPlace != null) {
            var req = {};
            req.operation = "diagnostic";
            req.customerId = $stateParams.customerId;;
            req.workPlaceId = $scope.employee.workPlace.id;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/job/listByWorkPlace',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.jobs = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });
        } else {
            $scope.jobs = [];
        }
    };

    $scope.$watch("employee.workPlace", function () {
        //console.log('new result',result);
        loadJobs();
    });

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancelEmployee = function () {
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

                $timeout(function () {
                    toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                }, 500);

                return;

            } else {
                $scope.onSaveEmployee();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.onSaveEmployee = function () {

        var req = {};
        var data = JSON.stringify($scope.employee);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-employee/quickSave',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function(){
                $scope.attachment = response.data.result;
                toaster.pop('success', 'Operación Exitosa', 'Registro eliminado');
                $scope.onCloseModal();
            });
        }).catch(function(e){
            $log.error(e);
            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
        }).finally(function(){

        });

    };

});

app.controller('ModalInstanceSideHealthDamageEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function ()
    {
        if ($scope.employee.id != 0) {
            var req = {
                id: $scope.employee.id,
            };
            $http({
                method: 'GET',
                url: 'api/customer-employee',
                params: req
            })
                .catch(function(e, code){
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () { $state.go(messagered); }, 3000);
                    } else if (code == 404)
                    {
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function(){
                        $scope.employee = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function(){
                        $scope.onCloseModal();
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
            data: function(d) {
                d.customerId = $stateParams.customerId;
                return JSON.stringify(d);
            },
            url: 'api/customer-employee-modal-basic',
            contentType: 'application/json',
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

                var status = '<span class="' + label +'">' + text + '</span>';

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

                var status = '<span class="' + label +'">' + text + '</span>';

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

    $scope.editDisabilityEmployee = function(id){
        $scope.employee.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});

app.controller('ModalInstanceSideHealthDamageDiagnosticListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, employee, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var request = {};

    $scope.diagnostic = {
        id: 0,
        code:"",
        description:"",
        isActive: true
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.diagnostic);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function ()
    {
        if ($scope.diagnostic.id != 0) {
            var req = {
                id: $scope.diagnostic.id,
            };
            $http({
                method: 'GET',
                url: 'api/disability-diagnostic',
                params: req
            })
                .catch(function(e, code){
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () { $state.go(messagered); }, 3000);
                    } else if (code == 404)
                    {
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function(){
                        $scope.diagnostic = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function(){
                        $scope.onCloseModal();
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }

    $scope.dtInstanceDisabilityDiagnosticList = {};
    $scope.dtOptionsDisabilityDiagnosticList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerEmployeeId = employee.id;
                return JSON.stringify(d);
            },
            url: 'api/disability-diagnostic-employee',
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

    $scope.dtColumnsDisabilityDiagnosticList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar diagnóstico"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('code').withTitle("Código").withOption('width', 200),
        DTColumnBuilder.newColumn('description').withTitle("Diagnóstico"),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';

                if (data.isActive != null || data.isActive != undefined) {
                    if (data.isActive) {
                        label = 'label label-success';
                    } else {
                        label = 'label label-danger';
                    }
                }

                var status = '<span class="' + label +'">' + data.status + '</span>';

                return status;
        })
    ];

    var loadRow = function () {
        $("#dtDisabilityDiagnosticList a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editDisabilityDiagnostic(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityDiagnostic.reloadData();
    };

    $scope.viewDisabilityDiagnostic = function (id) {
        $scope.diagnostic.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityDiagnostic = function(id){
        $scope.diagnostic.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});
