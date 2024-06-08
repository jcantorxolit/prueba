'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticReportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert) {


        var log = $log;
        var request = {};
        $scope.question = {
            rate: {
                id: "0",
                text: "-- Seleccionar --",
                value: "-S-",
                code: "0"
            }
        };
        log.info("loading..customerDiagnosticReportCtrl ");

        // $rootScope.tabname = "tracking";

        // default view
        // $rootScope.tracking_section = "list";

        // Datatable configuration
        request.operation = "diagnostic";
        request.customer_id = $stateParams.customerId;
        request.diagnostic_id = $scope.$parent.currentDiagnostic;

        $scope.rate_id = 0;
        $scope.program_id = 0;

        $scope.categories = [];

        $scope.rates = $rootScope.rates();

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


        $scope.data_prg = {
            labels: [],
            datasets: []
        };


        // Chart.js Options
        $scope.options_prg = {

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


        // Propiedades de la grafica de avance
        $scope.data_sg = [];

        // Chart.js Options
        $scope.options_sg = {

            // Sets the chart to be responsive
            responsive: true,

            //Boolean - Whether we should show a stroke on each segment
            segmentShowStroke: true,

            //String - The colour of each segment stroke
            segmentStrokeColor: '#fff',

            //Number - The width of each segment stroke
            segmentStrokeWidth: 2,

            //Number - The percentage of the chart that we cut out of the middle
            percentageInnerCutout: 50, // This is 0 for Pie charts

            //Number - Amount of animation steps
            animationSteps: 100,

            //String - Animation easing effect
            animationEasing: 'easeOutBounce',

            //Boolean - Whether we animate the rotation of the Doughnut
            animateRotate: true,

            //Boolean - Whether we animate scaling the Doughnut from the centre
            animateScale: false,

            //String - A legend template
            legendTemplate: '<ul class="tc-chart-js-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>'

        };
        $scope.totalAvg = 0;

        $scope.changeRate = function (item, model, question) {
            log.info(item);
            log.info(model);
            $timeout(function () {
                $scope.rate_id = item.id;
                loadData();
            });
        };

        var loadReports = function () {

            var req = {};
            req.customer_id = $stateParams.customerId;
            req.diagnostic_id = $scope.$parent.currentDiagnostic;

            $http({
                method: 'GET',
                url: 'api/diagnostic/report',
                params: req
            }).then(function (response) {

                $timeout(function () {
                    $scope.data_prg = response.data.result.report_programs;
                    $scope.totalAvg = response.data.result.totalAvg;
                    $.each($scope.data_prg.datasets, function (k, v) {
                        // rgb.replace(/[^\d,]/g, '').split(',');
                        var cl = 'rgb(' + v.fillColor.r + ',' + v.fillColor.g + ',' + v.fillColor.b + ')';
                        v.fillColor = cl;
                        v.highlightFill = cl;
                        v.highlightStroke = cl;
                    });

                    $scope.data_sg = response.data.result.report_advances;
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });

        };

        loadReports();


        var loadData = function () {

            log.info("Intentamos cargar la información del programa_id: ");
            var req = {};
            req.diagnostic_id = $scope.$parent.currentDiagnostic;
            req.program_id = $scope.program_id;
            req.rate_id = $scope.rate_id;

            $http({
                method: 'GET',
                url: 'api/diagnostic/infoReport',
                params: req
            }).then(function (response) {
                console.log(response);

                // Actualiza categorias
                updateInfoPrograms(response.data.data.programs);

                $scope.loaded = true;

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Consultando Preguntas", "Se ha presentado un error durante la consulta del cuestionario, por favor intentelo de nuevo.", "error");
            }).finally(function () {
                loadReports();
            });

        };


        loadData();

        var updateInfoPrograms = function (data) {
            if (data == null || data.length == 0) {
                return false;
            }

            $scope.categories = data;

        };

        var updateInfoGralDiagnostic = function (data, reloadAll) {
            if (data == null || data.length == 0) {
                return false;
            }

            $scope.dashboardDiagnostic = data[0];
            $scope.averageTotal = $scope.dashboardDiagnostic.average;
        };

        $scope.editDiagnostic = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", $scope.$parent.currentDiagnostic);
            }
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.$parent.currentDiagnostic);
            }
        };

        $scope.clearFilter = function()
        {
            $scope.question = {
                rate: {
                    id: "0",
                    text: "-- Seleccionar --",
                    value: "-S-",
                    code: "0"
                }
            };
            $scope.rate_id = 0;
            loadData();
        }

        $scope.onSummaryExportPdf = function()
        {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.$parent.currentDiagnostic;
            kendo.drawing.drawDOM($("#export-pdf"))
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
                        fileName: "DiagnosticoReporte.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/diagnostic/infoReport/export-excel?diagnostic_id=" + $scope.$parent.currentDiagnostic +"&program_id=" + $scope.program_id + "&rate_id=" + $scope.rate_id;
        }

    }]);