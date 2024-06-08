'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticReportMonthlyCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert) {


        var log = $log;
        var request = {};
        log.info("loading..customerDiagnosticReportMonthlyCtrl ");

        $scope.request = {};
        $scope.audit = {};
        $scope.audit.operation = "info";
        $scope.audit.diagnosticId = $scope.$parent.currentDiagnostic;
        $scope.audit.year = 0;

        $scope.filter = {
            selectedYear:null
        };

        $scope.years = [];


        var getRandomColor = function () {
            var color = randomColor({
                /*luminosity: 'bright',*/
                luminosity: 'bright', // bright, light or dark.
                hue: 'orange',  // red, orange, yellow, green, blue, purple, pink, monochrome
                format: 'rgb' // e.g. 'rgb(225,200,20)'
            });
            log.info(color);
            return color;
        };


        // Propiedades de la grafica de programas

        var colorprg1 = getRandomColor(); // 0.5, 0.8, 0.75, 1
        var colorprg2 = getRandomColor();
        var colorprg3 = getRandomColor();
        var colorprg4 = getRandomColor();
        var colorprg5 = getRandomColor();
        var colorprg6 = getRandomColor();


        $scope.dataProgram = null;

        $scope.dataEvolutionProgram = null;

        $scope.dataEvolutionTotal = null;

        $scope.dataEvolutionAvg = null;

        $scope.optionsLine = {

            //Legened position

            legend: {position: 'right'},

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
            onAnimationProgress: function () { },

            // Function - on animation complete
            onAnimationComplete: function () { },

            //String - A legend template
            legendTemplate: '<ul class="tc-chart-js-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].strokeColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>'
        };

        // Chart.js Options
        $scope.optionsBar = {

            //Legened position

            legend: {position: 'right'},

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

        var loadFilters = function() {
            var req = {};
            req.customer_id = $stateParams.customerId;
            req.diagnostic_id = $scope.$parent.currentDiagnostic;
            req.year = $scope.audit.year;

            $http({
                method: 'POST',
                url: 'api/diagnostic/report-monthly-filter',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.years = response.data.data;

                if ($scope.years.length > 0) {
                    $scope.filter.selectedYear = $scope.years[0];
                    $scope.audit.year = $scope.filter.selectedYear.value;
                    $scope.request.data = Base64.encode(JSON.stringify($scope.audit));
                    $scope.reloadData();
                    $scope.reloadIndicatorData();
                    loadReports();
                }
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        };

        var loadReports = function () {
            var req = {};
            req.customer_id = $stateParams.customerId;
            req.diagnostic_id = $scope.$parent.currentDiagnostic;
            req.year = $scope.audit.year;

            $http({
                method: 'GET',
                url: 'api/diagnostic/reportMonthly',
                params: req
            }).then(function (response) {

                $timeout(function () {
                    $scope.dataProgram = response.data.result.report_programs;
                    $scope.dataEvolutionProgram = response.data.result.line_programs;
                    $scope.dataEvolutionTotal = response.data.result.line_total;
                    $scope.dataEvolutionAvg = response.data.result.line_avg;

                    $scope.datasetChartEvolutionAvgLabels = [];
                    for (var i=0; i<response.data.result.line_avg.labels.length; i++) {
                        $scope.datasetChartEvolutionAvgLabels.push(response.data.result.line_avg.labels[i])
                    }


                    $scope.datasetChartEvolutionAvgLabels = [];
                    for (var i=0; i<response.data.result.line_avg.labels.length; i++) {
                        $scope.datasetChartEvolutionAvgLabels.push(response.data.result.line_avg.labels[i])
                    }


                    //FOR LINE AVG CHART

                    $scope.datasetEvolutionAvgData = [];
                    for (var i=0; i<response.data.result.line_avg.datasets[0].data.length; i++) {
                        $scope.datasetEvolutionAvgData.push(response.data.result.line_avg.datasets[0].data[i]);
                    }

                    $scope.dataEvolutionAvg = {
                        labels: $scope.datasetChartEvolutionAvgLabels,
                        datasets: [{
                            label: 'Promedio Total % (calificación)',
                            borderColor: "rgba(218,79,74,1)",
                            backgroundColor: "rgba(218,79,74,1)",
                            data: $scope.datasetEvolutionAvgData
                        }]
                    };

                    //FOR LINE TOTAL CHART

                    $scope.datasetEvolutionTotalData = [];
                    for (var i=0; i<response.data.result.line_total.datasets[0].data.length; i++) {
                        $scope.datasetEvolutionTotalData.push(response.data.result.line_total.datasets[0].data[i]);
                    }

                    $scope.dataEvolutionTotal = {
                        labels: $scope.datasetChartEvolutionAvgLabels,
                        datasets: [{
                            label: 'Avance % (respuestas / preguntas)',
                            borderColor: "rgba(51,149,255,1)",
                            backgroundColor: "rgba(51,149,255,1)",
                            data: $scope.datasetEvolutionTotalData
                        }]
                    };


                    //FOR LINE PROGRAMS CHART

                    $scope.datasetEvolutionProgramsColors = [
                        "rgba(255,99,132,0.2)",
                        "rgba(54,162,235,0.2)",
                        "rgba(255,206,86,0.2)",
                        "rgba(139,195,74,0.2)",
                        "rgba(255,87,34,0.2)"
                    ];


                    $scope.datasetEvolutionProgramsData = [];
                    for (var i=0; i<response.data.result.line_programs.datasets.length; i++) {
                        $scope.datasetEvolutionProgramsData.push(
                            {label: response.data.result.line_programs.datasets[i].label,
                                backgroundColor: $scope.datasetEvolutionProgramsColors[i],
                                hoverBackgroundColor: $scope.datasetEvolutionProgramsColors[i],
                                data: response.data.result.line_programs.datasets[i].data}
                        );
                    }

                    $scope.dataEvolutionProgram = {
                        labels: $scope.datasetChartEvolutionAvgLabels,
                        datasets: $scope.datasetEvolutionProgramsData
                    };

                    //FOR LINE REPORT CHART
                    console.log(response.data.result.report_programs);


                    $scope.datasetEvolutionReportData = [];
                    $scope.datasetEvolutionReportColors = [
                        "#FF6384",
                        "#36A2EB",
                        "#FFCE56",
                        "#8BC34A",
                        "#FF5722"
                    ];

                    for (var i=0; i<response.data.result.report_programs.datasets.length; i++) {
                        $scope.datasetEvolutionReportData.push(
                            {label: response.data.result.report_programs.datasets[i].label,
                                backgroundColor: $scope.datasetEvolutionReportColors[i] ,
                                hoverBackgroundColor: $scope.datasetEvolutionReportColors[i],
                                data: response.data.result.report_programs.datasets[i].data}
                        );
                    }

                    $scope.dataProgram = {
                        labels: $scope.datasetChartEvolutionAvgLabels,
                        datasets: $scope.datasetEvolutionReportData
                    };


                    $.each($scope.dataProgram.datasets, function (k, v) {
                        // rgb.replace(/[^\d,]/g, '').split(',');
                        var cl = 'rgb(' + v.fillColor.r + ',' + v.fillColor.g + ',' + v.fillColor.b + ')';
                        v.fillColor = cl;
                        v.highlightFill = cl;
                        v.highlightStroke = cl;
                    });
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });

        };

        loadFilters();
        loadReports();

        $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

        $scope.dtInstanceDiagnosticSummaryProgram = {};
		$scope.dtOptionsDiagnosticSummaryProgram = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/diagnostic/summary-program',
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

        $scope.dtColumnsDiagnosticSummaryProgram = [
            DTColumnBuilder.newColumn('abbreviation')
                .withTitle("Ccde")
                .withOption('width', 100),
            DTColumnBuilder.newColumn('name')
                .withTitle("Programa")
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

        $scope.dtInstanceDiagnosticSummaryIndicator = {};
		$scope.dtOptionsDiagnosticSummaryIndicator = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/diagnostic/summary-indicator',
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

        $scope.dtColumnsDiagnosticSummaryIndicator = [
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

        var convertToInt = function(value) {
            return value != null ? parseInt(value) : "";
        }

        $scope.reloadData = function () {
            $scope.dtInstanceDiagnosticSummaryProgram.reloadData();
        };

        $scope.reloadIndicatorData = function () {
            $scope.dtInstanceDiagnosticSummaryIndicator.reloadData();
        };


        $scope.editDiagnosticSummary = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("form", "edit", id);
            }
        };

        $scope.editDiagnosticReport = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", $scope.$parent.currentDiagnostic);
            }
        };

        $scope.editDiagnosticReportMonthly = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("report_monthly", "report_monthly", $scope.$parent.currentDiagnostic);
            }
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.$parent.currentDiagnostic);
            }
        };

        $scope.editDiagnostic = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("report", "report", $scope.$parent.currentDiagnostic);
            }
        };



        $scope.changeYear = function (item, model) {
            $timeout(function () {
                $scope.audit.operation = "info";
                $scope.audit.diagnosticId = $scope.$parent.currentDiagnostic;
                $scope.audit.year = item.value;

                $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

                $scope.reloadData();
                $scope.reloadIndicatorData();
                loadReports();
            });
        };

        $scope.clearYear = function()
        {
            $timeout(function () {
                $scope.audit.operation = "info";
                $scope.audit.diagnosticId = $scope.$parent.currentDiagnostic;
                $scope.audit.year = 0;

                $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

                $scope.filter.selectedYear = null;
                $scope.reloadData();
                $scope.reloadIndicatorData();
                loadReports();
            });
        }

        $scope.onSummaryByProgramExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/diagnostic/summary-program/export?data=" + Base64.encode(JSON.stringify($scope.audit));
        }

        $scope.onSummaryByProgramExportPdf = function()
        {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.$parent.currentDiagnostic;
            kendo.drawing.drawDOM($(".export-pdf-program"))
                .then(function(group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: { left: "1cm", top: "1cm", right: "1cm", bottom: "1cm" }
                    });
                })
                .done(function(data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Diagnóstico Reporte Mensual Program.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryByIndicatorExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/diagnostic/summary-indicator/export?data=" + Base64.encode(JSON.stringify($scope.audit));
        }

        $scope.onSummaryByIndicatorExportPdf = function()
        {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.$parent.currentDiagnostic;
            kendo.drawing.drawDOM($(".export-pdf-indicator"))
                .then(function(group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: { left: "1cm", top: "1cm", right: "1cm", bottom: "1cm" }
                    });
                })
                .done(function(data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Diagnóstico Reporte Mensual Indicadores.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

    }]);