'use strict';
/**
 * controller for Customers
 */
app.controller('customerEvaluationMinimumStandardMonthlyReportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ChartService) {


        var log = $log;
        var currentId =  0;

        $scope.chart = {
            bar: { options: null },
            line: { options: null },
            status: { data: null },
            average: { data: null },
            total: { data: null },
            advance: { data: null },
        };       

        function getCharts() {
            var $criteria = {
                customerId: $stateParams.customerId,
                evaluationMinimumStandardId: $scope.currentId,
                year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0
            };

            var entities = [           
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_line_options', criteria: null}, 
                { name: 'customer_evaluation_minimum_standard_monthly', criteria: $criteria }
            ];
    
            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.line.options = response.data.data.chartLineOptions; 

                    $scope.chart.bar.options.legend.position = 'bottom';
                    $scope.chart.line.options.legend.position = 'bottom';

                    $scope.chart.status.data = response.data.data.customerEvaluationMinimumStandardMonthlyStatus;
                    $scope.chart.average.data = response.data.data.customerEvaluationMinimumStandardMonthlyAverage;
                    $scope.chart.total.data = response.data.data.customerEvaluationMinimumStandardMonthlyTotal;
                    $scope.chart.advance.data = response.data.data.customerEvaluationMinimumStandardMonthlyAdvance;

                    currentId = response.data.data.customerEvaluationMinimumStandardId;
                    
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.audit = {};
        $scope.audit.operation = "info";
        $scope.audit.year = 0;
        $scope.audit.standardId = $scope.currentId;

        $scope.filter = {
            selectedYear: null
        };

        $scope.years = [];

        var initialize = function () {
            var req = {};
            req.customer_id = $stateParams.customerId;
            req.standard_id = currentId;

            $http({
                method: 'POST',
                url: 'api/customer/evaluation-minimum-standard/list-data',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.cycles = response.data.data.cycle;
                    $scope.rates = response.data.data.rateReal;
                    $scope.years = response.data.data.years;

                    if ($scope.years.length > 0) {
                        $scope.filter.selectedYear = $scope.years[0];
                        $scope.audit.year = $scope.filter.selectedYear.value;
                        $scope.audit.standardId = $scope.currentId;                        
                        $scope.reloadData();
                        $scope.reloadIndicatorData();
                        getCharts();
                    }
                });

            }).catch(function (e) {
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        }

        initialize();

        $scope.dtInstanceMinimumStandardSummaryProgram = {};
        $scope.dtOptionsMinimumStandardSummaryProgram = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.data = Base64.encode(JSON.stringify($scope.audit));

                    return d;
                },
                url: 'api/customer/evaluation-minimum-standard/summary-program',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {

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
                //Pace.stop();

            })
            .withDOM('tr')
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })


            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsMinimumStandardSummaryProgram = [
            DTColumnBuilder.newColumn('abbreviation')
                .withTitle("Código")
                .withOption('width', 100),
            DTColumnBuilder.newColumn('name')
                .withTitle("Ciclo")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('ENE')
                .withTitle("ENE")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('FEB')
                .withTitle("FEB")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('MAR')
                .withTitle("MAR")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('ABR')
                .withTitle("ABR")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('MAY')
                .withTitle("MAY")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('JUN')
                .withTitle("JUN")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('JUL')
                .withTitle("JUL")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('AGO')
                .withTitle("AGO")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('SEP')
                .withTitle("SEP")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('OCT')
                .withTitle("OCT")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('NOV')
                .withTitle("NOV")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('DIC')
                .withTitle("DIC")
                .withOption('width', 200),
        ];


        /////////////////////////////////////////////////////////////////
        //Indicators
        ////////////////////////////////////////////////////////////////

        $scope.dtInstanceMinimumStandardSummaryIndicator = {};
        $scope.dtOptionsMinimumStandardSummaryIndicator = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.data = Base64.encode(JSON.stringify($scope.audit));

                    return d;
                },
                url: 'api/customer/evaluation-minimum-standard/summary-indicator',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {

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

            })
            .withDOM('tr')
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })


            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsMinimumStandardSummaryIndicator = [
            DTColumnBuilder.newColumn('indicator')
                .withTitle("Indicador")
                .withOption('width', 400),

            DTColumnBuilder.newColumn(null)
                .withTitle("ENE")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.ENE) : data.ENE;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("FEB")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.FEB) : data.FEB;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("MAR")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.MAR) : data.MAR;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("ABR")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.ABR) : data.ABR;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("MAY")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.MAY) : data.MAY;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("JUN")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.JUN) : data.JUN;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("JUL")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.JUL) : data.JUL;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("AGO")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.AGO) : data.AGO;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("SEP")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.SEP) : data.SEP;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("OCT")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.OCT) : data.OCT;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("NOV")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.NOV) : data.NOV;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("DIC")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.DIC) : data.DIC;
                }),
        ];

        var convertToInt = function (value) {
            return value != null ? parseInt(value) : "";
        }

        $scope.dtInstanceMinimumStandardSummaryProgramCallback = function (instance) {
            $scope.dtInstanceMinimumStandardSummaryProgram = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceMinimumStandardSummaryProgram.reloadData();
        };


        $scope.dtInstanceMinimumStandardSummaryIndicatorCallback = function (instance) {
            $scope.dtInstanceMinimumStandardSummaryIndicator = instance;
        };

        $scope.reloadIndicatorData = function () {
            $scope.dtInstanceMinimumStandardSummaryIndicator.reloadData();
        };

        $scope.onContinue = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", $scope.currentId);
            }
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.currentId);
            }
        };

        $scope.changeYear = function (item, model) {
            $timeout(function () {
                $scope.audit.operation = "info";
                $scope.audit.standardId = $scope.currentId;
                $scope.audit.year = item.value;
                $scope.reloadData();
                $scope.reloadIndicatorData();
                getCharts();
            });
        };

        $scope.clearYear = function () {
            $timeout(function () {
                $scope.audit.operation = "info";
                $scope.audit.standardId = $scope.currentId;
                $scope.audit.year = 0;
                $scope.filter.selectedYear = null;
                $scope.reloadData();
                $scope.reloadIndicatorData();
                getCharts();
            });
        }

        $scope.onSummaryByProgramExportExcel = function () {
            jQuery("#downloadDocument")[0].src = "api/customer/evaluation-minimum-standard/summary-program-export?data=" + Base64.encode(JSON.stringify($scope.audit));
        }

        $scope.onSummaryByProgramExportPdf = function () {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.currentId;
            kendo.drawing.drawDOM($(".export-pdf-program"))
                .then(function (group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: {left: "1cm", top: "1cm", right: "1cm", bottom: "1cm"}
                    });
                })
                .done(function (data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Diagnóstico Reporte Mensual Program.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryByIndicatorExportExcel = function () {
            jQuery("#downloadDocument")[0].src = "api/customer/evaluation-minimum-standard/summary-indicator-export?data=" + Base64.encode(JSON.stringify($scope.audit));
        }

        $scope.onSummaryByIndicatorExportPdf = function () {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.currentId;
            kendo.drawing.drawDOM($(".export-pdf-indicator"))
                .then(function (group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: {left: "1cm", top: "1cm", right: "1cm", bottom: "1cm"}
                    });
                })
                .done(function (data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Diagnóstico Reporte Mensual Indicadores.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

    }]);