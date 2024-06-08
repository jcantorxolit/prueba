'use strict';
/**
 * controller for Customers
 */
app.controller('customerOccupationalReportIncidentAnalysisCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document', '$filter', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter, ChartService) {

        var log = $log;
        
        $scope.chart = {
            bar: { options: null },            
            doughnut: { options: null },

            accidentType: { data: null },
            deathCause: { data: null },
            location: { data: null },
            link: { data: null },
            workTime: { data: null },
            weekDay: { data: null },
            place: { data: null },
            injury: { data: null },
            body: { data: null },
            factor: { data: null },
            status: { data: null },
        };

        function getCharts() {
            var $criteria = {
                customerId: $stateParams.customerId,
                year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0
            };

            var entities = [            
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_doughnut_options', criteria: null},                
                {name: 'customer_occupational_report_incident', criteria: $criteria},                
            ];
    
            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.doughnut.options = response.data.data.chartLineOptions;
                    
                    $scope.chart.bar.options.legend.position = "bottom";

                    $scope.chart.accidentType.data = response.data.data.customerOccupationalReportAccidentType;  
                    $scope.chart.deathCause.data = response.data.data.customerOccupationalReportDeathCause;  
                    $scope.chart.location.data = response.data.data.customerOccupationalReportLocation;  
                    $scope.chart.link.data = response.data.data.customerOccupationalReportLink;  
                    $scope.chart.workTime.data = response.data.data.customerOccupationalReportWorkTime;  
                    $scope.chart.weekDay.data = response.data.data.customerOccupationalReportWeekDay;  
                    $scope.chart.place.data = response.data.data.customerOccupationalReportPlace;  
                    $scope.chart.injury.data = response.data.data.customerOccupationalReportInjury;  
                    $scope.chart.body.data = response.data.data.customerOccupationalReportBody;  
                    $scope.chart.factor.data = response.data.data.customerOccupationalReportFactor;  
                    $scope.chart.status.data = response.data.data.customerOccupationalReportStatus;  
                      
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }        
        

        $scope.request = {};
        $scope.audit = {};
        $scope.audit.operation = "info";
        $scope.audit.customerId = $stateParams.customerId;
        $scope.audit.year = 0;

        $scope.filter = {
            selectedYear:null
        };

        $scope.years = [];


        var loadFilters = function() {
            var req = {};
            req.customer_id = $stateParams.customerId;
            req.year = $scope.audit.year;

            $http({
                method: 'POST',
                url: 'api/occupational-report-incident/monthly-filter',
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
                    getCharts();
                }
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        };      

        loadFilters();        

        $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

        $scope.dtInstanceReportIncidentSummaryProgram = {};
		$scope.dtOptionsReportIncidentSummaryProgram = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/occupational-report-incident/summary-lesion',
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

        $scope.dtColumnsReportIncidentSummaryProgram = [

            DTColumnBuilder.newColumn('name')
                .withTitle("Lesión")
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

        $scope.dtInstanceReportIncidentSummaryIndicator = {};
		$scope.dtOptionsReportIncidentSummaryIndicator = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/occupational-report-incident/summary-indicator',
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

        $scope.dtColumnsReportIncidentSummaryIndicator = [
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

        $scope.dtInstanceReportIncidentSummaryProgramCallback = function (instance) {
            $scope.dtInstanceReportIncidentSummaryProgram = instance;
        };

        $scope.reloadData = function () {
            //$scope.dtInstanceReportIncidentSummaryProgram.reloadData();
        };

        $scope.dtInstanceReportIncidentSummaryIndicatorCallback = function (instance) {
            $scope.dtInstanceReportIncidentSummaryIndicator = instance;
        };

        $scope.reloadIndicatorData = function () {
            //$scope.dtInstanceReportIncidentSummaryIndicator.reloadData();
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", 0);
            }
        };

        $scope.changeYear = function (item, model) {
            $timeout(function () {
                $scope.audit.operation = "info";
                $scope.audit.customerId = $stateParams.customerId;
                $scope.audit.year = item.value;

                $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

                $scope.reloadData();
                $scope.reloadIndicatorData();
                getCharts();
            });
        };

        $scope.clearYear = function()
        {
            $timeout(function () {
                $scope.audit.operation = "info";
                $scope.audit.customerId = $stateParams.customerId;
                $scope.audit.year = 0;

                $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

                $scope.filter.selectedYear = null;
                $scope.reloadData();
                $scope.reloadIndicatorData();
                getCharts();
            });
        }

        $scope.onSummaryByProgramExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/occupational-report-incident/summary-lesion/export?data=" + Base64.encode(JSON.stringify($scope.audit));
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
                        fileName: "Reporte Mensual Programas Empresariales.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryByIndicatorExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/management/summary-indicator/export?data=" + Base64.encode(JSON.stringify($scope.audit));
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
                        fileName: "Reporte Mensual Indicadores Programas Empresariales.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

    }]);