'use strict';
/**
 * controller for Customers
 */
app.controller('customerHealthDamageAnalysisCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter) {

        var log = $log;
        var request = {};
        log.info("loading..customerHealthDamageAnalysisCtrl ");

        $scope.request = {};
        $scope.audit = {};
        $scope.audit.operation = "info";
        $scope.audit.customerId = $stateParams.customerId;
        $scope.audit.year = 0;

        $scope.filter = {
            selectedYear: null
        };

        $scope.years = [];

        $scope.dataProgram = null;

        $scope.dataEvolutionProgram = null;

        $scope.dataEvolutionTotal = null;

        $scope.dataEvolutionAvg = null;

        $scope.optionsPie = {
            //Legened position

            legend: {position: 'bottom'},

            // Sets the chart to be responsive
            responsive: false,

            //Boolean - Whether we should show a stroke on each segment
            segmentShowStroke: true,

            //String - The colour of each segment stroke
            segmentStrokeColor: '#fff',

            //Number - The width of each segment stroke
            segmentStrokeWidth: 2,

            //Number - The percentage of the chart that we cut out of the middle
            percentageInnerCutout: 0, // This is 0 for Pie charts

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

        // Chart.js Options
        $scope.optionsBar = {
            //Legened position

            legend: {position: 'bottom'},

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

        var loadFilters = function () {
            var req = {};
            req.customer_id = $stateParams.customerId;
            req.year = $scope.audit.year;

            $http({
                method: 'POST',
                url: 'api/customer/health-damage/analysis/filter-year',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.years = response.data.data;

                if ($scope.years.length > 0) {
                    $scope.filter.selectedYear = $scope.years[0];
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
            req.year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0;

            $http({
                method: 'POST',
                url: 'api/customer/health-damage/analysis',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.dataBarLink = response.data.result.dataBarLink;
                    //$scope.dataPieWorkTIme = response.data.result.dataPieWorkTIme;
                    //$scope.dataBarWeekDay = response.data.result.dataBarWeekDay;
                    //$scope.dataBarPlace = response.data.result.dataBarPlace;
                    //$scope.dataBarLesionType = response.data.result.dataBarLesionType;
                    //$scope.dataBarBody = response.data.result.dataBarBody;
                    //$scope.dataBarFactor = response.data.result.dataBarFactor;

                    // CHART WORK TIME

                    if(response.data.result.dataPieWorkTIme.labels != null){


                        $scope.datasetPieWorkTImeData = [];
                        for (var j=0; j<response.data.result.dataPieWorkTIme.datasets.length; j++) {
                            $scope.datasetPieWorkTImeData.push(
                                {label: response.data.result.dataPieWorkTIme.datasets[j].label,
                                    backgroundColor: response.data.result.dataPieWorkTIme.datasets[j].pointColor,
                                    hoverBackgroundColor: response.data.result.dataPieWorkTIme.datasets[j].pointColor,
                                    data: response.data.result.dataPieWorkTIme.datasets[j].data}
                            );
                        }

                        $scope.dataPieWorkTIme = {
                            labels: response.data.result.dataPieWorkTIme.labels,
                            datasets: $scope.datasetPieWorkTImeData
                        };

                    }

                    // CHART WEEK DAY TIME

                    if(response.data.result.dataBarWeekDay.labels != null){


                        $scope.datasetBarWeekDayData = [];
                        for (var j=0; j<response.data.result.dataBarWeekDay.datasets.length; j++) {
                            $scope.datasetBarWeekDayData.push(
                                {label: response.data.result.dataBarWeekDay.datasets[j].label,
                                    backgroundColor: response.data.result.dataBarWeekDay.datasets[j].pointColor,
                                    hoverBackgroundColor: response.data.result.dataBarWeekDay.datasets[j].pointColor,
                                    data: response.data.result.dataBarWeekDay.datasets[j].data}
                            );
                        }

                        $scope.dataBarWeekDay = {
                            labels: response.data.result.dataBarWeekDay.labels,
                            datasets: $scope.datasetBarWeekDayData
                        };

                    }

                    // CHART PLACE

                    if(response.data.result.dataBarPlace.labels != null){

                        $scope.datasetBarPlaceData = [];
                        for (var j=0; j<response.data.result.dataBarPlace.datasets.length; j++) {
                            $scope.datasetBarPlaceData.push(
                                {label: response.data.result.dataBarPlace.datasets[j].label,
                                    backgroundColor: response.data.result.dataBarPlace.datasets[j].pointColor,
                                    hoverBackgroundColor: response.data.result.dataBarPlace.datasets[j].pointColor,
                                    data: response.data.result.dataBarPlace.datasets[j].data}
                            );
                        }

                        $scope.dataBarPlace = {
                            labels: response.data.result.dataBarPlace.labels,
                            datasets: $scope.datasetBarPlaceData
                        };

                    }


                    // CHART LESION TYPE

                    if(response.data.result.dataBarLesionType.labels != null){

                        $scope.datasetBarLesionTypeData = [];
                        for (var j=0; j<response.data.result.dataBarLesionType.datasets.length; j++) {
                            $scope.datasetBarLesionTypeData.push(
                                {label: response.data.result.dataBarLesionType.datasets[j].label,
                                    backgroundColor: response.data.result.dataBarLesionType.datasets[j].pointColor,
                                    hoverBackgroundColor: response.data.result.dataBarLesionType.datasets[j].pointColor,
                                    data: response.data.result.dataBarLesionType.datasets[j].data}
                            );
                        }

                        $scope.dataBarLesionType = {
                            labels: response.data.result.dataBarLesionType.labels,
                            datasets: $scope.datasetBarLesionTypeData
                        };

                    }

                    // CHART BODY

                    if(response.data.result.dataBarBody.labels != null){

                        $scope.datasetBarBodyData = [];
                        for (var j=0; j<response.data.result.dataBarBody.datasets.length; j++) {
                            $scope.datasetBarBodyData.push(
                                {label: response.data.result.dataBarBody.datasets[j].label,
                                    backgroundColor: response.data.result.dataBarBody.datasets[j].pointColor,
                                    hoverBackgroundColor: response.data.result.dataBarBody.datasets[j].pointColor,
                                    data: response.data.result.dataBarBody.datasets[j].data}
                            );
                        }

                        $scope.dataBarBody = {
                            labels: response.data.result.dataBarBody.labels,
                            datasets: $scope.datasetBarBodyData
                        };

                    }

                    //CHART FACTOR

                    if(response.data.result.dataBarFactor.labels != null){

                        $scope.datasetBarFactorData = [];
                        for (var j=0; j<response.data.result.dataBarFactor.datasets.length; j++) {
                            $scope.datasetBarFactorData.push(
                                {label: response.data.result.dataBarFactor.datasets[j].label,
                                    backgroundColor: response.data.result.dataBarFactor.datasets[j].pointColor,
                                    hoverBackgroundColor: response.data.result.dataBarFactor.datasets[j].pointColor,
                                    data: response.data.result.dataBarFactor.datasets[j].data}
                            );
                        }

                        $scope.dataBarFactor = {
                            labels: response.data.result.dataBarFactor.labels,
                            datasets: $scope.datasetBarFactorData
                        };

                    }

                    /*$.each($scope.dataProgram.datasets, function (k, v) {
                     // rgb.replace(/[^\d,]/g, '').split(',');
                     var cl = 'rgb(' + v.fillColor.r + ',' + v.fillColor.g + ',' + v.fillColor.b + ')';
                     v.fillColor = cl;
                     v.highlightFill = cl;
                     v.highlightStroke = cl;
                     });*/
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });

        };

        loadFilters();
        loadReports();

        $scope.changeYear = function (item, model) {
            $timeout(function () {
                loadReports();
            });
        };

        $scope.clearYear = function () {
            $timeout(function () {
                $scope.filter.selectedYear = null;
                loadReports();
            });
        }

        $scope.onSummaryByProgramExportExcel = function () {
            jQuery("#downloadDocument")[0].src = "api/occupational-report-incident/summary-lesion/export?data=" + Base64.encode(JSON.stringify($scope.audit));
        }

        $scope.onSummaryByProgramExportPdf = function () {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.$parent.currentDiagnostic;
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
                        fileName: "Reporte Mensual Programas Empresariales.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryByIndicatorExportExcel = function () {
            jQuery("#downloadDocument")[0].src = "api/management/summary-indicator/export?data=" + Base64.encode(JSON.stringify($scope.audit));
        }

        $scope.onSummaryByIndicatorExportPdf = function () {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.$parent.currentDiagnostic;
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
                        fileName: "Reporte Mensual Indicadores Programas Empresariales.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

    }]);