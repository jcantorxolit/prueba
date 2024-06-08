'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert) {


        var log = $log;
        var request = {};

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";


        $scope.filter = {};
        $scope.filter.selectedEconomicGroup = null;
        $scope.filter.selectedEconomicGroupCustomer = null;

        $scope.filter.selectedCustomer = null;
        $scope.filter.selectedCustomerContractor = null;

        $scope.economicGroupList = [];
        $scope.economicGroupCustomerList = [];

        $scope.customerList = [];
        $scope.customerContractorList = [];


        // Datatable configuration
        request.operation = "diagnostic";

        $scope.data_prg = {
            labels: [],
            datasets: []
        };

        $scope.data_prg_contractor = {
            labels: [],
            datasets: []
        };

        $scope.data_prg_monthly = {
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
        $scope.data_sg_contractor = [];

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


        var loadList = function () {

            var req = {};
            req.operation = "diagnostic";

            return $http({
                method: 'POST',
                url: 'api/diagnostic/economic-group',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.economicGroupList = response.data.data.economicGroup;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el centro de trabajo por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        var loadListContracting = function () {

            var req = {};
            req.operation = "diagnostic";

            return $http({
                method: 'POST',
                url: 'api/diagnostic/contracting',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.customerList = response.data.data.economicGroup;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el centro de trabajo por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        var loadEconomicGroupCustomers = function () {
            var req = {};
            req.operation = "diagnostic";
            req.parentId = $scope.filter.selectedEconomicGroup.value;
            ;

            return $http({
                method: 'POST',
                url: 'api/diagnostic/economic-group-customer',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.economicGroupCustomerList = response.data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el centro de trabajo por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        }

        var loadContractingCustomers = function () {
            var req = {};
            req.operation = "diagnostic";
            req.parentId = $scope.filter.selectedCustomer.value;
            ;

            return $http({
                method: 'POST',
                url: 'api/diagnostic/contracting-customer',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.customerContractorList = response.data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el centro de trabajo por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        }

        loadList();

        loadListContracting();

        var loadReport = function () {

            var req = {};
            req.operation = "diagnostic";
            req.parent_id = $scope.filter.selectedEconomicGroup.value;

            return $http({
                method: 'POST',
                url: 'api/diagnostic/report-economic-group',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
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
                SweetAlert.swal("Error de guardado", "Error guardando el centro de trabajo por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        var loadReportContracting = function () {

            var req = {};
            req.operation = "diagnostic";
            req.parent_id = $scope.filter.selectedCustomer.value;

            return $http({
                method: 'POST',
                url: 'api/diagnostic/report-contracting',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.data_prg_contractor = response.data.result.report_programs;
                    $scope.totalAvg_contractor = response.data.result.totalAvg;
                    $.each($scope.data_prg_contractor.datasets, function (k, v) {
                        // rgb.replace(/[^\d,]/g, '').split(',');
                        var cl = 'rgb(' + v.fillColor.r + ',' + v.fillColor.g + ',' + v.fillColor.b + ')';
                        v.fillColor = cl;
                        v.highlightFill = cl;
                        v.highlightStroke = cl;
                    });

                    $scope.data_sg_contractor = response.data.result.report_advances;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el centro de trabajo por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        var loadReports = function () {

            var req = {};
            req.diagnostic_id = $scope.filter.selectedEconomicGroupCustomer.diagnosticId;

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

        var loadReportsContracting = function () {

            var req = {};
            req.diagnostic_id = $scope.filter.selectedCustomerContractor.diagnosticId;

            $http({
                method: 'GET',
                url: 'api/diagnostic/report',
                params: req
            }).then(function (response) {

                $timeout(function () {
                    $scope.data_prg_contractor = response.data.result.report_programs;
                    $scope.totalAvg_contractor = response.data.result.totalAvg;
                    $.each($scope.data_prg_contractor.datasets, function (k, v) {
                        // rgb.replace(/[^\d,]/g, '').split(',');
                        var cl = 'rgb(' + v.fillColor.r + ',' + v.fillColor.g + ',' + v.fillColor.b + ')';
                        v.fillColor = cl;
                        v.highlightFill = cl;
                        v.highlightStroke = cl;
                    });

                    $scope.data_sg_contractor = response.data.result.report_advances;
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        };

        $scope.dtInstanceDiagnosticSummary = {};
        $scope.dtOptionsDiagnosticSummary = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function () {

                    if ($scope.filter.selectedEconomicGroup != null && $scope.filter.selectedEconomicGroupCustomer != null) {
                        request.operation = 'customer';
                        request.parentId = $scope.filter.selectedEconomicGroupCustomer.diagnosticId;
                    } else if ($scope.filter.selectedEconomicGroup != null && $scope.filter.selectedEconomicGroupCustomer == null) {
                        request.operation = 'economic-group';
                        request.parentId = $scope.filter.selectedEconomicGroup.value
                    } else {
                        request.operation = '';
                        request.parentId = ''
                    }

                    return request
                },
                url: 'api/diagnostic/economic-group-summary',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (response) {

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
            .withDOM('tr')
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })


            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsDiagnosticSummary = [
            DTColumnBuilder.newColumn('abbreviation')
                .withTitle("Código")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('name')
                .withTitle("Programa")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('questions')
                .withTitle("Preguntas")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('answers')
                .withTitle("Respuestas")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('advance')
                .withTitle("Avance (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('average')
                .withTitle("Promedio Total (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn(null)
                .withTitle("Estado")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Sin Iniciar';

                    if (parseInt(data.answers) == parseInt(data.questions)) {
                        text = 'Completado';
                        label = 'label label-info';
                    }
                    else if (parseInt(data.answers) > 0) {
                        text = 'Iniciado';
                        label = 'label label-success';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
                .notSortable()
        ];

        $scope.dtInstanceDiagnosticSummaryContractor = {};
        $scope.dtOptionsDiagnosticSummaryContractor = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function () {

                    if ($scope.filter.selectedCustomer != null && $scope.filter.selectedCustomerContractor != null) {
                        request.operation = 'customer';
                        request.parentId = $scope.filter.selectedCustomerContractor.diagnosticId;
                    } else if ($scope.filter.selectedCustomer != null && $scope.filter.selectedCustomerContractor == null) {
                        request.operation = 'economic-group';
                        request.parentId = $scope.filter.selectedCustomer.value
                    } else {
                        request.operation = '';
                        request.parentId = ''
                    }

                    return request
                },
                url: 'api/diagnostic/contracting-summary',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (response) {

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
            .withDOM('tr')
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })


            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsDiagnosticSummaryContractor = [
            DTColumnBuilder.newColumn('abbreviation')
                .withTitle("Código")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('name')
                .withTitle("Programa")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('questions')
                .withTitle("Preguntas")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('answers')
                .withTitle("Respuestas")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('advance')
                .withTitle("Avance (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('average')
                .withTitle("Promedio Total (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn(null)
                .withTitle("Estado")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Sin Iniciar';

                    if (parseInt(data.answers) == parseInt(data.questions)) {
                        text = 'Completado';
                        label = 'label label-info';
                    }
                    else if (parseInt(data.answers) > 0) {
                        text = 'Iniciado';
                        label = 'label label-success';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
                .notSortable()
        ];

        var loadRow = function () {


        };

        $scope.dtInstanceDiagnosticSummaryCallback = function (instance) {
            $scope.dtInstanceDiagnosticSummary = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceDiagnosticSummary.reloadData();
        };

        $scope.dtInstanceDiagnosticSummaryContractorCallback = function (instance) {
            $scope.dtInstanceDiagnosticSummaryContractor = instance;
        };

        $scope.reloadDataContractor = function () {
            $scope.dtInstanceDiagnosticSummaryContractor.reloadData();
        };

        //-----------------------------------------------------------EVENTS ECONOMIC GROUP
        $scope.changeEconomicGroup = function (item, model) {
            $timeout(function () {
                $scope.filter.selectedEconomicGroupCustomer = null;
                loadEconomicGroupCustomers();
                loadReport();
                $scope.reloadData();
            });
        };

        $scope.clearEconomicGroup = function () {
            $timeout(function () {
                $scope.filter.selectedEconomicGroup = null;
                $scope.filter.selectedEconomicGroupCustomer = null;
                $scope.data_prg = {
                    labels: [],
                    datasets: []
                };

                $scope.data_prg_monthly = {
                    labels: [],
                    datasets: []
                };
                $scope.reloadData();
            });
        }


        $scope.changeEconomicGroupCustomer = function (item, model) {
            $timeout(function () {
                $scope.reloadData();
                loadReports();
            });
        };

        $scope.clearEconomicGroupCustomer = function () {
            $timeout(function () {

                $scope.filter.selectedEconomicGroupCustomer = null;
                if ($scope.filter.selectedEconomicGroup != null) {
                    loadReport();
                } else {
                    $scope.data_prg = {
                        labels: [],
                        datasets: []
                    };

                    $scope.data_prg_monthly = {
                        labels: [],
                        datasets: []
                    };
                }

                $scope.reloadData();
            });
        }


        //-----------------------------------------------------------EVENTS CONTRACTING
        $scope.changeCustomer = function (item, model) {
            $timeout(function () {
                $scope.filter.selectedCustomerContractor = null;
                loadContractingCustomers();
                loadReportContracting();
                $scope.reloadDataContractor();
            });
        };

        $scope.clearCustomer = function () {
            $timeout(function () {
                $scope.filter.selectedCustomer = null;
                $scope.filter.selectedCustomerContractor = null;
                $scope.data_prg_contractor = {
                    labels: [],
                    datasets: []
                };

                $scope.reloadDataContractor();
            });
        }


        $scope.changeCustomerContractor = function (item, model) {
            $timeout(function () {
                $scope.reloadDataContractor();
                loadReportsContracting();
            });
        };

        $scope.clearCustomerContractor = function () {
            $timeout(function () {

                $scope.filter.selectedCustomerContractor = null;
                if ($scope.filter.selectedCustomer != null) {
                    loadReportContracting();
                } else {
                    $scope.data_prg_contractor = {
                        labels: [],
                        datasets: []
                    };
                }

                $scope.reloadDataContractor();
            });
        }


        //------------------------------------------------------------EXPORT

        $scope.onSummaryExportPdf = function () {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.$parent.currentDiagnostic;
            kendo.drawing.drawDOM($(".export-pdf"))
                .then(function (group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: {left: "1cm", top: "1cm", right: "1cm", bottom: "1cm"}
                    });
                })
                .done(function (response) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Diagnóstico.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryExportExcel = function () {
            jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-excel?id=" + $scope.$parent.currentDiagnostic;
        }

    }]);