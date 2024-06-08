'use strict';
/**
 * controller for Customers
 */
app.controller('customerVrEmployeeRegisterStagingListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter', '$aside', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService) {

        var log = $log;
        var $exportUrl = '';

        $scope.audit = {
            fields: [],
            filters: [],
        };

        getList();

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


        $scope.dtInstanceCustomerEmployeeVrStagingDT = {};
        $scope.dtOptionsCustomerEmployeeVrStagingDT = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    d.sessionId = $scope.$parent.currentId;
                    d.isValid = 0;

                    return JSON.stringify(d);
                },
                url: 'api/customer-vr-employee-staging',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[1, 'asc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
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

        $scope.dtColumnsCustomerEmployeeVrStagingDT = [
            DTColumnBuilder.newColumn(null).withTitle('Fila').withOption('width', 50)
                .renderWith(function (data, type, full, meta) {

                    var $class = data.is_valid == 1 || data.is_valid ? 'badge badge-success' : 'badge badge-danger';
                    var $icon = data.is_valid == 1 || data.is_valid ? ' <i class=" fa fa-check"></i>' : ' <i class=" fa fa-ban"></i>';

                    return '<span class="'+ $class +'">'  + data.index + $icon + '</span>';
                }),
            DTColumnBuilder.newColumn('registration_date').withTitle("Fecha Realización").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('document_type').withTitle("Tipo Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('document_number').withTitle("Nro Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('experience').withTitle("Experiencia").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('experience_scene').withTitle("Escena").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('indicator').withTitle("Indicador").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('value').withTitle("Valoración").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('justification').withTitle("Justificación").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observation_type').withTitle("Tipo de Observación").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observation_value').withTitle("Observación").withOption('width', 180).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('error_validation').withTitle("Mensaje Importación").withOption('width', 180).withOption('defaultContent', '')
        ];

        var loadRow = function () {

        }

        $scope.dtInstanceCustomerEmployeeVrStagingDTCallback = function(instance) {
            $scope.dtInstanceCustomerEmployeeVrStagingDT = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerEmployeeVrStagingDT.reloadData();
        };


        $scope.onCancel = function () {
            $document.scrollTop(40, 2000);
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        }

    }
]);

app.controller('ModalInstanceSideCustomerAbsenteeismDisabilityStagingEditCtrl', function ($rootScope, $stateParams, $scope, dataItem, isView, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, $document, $filter, $aside, ListService) {


    $scope.onCloseModal = function () {
        $uibModalInstance.close(null);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var log = $log;
        var $dayChargedIsDeathValue = 0;
        var $currentMinimumDaily = 0;

        $scope.causeList = $rootScope.parameters("absenteeism_disability_causes");
        $scope.causeListAdmin = $rootScope.parameters("absenteeism_disability_causes_admin");
        $scope.causes = $rootScope.parameters("absenteeism_disability_causes");
        $scope.types = $rootScope.parameters("absenteeism_disability_type");
        $scope.contractTypes = $rootScope.parameters("employee_contract_type");
        $scope.years = [];
        $scope.documentType = $rootScope.parameters("customer_document_type");
        $scope.tiposdoc = $rootScope.parameters("tipodoc");
        $scope.absenteeismType = $rootScope.parameters("absenteeism_category");
        $scope.concept = $rootScope.parameters("absenteeism_concept_cost");
        $scope.diagnostics = [];
        $scope.employees = [];


        getList();

        function getList() {
            var entities = [
                {name: 'absenteeism_disability_day_charged_is_death', value: null},
                {name: 'absenteeism_disability_current_minimum_daily', value: null},
                {name: 'config_day_charged_part', value: null},
                {name: 'customer_workplace', value: $stateParams.customerId},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.workplaceList = response.data.data.workplaceList;
                    $dayChargedIsDeathValue = response.data.data.absenteeismDisabilityDayChargedIsDeath ? response.data.data.absenteeismDisabilityDayChargedIsDeath.value : 0;
                    $currentMinimumDaily = response.data.data.absenteeismDisabilityCurrentMinimumDaily ? response.data.data.absenteeismDisabilityCurrentMinimumDaily.value : 0;
                    $scope.configDayChargedPartList = response.data.data.configDayChargedPart;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        $scope.loading = true;
        $scope.customer_id = $stateParams.customerId;

        $scope.isView = isView;
        $scope.isCreate = true;
        $scope.format = 'dd-MM-yyyy';
        //$scope.minDate = new Date() - 1;

        $scope.currentYear = null;

        $scope.filter = {
            selectedCause: null,
        };

        var init = function () {
            $scope.disability = {
                id: dataItem.id,
                category: null,
                type: null,
                cause: null,
                workplace: null,
                accidentType: null,
                diagnostic: null,
                employee: null,
                dayLiquidationBasis: 0,
                dayLiquidationBasisFormat: "",
                hourLiquidationBasis: 0,
                hourLiquidationBasisFormat: "",
                startDate: new Date(),
                endDate: new Date(),
                numberDays: 0,
                chargedDays: 0,
                charged: false,
                amountPaid: 0,
                indirectCost: [],
                directCostTotal: 0,
                indirectCostTotal: 0,
            };
        };

        init();

        var initializeAccidentTypeLIst = function() {
            var $accidentTypeList = $scope.accidentTypeList = $rootScope.parameters("absenteeism_disability_accident_type");

            if ($accidentTypeList !== null && $scope.disability.cause) {
                $scope.accidentTypeList = $accidentTypeList.filter(function(parameter) {
                    if ($scope.disability.type != null && $scope.disability.type.value == 'Sin Incapacidad') {
                        return parameter.value == 'L' || parameter.value == 'M';
                    } else if ($scope.disability.cause.value == 'AL' || $scope.disability.cause.value == 'AT') {
                        return parameter.value !== 'N';
                    } else if ($scope.disability.cause.value == 'EL' || $scope.disability.cause.value == 'ELC') {
                        return parameter.value == 'N' || parameter.value == 'M';
                    }
                });
            }
        }

        initializeAccidentTypeLIst();

        $scope.onLoadRecord = function () {
            if ($scope.disability.id != 0) {

                var req = {
                    id: $scope.disability.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer-absenteeism-disability-staging/get',
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
                        console.log(response);

                        $timeout(function () {
                            $scope.disability = response.data.result;

                            if ($scope.disability.category != null && $scope.disability.category.value != "Incapacidad") {
                                $scope.causes = $scope.causeListAdmin;
                            } else {
                                $scope.causes = $scope.causeList;
                            }

                            initializeDatesAndFormats();

                            $scope.onChangeStartDate();

                            calculateValues();
                            calculateDayChargedIsDeath();

                            initializeAccidentTypeLIst();

                            var $accidentTypeValid = $scope.accidentTypeList.filter(function (item) {
                                return item != null  && $scope.disability.accidentType && item.value == $scope.disability.accidentType.value;
                            })

                            if ($accidentTypeValid.length == 0) {
                                $scope.disability.accidentType = null;
                            }
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

        $scope.onLoadRecord();

        var initializeDatesAndFormats = function() {
            $scope.disability.startDate = new Date($scope.disability.startDate.date);
            $scope.disability.endDate = new Date($scope.disability.endDate.date);

            if ($scope.disability.diagnostic != null) {
                var result = $filter('filter')($scope.diagnostics, {id: $scope.disability.diagnostic.id});

                if (result.length == 0) {
                    $scope.diagnostics.push($scope.disability.diagnostic);
                }
            }

            $scope.disability.dayLiquidationBasisFormat = $filter('currency')($scope.disability.dayLiquidationBasis, "$ ", 2);
            $scope.disability.hourLiquidationBasisFormat = $filter('currency')($scope.disability.hourLiquidationBasis, "$ ", 2);
            $scope.disability.indirectCostTotal = $filter('currency')($scope.disability.indirectCostTotal, "$ ", 2);
        }

        $scope.$watch("disability.endDate - disability.startDate", function () {
            calculateDays();
            calculateValues();
        });

        var calculateDays = function() {
            var end = new moment($scope.disability.endDate);
            var start = new moment($scope.disability.startDate);

            if (!$scope.disability.isHour) {
                if ($scope.disability.type != null && $scope.disability.type.value == 'Sin Incapacidad') {
                    $scope.disability.numberDays = 0;
                } else {
                    $scope.disability.numberDays = end.diff(start, 'days') + 1;
                }
            }
        }

        $scope.onChangeStartDate = function(e) {
            if ($scope.disability.type != null && $scope.disability.type.value == 'Sin Incapacidad') {
                $scope.disability.numberDays = 0;
                $scope.disability.endDate = new Date($scope.disability.startDate);
            }
        }

        $scope.$watch("disability.employee.salary", function () {

            if ($scope.disability.employee != null) {
                if ($scope.disability.employee.salary > 0) {
                    $scope.disability.dayLiquidationBasis = $scope.disability.employee.salary / 30;
                }

                if ($scope.disability.dayLiquidationBasis > 0) {
                    $scope.disability.hourLiquidationBasis = $scope.disability.dayLiquidationBasis / 8;
                }

                $scope.disability.dayLiquidationBasisFormat = $filter('currency')($scope.disability.dayLiquidationBasis, "$ ", 2);
                $scope.disability.hourLiquidationBasisFormat = $filter('currency')($scope.disability.hourLiquidationBasis, "$ ", 2);
            }
        });

        var calculateValues = function () {
            var amountPaid = 0;

            if ($scope.disability.id == 0) {
                $scope.disability.amountPaid = 0;
                if ($scope.disability.cause != null) {
                    if ($scope.disability.employee != null && $scope.disability.employee.salary != '' && $scope.disability.numberDays != '') {
                        if ($scope.disability.cause.value == "AL" || $scope.disability.cause.value == "AT") {
                            if (parseInt($scope.disability.numberDays) > 1) {
                                amountPaid = (parseFloat($scope.disability.employee.salary) / 30) * (parseInt($scope.disability.numberDays) - 1)
                                $scope.disability.amountPaid = Math.round(amountPaid * 100) / 100;
                            }
                        } else if ($scope.disability.cause.value == "EG") {
                            if ($scope.disability.type != null && $scope.disability.type.item == "Inicial") {
                                /*if (parseInt($scope.disability.numberDays) == 1) {
                                    amountPaid = 0;
                                    $scope.disability.amountPaid = Math.round(amountPaid * 100) / 100;
                                } else {
                                    amountPaid = (parseFloat($scope.disability.employee.salary) / 30) * (parseInt($scope.disability.numberDays) - 2)
                                    $scope.disability.amountPaid = Math.round(amountPaid * 100) / 100;
                                }*/
                                if (parseInt($scope.disability.numberDays) > 2) {
                                    var daily = (parseFloat($scope.disability.employee.salary) / 30) * 0.6667;
                                    daily = daily < parseFloat($currentMinimumDaily) ? parseFloat($currentMinimumDaily) : daily;
                                    amountPaid = Math.round(daily * (parseInt($scope.disability.numberDays) - 2));
                                    $scope.disability.amountPaid = amountPaid
                                }
                            } else if ($scope.disability.type != null && $scope.disability.type.item == "Prorroga") {
                                amountPaid = (parseFloat($scope.disability.employee.salary) / 30) * (parseInt($scope.disability.numberDays));
                                $scope.disability.amountPaid = Math.round(amountPaid * 100) / 100;
                            }
                        } else if ($scope.disability.cause.value == "LM" || $scope.disability.cause.value == "LP") {
                            amountPaid = (parseFloat($scope.disability.employee.salary) / 30) * parseInt($scope.disability.numberDays);
                            $scope.disability.amountPaid = Math.round(amountPaid * 100) / 100;
                        } else if ($scope.disability.cause.value == "EL") {
                            if (parseInt($scope.disability.numberDays) > 2) {
                                var daily = (parseFloat($scope.disability.employee.salary) / 30) * 0.6667;
                                daily = daily < parseFloat($currentMinimumDaily) ? parseFloat($currentMinimumDaily) : daily;
                                amountPaid = Math.round(daily * (parseInt($scope.disability.numberDays) - 2));
                                $scope.disability.amountPaid = amountPaid;
                            }
                        } else if ($scope.disability.cause.value == "ELC") {
                            if (parseInt($scope.disability.numberDays) > 1) {
                                amountPaid = (parseFloat($scope.disability.employee.salary) / 30) * (parseInt($scope.disability.numberDays) - 1)
                                $scope.disability.amountPaid = Math.round(amountPaid * 100) / 100;
                            }
                        }
                    }
                }

                var directCostTotal = 0;

                if ($scope.disability.category != null) {
                    if ($scope.disability.category.value == "Incapacidad") {
                        if ($scope.disability.employee != null && $scope.disability.employee.salary != '' && $scope.disability.numberDays != '') {
                            if ($scope.disability.cause !== null && ($scope.disability.cause.value == "EG" || $scope.disability.cause.value == "LM")) {
                                if ($scope.disability.type != null && $scope.disability.type.item == "Inicial") {
                                    if (parseInt($scope.disability.numberDays) >= 2) {
                                        directCostTotal = (parseFloat($scope.disability.employee.salary) / 30) * 2
                                    } else {
                                        directCostTotal = (parseFloat($scope.disability.employee.salary) / 30) * (parseInt($scope.disability.numberDays));
                                    }
                                }
                            }
                        }
                    } else {
                        // if ($scope.disability.employee != null && $scope.disability.employee.salary != '' && $scope.disability.numberDays != '') {
                        //     directCostTotal = (parseFloat($scope.disability.employee.salary) / 30) * parseInt($scope.disability.numberDays)
                        // }
                    }
                }

                if ((Math.round(directCostTotal * 100) / 100) > 0) {
                    $scope.disability.directCostTotal = Math.round(directCostTotal * 100) / 100;
                }
            }
        }

        var calculateDayChargedIsDeath = function() {
            var $category = $scope.disability.category ? $scope.disability.category.value : null;
            var $type = $scope.disability.type ? $scope.disability.type.value : null;
            var $cause = $scope.disability.cause ? $scope.disability.cause.value : null;
            var $accidentType = $scope.disability.accidentType ? $scope.disability.accidentType.value : null;

            $scope.disability.chargedDays = 0;

            if ($category == "Incapacidad" && $type == "Inicial") {
                if (($cause == "AT" || $cause == "AL") && $accidentType == "M") {
                    $scope.disability.chargedDays = $dayChargedIsDeathValue;
                }
            } else if ($category == "Incapacidad" && $type == "Sin Incapacidad") {
                if (($cause == "AT" || $cause == "AL") && $accidentType == "M") {
                    $scope.disability.chargedDays = $dayChargedIsDeathValue;
                }
            }
        };


        $scope.onSelectCategory = function () {
            if ($scope.disability.category != null && $scope.disability.category.value != "Incapacidad") {
                $scope.disability.type = null;
                $scope.disability.cause = null;
                $scope.causes = $scope.causeListAdmin;
            } else {
                $scope.disability.isHour = false;
                $scope.causes = $scope.causeList;
            }

            $scope.onChangeIsHour();
            calculateValues();
            calculateDayChargedIsDeath();
        };

        $scope.onSelectType = function () {
            $scope.disability.accidentType = null;
            $scope.onChangeStartDate();
            initializeAccidentTypeLIst();
            calculateValues();
            calculateDayChargedIsDeath();
        };

        $scope.onSelectCause = function () {
            $scope.disability.accidentType = null;
            initializeAccidentTypeLIst();
            calculateValues();
            calculateDayChargedIsDeath();
        };

        $scope.onSelectAccidentType = function () {
            calculateDayChargedIsDeath();
        };

        $scope.onChangeIsHour = function() {
            if (!$scope.disability.isHour) {
                calculateDays();
                calculateValues();
            } else {
                $scope.disability.numberDays = 0;
            }
        }

        $scope.$watch("disability.numberDays", function () {
            calculateValues();
        });

        $scope.$watch("disability.indirectCost", function () {

            if ($scope.disability.indirectCost != null && $scope.disability.indirectCost.length > 0) {
                var indirectCostTotal = 0;

                angular.forEach($scope.disability.indirectCost, function (value, key) {
                    if (value.concept != null && value.amount != '') {
                        if (angular.isNumber(parseFloat(value.amount))) {
                            indirectCostTotal += parseFloat(value.amount);
                        }
                    }
                });

                $scope.disability.indirectCostTotal = indirectCostTotal;
            } else {
                $scope.disability.indirectCostTotal = 0;
            }

        }, true);

        $scope.master = $scope.disability;
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
                    log.info($scope.disability);
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
            var end = new moment($scope.disability.endDate);
            var start = new moment($scope.disability.startDate);

            if ($scope.disability.type && $scope.disability.type.value != 'Sin Incapacidad') {
                if (end < start) {
                    SweetAlert.swal("El formulario contiene errores!", "La fecha final debe ser mayor a la fecha inicial.", "error");
                    return;
                }
            }

            var req = {};

            var data = JSON.stringify($scope.disability);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-absenteeism-disability-staging/save',
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

        $scope.onAddDisabilityDiagnostic = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_diagnostic_modal.htm",
                placement: 'left',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideDisabilityDiagnosticListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (diagnostic) {

                var result = $filter('filter')($scope.diagnostics, {id: diagnostic.id});

                if (result.length == 0) {
                    $scope.diagnostics.push(diagnostic);
                }

                $scope.disability.diagnostic = diagnostic;
            }, function() {

            });
        };

        $scope.onAddDisabilityEmployeeList = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'left',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideDisabilityEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (employee) {
                var result = $filter('filter')($scope.employees, {id: employee.id});

                if (result.length == 0) {
                    $scope.employees.push(employee);
                }

                $scope.disability.employee = employee;
            }, function() {

            });
        };




});

app.controller('ModalInstanceSideCustomerAbsenteeismDisabilityParentStagingCtrl', function ($rootScope, $stateParams, $scope, dataItem, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, bsLoadingOverlayService) {

    $scope.title = 'AUSENTISMOS INICIALES';
    $scope.loadingOverlayTemplateUrl = $rootScope.app.themeRootUrl + 'templates/loading-overlay-element-template.htm';

    var init = function () {
        $scope.disability = {
            id: dataItem.id,
            category: null,
            type: null,
            cause: null,
            workplace: null,
            accidentType: null,
            diagnostic: null,
            employee: null,
            dayLiquidationBasis: 0,
            dayLiquidationBasisFormat: "",
            hourLiquidationBasis: 0,
            hourLiquidationBasisFormat: "",
            startDate: new Date(),
            endDate: new Date(),
            numberDays: 0,
            chargedDays: 0,
            charged: false,
            amountPaid: 0,
            indirectCost: [],
            directCostTotal: 0,
            indirectCostTotal: 0,
            disabilityParent: null
        };
    };

    init();

    $scope.onLoadRecord = function () {
        if ($scope.disability.id != 0) {

            var req = {
                id: $scope.disability.id
            };
            $http({
                method: 'GET',
                url: 'api/customer-absenteeism-disability-staging/get',
                params: req
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.disability = response.data.result;
                        $scope.reloadData();
                    });

                }).finally(function () {

                });
        }
    }

    $scope.onLoadRecord();

    $scope.onCloseModal = function () {
        $uibModalInstance.close(null);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerId = $stateParams.customerId;
                d.typeValue = 'Inicial';
                d.causeValue = $scope.disability.cause ? $scope.disability.cause.value : '';
                d.customerEmployeeId = $scope.disability.employee ? $scope.disability.employee.id : 0;
                return JSON.stringify(d);
            },
            url: 'api/customer-absenteeism-disability-related',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
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

    $scope.dtColumnsCommonDataTableList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar item"  data-id="' + data.id + '" data-cause="' + data.causeItem + '" data-date="' + data.startDateFormat + '" >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('causeItem').withTitle("Causa Incapacidad").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('startDateFormat').withTitle("F Inicial").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('endDateFormat').withTitle("F Final").withOption('width', 180).withOption('defaultContent', '')
    ];

    var loadRow = function () {
        angular.element("#dtCommonDataTableList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            var cause = angular.element(this).data("cause");
            var date = angular.element(this).data("date");

            $scope.disability.disabilityParent = {
                id: id,
                name: date + ' | ' + cause
            };

            save();
        });
    };

    var save = function () {
        var req = {};

        var data = JSON.stringify($scope.disability);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/customer-absenteeism-disability-staging/update',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $uibModalInstance.close(null);
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    $scope.dtInstanceCommonDataTableListCallback = function (instance) {
        $scope.dtInstanceCommonDataTableList = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceCommonDataTableList.reloadData(null, false);
    };

});
