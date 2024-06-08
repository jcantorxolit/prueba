'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeEditCtrl', ['$scope', '$location', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory',
    'cfpLoadingBar', '$filter', 'ListService', '$aside', 'moment', 'CustomerEmployeeService',

    function ($scope, $location, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, ListService, $aside, moment,
        CustomerEmployeeService) {

        var log = $log;
        var request = {};
        $scope.request = {};

        $scope.flowConfig = { target: '/api/customer-employee/upload', singleFile: true };
        $scope.loading = true;
        $scope.isView = false;
        $scope.isNew = $scope.$parent.currentEmployee == 0;
        $scope.customerId = $stateParams.customerId;
        $scope.tabname = null;

        $scope.showAuthorized = false;
        $scope.isOnlyNumber = true;
        $scope.pattern = "\\d*";

        $scope.dateConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
            //value: $scope.project.deliveryDate.date
        };

        //DB->20170321: Use permission by role
        //$scope.showAuthorized = $scope.$parent.isCustomerContractor || $scope.$parent.isAdmin || $scope.$parent.isAgent;
        //DB->20230626: Remove permission by customer type
        //$scope.showAuthorized = $scope.$parent.isCustomerContractor || $rootScope.can('empleado_authorize');
        $scope.showAuthorized = $rootScope.can('empleado_authorize');

        CustomerEmployeeService.setCanAuthorize($scope.showAuthorized);

        if ($scope.$parent != null) {
            $scope.isView = $scope.$parent.$parent.$parent.editMode == "view";
        }

        $scope.arl = $rootScope.parameters("arl");
        $scope.afp = $rootScope.parameters("afp");
        $scope.eps = $rootScope.parameters("eps");
        $scope.eps = $rootScope.parameters("eps");
        $scope.genders = $rootScope.parameters("gender");
        $scope.professions = $rootScope.parameters("employee_profession");
        //$scope.occupations = $rootScope.parameters("employee_occupation");
        $scope.contractTypes = $rootScope.parameters("employee_contract_type");
        $scope.documentTypes = $rootScope.parameters("employee_document_type");
        $scope.docomentTypesAllowLetters = $rootScope.parameters("wg_employee_doc_type_allow_letter");
        $scope.workShiftList = $rootScope.parameters("work_shifts");
        //$scope.extrainfo = $rootScope.parameters("extrainfo");
        $scope.extrainfo = $scope.customer.extraContactInformationList;
        $scope.countries = $rootScope.countries();
        $scope.states = [];
        $scope.towns = [];

        $scope.structureOrganizational = false;
        $scope.locations = [];
        $scope.departments = [];
        $scope.areas = [];
        $scope.turns = [];

        var initialize = function () {
            $scope.employee = {
                id: $scope.$parent.currentEmployee,
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
                    documentNumber: "",
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
                    rh: null,
                    riskLevel: 0,
                    neighborhood: "",
                    observation: "",
                    logo: "",
                    details: [],
                    isActive: false,
                    age: null
                },
                location: "",
                department: "",
                area: "",
                turn: "",
                validityList: [],
                workShift: null
            };
        };

        $scope.document = {
            id: 0,
            customerEmployeeId: 0,
            jobId: 0,
            activity: null
        }

        var onDestroyIsAuthorized$ = $rootScope.$on('isAuthorizedChangeInDocument', function (event, args) {
            $scope.employee.isAuthorized = args.newValue;
        });


        $scope.$on("$destroy", function () {
            onDestroyIsAuthorized$();
        });

        initialize();
        getList();

        var loadWorkPlace = function () {

            var req = {};
            req.operation = "diagnostic";
            req.customerId = $scope.customerId;


            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/workplace/listProcess',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

        $scope.$watch("employee.workPlace", function (newValue, oldValue, scope) {
            $scope.jobs = [];

            if (oldValue != null && !angular.equals(newValue, oldValue)) {
                $scope.employee.job = null;
            }
        });

        $scope.$watch('employee.entity.birthDate', function () {
            if ($scope.employee.entity.birthDate !== null) {
                var birthday = new moment($scope.employee.entity.birthDate);

                $scope.employee.entity.age = moment().diff(birthday, 'years', false);
            } else {
                $scope.employee.entity.age = 0;
            }
        });

        $scope.onSelectDocumentType = function () {
            $scope.pattern = $scope.employee.entity.documentType.code == "N" ? "\\d*" : "^[a-zA-Z0-9]+$";
        }

        $scope.uploader = new Flow();

        if ($scope.employee.entity.logo == '') {
            $scope.noImage = true;
        }

        if ($scope.employee.id) {

            var req = {
                id: $scope.employee.id
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
                        SweetAlert.swal("Información no disponible", "Empleado no encontrado", "error");
                        $timeout(function () {
                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                    }
                })
                .then(function (response) {
                    $timeout(function () {
                        $scope.employee = response.data.result;

                        CustomerEmployeeService.setLastAuthorization($scope.employee.isAuthorized);
                        CustomerEmployeeService.setAuthorization($scope.employee.isAuthorized);

                        $scope.$parent.currentEmployee = $scope.employee.id;

                        if ($scope.employee.entity.details == null || $scope.employee.entity.details.length == 0) {

                        }

                        if ($scope.employee.entity.logo != null && $scope.employee.entity.logo.path != null) {
                            $scope.noImage = false;
                        } else {
                            $scope.noImage = true;
                        }

                        initializeDates();
                        $scope.onSelectDocumentType();

                        if ($scope.employee.job != null) {
                            $scope.request.job_id = $scope.employee.job.id;
                        }

                        $scope.changeJob();

                        var state = $scope.employee.entity.state;
                        var town = $scope.employee.entity.town;

                        $scope.changeCountry($scope.employee.entity.country);
                        $scope.changeState(state);

                        $scope.employee.entity.state = state;
                        $scope.employee.entity.town = town;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }

        $scope.removeImage = function () {
            $scope.noImage = true;
            $scope.employee.entity.removeLogo = true;
            $scope.employee.entity.logo = null;
        };

        $scope.onAuthChange = function () {
            console.log("onAuthChange");
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
                }, function () {

                });
            }
        };

        $scope.master = $scope.employee;
        $scope.form = {

            submit: function (form) {
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
                    save();
                }

            },
            reset: function (form) {
                $scope.employee = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {

            if ($scope.employee.entity.documentType) {
                if ($scope.employee.entity.documentType.code == "N" && isNaN($scope.employee.entity.documentNumber)){
                    SweetAlert.swal("El formulario contiene errores!", "El campo Número de Documento debe contener caracteres numéricos.", "error");
                    return;
                }
                var pattern = /^[a-zA-Z0-9]+$/;

                if ($scope.employee.entity.documentType.code == "A" && !pattern.test($scope.employee.entity.documentNumber)) {
                    SweetAlert.swal("El formulario contiene errores!", "El campo Número de Documento debe contener caracteres alfanuméricos.", "error");
                    return;
                }
            }

            var req = {};
            var data = JSON.stringify($scope.employee);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-employee/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {

                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                //your code for submit

                $scope.uploader.flow.opts.query.id = response.data.result.entity.id;
                $scope.uploader.flow.resume();

                $timeout(function () {

                    if ($scope.isNew) {
                        if ($scope.$parent != null) {
                            $scope.$parent.navToSection("edit", "edit", response.data.result.id);
                            $scope.$parent.currentEmployee = response.data.result.id;
                        }
                    }

                    $scope.employee = response.data.result;

                    CustomerEmployeeService.setLastAuthorization($scope.employee.isAuthorized);
                    CustomerEmployeeService.setAuthorization($scope.employee.isAuthorized);
                    $rootScope.$emit('isAuthorizedChangeInBasic', { newValue: $scope.employee.isAuthorized });

                    if ($scope.employee.entity.details == null || $scope.employee.entity.details.length == 0) {

                    }

                    if ($scope.employee.entity.logo != null && $scope.employee.entity.logo.path != null) {
                        $scope.noImage = false;
                    } else {
                        $scope.noImage = true;
                    }

                    initializeDates();

                    var state = $scope.employee.entity.state;
                    var town = $scope.employee.entity.town;

                    $scope.changeCountry($scope.employee.entity.country);
                    $scope.changeState(state);

                    $scope.employee.entity.state = state;
                    $scope.employee.entity.town = town;


                    if ($scope.employee.job != null) {
                        $scope.request.job_id = $scope.employee.job.id;
                    }

                    $scope.changeJob();

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $timeout(function () {
                    if ($scope.uploader.flow.files.length) {
                        var $logo = getBase64($scope.uploader.flow.files[0].file);
                        getBase64($logo);
                    }
                }, 1000);
            });

        };

        var getBase64 = function (file) {
            var reader = new FileReader();
            reader.onloadend = function () {
                $scope.uploader.flow.cancel();
                $scope.employee.entity.logo = { path: reader.result };
                $scope.noImage = false;
            }

            if (file) {
                reader.readAsDataURL(file);
            }
        }

        $scope.changeCountry = function (item, model) {

            if (item == null) {
                return;
            }

            $scope.states = [];
            $scope.towns = [];

            $scope.employee.entity.state = null;
            $scope.employee.entity.town = null;

            var req = {
                cid: item.id
            };

            $http({
                method: 'GET',
                url: 'api/states',
                params: req
            }).catch(function (e, code) {

            }).then(function (response) {
                $scope.states = response.data.result;
                $scope.towns = [];
            }).finally(function () {

            });
        };

        $scope.changeState = function (item, model) {

            $scope.towns = [];

            var req = {
                sid: item.id
            };

            $scope.employee.entity.town = null;

            $http({
                method: 'GET',
                url: 'api/towns',
                params: req
            }).then(function (response) {
                $scope.towns = response.data.result;
            }).finally(function () {

            });

        };

        $scope.cancelEdition = function (index) {

            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
            return;

            if ($scope.isView) {
                $state.go('app.clientes.list');
            } else {
                SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Perderá todos los cambios realizados en este formulario.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, regresar!",
                    cancelButtonText: "No, continuar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                    function (isConfirm) {
                        if (isConfirm) {
                            $timeout(function () {
                                if ($scope.$parent != null) {
                                    $scope.$parent.navToSection("list", "list");
                                }
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        $scope.onAddValidity = function () {

            $timeout(function () {
                if ($scope.employee.validityList == null) {
                    $scope.employee.validityList = [];
                }
                $scope.employee.validityList.push({
                    id: 0,
                    startDate: "",
                    endDate: "",
                    description: ""
                });
            });
        };

        $scope.onRemoveValidity = function (index) {
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
                            var date = $scope.employee.validityList[index];

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-employee/validity/delete',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                    $scope.employee.validityList.splice(index, 1);
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

        $scope.onAddInfoDetail = function () {
            $timeout(function () {
                if ($scope.employee.entity.details == null) {
                    $scope.employee.entity.details = [];
                }
                $scope.employee.entity.details.push({
                    id: 0,
                    value: "",
                    type: null
                });
            });
        };

        $scope.onRemoveInfoDetail = function (index) {
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
                            var contact = $scope.employee.entity.details[index];

                            $scope.employee.entity.details.splice(index, 1);

                            if (contact.id != 0) {
                                var req = {};
                                req.id = contact.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-employee-contact/delete',
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

        $scope.changeJob = function (item, model) {

            $timeout(function () {
                if ($scope.employee.job != null && $scope.employee.id != 0) {

                    $scope.currentJobId = $scope.employee.job.id;

                    $scope.request.job_id = $scope.currentJobId;

                    var req = {};
                    req.id = $scope.employee.id;
                    req.job_id = $scope.currentJobId;
                    return $http({
                        method: 'POST',
                        url: 'api/customer-employee/critical-activity/duplicate',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        data: $.param(req)
                    }).then(function (response) {

                        $scope.reloadData();

                    }).catch(function (e) {
                        $log.error(e);
                        toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
                    }).finally(function () {

                    });
                }
            }, 400);
        };

        $scope.onAddActivity = function () {
            if ($scope.employee.job == null) {
                SweetAlert.swal("El formulario contiene errores!", "Debe seleccionar un cargo " +
                    "", "error");
                return;
            }
            
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/employee/customer_employee_critical_activity_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerEmployeeCriticalActivityListCtrl',
                scope: $scope,
                resolve: {
                    criteria: function () {
                        return { 
                            customerId: $stateParams.customerId,
                            jobId: $scope.employee.job.id,
                            id: $scope.employee.id
                        };
                    }
                }
            });
            modalInstance.result.then(function (activities) {
               if (activities && activities.length > 0) {
                   saveCriticalActivity(activities)
               }

            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        }

        var saveCriticalActivity = function ($activities) {

            if ($scope.employee.job == null) {
                SweetAlert.swal("El formulario contiene errores!", "Debe seleccionar un cargo " +
                    "", "error");
                return;
            }

            var payload = {
                customerEmployeeId:  $scope.employee.id,
                activities:  $activities,
                jobId:  $scope.employee.job.id,
            };

            

            var req = {};
            var data = JSON.stringify(payload);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-employee-critical-activity/bulk',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $scope.reloadData();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };


        $scope.dtInstanceCriticalActivity = {};
        $scope.dtOptionsCriticalActivity = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.operation = "document";
                    d.customerEmployeeId = $scope.employee.id;
                    d.customerId = $scope.customerId;
                    d.jobId = $scope.currentJobId ? $scope.currentJobId : 0;

                    return JSON.stringify(d);
                },
                url: 'api/customer-employee-critical-activity',
                contentType: "application/json",
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

            });;

        $scope.dtColumnsCriticalActivity = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 200).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var url = data.document != null ? data.document.path : "";
                    var actions = "";
                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                        '   <i class="fa fa-download"></i></a> ';
                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-folder-open-o"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-light-red btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" data-criticalActivityId="' + data.criticalActivityId + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';


                    var isButtonVisible = !$scope.isView;

                    if ($rootScope.can("empleado_documento_open")) {
                        actions += viewTemplate;
                    }

                    if ($rootScope.can("empleado_documento_download")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("empleado_documento_invalidate")) {
                        if (data.status == "Vigente") {
                            actions += deleteTemplate;
                        }
                    }

                    return isButtonVisible ? deleteTemplate : "";
                }),
            DTColumnBuilder.newColumn('job').withTitle("Cargo"),
            DTColumnBuilder.newColumn('activity').withTitle("Actividad Crítica"),
        ];

        var loadRow = function () {

            $("#dtCriticalActivity a.delRow").on("click", function () {
                var id = $(this).data("criticalactivityid");

                SweetAlert.swal({
                    title: "Está seguro?",
                    text: "El registro se eliminará.",
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

                            return $http({
                                method: 'POST',
                                url: 'api/customer-employee/critical-activity/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {

                                $timeout(function () {
                                    SweetAlert.swal("Operación exitosa", "Registro eliminado satisfactoriamente", "success");
                                    $scope.reloadData();
                                });
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error de guardado", "Error eliminado el registro. Por favor verifique los datos ingresados!", "error");
                            }).finally(function () {

                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });
        };

        $scope.dtInstanceCriticalActivityCallback = function (instance) {
            $scope.dtInstanceCriticalActivity = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCriticalActivity.reloadData();
        };

        var initializeDates = function () {
            if ($scope.employee.entity.expeditionDate != null) {
                $scope.employee.entity.expeditionDate = new Date($scope.employee.entity.expeditionDate.date);
            }

            if ($scope.employee.entity.birthDate != null) {
                $scope.employee.entity.birthDate = new Date($scope.employee.entity.birthDate.date);
            }

            if ($scope.employee.validityList.length > 0) {
                angular.forEach($scope.employee.validityList, function (validity) {

                    if (validity.startDate != null) {
                        validity.startDate = new Date(validity.startDate.date)
                    }

                    if (validity.endDate != null) {
                        validity.endDate = new Date(validity.endDate.date)
                    }
                });
            }
        }

        $scope.onSearchJob = function () {

            if (!$scope.employee.workPlace) {
                toaster.pop("error", "Validación", "Debe seleccionar un centro de trabajo válido");
                return;
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_diagnostic.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/data_table_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                resolve: {
                    workPlace: function () {
                        return $scope.employee.workPlace;
                    }
                },
                controller: 'ModalInstanceSideCustomerEmployeeJobListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (job) {
                if ($scope.jobs === undefined || $scope.jobs == null) {
                    $scope.jobs = [];
                }

                var result = $filter('filter')($scope.jobs, { id: job.id });

                if (result.length == 0) {
                    $scope.jobs.push(job);
                }

                $scope.employee.job = job;

                $scope.changeJob();

            }, function () {

            });
        }

        function getList() {
            var entities = [
                { name: 'customer_employee_type_rh', criteria: {} },
                { name: 'customer_employee_location', criteria: { customerId: $stateParams.customerId } },
                { name: 'customer_parameter', criteria: { customerId: $stateParams.customerId, group: 'employeesOrganizationalStructure' } }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.listRh = response.data.data.customer_employee_type_rh;
                    $scope.locations = response.data.data.customerEmployeeLocation;
                    $scope.structureOrganizational = response.data.data.employeesOrganizationalStructure && response.data.data.employeesOrganizationalStructure.value == "1";
                }, function (error) {
                    $scope.status = "Unable to load customer data: " + error.message;
                });
        }

        $scope.onSelectLocation = function () {
            $scope.employee.department = null;
            $scope.employee.area = null;
            $scope.employee.turn = null;

            $scope.departments = [];
            $scope.areas = [];
            $scope.turns = [];

            var entities = [{
                name: 'customer_employee_departments_by_location',
                criteria: {
                    customerId: $stateParams.customerId,
                    location: $scope.employee.location.value
                }
            }];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.departments = response.data.data.customerEmployeeDepartmentsByLocation;
                }, function (error) {
                    $scope.status = "Unable to load customer data: " + error.message;
                });
        }

        $scope.onSelectDepartment = function () {
            $scope.employee.area = null;
            $scope.employee.turn = null;

            $scope.areas = [];
            $scope.turns = [];

            var entities = [{
                name: 'customer_employee_areas_by_department',
                criteria: {
                    customerId: $stateParams.customerId,
                    location: $scope.employee.location.value,
                    department: $scope.employee.department.value
                }
            }];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.areas = response.data.data.customerEmployeeAreasByDepartment;
                }, function (error) {
                    $scope.status = "Unable to load customer data: " + error.message;
                });
        }

        $scope.onSelectArea = function () {
            $scope.employee.turn = null;
            $scope.turns = [];

            var entities = [{
                name: 'customer_employee_turns_by_area',
                criteria: {
                    customerId: $stateParams.customerId,
                    location: $scope.employee.location.value,
                    department: $scope.employee.department.value,
                    area: $scope.employee.area.value
                }
            }];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.turns = response.data.data.customerEmployeeTurnsByArea;
                }, function (error) {
                    $scope.status = "Unable to load customer data: " + error.message;
                });
        }

    }
]);

app.controller('ModalInstanceSideCustomerEmployeeJobListCtrl', function ($rootScope, $stateParams, $scope, workPlace, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'CARGOS DISPONIBLES'

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
                url: 'api/customer-config-job',
                params: req
            })
                .catch(function (response) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.entity = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
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
            data: function (d) {
                d.customerId = $stateParams.customerId;
                d.workPlaceId = workPlace.id;
                return JSON.stringify(d);
            },
            url: 'api/customer-config-job',
            contentType: "application/json",
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

        });;

    $scope.dtColumnsCommonDataTableList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

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

    var loadRow = function () {
        $("#dtCommonDataTableList a.editRow").on("click", function () {
            var id = $(this).data("id");
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

app.controller('ModalInstanceSideCustomerEmployeeCriticalActivityListCtrl', function ($rootScope, $stateParams, $scope, criteria, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {
    
    $scope.onCloseModal = function () {
        $uibModalInstance.close();
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.canShowDataTable = false;

    $scope.filter = {
        selectedType: null
    };

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
    var params = null;

    var apiUrl = 'api/customer-employee-critical-activity/available';

    var buildDTColumns = function () {
        var $columns = [
            DTColumnBuilder.newColumn(null).withOption('width', 30)
                .notSortable()
                .withClass("center")
                .renderWith(function (data, type, full, meta) {
                    var checkTemplate = '';
                    var isChecked = $selectedItems[data.id].selected;
                    var checked = isChecked ? "checked" : ""

                    checkTemplate = '<div class="checkbox clip-check check-danger ">' +
                        '<input class="selectedRow" type="checkbox" id="chk_employee_document_select_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label class="padding-left-10" for="chk_employee_document_select_' + data.id + '"> </label></div>';

                    return checkTemplate;
                })
        ];

        $columns.push(buildDTColumn('job', 'Cargo', '', 200));
        $columns.push(buildDTColumn('activity', 'Actividad Crítica', '', 200));

        return $columns;
    }

    var buildDTColumn = function (field, title, defaultContent, width) {
        return DTColumnBuilder.newColumn(field)
            .withTitle(title)
            .withOption('defaultContent', defaultContent)
            .withOption('width', width);
    };

    var initializeDatatable = function () {
        $scope.canShowDataTable = true;

        var $lastSearch = '';

        $scope.dtOptionsCustomerEmployeeCiriticalActivity = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    d.customerEmployeeId = criteria.id;
                    d.jobId = criteria.jobId;
                    params = d;
                    return JSON.stringify(d);
                },
                dataSrc: function (response) {
                    $currentPageUids = response.data.map(function (item, index, array) {
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
                url: apiUrl,
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator                    
                },
                complete: function () {

                }
            })
            .withDataProp('data')
            .withOption('serverSide', true)
            .withOption('processing', true)
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

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsCustomerEmployeeCiriticalActivity = buildDTColumns();
    }

    var loadRow = function () {

        angular.element("#dtCustomerEmployeeCiriticalActivity input.selectedRow").on("change", function () {
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

    $scope.dtInstanceCustomerEmployeeCiriticalActivityCallback = function (instance) {
        $scope.dtInstanceCustomerEmployeeCiriticalActivity = instance;
        $scope.dtInstanceCustomerEmployeeCiriticalActivity.DataTable.on('page', function () {
            $timeout(function () {
                $scope.toggle.isChecked = $scope.toggle.selectAll;
            }, 300);
        })

        $scope.dtInstanceCustomerEmployeeCiriticalActivity.DataTable.on('order', function () {
            $timeout(function () {
                $scope.toggle.isChecked = $scope.toggle.selectAll;
            }, 300);
        })
    };

    $scope.reloadData = function () {
        if ($scope.dtInstanceCustomerEmployeeCiriticalActivity != null) {
            $scope.dtInstanceCustomerEmployeeCiriticalActivity.reloadData(null, false);
        }
    };

    $scope.onSelectType = function () {
        $scope.reloadData();
        $selectedItems = {};
        $scope.toggle.isChecked = false;
        $scope.toggle.selectAll = false;
        $scope.records.hasSelected = false;
        $scope.records.countSelected = 0;
    }

    $scope.onAccept = function () {
        var activities = Object.keys($selectedItems).filter(function(key) {
            return $selectedItems[key].selected 
        }).map(function(item) {
            return item;
        });

        $uibModalInstance.close(activities);
    };

    $scope.onToggle = function () {
        $scope.toggle.isChecked = !$scope.toggle.isChecked;
        onCheck($currentPageUids, $scope.toggle.isChecked);
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

    var onCheck = function ($items, $isCheck, $forceUnCheck) {
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
            var $uid = angular.element(elem).data("id");
            angular.element(elem).prop("checked", $selectedItems[$uid].selected);
        });

        $scope.records.hasSelected = countSelected > 0;
        $scope.records.countSelected = countSelected;
    }

    initializeDatatable();
});