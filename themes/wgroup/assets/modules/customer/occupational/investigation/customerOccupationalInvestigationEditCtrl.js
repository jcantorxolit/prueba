'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerOccupationalInvestigationEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', '$document', 'toaster',
    'FileUploader', 'ListService',
    function ($scope, $stateParams, $log, $compile, $state,
        SweetAlert, $rootScope, $http, $timeout, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
        $uibModal, flowFactory, cfpLoadingBar, $filter, $aside, $document, toaster, FileUploader, ListService) {

        var log = $log;

        var request = {};
        var currentId = $scope.$parent.currentId;

        $scope.currentStep = 0;
        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";
        $scope.reloadEmployee = true;

        $scope.isView = $scope.$parent.modeDsp == "view";
        $scope.minDateCurrent = new Date();
        $scope.customerId = $stateParams.customerId;
        $scope.users = [];
        $scope.employees = [];

        $scope.employeeHasCountry = false;
        $scope.employeeHasState = false;
        $scope.employeeHasCity = false;

        getList();

        function getList() {
            var entities = [{
                name: 'wg_customer_productivity_stata_person_type',
                value: null
            }, ];

            ListService.getDataList(entities)
                .then(function (response) {

                    $scope.personTypeList = response.data.data.wg_customer_productivity_stata_person_type;

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.conditionalList = [{
            item: 'Si',
            value: 1
        }, {
            item: 'No',
            value: 0
        }];

        $scope.weekDays = [{
                item: 'Domingo',
                value: 7
            },
            {
                item: 'Lunes',
                value: 1
            },
            {
                item: 'Martes',
                value: 2
            },
            {
                item: 'Miercoles',
                value: 3
            },
            {
                item: 'Jueves',
                value: 4
            },
            {
                item: 'Viernes',
                value: 5
            },
            {
                item: 'Sábado',
                value: 6
            },
        ];

        // parametros para seguimientos

        $scope.eps = $rootScope.parameters("eps");
        $scope.afp = $rootScope.parameters("afp");
        $scope.arl = $rootScope.parameters("arl");
        $scope.employment_relationship = $rootScope.parameters("wg_report_employment_relationship");
        $scope.document_type = $rootScope.parameters("tipodoc");
        $scope.zone = $rootScope.parameters("wg_report_zone");
        $scope.economic_activity = $rootScope.parameters("wg_economic_activity");
        $scope.type_linkage = $rootScope.parameters("wg_type_linkage");
        $scope.gender = $rootScope.parameters("gender");
        //$scope.employee_occupation =  $rootScope.parameters("employee_occupation");
        $scope.report_regular_work = $rootScope.parameters("wg_report_regular_work"); //Diurna, noctura...
        $scope.report_week_day = $rootScope.parameters("wg_report_week_day");
        $scope.report_working_day = $rootScope.parameters("wg_report_working_day");
        //$scope.report_regular_task =  $rootScope.parameters("wg_report_regular_task");
        $scope.report_accident_type = $rootScope.parameters("wg_report_accident_type");
        $scope.report_location = $rootScope.parameters("wg_report_location");
        $scope.report_place = $rootScope.parameters("wg_report_place");
        $scope.report_lesion_type = $rootScope.parameters("wg_report_lesion_type");
        $scope.report_body_part = $rootScope.parameters("wg_report_body_part");
        $scope.report_factor = $rootScope.parameters("wg_report_factor");
        $scope.report_mechanism = $rootScope.parameters("wg_report_mechanism");
        $scope.accidentTypeList = $rootScope.parameters("wg_report_accident_type");
        $scope.employeeLinkTypeList = $rootScope.parameters("wg_type_linkage");
        $scope.accidentWorkingDayList = $rootScope.parameters("wg_report_working_day");
        $scope.accidentCategoryList = $rootScope.parameters("wg_report_accident_type");
        $scope.accidentLocationList = $rootScope.parameters("wg_report_location");
        $scope.accidentPlaceList = $rootScope.parameters("wg_report_place");
        $scope.measureTypeList = $rootScope.parameters("investigation_measure");

        $scope.arl = $rootScope.parameters("arl");
        $scope.documentType = $rootScope.parameters("customer_document_type");
        $scope.tiposdoc = $rootScope.parameters("tipodoc");
        $scope.countryList = $rootScope.countries();

        $scope.arl = $rootScope.parameters("arl");
        $scope.documentType = $rootScope.parameters("customer_document_type");
        $scope.tiposdoc = $rootScope.parameters("tipodoc");
        $scope.extrainfo = $rootScope.parameters("extrainfo");
        $scope.employeeZoneList = $rootScope.parameters("wg_report_zone");
        $scope.accidentZoneList = $rootScope.parameters("wg_report_zone");
        $scope.testimonyList = $rootScope.parameters("investigation_testimony_type");
        $scope.factorImmediateList = $rootScope.parameters("investigation_cause_classification_immediate");
        $scope.factorBasicList = $rootScope.parameters("investigation_cause_classification_basic");

        $scope.customers = [];
        $scope.employees = [];
        $scope.responsibleList = [];
        $scope.states = [];
        $scope.cities = [];
        $scope.reportAtList = [];

        $scope.months = $rootScope.parameters("month");


        var createWitness = function () {
            return {
                id: 0,
                type: null,
                accidentIsWatching: null,
                documentType: null,
                documentNumber: '',
                name: '',
                job: '',
                story: '',
            };
        }

        $scope.investigation = {
            id: currentId,
            customer: $scope.customer,
            employee: null,
            isReportAtRelated: false,
            reportAt: null,
            accidentDate: null,
            accidentType: null,
            country: null,
            state: null,
            city: null,
            observation: '',
            accidentWeekDay: null,
            accidentMonth: null,
            reportDate: null,
            notificationArlDate: null,
            notificationDocumentDate: null,
            responsibleList: [],

            customerIsWorkingInHq: false,

            witnessList: [
                createWitness()
            ],

            customerObservation: null,
            customerPrincipalZone: null,
            customerPrincipalEconomicActivity: null,
            customerPrincipalRiskClass: null,
            customerBranchEconomicActivity: null,
            customerBranchRiskClass: null,
            customerBranchCountry: null,
            customerBranchState: null,
            customerBranchCity: null,
            customerBranchZone: null,
            customerResponsibleHealth: null,
            details: [],
            employeeLinkType: null,
            employeeStartDate: null,
            employeeZone: null,
            employeeHabitualOccupationCode: '',
            employeeHabitualOccupation: '',
            employeeHabitualOccupationTime: '',
            employeeDuration: '',
            employeeJobTask: '',

            accidentDateOf: null,
            accidentWorkingDay: null,
            accidentCategory: null,
            accidentWorkTimeHour: 0,
            accidentWorkTimeMinute: 0,
            accidentIsDeathCause: null,
            accidentDateOfDeath: null,
            accidentIsRegularWork: null,
            accidentOtherRegularWorkText: '',
            accidentOtherRegularWorkTextCode: '',
            accidentCountry: null,
            accidentState: null,
            accidentCity: null,
            accidentZone: null,
            accidentLocation: null,
            accidentPlace: null,
            lesions: [],
            bodies: [],
            factors: [],
            mechanisms: [],
        };

        var initBasic = function () {
            $scope.basic = {
                id: 0,
                customerOccupationalInvestigationId: currentId,
                type: 'basic',
                factor: null,
                cause: null,
            }
        }

        var initImmediate = function () {
            $scope.immediate = {
                id: 0,
                customerOccupationalInvestigationId: currentId,
                type: 'immediate',
                factor: null,
                cause: null,
            }
        }

        var initMeasure = function () {
            $scope.measure = {
                id: 0,
                customerOccupationalInvestigationId: currentId,
                type: null,
                description: ''
            }
        }

        initImmediate();
        initBasic();
        initMeasure();

        $scope.cancelEdition = function (index) {
            if ($scope.isView) {
                if ($scope.$parent != null) {
                    $scope.$parent.navToSection("list", "list");
                }
            } else {
                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Perderá todos los cambios realizados en este formulario.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Si, cancelar!",
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

        var buildAdditionalInfo = function (array) {

            var additionalInformation = [];

            angular.forEach(array, function (item) {
                var data = {
                    id: 0,
                    customerOccupationalReportAlId: 0,
                    itemId: item.value,
                    description: item.item,
                    isActive: false
                }

                additionalInformation.push(data);

            });

            return additionalInformation;
        };

        var onLoadRecord = function (id) {
            // se debe cargar primero la información actual del cliente..

            if (id) {
                var req = {
                    id: id
                };

                $http({
                        method: 'GET',
                        url: 'api/customer/occupational-investigation-al',
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
                            //SweetAlert.swal("Información no disponible", "Seguimiento no encontrado", "error");
                            $timeout(function () {
                                // $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del seguimiento", "error");
                        }
                    })
                    .then(function (response) {

                        $scope.reloadEmployee = false;

                        $timeout(function () {
                            $scope.investigation = response.data.result;

                            $scope.employeeHasCountry = $scope.investigation.employee.entity.country != null;
                            $scope.employeeHasState = $scope.investigation.employee.entity.state != null;
                            $scope.employeeHasCity = $scope.investigation.employee.entity.town != null;

                            if ($scope.investigation.customerPrincipalEconomicActivity == null) {
                                $scope.investigation.customerPrincipalEconomicActivity = $scope.investigation.customer.economicActivity;
                            }

                            getLocationList('general', $scope.investigation.country, $scope.investigation.state);

                            getLocationList('customer', $scope.investigation.customer.country, $scope.investigation.customer.state);

                            getLocationList('customerBranch', $scope.investigation.customerBranchCountry, $scope.investigation.customerBranchState);

                            getLocationList('accident', $scope.investigation.accidentCountry, $scope.investigation.accidentState);

                            $scope.investigation.lesions = convertToBool($scope.investigation.lesions);
                            $scope.investigation.bodies = convertToBool($scope.investigation.bodies);
                            $scope.investigation.factors = convertToBool($scope.investigation.factors);
                            $scope.investigation.mechanisms = convertToBool($scope.investigation.mechanisms);

                            initializeDates();

                            if ($scope.investigation.witnessList == null || $scope.investigation.witnessList.length == 0) {
                                $scope.investigation.witnessList = [
                                    createWitness()
                                ]
                            }
                        });

                        $timeout(function () {
                            $scope.reloadEmployee = true;
                        }, 100)

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);
                    });
            } else {
                $scope.investigation.lesions = buildAdditionalInfo($scope.report_lesion_type);
                $scope.investigation.bodies = buildAdditionalInfo($scope.report_body_part);
                $scope.investigation.factors = buildAdditionalInfo($scope.report_factor);
                $scope.investigation.mechanisms = buildAdditionalInfo($scope.report_mechanism);
            }
        };

        onLoadRecord(currentId);

        $scope.master = $scope.report;

        $scope.form = {

            next: function (form) {

                $scope.toTheTop();

                if (form.$valid || $scope.currentStep == 5) {
                    if ($scope.investigation.id != 0) {
                        nextStep();
                    } else {
                        toaster.pop('error', 'Error', 'Para continuar, primero debe guardar');
                    }
                } else {
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
                    errorMessage();
                }
            },
            prev: function (form) {
                $scope.toTheTop();
                prevStep();
            },
            goTo: function (form, i) {
                if (parseInt($scope.currentStep) > parseInt(i)) {
                    $scope.toTheTop();
                    goToStep(i);

                } else {
                    if (form.$valid) {
                        $scope.toTheTop();
                        goToStep(i);
                    } else {
                        errorMessage();
                    }
                }
            },

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
                    log.info($scope.customer);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;

                } else {
                    //your code for submit
                    log.info($scope.customer);
                    save();
                }

            },
            reset: function (form) {

                $scope.report = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var errorMessage = function (i) {
            toaster.pop('error', 'Error', 'Por favor complete el formulario en este paso antes de continuar');
        };

        var nextStep = function () {
            $scope.currentStep++;
        };

        var prevStep = function () {
            $scope.currentStep--;
        };

        var goToStep = function (i) {
            $scope.currentStep = i;
        };

        var save = function () {
            var req = {};

            if ($scope.investigation.responsibleList == null || $scope.investigation.responsibleList.length == 0) {
                SweetAlert.swal("Información requerida", "Debe ingresar al menos un investigador", "error");
                return;
            }

            var data = JSON.stringify($scope.investigation);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/occupational-investigation-al/save',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {

                    SweetAlert.swal("Validación exitosa", "La información ha sido guardada satisfactoriamente", "success");

                    $scope.investigation = response.data.result;
                    request.investigation_id = response.data.result.id;
                    currentId = response.data.result.id;

                    $scope.employeeHasCountry = $scope.investigation.employee.entity.country != null;
                    $scope.employeeHasState = $scope.investigation.employee.entity.state != null;
                    $scope.employeeHasCity = $scope.investigation.employee.entity.town != null;

                    if ($scope.investigation.customerPrincipalEconomicActivity == null) {
                        $scope.investigation.customerPrincipalEconomicActivity = $scope.investigation.customer.economicActivity;
                    }

                    initializeDates();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        function getLocationList($type, $country, $state) {
            var entities = [
                {name: 'state', value: $country ? $country.id : 68},
                {name: 'city', value: $state ? $state.id : 0},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    switch ($type) {
                        case 'general':
                            $scope.stateList = response.data.data.stateList;
                            $scope.cityList = response.data.data.cityList;
                            break;

                        case 'customerBranch':
                            $scope.branchStateList = response.data.data.stateList;
                            $scope.branchCityList = response.data.data.cityList;
                            break;

                        case 'employee':
                            $scope.employeeStateList = response.data.data.stateList;
                            $scope.employeeCityList = response.data.data.cityList;
                            break;

                        case 'accident':
                            $scope.accidentStateList = response.data.data.stateList;
                            $scope.accidentCityList = response.data.data.cityList;
                            break;

                        default:
                            $scope.stateList = response.data.data.stateList;
                            $scope.cityList = response.data.data.cityList;
                            break;
                    }
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.onSelectCountry = function () {
            if (!$scope.investigation.isReportAtRelated) {
                $scope.investigation.state = null;
                $scope.investigation.city = null;
            }

            getLocationList('general', $scope.investigation.country, null);
        };

        $scope.onSelectState = function () {
            if (!$scope.investigation.isReportAtRelated) {
                $scope.investigation.city = null;
            }

            getLocationList('general', $scope.investigation.country, $scope.investigation.state);
        };

        $scope.onSelectCountryBranch = function () {
            $scope.investigation.customerBranchState = null;
            $scope.investigation.customerBranchCity = null;

            getLocationList('customerBranch', $scope.investigation.customerBranchCountry, null);
        };

        $scope.onSelectStateBranch = function () {
            $scope.investigation.customerBranchCity = null;

            getLocationList('customerBranch', $scope.investigation.customerBranchCountry, $scope.investigation.customerBranchState);
        };

        $scope.onSelectEmployeeCountry = function () {
            $scope.investigation.employee.entity.state = null;
            $scope.investigation.employee.entity.city = null;
            getLocationList('employee', $scope.investigation.employee.entity.country, null);
        };

        $scope.onSelectEmployeeState = function () {
            $scope.investigation.employee.entity.city = null;
            getLocationList('employee', $scope.investigation.employee.entity.country, $scope.investigation.employee.entity.state);
        };

        $scope.onSelectCountryAccident = function () {
            if (!$scope.investigation.isReportAtRelated) {
                $scope.investigation.accidentState = null;
                $scope.investigation.accidentCity = null;
            }

            getLocationList('accident', $scope.investigation.accidentCountry, null);
        };

        $scope.onSelectStateAccident = function (item, model) {
            if (!$scope.investigation.isReportAtRelated) {
                $scope.investigation.accidentCity = null;
            }

            getLocationList('accident', $scope.investigation.accidentCountry, $scope.investigation.accidentState);
        };

        var convertToBool = function (array) {

            angular.forEach(array, function (item) {
                item.isActive = item.isActive == '0' ? false : true;
            });

            return array;
        }

        var initializeDates = function () {
            if ($scope.investigation.accidentDate != null) {

                var days = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

                if ($scope.investigation.accidentWeekDayIndex != -1) {
                    var weekDay = {
                        item: days[$scope.investigation.accidentWeekDayIndex],
                        value: $scope.investigation.accidentWeekDayIndex
                    };
                    $scope.investigation.accidentWeekDay = weekDay;
                }
            }

            $scope.investigation.accidentDate = parseValidDateTime($scope.investigation.accidentDate);
            $scope.investigation.reportDate = parseValidDateTime($scope.investigation.reportDate);
            $scope.investigation.notificationArlDate = parseValidDateTime($scope.investigation.notificationArlDate);
            $scope.investigation.notificationDocumentDate = parseValidDateTime($scope.investigation.notificationDocumentDate);

            $scope.investigation.employee.entity.birthDate = parseValidDateTime($scope.investigation.employee.entity.birthDate);
            $scope.investigation.employeeStartDate = parseValidDateTime($scope.investigation.employeeStartDate);

            $scope.investigation.accidentDateOf = parseValidDateTime($scope.investigation.accidentDateOf);
            $scope.investigation.accidentDateOfDeath = parseValidDateTime($scope.investigation.accidentDateOfDeath);
        }

        var parseValidDateTime = function (value) {
            if (value == null) {
                return null;
            }

            return new Date(value.date);
        }


        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", 0);
            }
        }

        $scope.onPreview = function () {
            if ($scope.$parent != null) {
                var mode = $scope.isView ? "view" : "preview";
                $scope.$parent.navToSection("preview", mode, currentId);
            }
        }

        $scope.onChangeWorkplace = function () {
            var $isWorkingInHq = $scope.investigation.customerIsWorkingInHq;

            $scope.investigation.customerBranchEconomicActivity = $isWorkingInHq ? $scope.investigation.customerPrincipalEconomicActivity : null;
            $scope.investigation.customerBranchCountry = $isWorkingInHq ? $scope.investigation.customer.country : null;
            $scope.investigation.customerBranchState = $isWorkingInHq ? $scope.investigation.customer.state : null;
            $scope.investigation.customerBranchCity = $isWorkingInHq ? $scope.investigation.customer.town : null;
            $scope.investigation.customerBranchZone = $isWorkingInHq ? $scope.investigation.customerPrincipalZone : null;
            $scope.investigation.details = $isWorkingInHq ? $scope.investigation.customer.contacts : [];
        }


        //----------------------------------------------------------------EMPLOYEE

        $scope.onAddEmployee = function () {
            if ($scope.investigation.customer != null) {
                var modalInstance = $aside.open({
                    //templateUrl: 'app_modal_disability_employee.htm',
                    templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/investigation/customer_absenteeism_disability_employee_modal.htm",
                    placement: 'right',
                    size: 'lg',
                    backdrop: true,
                    controller: 'ModalInstanceSideOccupationalInvestigationEmployeeCtrl',
                    scope: $scope,
                    resolve: {
                        customer: function () {
                            return $scope.investigation.customer;
                        }
                    }
                });
                modalInstance.result.then(function (employee) {
                    initializeEmployee(employee);
                });
            } else {
                toaster.pop("error", "Error", "Debe seleccionar una empresa");
            }

        };

        $scope.onAddDisabilityEmployeeList = function () {
            if ($scope.investigation.customer != null) {
                var modalInstance = $aside.open({
                    //templateUrl: 'app_modal_disability_employee_list.htm',
                    templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/investigation/customer_absenteeism_disability_employee_list_modal.htm",
                    placement: 'right',
                    size: 'lg',
                    backdrop: true,
                    controller: 'ModalInstanceSideOccupationalInvestigationEmployeeListCtrl',
                    scope: $scope,
                    resolve: {
                        customer: function () {
                            return $scope.investigation.customer;
                        }
                    }
                });
                modalInstance.result.then(function (employee) {
                    initializeEmployee(employee);
                });
            } else {
                toaster.pop("error", "Error", "Debe seleccionar una empresa");
            }
        };

        var initializeEmployee = function (employee) {
            var result = $filter('filter')($scope.employees, {
                id: employee.id
            });

            if (result.length == 0) {
                $scope.employees.push(employee);
            }

            $scope.investigation.employee = employee;
        }

        //----------------------------------------------------------------REPORT AT

        $scope.onSearchReportAT = function (target) {
            if ($scope.investigation.customer != null) {
                var modalInstance = $aside.open({
                    //templateUrl: 'app_modal_disability_employee_list.htm',
                    templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/investigation/customer_absenteeism_disability_employee_list_modal.htm",
                    placement: 'right',
                    size: 'lg',
                    backdrop: true,
                    controller: 'ModalInstanceSideOccupationalInvestigationReportListCtrl',
                    scope: $scope,
                    resolve: {
                        customer: function () {
                            return $scope.investigation.customer;
                        }
                    }
                });
                modalInstance.result.then(function (report) {
                    initializeReportAT(report);
                });
            } else {
                toaster.pop("error", "Error", "Debe seleccionar una empresa");
            }
        };

        $scope.onSelectReportAt = function () {
            initializeInvestigationWithReport();
        }

        $scope.onSelectMadeByType = function () {

        }

        var initializeReportAT = function (report) {
            var result = $filter('filter')($scope.reportAtList, {
                id: report.id
            });

            if (result.length == 0) {
                $scope.reportAtList.push(report);
            }

            $scope.investigation.reportAt = report;

            initializeInvestigationWithReport();
        }

        var initializeInvestigationWithReport = function () {
            var report = $scope.investigation.reportAt;

            var countryList = $filter('filter')($scope.countryList, {
                id: 68
            }, true);
            var countryCO = countryList.length > 0 ? countryList[0] : null;

            $scope.investigation.employee = report.employee;
            $scope.investigation.accidentDate = report.accidentDate;
            $scope.investigation.accidentWeekDay = report.accidentWeekDay;
            $scope.investigation.accidentType = report.accidentType;
            $scope.investigation.country = countryCO;
            $scope.investigation.state = report.accidentState;
            $scope.investigation.city = report.accidentCity;
            $scope.investigation.reportDate = report.date;
            $scope.investigation.customerPrincipalEconomicActivity = report.customerEconomicActivity;
            $scope.investigation.customerPrincipalZone = report.customerZone;
            $scope.investigation.employeeLinkType = report.typeLinkage;
            $scope.investigation.employeeZone = report.zone;
            $scope.investigation.employeeHabitualOccupationTime = report.accidentWorkTime;
            $scope.investigation.accidentWorkingDay = report.accidentWorkingDay;
            $scope.investigation.accidentIsDeathCause = report.accidentDeathCause;
            $scope.investigation.accidentCategory = report.accidentType;
            $scope.investigation.accidentWorkTimeHour = report.accidentWorkTime;
            $scope.investigation.accidentIsRegularWork = report.accidentRegularWork;
            $scope.investigation.accidentOtherRegularWorkText = report.accidentRegularWorkText ? report.accidentRegularWorkText.name : null;
            $scope.investigation.accidentOtherRegularWorkTextCode = report.accidentRegularWorkText.name;
            $scope.investigation.accidentIsRegularWork = report.accidentRegularWork;
            $scope.investigation.customerBranchCountry = countryCO;
            $scope.investigation.accidentCountry = countryCO;
            $scope.investigation.accidentState = report.accidentState;
            $scope.investigation.accidentCity = report.accidentCity;
            $scope.investigation.accidentZone = report.accidentZone;
            $scope.investigation.accidentLocation = report.accidentLocation;
            $scope.investigation.accidentPlace = report.accidentPlace;
            $scope.investigation.accidentPlace = report.accidentPlace;

            $scope.investigation.lesions = report.lesions.map(function (item, index, array) {
                return {
                    id: 0,
                    customerOccupationalReportAlId: 0,
                    itemId: item.itemId,
                    description: item.description,
                    isActive: item.isActive == '1' || item.isActive == 1
                }
            });

            $scope.investigation.bodies = report.bodies.map(function (item, index, array) {
                return {
                    id: 0,
                    customerOccupationalReportAlId: 0,
                    itemId: item.itemId,
                    description: item.description,
                    isActive: item.isActive == '1' || item.isActive == 1
                }
            });

            $scope.investigation.factors = report.factors.map(function (item, index, array) {
                return {
                    id: 0,
                    customerOccupationalReportAlId: 0,
                    itemId: item.itemId,
                    description: item.description,
                    isActive: item.isActive == '1' || item.isActive == 1
                }
            });

            $scope.investigation.mechanisms = report.mechanisms.map(function (item, index, array) {
                return {
                    id: 0,
                    customerOccupationalReportAlId: 0,
                    itemId: item.itemId,
                    description: item.description,
                    isActive: item.isActive == '1' || item.isActive == 1
                }
            });

            $scope.investigation.accidentInjuryTypeText = report.accidentDescription;

            initializeDates();
        }

        //----------------------------------------------------------------AGENT BASE

        var onAddAgentBase = function (target) {
            if ($scope.investigation.customer != null) {
                var modalInstance = $aside.open({
                    //templateUrl: 'app_modal_disability_employee.htm',
                    templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/investigation/customer_absenteeism_disability_employee_modal.htm",
                    placement: 'right',
                    size: 'lg',
                    backdrop: true,
                    controller: 'ModalInstanceSideOccupationalInvestigationAgentCtrl',
                    scope: $scope,
                    resolve: {
                        customer: function () {
                            return $scope.investigation.customer;
                        }
                    }
                });
                modalInstance.result.then(function (agent) {
                    initializeResponsible(agent, target);
                });
            } else {
                toaster.pop("error", "Error", "Debe seleccionar una empresa");
            }

        };

        var onSearchAgentBaseList = function (target) {
            if ($scope.investigation.customer != null) {
                var modalInstance = $aside.open({
                    //templateUrl: 'app_modal_disability_employee_list.htm',
                    templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/data_table_list_modal.htm",
                    placement: 'right',
                    size: 'lg',
                    backdrop: true,
                    controller: 'ModalInstanceSideOccupationalInvestigationAgentListCtrl',
                    scope: $scope,
                    resolve: {
                        customer: function () {
                            return $scope.investigation.customer;
                        }
                    }
                });
                modalInstance.result.then(function (responsible) {
                    initializeResponsible(responsible, target);
                });
            } else {
                toaster.pop("error", "Error", "Debe seleccionar una empresa");
            }
        };

        var initializeResponsible = function (responsible, target) {
            var result = $filter('filter')($scope.responsibleList, {
                id: responsible.id
            });

            if (result.length == 0) {
                $scope.responsibleList.push(responsible);
            }

            $scope.investigation.responsibleList[target].responsible = responsible;
        }


        //----------------------------------------------------------------AGENT

        $scope.onAddAgent = function (index) {
            onAddAgentBase(index);
        };

        $scope.onSearchAgentList = function (index) {
            onSearchAgentBaseList(index)
        };

        $scope.onAddAgents = function () {
            $timeout(function () {
                if ($scope.investigation.responsibleList == null) {
                    $scope.investigation.responsibleList = [];
                }
                $scope.investigation.responsibleList.push({
                    id: 0,
                    type: null,
                    responsible: null,
                    documentNumber: '',
                    name: '',
                    job: '',
                    role: null
                });
            });
        };

        $scope.onAddAgents();

        $scope.onRemoveAgents = function (index) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Confirma eliminar este registro??",
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
                            var contact = $scope.investigation.responsibleList[index];

                            $scope.investigation.responsibleList.splice(index, 1);

                            if (contact.id != 0) {
                                var req = {};
                                req.id = contact.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-occupational-investigation-al-responsible/delete',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function (e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function () {});
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }


        var onSearchEconomicActivityList = function (target) {
            if ($scope.investigation.customer != null) {
                var modalInstance = $aside.open({
                    //templateUrl: 'app_modal_disability_employee_list.htm',
                    templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/investigation/customer_absenteeism_disability_employee_list_modal.htm",
                    placement: 'right',
                    size: 'lg',
                    backdrop: true,
                    controller: 'ModalInstanceSideOccupationalInvestigationEconomicActivityListCtrl',
                    scope: $scope
                });
                modalInstance.result.then(function (agent) {
                    initializeEconomicActivity(agent, target);
                });
            } else {
                toaster.pop("error", "Error", "Debe seleccionar una empresa");
            }
        };

        var initializeEconomicActivity = function (economicActivity, target) {

            switch (target) {
                case 'P':
                    $scope.investigation.customerPrincipalEconomicActivity = economicActivity;
                    break;

                case 'B':
                    $scope.investigation.customerBranchEconomicActivity = economicActivity;
                    break;
            }
        }


        //----------------------------------------------------------------HQ

        $scope.onSearchPrincipalList = function () {
            onSearchEconomicActivityList('P')
        };

        //----------------------------------------------------------------BRANCH

        $scope.onSearchBranchList = function () {
            onSearchEconomicActivityList('B')
        };

        $scope.onAddInfoDetail = function () {
            $timeout(function () {
                if ($scope.investigation.details == null) {
                    $scope.investigation.details = [];
                }
                $scope.investigation.details.push({
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
                            var contact = $scope.investigation.details[index];

                            $scope.investigation.details.splice(index, 1);

                            if (contact.id != 0) {
                                var req = {};
                                req.id = contact.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-employee-contact/delete',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function (e) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                    $scope.onSaveEmployee();
                                }).finally(function () {


                                });
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }


        //----------------------------------------------------------------BRANCH

        $scope.onAddWitness = function () {
            $timeout(function () {
                if ($scope.investigation.witnessList == null) {
                    $scope.investigation.witnessList = [];
                }
                $scope.investigation.witnessList.push(createWitness());
            });
        };

        $scope.onRemoveWitness = function (index) {
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
                            var contact = $scope.investigation.witnessList[index];

                            $scope.investigation.witnessList.splice(index, 1);

                            if (contact.id !== undefined && contact.id != 0) {
                                var req = {};
                                req.id = contact.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/occupational-investigation-al-witness/delete',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
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


        //----------------------------------------------------------------CAUSE BASE

        $scope.onAddImmediate = function () {
            var req = {};

            if ($scope.immediate.factor == null) {
                toaster.pop("error", "Error", "Debe seleccionar la clasificación de la causa inmediata");
                return;
            }

            if ($scope.immediate.cause == null) {
                toaster.pop("error", "Error", "Debe ingresar el código de la causa inmediata");
                return;
            }


            $scope.immediate.customerOccupationalInvestigationId = currentId;

            var data = JSON.stringify($scope.immediate);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/occupational-investigation-al-cause/save',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.reloadImmediateData();
                    initImmediate();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        $scope.cancelImmediate = function () {
            initImmediate();
        }

        $scope.onAddBasic = function () {
            var req = {};

            if ($scope.basic.factor == null) {
                toaster.pop("error", "Error", "Debe seleccionar la clasificación de la causa básica");
                return;
            }

            if ($scope.basic.cause == null) {
                toaster.pop("error", "Error", "Debe ingresar el código de la causa básica");
                return;
            }

            $scope.basic.customerOccupationalInvestigationId = currentId;

            var data = JSON.stringify($scope.basic);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/occupational-investigation-al-cause/save',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.reloadBasicData();
                    initBasic();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        $scope.cancelBasic = function () {
            initBasic();;
        }

        var onSearchCauseBaseList = function (target) {

            var filterBy = '';

            switch (target) {
                case 'CI':
                    filterBy = target + $scope.immediate.factor.value;
                    break;

                case 'CB':
                    filterBy = target + $scope.basic.factor.value;
                    break;
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/investigation/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideOccupationalInvestigationCauseListCtrl',
                scope: $scope,
                resolve: {
                    target: function () {
                        return filterBy;
                    }
                }
            });
            modalInstance.result.then(function (cause) {
                initializeCause(cause, target);
            });

        };

        var initializeCause = function (cause, target) {

            switch (target) {
                case 'CI':
                    $scope.immediate.cause = cause;
                    break;

                case 'CB':
                    $scope.basic.cause = cause;
                    break;
            }
        }


        //------------------------------CAUSES IMMEDIATE DATATABLE
        request.investigation_id = currentId;

        $scope.dtInstanceOccupationalInvestigationFactorImmediate = {};
        $scope.dtOptionsOccupationalInvestigationFactorImmediate = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/occupational-investigation-al-cause/immediate',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {}
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
                loadRowInmmediate();
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

        $scope.dtColumnsOccupationalInvestigationFactorImmediate = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if ($rootScope.can("seguimiento_edit")) {
                    //actions += editTemplate;
                }

                if ($rootScope.can("seguimiento_delete")) {}

                if (!$scope.isView) {
                    actions += deleteTemplate;
                }

                return actions;
            }),
            DTColumnBuilder.newColumn('factor.item').withTitle("Factor").withOption('width', 300).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('cause.code').withTitle("Código Causa").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('cause.name').withTitle("Causa").withOption('defaultContent', ''),
        ];

        var loadRowInmmediate = function () {

            angular.element("#dtOccupationalInvestigationFactorImmediate a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.analysis.id = id;
                $scope.onLoadRecord();
            });

            angular.element("#dtOccupationalInvestigationFactorImmediate a.delRow").on("click", function () {
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
                                url: 'api/customer/occupational-investigation-al-cause/delete',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadImmediateData();
                            });

                        } else {
                            $scope.reloadImmediateData();
                        }
                    });
            });

        };

        $scope.dtInstanceOccupationalInvestigationFactorImmediateCallback = function (instance) {
            $scope.dtInstanceOccupationalInvestigationFactorImmediate = instance;
        }

        $scope.reloadImmediateData = function () {
            request.investigation_id = currentId;
            $scope.dtInstanceOccupationalInvestigationFactorImmediate.reloadData();
        };


        //------------------------------CAUSES BASIC DATATABLE
        request.investigation_id = currentId;

        $scope.dtInstanceOccupationalInvestigationFactorBasic = {};
        $scope.dtOptionsOccupationalInvestigationFactorBasic = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/occupational-investigation-al-cause/basic',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {}
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
                loadRowBasic();
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

        $scope.dtColumnsOccupationalInvestigationFactorBasic = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if ($rootScope.can("seguimiento_edit")) {
                    //actions += editTemplate;
                }

                if (!$scope.isView) {
                    actions += deleteTemplate;
                }                

                return actions;
            }),
            DTColumnBuilder.newColumn('factor.item').withTitle("Factor").withOption('width', 300).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('cause.code').withTitle("Código Causa").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('cause.name').withTitle("Causa").withOption('defaultContent', ''),
        ];

        var loadRowBasic = function () {

            angular.element("#dtOccupationalInvestigationFactorBasic a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.analysis.id = id;
                $scope.onLoadRecord();
            });

            angular.element("#dtOccupationalInvestigationFactorBasic a.delRow").on("click", function () {
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
                                url: 'api/customer/occupational-investigation-al-cause/delete',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadBasicData();
                            });

                        } else {
                            $scope.reloadBasicData();
                        }
                    });
            });

        };

        $scope.dtInstanceOccupationalInvestigationFactorBasicCallback = function (instance) {
            $scope.dtInstanceOccupationalInvestigationFactorBasic = instance;
        }

        $scope.reloadBasicData = function () {
            request.investigation_id = currentId;
            $scope.dtInstanceOccupationalInvestigationFactorBasic.reloadData();
        };


        //----------------------------------------------------------------INSECURE ACT

        $scope.onSearchInsecureActList = function (target) {
            onSearchCauseBaseList(target)
        };



        //----------------------------------------------------------------MEASURES

        $scope.onAddMeasure = function () {
            var req = {};

            if ($scope.measure.type == null) {
                toaster.pop("error", "Error", "Debe seleccionar el tipo de medida de intervención");
                return;
            }

            if ($scope.measure.description == null || $scope.measure.description == "") {
                toaster.pop("error", "Error", "Debe ingresar la de medida de intervención");
                return;
            }

            $scope.measure.customerOccupationalInvestigationId = currentId;

            var data = JSON.stringify($scope.measure);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/occupational-investigation-al-measure/save',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.reloadMeasureData();
                    initMeasure();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        $scope.cancelMeasure = function () {
            initMeasure();
        }

        //------------------------------MEASURE DATATABLE
        request.investigation_id = currentId;

        $scope.dtInstanceOccupationalInvestigationMeasure = {};
        $scope.dtOptionsOccupationalInvestigationMeasure = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/occupational-investigation-al-measure',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {}
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

        $scope.dtColumnsOccupationalInvestigationMeasure = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var improvementPlan = '<a class="btn btn-warning btn-xs improvementRow lnk" href="#" uib-tooltip="Plan Mejoramiento" data-id="' + data.id + '" data-description="' + data.description + '">' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if ($rootScope.can("seguimiento_edit")) {
                    //actions += editTemplate;
                }

                actions += improvementPlan;
                
                if (!$scope.isView) {                    
                    actions += deleteTemplate;
                }
                
                return actions;
            }),
            DTColumnBuilder.newColumn('type.item').withTitle("Factor").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Medida").withOption('defaultContent', '')
        ];

        var loadRow = function () {

            angular.element("#dtOccupationalInvestigationMeasure a.improvementRow").on("click", function () {
                var id = $(this).data("id");
                var description = $(this).data("description");
                onAddImprovementPlan({
                    id: id,
                    comment: description
                });
            });

            angular.element("#dtOccupationalInvestigationMeasure a.delRow").on("click", function () {
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
                                url: 'api/customer/occupational-investigation-al-measure/delete',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadMeasureData();
                            });

                        } else {
                            $scope.reloadMeasureData();
                        }
                    });
            });

        };

        $scope.dtInstanceOccupationalInvestigationMeasureCallback = function (instace) {
            $scope.dtInstanceOccupationalInvestigationMeasure = instace;
        }

        $scope.reloadMeasureData = function () {
            request.investigation_id = currentId;
            $scope.dtInstanceOccupationalInvestigationMeasure.reloadData();
        };


        //----------------------------------------------------------------------------IMPROVEMENT PLAN
        var onAddImprovementPlan = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_improvement_plan_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerOccupationalInvestigationImprovementPlanCtrl',
                scope: $scope,
                resolve: {
                    dataItem: function () {
                        return dataItem;
                    },
                    isView: function () {
                        return $scope.isView
                    },
                }
            });
            modalInstance.result.then(function () {

            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        };


        //----------------------------------------------------------------WATCH
        $scope.$watch("investigation.accidentDate", function () {
            initializeAccidentDates();
        });

        var initializeAccidentDates = function () {
            $timeout(function () {
                if ($scope.investigation.accidentDate != null) {

                    var days = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
                    var n = $scope.investigation.accidentDate.getDay();
                    var weekDay = {
                        item: days[n],
                        value: n
                    };

                    $scope.investigation.accidentWeekDay = weekDay;
                }
            }, 400);
        }

    }
]);

app.controller('ModalInstanceSideCustomerOccupationalInvestigationImprovementPlanCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, dataItem,
                                                                                                      $log, $timeout, SweetAlert, isView, $filter, FileUploader,
                                                                                                      $http, DTOptionsBuilder, DTColumnBuilder, $compile) {

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.isView = isView;

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
            classificationName: "INTERVENCIÓN",
            classificationId: null,
            entityName: 'AT',
            entityId: dataItem.id,
            type: null,
            endDate: null,
            description: dataItem.comment,
            observation: '',
            responsible: null,
            isRequiresAnalysis: false,
            status: {
                id: 0,
                value: 'CR',
                item: 'Creada'
            },
            trackingList: [],
            alertList: [],
            period: null
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
                        SweetAlert.swal("Información no disponible", "Registro no encontrado", "error");

                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del centro de trabajo", "error");
                    }
                })
                .then(function (response) {
                    console.log(response);
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
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                //your code for submit

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

app.controller('ModalInstanceSideOccupationalInvestigationEmployeeCtrl', function ($rootScope, $stateParams, $scope, customer, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.contractTypes = $rootScope.parameters("employee_contract_type");
    $scope.documentTypes = $rootScope.parameters("employee_document_type");

    var initialize = function () {
        $scope.employee = {
            id: 0,
            customerId: customer.id,
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

    var loadWorkPlace = function () {

        var req = {};
        req.operation = "restriction";
        req.customerId = $stateParams.customerId;;


        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/workplace/listProcess',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
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

    var loadJobs = function () {
        if ($scope.employee.workPlace != null) {
            var req = {};
            req.operation = "restriction";
            req.customerId = $stateParams.customerId;;
            req.workPlaceId = $scope.employee.workPlace.id;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/job/listByWorkPlace',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
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
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                $scope.attachment = response.data.result;
                toaster.pop('success', 'Operación Exitosa', 'Registro eliminado');
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            $log.error(e);
            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
        }).finally(function () {

        });

    };

});

app.controller('ModalInstanceSideOccupationalInvestigationEmployeeListCtrl', function ($rootScope, $stateParams, $scope, customer, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'Empleados';

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
                        $scope.employee = response.data.result;
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
            complete: function () {}
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
            var id = $(this).data("id");
            $scope.editDisabilityEmployee(id);
        });
    };

    $scope.dtInstanceDisabilityEmployeeListCallback = function (instance) {
        $scope.dtInstanceDisabilityEmployeeList = instance;
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

app.controller('ModalInstanceSideOccupationalInvestigationAgentCtrl', function ($rootScope, $stateParams, $scope, customer, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.contractTypes = $rootScope.parameters("employee_contract_type");
    $scope.documentTypes = $rootScope.parameters("employee_document_type");

    var initialize = function () {
        $scope.employee = {
            id: 0,
            customerId: customer.id,
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

    var loadWorkPlace = function () {

        var req = {};
        req.operation = "restriction";
        req.customerId = $stateParams.customerId;;


        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/workplace/listProcess',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
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

    var loadJobs = function () {
        if ($scope.employee.workPlace != null) {
            var req = {};
            req.operation = "restriction";
            req.customerId = $stateParams.customerId;;
            req.workPlaceId = $scope.employee.workPlace.id;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/job/listByWorkPlace',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
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
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                $scope.attachment = response.data.result;
                toaster.pop('success', 'Operación Exitosa', 'Registro eliminado');
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            $log.error(e);
            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
        }).finally(function () {

        });

    };

});

app.controller('ModalInstanceSideOccupationalInvestigationAgentListCtrl', function ($rootScope, $stateParams, $scope, customer, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'Agentes';

    var request = {};

    $scope.responsible = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.responsible);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var onLoadRecord = function (id, type) {
        if (id != 0) {
            var req = {
                id: id,
                type: type,
                customerId: $stateParams.customerId
            };
            $http({
                    method: 'GET',
                    url: 'api/customer-occupational-investigation-al-responsible/get-relation',
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
                        $scope.responsible = response.data.result;
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


    $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {

                d.customerId = customer.id;

                return JSON.stringify(d);
            },
            url: 'api/customer-occupational-investigation-al-responsible-available',
            contentType: 'application/json',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {}
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

            var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar" tooltip-placement="right" data-type="' + data.typeCode + '"   data-id="' + data.id + '"' + disabled + ' >' +
                '   <i class="fa fa-plus-square"></i></a> ';

            actions += editTemplate;

            return actions;
        }),

        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200),
        DTColumnBuilder.newColumn('documentNumber').withTitle("Documento").withOption('width', 200),
        DTColumnBuilder.newColumn('fullName').withTitle("Nombre").withOption('width', 200),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200),
    ];

    var loadRow = function () {
        angular.element("#dtCommonDataTableList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            var type = angular.element(this).data("type");
            onLoadRecord(id, type);
        });
    };

    $scope.dtInstanceCommonDataTableListCallback = function (instance) {
        $scope.dtInstanceCommonDataTableList = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceCommonDataTableList.reloadData();
    };

});

app.controller('ModalInstanceSideOccupationalInvestigationEconomicActivityListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'ACTIVIDADES ECONÓMICAS';

    var request = {};

    $scope.activity = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.activity);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.activity.id != 0) {
            var req = {
                id: $scope.activity.id,
            };
            $http({
                    method: 'GET',
                    url: 'api/investigation-al/economic-activity',
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
                        $scope.activity = response.data.result;
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

    request.operation = "restriction";
    request.data = "";

    $scope.dtInstanceDisabilityEmployeeList = {};
    $scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/investigation-al/economic-activity',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {}
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

        DTColumnBuilder.newColumn('code').withTitle("Código").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('name').withTitle("Actividad Económica").withOption('defaultContent', '')
    ];

    var loadRow = function () {
        angular.element("#dtDisabilityEmployeeList a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editDisabilityEmployee(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityEmployeeList.reloadData();
    };

    $scope.viewDisabilityEmployee = function (id) {
        $scope.activity.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityEmployee = function (id) {
        $scope.activity.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});

app.controller('ModalInstanceSideOccupationalInvestigationCauseListCtrl', function ($rootScope, $stateParams, $scope, target, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'CAUSAS';

    switch (target) {
        case 'CIAI':
            $scope.title = 'CAUSAS INMEDIATAS - ACTOS INSEGUROS';
            break;

        case 'CICI':
            $scope.title = 'CAUSAS INMEDIATAS - CONDICIONES INSEGURAS';
            break;

        case 'CBFT':
            $scope.title = 'CAUSAS BASICAS - FACTORES DEL TRABAJO';
            break;

        case 'CBFP':
            $scope.title = 'CAUSAS BASICAS - FACTORES PERSONALES';
            break;
    }

    var request = {};

    $scope.cause = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.cause);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.cause.id != 0) {
            var req = {
                id: $scope.cause.id,
            };
            $http({
                    method: 'GET',
                    url: 'api/investigation-al/cause',
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
                        $scope.cause = response.data.result;
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

    request.operation = "restriction";
    request.investigation_cause_category = target
    request.data = "";

    $scope.dtInstanceDisabilityEmployeeList = {};
    $scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/investigation-al/cause',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {}
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

        DTColumnBuilder.newColumn('parent.name').withTitle("Padre").withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('code').withTitle("Código").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('name').withTitle("Causa").withOption('defaultContent', '')
    ];

    var loadRow = function () {
        angular.element("#dtDisabilityEmployeeList a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editDisabilityEmployee(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityEmployeeList.reloadData();
    };

    $scope.viewDisabilityEmployee = function (id) {
        $scope.cause.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityEmployee = function (id) {
        $scope.cause.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});

app.controller('ModalInstanceSideOccupationalInvestigationReportListCtrl', function ($rootScope, $stateParams, $scope, customer, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'REPORTES AT';

    $scope.report = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.report);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.report.id != 0) {
            var req = {
                id: $scope.report.id,
            };
            $http({
                    method: 'GET',
                    url: 'api/occupational-report',
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
                        $scope.report = response.data.result;
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

    var request = {};
    request.operation = "restriction";
    request.customer_id = customer.id;
    request.data = "";

    $scope.dtInstanceDisabilityEmployeeList = {};
    $scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/occupational-report',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {}
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

    $scope.dtColumnsDisabilityEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 100).notSortable()
        .renderWith(function (data, type, full, meta) {

            var actions = "";
            var disabled = ""

            var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar reporte"  data-id="' + data.id + '"' + disabled + ' >' +
                '   <i class="fa fa-plus-square"></i></a> ';

            actions += editTemplate;

            return actions;
        }),

        DTColumnBuilder.newColumn('documentType').withTitle("Tipo de Identificación").withOption('width', 200),
        DTColumnBuilder.newColumn('documentNumber').withTitle("Número de Identificación").withOption('width', 200),
        DTColumnBuilder.newColumn('firstName').withTitle("Nombre"),
        DTColumnBuilder.newColumn('lastName').withTitle("Apellidos"),
        DTColumnBuilder.newColumn('accidentDate').withTitle("Fecha Accidente").withOption('width', 150),
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
        angular.element("#dtDisabilityEmployeeList a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editDisabilityEmployee(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityEmployeeList.reloadData();
    };

    $scope.viewDisabilityEmployee = function (id) {
        $scope.report.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityEmployee = function (id) {
        $scope.report.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});
