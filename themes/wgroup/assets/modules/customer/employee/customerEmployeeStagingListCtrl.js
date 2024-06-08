'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeStagingListCtrl',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService) {

        var log = $log;
        var $exportUrl = '';

        function getList() {
            var entities = [
                { name: 'export_url', value: null },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $exportUrl = response.data.data.exportUrl.item;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
        getList();


        $scope.dtInstanceEmployeeStagingDT = {};
        $scope.dtOptionsEmployeeStagingDT = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.customer_id = $stateParams.customerId;
                    d.session_id = $scope.$parent.currentEmployee;
                    return JSON.stringify(d);
                },
                url: 'api/customer-employee-staging',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[1, 'asc']])
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

        $scope.dtColumnsEmployeeStagingDT = [
            DTColumnBuilder.newColumn(null).withTitle("").withOption('width', 30).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var disabled = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var actions = data.isValid == 0 || !data.isValid ? editTemplate : '';
                    return actions;
                }),
            DTColumnBuilder.newColumn(null).withTitle('Fila').withOption('width', 50)
                .renderWith(function (data, type, full, meta) {
                    if(data.isValid == 1 || data.isValid){
                        var $class = 'badge badge-success';
                        var $icon = '<i class=" fa fa-check"></i>';
                        var $info = "Es correcto";
                    } else {
                        var $class = 'badge badge-danger';
                        var $icon = '<i class=" fa fa-ban"></i>';
                        var $info = data.errors;
                    }
                    return '<span uib-tooltip="'+$info+'" class="'+ $class +'">'  + data.index + $icon + '</span>';
                }),
            DTColumnBuilder.newColumn('documentType').withTitle("Tipo Documento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('documentNumber').withTitle("Nro Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('expeditionPlace').withTitle("Lugar Expedición").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('expeditionDate').withTitle("Fecha Expedición").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('birthdate').withTitle("Fecha Nacimiento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('gender').withTitle("Género").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('firstName').withTitle("Nombres").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('contractType').withTitle("Tipo Contrato").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('profession').withTitle("Profesión").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('occupation').withTitle("Ocupación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('workPlace').withTitle("Centro de Trabajo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('salary').withTitle("Salario").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('eps').withTitle("EPS").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('afp').withTitle("AFP").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('arl').withTitle("ARL").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('country_id').withTitle("Pais").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('state_id').withTitle("Departamento").withOption('width', 180).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('city_id').withTitle("Ciudad").withOption('width', 180).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('rh').withTitle("RH").withOption('width', 180).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('riskLevel').withTitle("Nivel de Riesgo").withOption('width', 280).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('neighborhood').withTitle("Centro de Costos").withOption('width', 280).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('mobil').withTitle("Celular").withOption('width', 280).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('address').withTitle("Dirección").withOption('width', 280).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('telephone').withTitle("Teléfono").withOption('width', 280).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('email').withTitle("Email").withOption('width', 280).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('active').withTitle("Activo").withOption('width', 280).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('workShift').withTitle("Turno de Trabajo").withOption('width', 280).withOption('defaultContent', ''),
        ];

        if($rootScope.isAuthorizationTemplate){
            $scope.dtColumnsEmployeeStagingDT.push(DTColumnBuilder.newColumn('isAuthorized').withTitle("Autorizado").withOption('width', 280).withOption('defaultContent', ''));
        }

        var loadRow = function () {
            angular.element("#dataEmployeeStagingDT a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                onEdit(id);
            });
        }

        $scope.dtInstanceEmployeeStagingDTCallback = function(instance) {
            $scope.dtInstanceEmployeeStagingDT = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceEmployeeStagingDT.reloadData();
        };

        var onEdit = function (id) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/employee/customer_employee_staging_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerEmployeeStagingEditCtrl',
                scope: $scope,
                resolve: {
                    dataItem: function () {
                        return { id: id ? id : 0 };
                    },
                    isView : function() {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {
                $scope.reloadData();
            });
        };

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        }

        $scope.onProcess = function () {
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
            function (isConfirm) {
                if (isConfirm) {

                    return $http({
                        method: 'POST',
                        url: $exportUrl + 'api/v1/customer-employee-import/confirm',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param({
                            id: $stateParams.customerId,
                            sessionId: $scope.$parent.currentEmployee,
                            hasCustomerEmployeeId: $rootScope.hasCustomerEmployeeId
                        })
                    }).then(function (response) {
                        $timeout(function () {
                            SweetAlert.swal("Registro", "La información ha sido importada satisfactoriamente", "success");
                            $scope.onCancel();
                        });
                    }).catch(function (e) {
                        $log.error(e);
                        SweetAlert.swal("Error de guardado", e.data.message, "error");
                    }).finally(function () {

                    });
                }
            });
        }

    }
);

app.controller('ModalInstanceSideCustomerEmployeeStagingEditCtrl', function ($rootScope, $stateParams, $scope, dataItem, isView, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, $document, $filter, $aside, ListService) {


        $scope.onCloseModal = function () {
            $uibModalInstance.close(null);
        };

        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        var log = $log;
        $scope.employees = [];
        $scope.arl = $rootScope.parameters("arl");
        $scope.afp = $rootScope.parameters("afp");
        $scope.eps = $rootScope.parameters("eps");
        $scope.genders = $rootScope.parameters("gender");
        $scope.professions = $rootScope.parameters("employee_profession");
        $scope.contractTypes = $rootScope.parameters("employee_contract_type");
        $scope.documentTypes = $rootScope.parameters("employee_document_type");
        $scope.workShifts = $rootScope.parameters("work_shifts");
        $scope.countries = $rootScope.countries();
        $scope.states = [];
        $scope.towns = [];
        $scope.showAuthorized = $rootScope.isAuthorizationTemplate && $rootScope.can('empleado_authorize');

        $scope.dateConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        function getList() {
            var entities = [
                { name: 'customer_employee_type_rh', criteria: {} },
            ];

            ListService.getDataList(entities)
            .then(function (response) {
                $scope.listRh = response.data.data.customer_employee_type_rh;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
        }
        getList();

        var loadWorkPlace = function() {
            var req = {};
            req.operation = "diagnostic";
            req.customerId = $stateParams.customerId;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/workplace/listProcess',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $timeout(function() {
                    $scope.workPlaces = response.data.data;
                });
            }).catch(function(e) {
            }).finally(function() {
            });
        };
        loadWorkPlace();

        $scope.onSearchJob = function() {

            if (!$scope.employee.workPlace) {
                SweetAlert.swal("Validación!", "Debe seleccionar un centro de trabajo válido.", "error");
                return;
            }

            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/data_table_list_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: 'static',
                resolve: {
                    workPlace: function() {
                        return $scope.employee.workPlace;
                    }
                },
                controller: 'ModalInstanceSideCustomerEmployeeJobListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function(job) {
                if ($scope.jobs === undefined || $scope.jobs == null) {
                    $scope.jobs = [];
                }
                var result = $filter('filter')($scope.jobs, { id: job.id });
                if (result.length == 0) {
                    $scope.jobs.push(job);
                }
                $scope.employee.job = job;
            }, function() {

            });
        }

        $scope.changeCountry = function(item, model) {

            if (item == null) {
                return;
            }

            $scope.states = [];
            $scope.towns = [];

            $scope.employee.state_id = null;
            $scope.employee.city_id = null;

            var req = {
                cid: item.id
            };

            $http({
                method: 'GET',
                url: 'api/states',
                params: req
            }).catch(function(e, code) {

            }).then(function(response) {
                $scope.states = response.data.result;
                $scope.towns = [];
            }).finally(function() {

            });
        };

        $scope.changeState = function(item, model) {

            $scope.towns = [];

            var req = {
                sid: item.id
            };

            $scope.employee.city_id = null;

            $http({
                method: 'GET',
                url: 'api/towns',
                params: req
            }).then(function(response) {
                $scope.towns = response.data.result;
            }).finally(function() {

            });

        };

        var init = function () {
            $scope.employee = {
                id: dataItem.id,
                index: null,
                customerEmployeeId: null,
                documentType: null,
                documentNumber: null,
                expeditionPlace: null,
                expeditionDate: null,
                birthdate: null,
                gender: null,
                firstName: null,
                lastName: null,
                contractType: null,
                profession: null,
                occupation: null,
                job: null,
                workPlace: null,
                salary: null,
                eps: null,
                afp: null,
                arl: null,
                country_id: null,
                state_id: null,
                city_id: null,
                rh: null,
                riskLevel: null,
                neighborhood: null,
                observation: null,
                mobil: null,
                address: null,
                telephone: null,
                email: null,
                isActive: null,
                isAuthorized: null,
                workShift: null,
                errors: null
            };
        };
        init();

        $scope.onLoadRecord = function () {
            if ($scope.employee.id != 0) {
                $http({
                    method: 'GET',
                    url: 'api/customer-employee-staging/get',
                    params: {
                        id: $scope.employee.id
                    }
                })
                    .catch(function (e, code) {
                    })
                    .then(function (response) {
                        $timeout(function () {
                            $scope.employee = response.data.result;
                            var country = $scope.employee.country_id;
                            var state = $scope.employee.state_id;
                            var city = $scope.employee.city_id;
                            if(country){
                                $scope.changeCountry(country);
                            }
                            if(state){
                                $scope.changeState(state);
                            }
                            $timeout(function() {
                                $scope.employee.state_id = state;
                                $scope.employee.city_id = city;
                            }, 1500)
                        });
                    }).finally(function () {
                    });
            }
        }

        $scope.onLoadRecord();

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
                    log.info($scope.employee);
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
            var data = JSON.stringify($scope.employee);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-employee-staging/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $scope.onCloseModal();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

});

app.controller('ModalInstanceSideCustomerEmployeeJobListCtrl', function($rootScope, $stateParams, $scope, workPlace, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'CARGOS DISPONIBLES'

    $scope.employee = {};

    $scope.onCloseModal = function() {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function() {
        $uibModalInstance.dismiss('cancel');
    };

    var onLoadRecord = function(id) {
        if (id != 0) {
            var req = {
                id: id,
            };
            $http({
                    method: 'GET',
                    url: 'api/customer-config-job',
                    params: req
                })
                .catch(function(response) {

                })
                .then(function(response) {

                    $timeout(function() {
                        $scope.employee = response.data.result;
                    });

                }).finally(function() {
                    $timeout(function() {
                        $scope.onCloseModal();
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }

    $scope.dtInstanceCommonDataTableList = {};
    $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerId = $stateParams.customerId;
                d.workPlaceId = workPlace.id;
                return JSON.stringify(d);
            },
            url: 'api/customer-config-job',
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

    $scope.dtColumnsCommonDataTableList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
        .renderWith(function(data, type, full, meta) {

            var actions = "";
            var disabled = ""

            var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar"  data-id="' + data.id + '"' + disabled + ' >' +
                '   <i class="fa fa-plus-square"></i></a> ';

            actions += editTemplate;

            return actions;
        }),

        DTColumnBuilder.newColumn('work_place').withTitle("Centro de Trabajo").withOption('width', 200),
        DTColumnBuilder.newColumn('macro_process').withTitle("Macro Proceso"),
        DTColumnBuilder.newColumn('process').withTitle("Proceso"),
        DTColumnBuilder.newColumn('job').withTitle("Cargo")
    ];

    var loadRow = function() {
        $("#dtCommonDataTableList a.editRow").on("click", function() {
            var id = $(this).data("id");
            onLoadRecord(id);
        });
    };

    $scope.dtInstanceCommonDataTableListCallback = function(instance) {
        $scope.dtInstanceCommonDataTableList = instance;
    };

    $scope.reloadData = function() {
        $scope.dtInstanceCommonDataTableList.reloadData();
    };

});
