'use strict';
/**
 * controller for Customers
 */
app.controller('customerAbsenteeismAnalysisResolution1111Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter',
    '$aside', 'ListService', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService, ChartService) {

        var log = $log;
        var request = {};

        $scope.isLoaded = false;

        $scope.classifications = $rootScope.parameters("absenteeism_disability_classification");

        $scope.chart = {
            line: { options: null },
            eventNumber: { data: null },
            disabilityDays: { data: null },
            IF: { data: null },
            IS: { data: null },
            ILI: { data: null },
        };

        function getCharts() {
            var $criteria = {
                customerId: $stateParams.customerId,
                year: $scope.indicator.period ? $scope.indicator.period.value : 0,
                workPlace: $scope.indicator.workCenter ? $scope.indicator.workCenter.value : null,
                classification: $scope.indicator.classification ? $scope.indicator.classification.value : null,
                resolution: '1111',
            };

            var entities = [
                { name: 'chart_line_options', criteria: null },
                { name: 'customer_absenteeism_indicator', criteria: $criteria }
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.line.options = response.data.data.chartLineOptions;
                    $scope.chart.eventNumber.data = response.data.data.customerAbsenteeismIndicatorEventNumber;
                    $scope.chart.disabilityDays.data = response.data.data.customerAbsenteeismIndicatorDisabilityDays;
                    $scope.chart.IF.data = response.data.data.customerAbsenteeismIndicatorIF;
                    $scope.chart.IS.data = response.data.data.customerAbsenteeismIndicatorIS;
                    $scope.chart.ILI.data = response.data.data.customerAbsenteeismIndicatorILI;
                }, function (error) {

                });
        }

        getList();

        function getList() {
            var entities = [
                { name: 'absenteeism_disability_indicator_years', value: $stateParams.customerId },
                { name: 'absenteeism_disability_indicator_workplaces', value: $stateParams.customerId },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.periods = response.data.data.absenteeism_disability_indicator_years;
                    $scope.workplaces = response.data.data.absenteeism_disability_indicator_workplaces;
                    $scope.indicator.period = $scope.periods.length > 0 ? $scope.periods[0] : null

                    $scope.isLoaded = true;

                    getCharts();
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.indicator = {
            classification: null,
            period: null,
            workCenter: null,
        };

        request.operation = "absenteeism";
        request.customer_id = $stateParams.customerId;

        $scope.dtInstanceIndicators = {};
        $scope.dtOptionsIndicators = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-indicator',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[2, 'desc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                loadRowIndicator();
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

                    actions += editTemplate;

                    return actions;
                }),

            DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200),
            DTColumnBuilder.newColumn('period').withTitle("Periodo").withOption('width', 200),
            DTColumnBuilder.newColumn('name').withTitle("Centro de trabajo").withOption('width', 200),
            DTColumnBuilder.newColumn('manHoursWorked').withTitle("(HHT)").withOption('width', 200),
            DTColumnBuilder.newColumn('disabilityDays').withTitle("Días incapacitantes)").withOption('width', 200),
            DTColumnBuilder.newColumn('eventNumber').withTitle("Eventos").withOption('width', 200),
            DTColumnBuilder.newColumn('directCost').withTitle("Costo Directo").withOption('width', 200),
            DTColumnBuilder.newColumn('indirectCost').withTitle("Costo Indirecto").withOption('width', 200),
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

        var loadRowIndicator = function () {
            angular.element("#dataTableIndicators a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                var modalInstance = $aside.open({
                    templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/analysis/1111/customer_absenteeism_disability_indicator_modal.htm",
                    placement: 'right',
                    size: 'lg',
                    backdrop: true,
                    controller: 'ModalInstanceSideDisabilityIndicatorCtrl',
                    scope: $scope,
                    resolve: {
                        indicator: function () {
                            return {id : id};
                        }
                    }
                });
                modalInstance.result.then(function () {
                    $scope.reloadDataIndicator();
                }, function() {

                });
            });
        };

        $scope.reloadDataIndicator = function () {
            $scope.dtInstanceIndicators.reloadData();
        };

        $scope.onConsolidate = function () {
            var req = {};
            req.id = $stateParams.customerId;
            $http({
                method: 'POST',
                url: 'api/customer-absenteeism-indicator/consolidate-1111',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                swal("Consolidación", "La Matriz de indicadores se ha consolidado satisfactoriamente", "info");
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error en la Consolidación�n", "Se ha presentado un error durante la Consolidación de la matriz por favor intentelo de nuevo", "error");
            }).finally(function () {
                $scope.reloadDataIndicator();
            });
        }

        //----------------------------------------------------------INDICATORS EVENTS
        $scope.dtOptionsEventNumber = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "absenteeism";
                    d.customer_id = $stateParams.customerId;
                    d.workCenter = $scope.indicator.workCenter ? $scope.indicator.workCenter.value : '';
                    d.year = $scope.indicator.period ? $scope.indicator.period.value : 0;
                    d.classification = $scope.indicator.classification ? $scope.indicator.classification.value : '';
                    d.name = "eventNumber";
                    return d;
                },
                url: 'api/absenteeism-indicator/report',
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
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsEventNumber = [
            DTColumnBuilder.newColumn('item').withTitle("Mes").notSortable(),
            DTColumnBuilder.newColumn('eventNumber').withTitle("Nro Eventos").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('targetEvent').withTitle("Meta").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceEventNumberCallback = function(instance) {
            $scope.dtInstanceEventNumber = instance;
        }

        //----------------------------------------------------------INDICATORS DISABILITY DAYS
        $scope.dtInstanceDisabilityDays = {};
        $scope.dtOptionsDisabilityDays = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "absenteeism";
                    d.customer_id = $stateParams.customerId;
                    d.workCenter = $scope.indicator.workCenter ? $scope.indicator.workCenter.value : '';
                    d.year = $scope.indicator.period ? $scope.indicator.period.value : 0;
                    d.classification = $scope.indicator.classification ? $scope.indicator.classification.value : '';
                    d.name = "disabilityDays";
                    return d;
                },
                url: 'api/absenteeism-indicator/report',
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
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsDisabilityDays = [
            DTColumnBuilder.newColumn('item').withTitle("Mes").notSortable(),
            DTColumnBuilder.newColumn('disabilityDays').withTitle("Nro Días").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('targetDisabilityDays').withTitle("Meta").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceDisabilityDaysCallback = function(instance) {
            $scope.dtInstanceDisabilityDays = instance;
        }


        //----------------------------------------------------------INDICATORS IF
        $scope.dtInstanceIF = {};
        $scope.dtOptionsIF = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "absenteeism";
                    d.customer_id = $stateParams.customerId;
                    d.workCenter = $scope.indicator.workCenter ? $scope.indicator.workCenter.value : '';
                    d.year = $scope.indicator.period ? $scope.indicator.period.value : 0;
                    d.classification = $scope.indicator.classification ? $scope.indicator.classification.value : '';
                    d.name = "IF";
                    return d;
                },
                url: 'api/absenteeism-indicator/report',
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
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsIF = [
            DTColumnBuilder.newColumn('item').withTitle("Mes").notSortable(),
            DTColumnBuilder.newColumn('frequencyIndex').withTitle("IF").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('targetFrequencyIndex').withTitle("Meta").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceIFCallback = function(instance) {
            $scope.dtInstanceIF = instance;
        }


        //----------------------------------------------------------INDICATORS IS
        $scope.dtInstanceIS = {};
        $scope.dtOptionsIS = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "absenteeism";
                    d.customer_id = $stateParams.customerId;
                    d.workCenter = $scope.indicator.workCenter ? $scope.indicator.workCenter.value : '';
                    d.year = $scope.indicator.period ? $scope.indicator.period.value : 0;
                    d.classification = $scope.indicator.classification ? $scope.indicator.classification.value : '';
                    d.name = "IS";
                    return d;
                },
                url: 'api/absenteeism-indicator/report',
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
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsIS = [
            DTColumnBuilder.newColumn('item').withTitle("Mes").notSortable(),
            DTColumnBuilder.newColumn('severityIndex').withTitle("IS").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('targetSeverityIndex').withTitle("Meta").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceISCallback = function(instance) {
            $scope.dtInstanceIS = instance;
        }


        //----------------------------------------------------------INDICATORS ILI
        $scope.dtInstanceILI = {};
        $scope.dtOptionsILI = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "absenteeism";
                    d.customer_id = $stateParams.customerId;
                    d.workCenter = $scope.indicator.workCenter ? $scope.indicator.workCenter.value : '';
                    d.year = $scope.indicator.period ? $scope.indicator.period.value : 0;
                    d.classification = $scope.indicator.classification ? $scope.indicator.classification.value : '';
                    d.name = "ILI";
                    return d;
                },
                url: 'api/absenteeism-indicator/report',
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
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsILI = [
            DTColumnBuilder.newColumn('item').withTitle("Mes").notSortable(),
            DTColumnBuilder.newColumn('disablingInjuriesIndex').withTitle("ILI").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('targetWorkAccident').withTitle("Meta").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceILICallback = function(instance) {
            $scope.dtInstanceILI = instance;
        }


        $scope.onFilter = function () {
            filterReport();
            getCharts();
        }

        var filterReport = function () {
            $scope.dtInstanceEventNumber.reloadData();
            $scope.dtInstanceDisabilityDays.reloadData();
            $scope.dtInstanceIF.reloadData();
            $scope.dtInstanceIS.reloadData();
            $scope.dtInstanceILI.reloadData();
        }

        $scope.onClear = function () {
            $scope.indicator.classification = null;
            $scope.indicator.workCenter = null;
            $scope.indicator.period = $scope.periods.length > 0 ? $scope.periods[0] : null
            filterReport();
            getCharts();
        }

        $scope.onCreateTarget = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_goal.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/analysis/1111/customer_absenteeism_disability_goal_modal.htm",
                placement: 'left',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideDisabilityGoalCtrl',
                scope: $scope
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        };


    }
]);

app.controller('ModalInstanceSideDisabilityGoalCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.period = $rootScope.parameters("absenteeism_indicator_period");

    $scope.period = $filter('orderBy')($scope.period, 'value', true);

    $scope.indicator = {
        id: 0,
        customerId: $stateParams.customerId,
        period: null,
        targetIF: 0,
        targetIS: 0,
        targetILI: 0,
        targetEvent: 0,
        targetDay: 0,
    };

    $scope.disabledType = false;


    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onReset = function () {
        $scope.indicator = {
            id: 0,
            customerId: $stateParams.customerId,
            period: null,
            targetIF: 0,
            targetIS: 0,
            targetILI: 0,
            targetEvent: 0,
            targetDay: 0,
        };
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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                //your code for submit
                $scope.onSave();
            }

        },
        reset: function (form) {
        }
    };

    $scope.onSave = function () {

        var req = {};
        var data = JSON.stringify($scope.indicator);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/absenteeism-indicator-target/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "El registro se guardó satisfactoriamente", "success");
                $scope.reloadData();
                $scope.onReset();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var request = {};
    request.operation = "document";
    request.customer_id = $scope.indicator.customerId;

    $scope.dtInstanceGoalIndicator = {};
    $scope.dtOptionsGoalIndicator = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/absenteeism-indicator-target',
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

    $scope.dtColumnsGoalIndicator = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.document != null ? data.document.path : "";
                var actions = "";

                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                actions += deleteTemplate;

                return actions;
            }),
        DTColumnBuilder.newColumn('period.item').withTitle("Periodo").withOption('width', 100),

        DTColumnBuilder.newColumn('targetEvent').withTitle("Meta Eventos"),
        DTColumnBuilder.newColumn('targetDay').withTitle("Meta Días Inc"),
        DTColumnBuilder.newColumn('targetIF').withTitle("Meta IF"),
        DTColumnBuilder.newColumn('targetIS').withTitle("Meta IS"),
        DTColumnBuilder.newColumn('targetILI').withTitle("Meta ILI")
    ];

    var loadRow = function () {

        angular.element("#dtDisabilityGoalIndicator a.delRow").on("click", function () {
            var id = angular.element(this).data("id");
            SweetAlert.swal({
                title: "Está seguro?",
                text: "Anularás el anexo seleccionado.",
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
                        $http({
                            method: 'POST',
                            url: 'api/absenteeism-indicator-target/delete',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

    $scope.reloadData = function () {
        $scope.dtInstanceGoalIndicator.reloadData();
    };

});

app.controller('ModalInstanceSideDisabilityIndicatorCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, indicator, $log, $timeout, $document, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.period = $rootScope.parameters("absenteeism_indicator_period");

    $scope.indicator = indicator;

    $scope.disabledType = false;


    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onReset = function () {
        $scope.indicator = {
            id: 0,
            customerId: $stateParams.customerId,
            period: null,
            targetIF: 0,
            targetIS: 0,
            targetILI: 0,
            targetEvent: 0,
            targetDay: 0,
        };
    };

    $scope.onLoadRecord = function () {
        if ($scope.indicator.id != 0) {

            // se debe cargar primero la información actual del cliente..

            var req = {
                id: $scope.indicator.id
            };
            $http({
                method: 'GET',
                url: 'api/absenteeism-indicator',
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

    var calculateTA = function () {
        if ($scope.indicator.population > 0) {
            $scope.indicator.diseaseRate = $scope.indicator.eventNumber / $scope.indicator.population;
        }

        $scope.indicator.diseaseRateFormat = $filter('number')(parseFloat($scope.indicator.diseaseRate) * 100, 2) + '%';;
    };

    var calculateIF = function () {
        if ($scope.indicator.manHoursWorked > 0) {
            $scope.indicator.frequencyIndex = (parseFloat($scope.indicator.eventNumber) * 20000) / parseFloat($scope.indicator.manHoursWorked);
        }

        $scope.indicator.frequencyIndexFormat = $filter('number')($scope.indicator.frequencyIndex, 2);
    };

    var calculateIS = function () {
        if ($scope.indicator.manHoursWorked > 0) {
            $scope.indicator.severityIndex = (parseFloat($scope.indicator.disabilityDays) * 20000) / parseFloat($scope.indicator.manHoursWorked);
        }

        $scope.indicator.severityIndexFormat = $filter('number')($scope.indicator.severityIndex, 2);
    };

    var calculateILI = function () {
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

    $scope.onLoadRecord();



    $scope.master = $scope.indicator;
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
        console.log($scope.indicator);
        var req = {};
        var data = JSON.stringify($scope.indicator);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/absenteeism-indicator/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                $scope.indicator = response.data.result;
                console.log($scope.indicator);
                $uibModalInstance.dismiss('cancel');
                $scope.dtInstanceIndicators.reloadData();


            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            //$scope.dtInstanceDisabilityIndicatorList.reloadData();
        });

    };

    $scope.dtInstanceDisabilityIndicatorList = {};
    $scope.dtOptionsDisabilityIndicatorList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.id = $scope.indicator.id;
                return JSON.stringify(d);
            },
            url: 'api/customer-absenteeism-indicator-summary',
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

    $scope.dtColumnsDisabilityIndicatorList = [
        DTColumnBuilder.newColumn('label').withTitle("Indicador"),
        DTColumnBuilder.newColumn('value').withTitle("Valor"),
        DTColumnBuilder.newColumn('goal').withTitle("Meta")
    ];

    var loadRow = function () {
    };

    $scope.dtInstanceDisabilityIndicatorListCallback = function (instance) {
        $scope.dtInstanceDisabilityIndicatorList = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityIndicatorList.reloadData();
    };

});
