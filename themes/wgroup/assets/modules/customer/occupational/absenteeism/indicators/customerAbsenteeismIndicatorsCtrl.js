'use strict';
/**
 * controller for Customers
 */
app.controller('customerAbsenteeismIndicatorsCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document', '$filter',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter) {


        var log = $log;
        var request = {};

        log.info("loading..customerAbsenteeismIndicatorsCtrl ");

        $scope.classifications = $rootScope.parameters("absenteeism_disability_classification");
        $scope.periods = $rootScope.parameters("absenteeism_indicator_period");
        $scope.workplaces = [];

        $scope.indicator = {
            id: 0,
            customerId: $stateParams.customerId,
            classification: null,
            period: null,
            workCenter: null,
            manHoursWorked: 0,
            population: 0,
            directCost: 0,
            eventNumber: 0,
            targetEvent: 0,
            diseaseRate: 0,
            diseaseRateFormat: 0,
            disabilityDays: 0,
            targetDisabilityDays: 0,
            targetFrequency: 0,
            targetFrequencyIndex: 0,
            frequencyIndex: 0,
            frequencyIndexFormat: 0,
            targetSeverity: 0,
            targetSeverityIndex: 0,
            severityIndex: 0,
            severityIndexFormat: 0,
            targetWorkAccident: 0,
            disablingInjuriesIndex: 0,
            disablingInjuriesIndexFormat: 0
        };


        var calculateTA = function()
        {
            if ($scope.indicator.population > 0) {
                $scope.indicator.diseaseRate = $scope.indicator.eventNumber / $scope.indicator.population;
            }

            $scope.indicator.diseaseRateFormat = $filter('number')(parseFloat($scope.indicator.diseaseRate) * 100, 2)+'%';;
        };

        var calculateIF = function()
        {
            if ($scope.indicator.manHoursWorked > 0) {
                $scope.indicator.frequencyIndex = (parseFloat($scope.indicator.eventNumber) * 20000) / parseFloat($scope.indicator.manHoursWorked);
            }

            $scope.indicator.frequencyIndexFormat = $filter('number')($scope.indicator.frequencyIndex, 2);
        };

        var calculateIS = function()
        {
            if ($scope.indicator.manHoursWorked > 0) {
                $scope.indicator.severityIndex = (parseFloat($scope.indicator.disabilityDays) * 20000) / parseFloat($scope.indicator.manHoursWorked);
            }

            $scope.indicator.severityIndexFormat = $filter('number')($scope.indicator.severityIndex, 2);
        };

        var calculateILI = function()
        {
            $scope.indicator.disablingInjuriesIndex = (parseFloat($scope.indicator.severityIndex) * parseFloat($scope.indicator.frequencyIndex)) / 1000;
            $scope.indicator.disablingInjuriesIndexFormat = $filter('number')($scope.indicator.disablingInjuriesIndex, 3);
        };

        $scope.$watch("indicator.eventNumber", function () {
            calculateTA();
            calculateIF();
            calculateILI();
        });

        $scope.$watch("indicator.population", function () {
            calculateTA();
        });

        $scope.$watch("indicator.manHoursWorked", function () {
            calculateIF();
            calculateIS();
            calculateILI();
        });

        $scope.$watch("indicator.disabilityDays", function () {
            calculateIS();
            calculateILI();
        });

        $scope.onLoadRecord = function () {
            if ($scope.indicator.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.disability.id);
                var req = {
                    id: $scope.disability.id
                };
                $http({
                    method: 'GET',
                    url: 'api/absenteeism-indicator/get',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta informaci�n.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Informaci�n no disponible", "Centro de trabajo no encontrado", "error");

                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la informaci�n del centro de trabajo", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.indicator = response.data.result;
                            calculateTA();
                            calculateIF();
                            calculateIS();
                            calculateILI();
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

        $scope.onLoadRecord();

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
                    SweetAlert.swal("Validación exitosa", "Guardando información de indicador...", "success");
                    //your code for submit
                    //  log.info($scope.disability);
                    save();
                }

            },
            reset: function (form) {
                $scope.clear();
            }
        };

        $scope.clear = function () {
            $timeout(function () {
                $scope.indicator = {
                    id: 0,
                    customerId: $stateParams.customerId,
                    classification: null,
                    period: null,
                    workCenter: null,
                    manHoursWorked: 0,
                    population: 0,
                    directCost: 0,
                    eventNumber: 0,
                    targetEvent: 0,
                    diseaseRate: 0,
                    diseaseRateFormat: 0,
                    disabilityDays: 0,
                    targetDisabilityDays: 0,
                    targetFrequency: 0,
                    targetFrequencyIndex: 0,
                    frequencyIndex: 0,
                    frequencyIndexFormat: 0,
                    targetSeverity: 0,
                    targetSeverityIndex: 0,
                    severityIndex: 0,
                    severityIndexFormat: 0,
                    targetWorkAccident: 0,
                    disablingInjuriesIndex: 0,
                    disablingInjuriesIndexFormat: 0
                };
            });

            $scope.isView = false;
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.indicator);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/absenteeism-indicator/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.indicator = response.data.result;

                    $scope.reloadData();

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.clear();
            });

        };

        request.operation = "absenteeism";
        request.customer_id = $stateParams.customerId;

        $scope.dtInstanceIndicators = {};
		$scope.dtOptionsIndicators = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/absenteeism-indicator',
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

        $scope.dtColumnsIndicators = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("clientes_view")) {
                        actions += viewTemplate;
                    }

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_delete")) {
                        actions += deleteTemplate;
                    }


                    return actions;
                }),

            DTColumnBuilder.newColumn('classification.item').withTitle("Clasificación").withOption('width', 200),
            DTColumnBuilder.newColumn('period.item').withTitle("Periodo").withOption('width', 200),
            DTColumnBuilder.newColumn('workCenter.item').withTitle("Centro de trabajo").withOption('width', 200),
            DTColumnBuilder.newColumn('diseaseRate').withTitle("% Tasa de Enf / AL").withOption('width', 200),
            DTColumnBuilder.newColumn('frequencyIndex').withTitle("(IF)").withOption('width', 200),
            DTColumnBuilder.newColumn('severityIndex').withTitle("(IS)").withOption('width', 200),
            DTColumnBuilder.newColumn('disablingInjuriesIndex').withTitle("(ILI)").withOption('width', 200),
        ];

        $scope.viewDiagnosticWorkPlace = function (id) {
            $scope.disability.id = id;
            $scope.isView = true;
            $scope.onLoadRecord();
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.absenteeism_id);
            }
        };

        var loadRow = function () {

            $("#dataTableIndicators a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editDiagnosticWorkPlace(id);
            });

            $("#dataTableIndicators a.viewRow").on("click", function () {
                var id = $(this).data("id");

                $scope.disability.id = id;
                $scope.viewDiagnosticWorkPlace(id);

            });

            $("#dataTableIndicators a.delRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminar�s el centro de trabajo seleccionado.",
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
                                url: 'api/absenteeism-indicator/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminaci�n", "Se ha presentado un error durante la eliminaci�n del registro por favor intentelo de nuevo", "error");
                            }).finally(function () {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });
        };

        $scope.reloadData = function () {
            $scope.dtInstanceIndicators.reloadData();
        };


        $scope.editDiagnosticWorkPlace = function (id) {
            $scope.indicator.id = id;
            $scope.isView = false;
            $scope.onLoadRecord()
        };


        var loadWorkPlaces = function()
        {
            var req = {};
            req.customer_id = $stateParams.customerId;

            $http({
                method: 'POST',
                url: 'api/absenteeism-indicator/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.workplaces = response.data.result;
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        }

        loadWorkPlaces();








    }]);
