'use strict';
/**
 * controller for Customers
 */
app.controller('customerManagementSummaryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ChartService) {


        var log = $log;

        $scope.chart = {
            bar: { options: null },
            doughnut: { options: null },
            programs: { data: null },
            progress: { 
                data: null, 
                total: 0
            }
        };

        getCharts();

        function getCharts() {
            var $criteria = {
                managementId: $scope.$parent.currentManagement
            };

            var entities = [            
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_doughnut_options', criteria: null},
                { name: 'customer_management', criteria: $criteria }
            ];
    
            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.doughnut.options = response.data.data.chartLineOptions;  
                    $scope.chart.programs.data = response.data.data.customerManagementProgram;
                    $scope.chart.progress.data = response.data.data.customerManagementProgress;        
                    $scope.chart.progress.total = response.data.data.customerManagementAverage;             
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $scope.dtInstanceManagementSummary = {};
		$scope.dtOptionsManagementSummary = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "management";
                    d.customer_id = $stateParams.customerId;
                    d.management_id = $scope.$parent.currentManagement;
                    return d;
                },
                url: 'api/management/summary',
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
                loadRow();
                //Pace.stop();

            })
            .withDOM('tr')
            .withOption('paging', false)
 
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsManagementSummary = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs continueRow lnk" href="#" uib-tooltip="Continuar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play-circle"></i></a> ';

                    if ($rootScope.can("programa_empresarial_continue")) {
                        actions += editTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('workplace')
                .withTitle("Centro de Trabajo")
                .withOption('width', 200),
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

                    if (parseInt(data.answers) == parseInt(data.questions))
                    {
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
            angular.element("a.continueRow").on("click", function () {
                var id = angular.element(this).data("id");
                if ($scope.$parent != null) {
                    $scope.$parent.navToSection("edit", "edit", $scope.$parent.currentManagement, id);
                }
            });
        };

        $scope.dtInstanceManagementSummaryCallback = function (instance) {
            $scope.dtInstanceManagementSummary = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceManagementSummary.reloadData();
        };


        $scope.onImport = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("import", "import", $scope.$parent.currentManagement);
            }
        };

        $scope.onAttachment = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("attachment", "attachment", $scope.$parent.currentManagement);
            }
        };
                
        $scope.onViewReport = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("report", "report", $scope.$parent.currentManagement);
            }
        };

        $scope.onViewReportMonthly = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("report_monthly", "report_monthly", $scope.$parent.currentManagement);
            }
        };

        $scope.onCancel = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        $scope.onExportPdf = function()
        {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.$parent.currentDiagnostic;
            kendo.drawing.drawDOM($(".management-export-pdf"))
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
                        fileName: "Programas_Empresariales_Resumen.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/management/summary/export-excel?id=" + $scope.$parent.currentManagement;
            //jQuery("#downloadDocument")[0].src = "api/management/infoReport/export-excel?management_id=" + $scope.$parent.currentManagement;
        }

    }]);