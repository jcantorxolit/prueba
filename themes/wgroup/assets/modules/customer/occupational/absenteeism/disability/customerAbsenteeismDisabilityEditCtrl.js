'use strict';
/**
 * controller for Customers
 */
app.controller('customerAbsenteeismDisabilityEditCtrl', ['$scope', '$stateParams', '$log', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter', '$aside',
    'ListService', 'bsLoadingOverlayService', '$q', 'DTColumnBuilder', 'DTOptionsBuilder', '$compile',
    function ($scope, $stateParams, $log, toaster, $state, $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside,
        ListService, bsLoadingOverlayService, $q, DTColumnBuilder, DTOptionsBuilder, $compile) {

        var log = $log;
        var $dayChargedIsDeathValue = 0;
        var $currentMinimumDaily = 0;
        var $exportUrl = null;

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
                {name: 'customer_workplace', value: $stateParams.customerId},
                { name: 'export_url', value: null },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $exportUrl = response.data.data.exportUrl.item;
                    $scope.workplaceList = response.data.data.workplaceList;
                    $dayChargedIsDeathValue = response.data.data.absenteeismDisabilityDayChargedIsDeath ? response.data.data.absenteeismDisabilityDayChargedIsDeath.value : 0;
                    $currentMinimumDaily = response.data.data.absenteeismDisabilityCurrentMinimumDaily ? response.data.data.absenteeismDisabilityCurrentMinimumDaily.value : 0;
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

        $scope.isView = $scope.$parent.editMode == "view";
        $scope.isCreate = true;
        $scope.format = 'dd-MM-yyyy';
        $scope.minDate = new Date() - 1;
        $scope.isDisabilityParentEnable = false;
        $scope.isDisabilityParentRequired = false;

        $scope.currentYear = null;

        $scope.filter = {
            selectedCause: null,
        };

        var init = function () {
            $scope.disability = {
                id: $scope.$parent.currentId,
                category: null,
                type: null,
                cause: null,
                workplace: null,
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
                accidentType: null,
                disabilityParent: null,
                isHour: false,
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
                    url: 'api/absenteeism-disability',
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

                            calculateValues();
                            calculateDayChargedIsDeath();

                            initializeAccidentTypeLIst();
                            initializeDatesAndFormats();
                            validateDisabilityParentRelation();
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
                                    $scope.disability.amountPaid = amountPaid;
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

        var validateDisabilityParentRelation = function() {
            $scope.isDisabilityParentEnable = !$scope.isView
                        && $scope.disability.type && ($scope.disability.type.value == 'Prorroga'
                            || ($scope.disability.type.value == 'Sin Incapacidad'
                                && $scope.disability.accidentType != null && $scope.disability.accidentType.value == 'M'))
                        && $scope.disability.cause != null;

            $scope.isDisabilityParentRequired = !$scope.isView
                        && $scope.disability.type && ($scope.disability.type.value == 'Prorroga'
                            || ($scope.disability.type.value == 'Sin Incapacidad'
                                && $scope.disability.accidentType != null && $scope.disability.accidentType.value == 'M'
                                && $scope.disability.cause != null && $scope.disability.cause.value == 'ELC'))
                        && $scope.disability.cause != null;
        }

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
            $scope.disability.disabilityParent = null;
            $scope.onChangeStartDate();
            initializeAccidentTypeLIst();
            calculateValues();
            calculateDayChargedIsDeath();
            validateDisabilityParentRelation();
        };

        $scope.onSelectCause = function () {
            $scope.disability.accidentType = null;
            $scope.disability.disabilityParent = null;
            initializeAccidentTypeLIst();
            calculateValues();
            calculateDayChargedIsDeath();
            validateDisabilityParentRelation();
        };

        $scope.onSelectAccidentType = function () {
            calculateDayChargedIsDeath();
            validateDisabilityParentRelation();
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
            var req = {};

            var data = JSON.stringify($scope.disability);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/absenteeism-disability/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $scope.disability = response.data.result;
                    initializeDatesAndFormats();

                    if ($scope.disability.disabilityParent) {
                        notify($scope.disability.disabilityParent.id);
                    } else {
                        notify($scope.disability.id);
                    }
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        var notify = function($id) {
            return $http({
                method: 'POST',
                url: $exportUrl + 'api/v1/customer-absenteeism-notify',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param({
                    id: $id,
                    customerId: $stateParams.customerId
                })
            }).then(function (response) {
            }).catch(function (e) {
            }).finally(function () {
            });
        }

        $scope.onAddIndirectCost = function () {
            if ($scope.disability.indirectCost == null) {
                $scope.disability.indirectCost = [];
            }
            $scope.disability.indirectCost.push(
                {
                    id: 0,
                    customerDisabilityId: 0,
                    concept: null,
                    amount: 0
                }
            );
        };

        $scope.onRemoveIndirectCost = function (index) {
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
                            if ($scope.disability.indirectCost[index].id == 0) {
                                $scope.disability.indirectCost.splice(index, 1);
                            } else {

                                var req = {id: $scope.disability.indirectCost[index].id};

                                $http({
                                    method: 'POST',
                                    url: 'api/absenteeism-disability-indirect-cost/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    $scope.disability.indirectCost.splice(index, 1);
                                }).catch(function (e) {
                                    $log.error(e);
                                    toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
                                }).finally(function () {

                                });
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        };


        $scope.onAddEmployee = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_employee_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideDisabilityEmployeeCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function () {
                //$scope.reloadData();
            }, function() {

            });
        };

        $scope.onAddDisabilityDiagnostic = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_diagnostic_modal.htm",
                placement: 'right',
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

        $scope.onAddDisabilityParent = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideDisabilityParentCtrl',
                resolve: {
                    entity: function () {
                        return {
                            cause: $scope.disability.cause.value,
                            customerEmployeeId: $scope.disability.employee.id
                        };
                    }
                }
            });
            modalInstance.result.then(function (result) {
                $scope.disability.disabilityParent = result;
            }, function() {

            });
        };

        $scope.onAddDisabilityEmployeeList = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
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

                $scope.disability.workplace = employee.workPlace;
                $scope.disability.employee = employee;
            }, function() {

            });
        };

        $scope.onAddDaysCharged = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideDisabilityDaysChargedCtrl',
                scope: $scope,
                resolve: {
                    entity: function () {
                        return {
                            id: $scope.disability.id
                        };
                    },
                    isView: function() {
                        return false
                    }
                }
            });
            modalInstance.result.then(function (employee) {
                $scope.reloadData();
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
                $scope.reloadData();
            });
        };

        $scope.onCancel = function () {
            $document.scrollTop(40, 2000);
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        }

        //----------------------------------------------------------DAYS CHARGED

        $scope.dtOptionsCustomerAbsenteeismDisabilityDayCharged = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
			.withOption('responsive', true)
            .withOption('ajax', {
				data: function (d) {
                    d.isDeleted = false;
                    d.customerDisabilityId = $scope.disability.id;
                    return JSON.stringify(d);
                },
                dataSrc: function (response) {
                    $timeout(function () {
                        if ($scope.disability.cause.value == 'AL' || $scope.disability.cause.value == 'AT') {
                            $scope.disability.chargedDays = response.extra ? response.extra :  0;
                        }
                    });
                    return response.data;
                },
                url: 'api/customer-absenteeism-disability-day-charged',
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

        $scope.dtColumnsCustomerAbsenteeismDisabilityDayCharged = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" tooltip="Eliminar registro" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    actions += deleteTemplate;

                    return actions;
                }),
            DTColumnBuilder.newColumn('type').withTitle("Incapacidad Permanente").withOption('width', 200).withOption('defaultContent', '').notSortable(),
            DTColumnBuilder.newColumn('classification').withTitle("Classificación de la Lesión").withOption('defaultContent', '').notSortable(),
            DTColumnBuilder.newColumn('part').withTitle("Parte del Cuerpo").withOption('defaultContent', '').notSortable(),
            DTColumnBuilder.newColumn('value').withTitle("Días").withOption('defaultContent', '').notSortable()
        ];

        var loadRow = function () {

            angular.element("#dtCustomerAbsenteeismDisabilityDayCharged a.delRow").on("click", function () {
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
                                url: 'api/customer-absenteeism-disability-day-charged/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (data) {
                                swal("Eliminado", "Regitro eliminado satisfactoriamente", "info");
                            }).catch(function (response) {
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                            }).finally(function () {
                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelado", "Operacion cancelada", "error");
                        }
                    });
            });

        };

		$scope.dtInstanceCustomerAbsenteeismDisabilityDayChargedCallback = function (instance) {
            $scope.dtInstanceCustomerAbsenteeismDisabilityDayCharged = instance;
        };

        $scope.reloadData = function () {
			if ($scope.dtInstanceCustomerAbsenteeismDisabilityDayCharged != null) {
				$scope.dtInstanceCustomerAbsenteeismDisabilityDayCharged.reloadData(null, false);
			}
        };

        $scope.onSaveDayCharged = function (part) {

            bsLoadingOverlayService.start({
                referenceId: 'data-table-list-modal'
            });

            var entity = {
                id: 0,
                customerDisabilityId: $scope.disability.id,
                configDayChargedPart: part
            }

            var req = {};

            var data = JSON.stringify(entity);
            req.data = Base64.encode(data);
            return $q(function (resolve, reject) {
                $http({
                    method: 'POST',
                    url: 'api/customer-absenteeism-disability-day-charged/save',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'El registro se guardo satisfactoriamente.');
                        $scope.reloadData();
                        bsLoadingOverlayService.stop({
                            referenceId: 'data-table-list-modal'
                        });
                        resolve(response);
                    });
                }).catch(function (e) {
                    toaster.pop("error", "Error", e.data.message);
                    bsLoadingOverlayService.stop({
                        referenceId: 'data-table-list-modal'
                    });
                    reject(e);
                }).finally(function () {

                });
            });
        };

    }
]);

app.controller('ModalInstanceSideDisabilityEmployeeCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.contractTypes = $rootScope.parameters("employee_contract_type");
    $scope.documentTypes = $rootScope.parameters("employee_document_type");

    var initialize = function () {
        $scope.employee = {
            id: 0,
            customerId: $stateParams.customerId,
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
        req.operation = "diagnostic";
        req.customerId = $stateParams.customerId;
        ;


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

    var loadJobs = function () {
        if ($scope.employee.workPlace != null) {
            var req = {};
            req.operation = "diagnostic";
            req.customerId = $stateParams.customerId;
            ;
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

app.controller('ModalInstanceSideDisabilityDiagnosticListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var request = {};

    $scope.diagnostic = {
        id: 0,
        code: "",
        description: "",
        isActive: true
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.diagnostic);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.diagnostic.id != 0) {
            var req = {
                id: $scope.diagnostic.id,
            };
            $http({
                method: 'GET',
                url: 'api/disability-diagnostic',
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
                        $scope.diagnostic = response.data.result;
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

    $scope.dtInstanceDisabilityDiagnosticList = {};
    $scope.dtOptionsDisabilityDiagnosticList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                return JSON.stringify(d);
            },
            url: 'api/disability-diagnostic',
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
        angular.element("#dtDisabilityDiagnosticList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
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

    $scope.editDisabilityDiagnostic = function (id) {
        $scope.diagnostic.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});

app.controller('ModalInstanceSideDisabilityEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

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
                    console.log(response);

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
        .withBootstrap()
        .withOption('responsive', true)
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

app.controller('ModalInstanceSideDisabilityDaysChargedCtrl', function ($rootScope, $stateParams, $scope, entity, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, bsLoadingOverlayService) {

    $scope.title = 'DÍAS CARGADOS';
    $scope.loadingOverlayTemplateUrl = $rootScope.app.themeRootUrl + 'templates/loading-overlay-element-template.htm';

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
                d.isDeleted = false;
                d.customerDisabilityId = entity.id;
                return JSON.stringify(d);
            },
            url: 'api/customer-absenteeism-disability-day-charged-available',
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
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar item"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('type').withTitle("Incapacidad Permanente").withOption('width', 200).withOption('defaultContent', '').notSortable(),
        DTColumnBuilder.newColumn('classification').withTitle("Classificación de la Lesión").withOption('defaultContent', '').notSortable(),
        DTColumnBuilder.newColumn('part').withTitle("Parte del Cuerpo").withOption('defaultContent', '').notSortable(),
        DTColumnBuilder.newColumn('value').withTitle("Días").withOption('defaultContent', '').notSortable()
    ];

    var loadRow = function () {
        angular.element("#dtCommonDataTableList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");

            $timeout(function () {
                bsLoadingOverlayService.stop({
                    referenceId: 'data-table-list-modal'
                });

                $scope.onSaveDayCharged({id: id})
                    .then(
                        function (response) {
                            $scope.reloadData();
                        },
                        function (error) {
                        }
                    )
                    .finally(function () {

                    });

            });
        });
    };

    $scope.dtInstanceCommonDataTableListCallback = function (instance) {
        $scope.dtInstanceCommonDataTableList = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceCommonDataTableList.reloadData(null, false);
    };

});

app.controller('ModalInstanceSideDisabilityParentCtrl', function ($rootScope, $stateParams, $scope, entity, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, bsLoadingOverlayService) {

    $scope.title = 'AUSENTISMOS INICIALES';
    $scope.loadingOverlayTemplateUrl = $rootScope.app.themeRootUrl + 'templates/loading-overlay-element-template.htm';

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
                d.causeValue = entity.cause;
                d.customerEmployeeId = entity.customerEmployeeId;
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

            $uibModalInstance.close({
                id: id,
                name: date + ' | ' + cause
            });
        });
    };

    $scope.dtInstanceCommonDataTableListCallback = function (instance) {
        $scope.dtInstanceCommonDataTableList = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceCommonDataTableList.reloadData(null, false);
    };

});
