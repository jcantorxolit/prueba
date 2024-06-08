'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerOccupationalReportALEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$aside', 'toaster', 'ListService',
    function ($scope, $stateParams, $log, $compile,  $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $aside, toaster,
              ListService) {

        var log = $log;

        var request = {};
        var idTraking = $scope.$parent.currentTraking;

        $scope.currentStep = 0;
        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";
        $scope.reloadEmployee = true;

        $scope.isView =  $scope.$parent.modeDsp == "view";
        $scope.minDateCurrent = new Date();
        $scope.customerId = $stateParams.customerId;
        $scope.users = [];
        $scope.employees = [];

        $scope.conditionalList = [
            {
                item: 'Si',
                value: 1
            }, {
                item: 'No',
                value: 0
            }
        ];

        log.info("loading..customerTrackingEditCtrl con el id de tracking: ", idTraking);

        // parametros para seguimientos

        $scope.eps =  $rootScope.parameters("eps");
        $scope.afp =  $rootScope.parameters("afp");
        $scope.arl =  $rootScope.parameters("arl");
        $scope.employment_relationship =  $rootScope.parameters("wg_report_employment_relationship");
        $scope.document_type =  $rootScope.parameters("tipodoc");
        $scope.zone =  $rootScope.parameters("wg_report_zone");
        $scope.economic_activity =  $rootScope.parameters("wg_economic_activity");
        $scope.type_linkage =  $rootScope.parameters("wg_type_linkage");
        $scope.gender =  $rootScope.parameters("gender");
        //$scope.employee_occupation =  $rootScope.parameters("employee_occupation");
        $scope.report_regular_work =  $rootScope.parameters("wg_report_regular_work"); //Diurna, noctura...
        $scope.report_week_day =  $rootScope.parameters("wg_report_week_day");
        $scope.report_working_day =  $rootScope.parameters("wg_report_working_day");
        //$scope.report_regular_task =  $rootScope.parameters("wg_report_regular_task");
        $scope.report_accident_type =  $rootScope.parameters("wg_report_accident_type");
        $scope.report_location =  $rootScope.parameters("wg_report_location");
        $scope.report_place =  $rootScope.parameters("wg_report_place");
        $scope.report_lesion_type =  $rootScope.parameters("wg_report_lesion_type");
        $scope.report_body_part =  $rootScope.parameters("wg_report_body_part");
        $scope.report_factor =  $rootScope.parameters("wg_report_factor");
        $scope.report_mechanism =  $rootScope.parameters("wg_report_mechanism");

        getList();

        function getList() {
            var entities = [
                { name: 'customer_config_job_activity_list', value: $stateParams.customerId },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.report_regular_task = response.data.data.customerConfigJobActivityList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.states =  [];
        $scope.cities =  [];


        $scope.report = {
            id : 0,
            customerId : $stateParams.customerId,
            employeeId : 0,
            typeLinkage: null,
            firstLastName: "",
            secondLastName: "",
            firstName: "",
            secondName: "",
            documentType: null,
            documentNumber: "",
            birthDate: null,
            gender: null,
            address: null,
            telephone: null,
            fax: null,
            state: null,
            city: null,
            zone: null,
            job: null,
            occupation: null,
            occupationTimeDay: "",
            occupationTimeMonth: "",
            startDate: null,
            salary: "",
            workingDay: null,
            eps: null,
            arl: null,
            isAfp: null,
            afp: null,
            customerEmploymentRelationship: null,
            customerEconomicActivity: null,
            customerBusinessName: "",
            customerDocumentType: null,
            customerDocumentNumber: "",
            customerAddress: "",
            customerEmail: "",
            customerTelephone: "",
            customerFax: "",
            customerState: null,
            customerCity: null,
            customerZone: null,
            isCustomerBranchName: null,
            customerBranchEconomicActivity: null,
            customerBranchAddress: "",
            customerBranchTelephone: "",
            customerBranchFax: "",
            customerBranchState: null,
            customerBranchCity: null,
            accidentDate: null,
            accidentWeekDay: null,
            accidentWorkingDay: null,
            accidentRegularWork: null,
            accidentRegularWorkText: null,
            accidentWorkTime: null,
            accidentType: null,
            accidentDeathCause: null,
            accidentState: null,
            accidentCity: null,
            accidentZone: null,
            accidentLocation: null,
            accidentPlace: null,
            accidentLesionDescription: "",
            accidentBodyPartDescription: "",
            accidentMechanismDescription: "",
            accidentDescription: "",
            isAccidentWitness: null,
            date: null,
            responsibleName: "",
            responsibleDocumentType: null,
            responsibleDocumentNumber: "",
            responsibleDocumentJob: "",
            status: "abierto",
            witnesses: [
                {
                    id: 0,
                    name: "",
                    documentType: null,
                    documentNumber: 0,
                    job: ""
                } , {
                    id: 0,
                    name: "",
                    documentType: null,
                    documentNumber: 0,
                    job: ""
                }
            ],
            lesions: [],
            bodies: [],
            factors: [],
            mechanisms: [],
        };

        var loadOccupations = function()
        {
            if ($scope.report.job != null) {
                var req = {};
                req.operation = "diagnostic";
                req.customerId = $scope.customerId;
                req.jobId = $scope.report.job.id;

                return $http({
                    method: 'POST',
                    url: 'api/customer/config-sgsst/job-activity/list',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        $scope.employee_occupation = response.data.data;
                    });
                }).catch(function (e) {

                }).finally(function () {

                });
            } else {
                $scope.employee_occupation = [];
            }
        };


        $scope.$watch("report.job", function () {
            //console.log('new result',result);
            loadOccupations();
        });

        $scope.cancelEdition = function (index) {
            if($scope.isView){
                if($scope.$parent != null){
                    $scope.$parent.navToSection("list", "list");
                }
            }else{
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
                                if($scope.$parent != null){
                                    $scope.$parent.navToSection("list", "list");
                                }
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        var buildAdditionalInfo = function(array) {

            var additionalInformation = [];

            angular.forEach(array, function(item){
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

        var onLoadRecord = function(id){
            // se debe cargar primero la información actual del cliente..

            if (id) {
                var req = {
                    id: id
                };

                $http({
                    method: 'GET',
                    url: 'api/occupational-report',
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

                        $timeout(function(){
                            $scope.report = response.data.result;

                            initializeDates();
                            initializeAccidentDates();

                            $scope.report.lesions = convertToBool($scope.report.lesions);
                            $scope.report.bodies = convertToBool($scope.report.bodies);
                            $scope.report.factors = convertToBool($scope.report.factors);
                            $scope.report.mechanisms = convertToBool($scope.report.mechanisms);

                            $scope.changeState($scope.report.state);
                            $scope.changeCustomerState($scope.report.customerState);
                            $scope.changeCustomerBranchState($scope.report.customerBranchState);
                            $scope.changeAccidentState($scope.report.accidentState)

                        });

                        $timeout(function(){
                            $scope.reloadEmployee = true;
                        }, 100)

                    }).finally(function () {
                        $timeout(function(){
                            $scope.loading =  false;
                        }, 400);
                    });
            } else {
                $scope.report.lesions = buildAdditionalInfo($scope.report_lesion_type);
                $scope.report.bodies = buildAdditionalInfo($scope.report_body_part);
                $scope.report.factors = buildAdditionalInfo($scope.report_factor);
                $scope.report.mechanisms = buildAdditionalInfo($scope.report_mechanism);
            }
        };

        //----------------------------------------------------------------WATCH
        $scope.$watch("report.accidentDate", function () {
            initializeAccidentDates();
        });

        var initializeAccidentDates = function () {
            $timeout(function () {
                if ($scope.report.accidentDate != null) {

                    var days = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
                    var n = $scope.report.accidentDate.getDay();
                    var weekDay = {item: days[n], value: n};

                    $scope.report.accidentWeekDay = weekDay;
                }
            }, 400);
        }


        var initializeDates = function() {
            if ($scope.report.birthDate) {
                $scope.report.birthDate = new Date($scope.report.birthDate.date);
            }

            if ($scope.report.startDate) {
                $scope.report.startDate = new Date($scope.report.startDate.date);
            }

            if ($scope.report.accidentDate) {
                $scope.report.accidentDate = new Date($scope.report.accidentDate.date);
            }

            if ($scope.report.date) {
                $scope.report.date = new Date($scope.report.date.date);
            }
        }

        onLoadRecord($scope.$parent.currentReport);

        $scope.master = $scope.report;

        $scope.form = {

            next: function (form) {

                $scope.toTheTop();

                if (form.$valid) {
                    nextStep();
                } else {
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
/*
            if ($scope.report.birthDate != null && $scope.report.birthDate != "") {
                if ($scope.report.birthDate.date != undefined) {
                    $scope.report.birthDate = $scope.report.birthDate.date;
                } else {
                    $scope.report.birthDate = $scope.report.birthDate.toISOString();
                }
            }

            if ($scope.report.date != null && $scope.report.date != "") {
                if ($scope.report.date.date != undefined) {
                    $scope.report.date = $scope.report.date.date;
                } else {
                    $scope.report.date = $scope.report.date.toISOString();
                }
            }

            if ($scope.report.accidentDate != null && $scope.report.accidentDate != "") {
                if ($scope.report.accidentDate.date != undefined) {
                    $scope.report.accidentDate = $scope.report.accidentDate.date;
                } else {
                    $scope.report.accidentDate = $scope.report.accidentDate.toISOString();
                }
            }

            if ($scope.report.startDate != null && $scope.report.startDate != "") {
                if ($scope.report.startDate.date != undefined) {
                    $scope.report.startDate = $scope.report.startDate.date;
                } else {
                    $scope.report.startDate = $scope.report.startDate.toISOString();
                }
            }
*/
            var data = JSON.stringify($scope.report);
            req.data = Base64.encode(data);

            var isNew = $scope.report.id == 0;


            return $http({
                method: 'POST',
                url: 'api/occupational-report/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function() {
                    $scope.report = response.data.result;

                    if (isNew) {
                        $scope.$parent.currentReport = $scope.report.id;
                        onLoadRecord($scope.$parent.currentReport);
                    } else {

                        initializeDates();
                        initializeAccidentDates();

                        $scope.report.lesions = convertToBool($scope.report.lesions);
                        $scope.report.bodies = convertToBool($scope.report.bodies);
                        $scope.report.factors = convertToBool($scope.report.factors);
                        $scope.report.mechanisms = convertToBool($scope.report.mechanisms);

                        $scope.changeState($scope.report.state);
                        $scope.changeCustomerState($scope.report.customerState);
                        $scope.changeCustomerBranchState($scope.report.customerBranchState);
                        $scope.changeAccidentState($scope.report.accidentState)
                    }

                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                });
            }).catch(function(e){
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function(){

            });

        };


        var loadStates = function () {
            var req = {
                cid: 68
            };
            $http({
                method: 'GET',
                url: 'api/states',
                params: req
            }).catch(function (e, code) {

            }).then(function (response) {
                $scope.states = response.data.result;
                $scope.cities = [];
            }).finally(function () {

            });
        }

        var loadEmployees = function () {

            var req = {};
            req.customer_id = $stateParams.customerId;

            $http({
                method: 'POST',
                url: 'api/absenteeism-disability/employee',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.employees = response.data.data;
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });

        };

        var loadCustomer = function(id)
        {
            if (id) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.customer.id);
                var req = {
                    id: id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer',
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
                            SweetAlert.swal("Información no disponible", "Cliente no encontrado", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.customer = response.data.result;

                            $scope.report.customerBusinessName = $scope.customer.businessName;
                            $scope.report.customerDocumentType = $scope.customer.documentType;
                            $scope.report.customerDocumentNumber = $scope.customer.documentNumber;
                            $scope.report.customerEconomicActivity = $scope.customer.economicActivity;


                            var address = $filterDetail($scope.customer.contacts, "dir");
                            var telephone = $filterDetail($scope.customer.contacts, "tel");
                            var fax = $filterDetail($scope.customer.contacts, "fax");
                            var email = $filterDetail($scope.customer.contacts, "email");

                            $scope.report.customerAddress = address.length > 0 ? address[0].value  : "";
                            $scope.report.customerTelephone = telephone.length > 0 ? telephone[0].value  : "";
                            $scope.report.customerFax = fax.length > 0 ? fax[0].value  : "";
                            $scope.report.customerEmail = email.length > 0 ? email[0].value  : "";

                            $scope.report.customerState = $scope.customer.state;
                            $scope.changeCustomerState($scope.report.customerState);
                            $scope.report.customerCity = $scope.customer.town;

                        });

                    }).finally(function () {

                    });


            } else {

            }
        }

        loadStates();
        //loadEmployees();
        loadCustomer($scope.report.customerId);

        $scope.$watch("report.employee", function () {

            if ($scope.report.employee == null || $scope.report.employee.entity == null || !$scope.reloadEmployee) return;

            $scope.report.employeeId = $scope.report.employee.entity.id;
            $scope.report.firstLastName = $scope.report.employee.entity.lastName;
            $scope.report.firstName = $scope.report.employee.entity.firstName;
            $scope.report.documentType = $scope.report.employee.entity.documentType;
            $scope.report.documentNumber = $scope.report.employee.entity.documentNumber;
            $scope.report.birthDate = null;

            if ($scope.report.employee.entity.birthDate) {
                $scope.report.birthDate = new Date($scope.report.employee.entity.birthDate.date);
            }
            $scope.report.gender = $scope.report.employee.entity.gender;
            $scope.report.state = $scope.report.employee.entity.state;
            $scope.changeState($scope.report.state);
            $scope.report.city = $scope.report.employee.entity.town;

            $scope.report.eps = $scope.report.employee.entity.eps;
            $scope.report.arl = $scope.report.employee.entity.arl;
            if ($scope.report.employee.entity.afp != null) {
                $scope.report.isAfp = { item: 'Si', value: '1' }
            } else {
                $scope.report.isAfp = { item: 'No', value: '0' }
            }
            $scope.report.afp = $scope.report.employee.entity.afp;

            var address = $filterDetail($scope.report.employee.entity.details, "dir");
            var telephone = $filterDetail($scope.report.employee.entity.details, "tel");
            var fax = $filterDetail($scope.report.employee.entity.details, "fax");

            $scope.report.address = address.length > 0 ? address[0].value  : "";
            $scope.report.telephone = telephone.length > 0 ? telephone[0].value  : "";
            $scope.report.fax = fax.length > 0 ? fax[0].value  : "";

            $scope.report.salary = $scope.report.employee.salary;

            $scope.report.job = $scope.report.employee.job;
            //$scope.report.occupation = $scope.report.employee.occupation != '' ? $scope.report.employee.occupation : $scope.report.occupation;
            $scope.report.occupation =  $scope.report.employee.occupation;

        });

        var $filterDetail = function(array, type) {

            var posFilterArray = [];

            angular.forEach(array, function(item){
                if(item.type != null && item.type.value === type){
                    posFilterArray.push(item);
                }
            });

            return posFilterArray;
        }

        var convertToBool = function(array) {

            angular.forEach(array, function(item){
                item.isActive = item.isActive == '0' ? false : true;
            });

            return array;
        }

        $scope.changeState = function (item, model) {

            // $("#ddlTown input.ui-select-search").val("");

            $scope.employeeCities = [];
            var req = {
                sid: item.id
            };

            $http({
                method: 'GET',
                url: 'api/towns',
                params: req
            }).then(function (response) {
                $scope.employeeCities = response.data.result;
            }).finally(function () {

            });

        };

        $scope.changeCustomerState = function (item, model) {

            // $("#ddlTown input.ui-select-search").val("");

            $scope.customerCities = [];
            var req = {
                sid: item.id
            };

            $http({
                method: 'GET',
                url: 'api/towns',
                params: req
            }).then(function (response) {
                $scope.customerCities = response.data.result;
            }).finally(function () {

            });

        };

        $scope.changeCustomerBranchState = function (item, model) {

            // $("#ddlTown input.ui-select-search").val("");

            if (item == null) return;

            $scope.customerBranchCities = [];
            var req = {
                sid: item.id
            };

            $http({
                method: 'GET',
                url: 'api/towns',
                params: req
            }).then(function (response) {
                $scope.customerBranchCities = response.data.result;
            }).finally(function () {

            });

        };

        $scope.changeAccidentState = function (item, model) {

            // $("#ddlTown input.ui-select-search").val("");

            $scope.accidentCities = [];
            var req = {
                sid: item.id
            };

            $http({
                method: 'GET',
                url: 'api/towns',
                params: req
            }).then(function (response) {
                $scope.accidentCities = response.data.result;
            }).finally(function () {

            });

        };

        $scope.onCancel = function()
        {
            if($scope.$parent != null){
                $scope.$parent.navToSection("list", "list", 0);
            }
        }

        $scope.onPreview = function()
        {
            if($scope.$parent != null){
                $scope.$parent.navToSection("preview", "preview", $scope.$parent.currentReport);
            }
        }

        $scope.onAddEmployeeList = function() {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/investigation/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideOccupationalEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (employee) {
                //loadEmployees();
                var result = $filter('filter')($scope.employees, {id: employee.id});

                if (result.length == 0) {
                    $scope.employees.push(employee);
                }

                $scope.report.employee = employee;
                $scope.report.job = $scope.report.employee.job;
            });
        };

        $scope.onSearchEconomicActivity = function () {

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/report/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideOccupationalCustomerEconomicActivityListCtrl',
                scope: $scope
            });
            modalInstance.result.then(function (data) {
                $scope.report.customerEconomicActivity = data;
            });

        };

    }]);

app.controller('ModalInstanceSideOccupationalEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

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

app.controller('ModalInstanceSideOccupationalCustomerEconomicActivityListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

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

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar actividad"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('code').withTitle("Código").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('name').withTitle("Actividad Económica").withOption('defaultContent', '')
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
