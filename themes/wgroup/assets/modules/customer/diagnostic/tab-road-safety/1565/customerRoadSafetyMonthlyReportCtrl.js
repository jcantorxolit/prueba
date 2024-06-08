'use strict';
/**
 * controller for Customers
 */
app.controller('customerRoadSafetyMonthlyReportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert) {


        var log = $log;
        var request = {};
        log.info("loading..customerRoadSafetyMonthlyReportCtrl ");

        $scope.currentId = $scope.$parent.currentId;

        $scope.request = {};
        $scope.audit = {};
        $scope.audit.operation = "info";
        $scope.audit.roadSafetyId = $scope.$parent.currentId;
        $scope.audit.year = 0;

        $scope.filter = {
            selectedYear: null
        };

        $scope.years = [];

        $scope.dataProgram = null;

        $scope.dataEvolutionProgram = null;

        $scope.dataEvolutionTotal = null;

        $scope.dataEvolutionAvg = null;

        $scope.optionsLine = {
            // Sets the chart to be responsive
            responsive: true,

            ///Boolean - Whether grid lines are shown across the chart
            scaleShowGridLines: true,

            //String - Colour of the grid lines
            scaleGridLineColor: 'rgba(0,0,0,.05)',

            //Number - Width of the grid lines
            scaleGridLineWidth: 1,

            //Boolean - Whether the line is curved between points
            bezierCurve: true,

            //Number - Tension of the bezier curve between points
            bezierCurveTension: 0.4,

            //Boolean - Whether to show a dot for each point
            pointDot: true,

            //Number - Radius of each point dot in pixels
            pointDotRadius: 4,

            //Number - Pixel width of point dot stroke
            pointDotStrokeWidth: 1,

            //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
            pointHitDetectionRadius: 20,

            //Boolean - Whether to show a stroke for datasets
            datasetStroke: true,

            //Number - Pixel width of dataset stroke
            datasetStrokeWidth: 2,

            //Boolean - Whether to fill the dataset with a colour
            datasetFill: true,

            // Function - on animation progress
            onAnimationProgress: function () {
            },

            // Function - on animation complete
            onAnimationComplete: function () {
            },

            //String - A legend template
            legendTemplate: '<ul class="tc-chart-js-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].strokeColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>'
        };

        // Chart.js Options
        $scope.optionsBar = {

            // Sets the chart to be responsive
            responsive: true,

            //Boolean - Whether the scale should start at zero, or an order of magnitude down from the lowest value
            scaleBeginAtZero: true,

            //Boolean - Whether grid lines are shown across the chart
            scaleShowGridLines: true,

            //String - Colour of the grid lines
            scaleGridLineColor: "rgba(0,0,0,.05)",

            //Number - Width of the grid lines
            scaleGridLineWidth: 1,

            //Boolean - If there is a stroke on each bar
            barShowStroke: true,

            //Number - Pixel width of the bar stroke
            barStrokeWidth: 2,

            //Number - Spacing between each of the X value sets
            barValueSpacing: 5,

            //Number - Spacing between data sets within X values
            barDatasetSpacing: 1,

            //String - A legend template
            legendTemplate: '<ul class="tc-chart-js-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].fillColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>'
        };

        var initialize = function () {
            var req = {};
            req.customer_id = $stateParams.customerId;
            req.road_safety_id = $scope.currentId;

            $http({
                method: 'POST',
                url: 'api/customer/road-safety/list-data',
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
                        $scope.request.data = Base64.encode(JSON.stringify($scope.audit));
                        $scope.reloadData();
                        $scope.reloadIndicatorData();
                        loadReports();
                    }
                });

            }).catch(function (e) {
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        }

        initialize();

        var loadReports = function () {
            var req = {};
            req.customer_id = $stateParams.customerId;
            req.road_safety_id = $scope.currentId;
            req.year = $scope.audit.year;

            $http({
                method: 'POST',
                url: 'api/customer/road-safety/monthly-report',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.dataProgram = response.data.result.report_programs;
                    $scope.dataEvolutionProgram = response.data.result.line_programs;
                    $scope.dataEvolutionTotal = response.data.result.line_total;
                    $scope.dataEvolutionAvg = response.data.result.line_avg;

                    console.log($scope.dataProgram);

                    //FOR LINE EVOLUTION AVG

                    $scope.datasetEvolutionAvgData = [];
                    for (var i=0; i<response.data.result.line_avg.datasets.length; i++) {
                        $scope.datasetEvolutionAvgData.push(
                            {label: response.data.result.line_avg.datasets[i].label,
                                backgroundColor: response.data.result.line_avg.datasets[i].pointColor,
                                hoverBackgroundColor: response.data.result.line_avg.datasets[i].pointColor,
                                data: response.data.result.line_avg.datasets[i].data}
                        );
                    }

                    $scope.dataEvolutionAvg = {
                        labels: $scope.dataEvolutionAvg.labels,
                        datasets: $scope.datasetEvolutionAvgData
                    };

                    //FOR LINE EVOLUTION TOTAL

                    $scope.datasetEvolutionTotalData = [];
                    for (var i=0; i<response.data.result.line_total.datasets.length; i++) {
                        $scope.datasetEvolutionTotalData.push(
                            {label: response.data.result.line_total.datasets[i].label,
                                backgroundColor: response.data.result.line_total.datasets[i].pointColor,
                                hoverBackgroundColor: response.data.result.line_total.datasets[i].pointColor,
                                data: response.data.result.line_total.datasets[i].data}
                        );
                    }

                    $scope.dataEvolutionTotal = {
                        labels: $scope.dataEvolutionTotal.labels,
                        datasets: $scope.datasetEvolutionTotalData
                    };

                    //FOR LINE EVOLUTION PROGRAM


                    $scope.datasetEvolutionProgramColors = [
                        "rgba(92,184,92,0.1)",
                        "rgba(224,214,83,0.1)",
                        "rgba(247,70,74,0.1)",
                        "rgba(70,191,189,0.1)",
                        "rgba(56,75,86,0.1)"
                    ];

                    $scope.datasetEvolutionProgramData = [];
                    for (var i=0; i<response.data.result.line_programs.datasets.length; i++) {
                        $scope.datasetEvolutionProgramData.push(
                            {label: response.data.result.line_programs.datasets[i].label,
                                backgroundColor:  $scope.datasetEvolutionProgramColors[i],
                                hoverBackgroundColor:  $scope.datasetEvolutionProgramColors[i],
                                data: response.data.result.line_programs.datasets[i].data}
                        );
                    }

                    $scope.dataEvolutionProgram = {
                        labels: $scope.dataEvolutionProgram.labels,
                        datasets: $scope.datasetEvolutionProgramData
                    };

                    //FOR BAR PROGRAM

                    $scope.datasetProgramColors = [
                        "#FF6384",
                        "#36A2EB",
                        "#FFCE56",
                        "#8BC34A",
                        "#FF5722"
                    ];

                    $scope.datasetProgramData = [];
                    for (var i=0; i<response.data.result.report_programs.datasets.length; i++) {
                        $scope.datasetProgramData.push(
                            {label: response.data.result.report_programs.datasets[i].label,
                                backgroundColor: $scope.datasetProgramColors[i],
                                hoverBackgroundColor: $scope.datasetProgramColors[i],
                                data: response.data.result.report_programs.datasets[i].data}
                        );
                    }

                    $scope.dataProgram = {
                        labels: $scope.dataProgram.labels,
                        datasets: $scope.datasetProgramData
                    };


                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });

        };

        $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

        $scope.dtInstanceRoadSafetySummaryProgram = {};
        $scope.dtOptionsRoadSafetySummaryProgram = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/customer/road-safety/summary-program',
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

        $scope.dtColumnsRoadSafetySummaryProgram = [
            DTColumnBuilder.newColumn('abbreviation')
                .withTitle("Ccde")
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

        $scope.dtInstanceRoadSafetySummaryIndicator = {};
        $scope.dtOptionsRoadSafetySummaryIndicator = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/customer/road-safety/summary-indicator',
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

        $scope.dtColumnsRoadSafetySummaryIndicator = [
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

        $scope.dtInstanceRoadSafetySummaryProgramCallback = function (instance) {
            $scope.dtInstanceRoadSafetySummaryProgram = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceRoadSafetySummaryProgram.reloadData();
        };


        $scope.dtInstanceRoadSafetySummaryIndicatorCallback = function (instance) {
            $scope.dtInstanceRoadSafetySummaryIndicator = instance;
        };

        $scope.reloadIndicatorData = function () {
            $scope.dtInstanceRoadSafetySummaryIndicator.reloadData();
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
                $scope.audit.roadSafetyId = $scope.currentId;
                $scope.audit.year = item.value;

                $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

                $scope.reloadData();
                $scope.reloadIndicatorData();
                loadReports();
            });
        };

        $scope.clearYear = function () {
            $timeout(function () {
                $scope.audit.operation = "info";
                $scope.audit.roadSafetyId = $scope.currentId;
                $scope.audit.year = 0;

                $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

                $scope.filter.selectedYear = null;
                $scope.reloadData();
                $scope.reloadIndicatorData();
                loadReports();
            });
        }

        $scope.onSummaryByProgramExportExcel = function () {
            jQuery("#downloadDocument")[0].src = "api/customer/road-safety/summary-program-export?data=" + Base64.encode(JSON.stringify($scope.audit));
            console.log($scope.audit);
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
            jQuery("#downloadDocument")[0].src = "api/customer/road-safety/summary-indicator-export?data=" + Base64.encode(JSON.stringify($scope.audit));
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