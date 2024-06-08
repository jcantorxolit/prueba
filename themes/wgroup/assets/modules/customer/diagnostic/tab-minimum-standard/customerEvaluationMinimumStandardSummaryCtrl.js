'use strict';
/**
 * controller for Customers
 */
app.controller('customerEvaluationMinimumStandardSummaryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ChartService) {

        var log = $log;
        
        $scope.currentId =  0;

        $scope.chart = {
            bar: { options: null },
            doughnut: { options: null },
            programs: { data: null },
            progress: { 
                data: null, 
                total: 0
            }
        };       

        function getCharts() {
            var $criteria = {
                customerId: $stateParams.customerId,
                evaluationMinimumStandardId: $scope.currentId
            };

            var entities = [           
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_doughnut_options', criteria: null}, 
                { name: 'customer_evaluation_minimum_standard', criteria: $criteria }
            ];
    
            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.doughnut.options = response.data.data.chartLineOptions; 
                    $scope.chart.programs.data = response.data.data.customerEvaluationMinimumStandardCycle;
                    $scope.chart.progress.data = response.data.data.customerEvaluationMinimumStandardProgress;
                    $scope.chart.progress.total = response.data.data.customerEvaluationMinimumStandardAverage;

                    $scope.currentId = response.data.data.customerEvaluationMinimumStandardId;
                    $scope.$parent.currentId = response.data.data.customerEvaluationMinimumStandardId;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }        
     
        $scope.dtInstanceEvaluationMinimumStandardSummary = {};
        $scope.dtOptionsEvaluationMinimumStandardSummary = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "standard";
                    d.customer_id = $stateParams.customerId;
                    d.standard_id = $scope.currentId;
                    return d;
                },
                url: 'api/customer/evaluation-minimum-standard/summary',
                type: 'POST',
                beforeSend: function () {
                },
                complete: function (data) {
                    getCharts();
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
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

        $scope.dtColumnsEvaluationMinimumStandardSummary = [
            DTColumnBuilder.newColumn('name')
                .withTitle("Ciclo")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('description')
                .withTitle("Estándar")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('items')
                .withTitle("Items")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('checked')
                .withTitle("Evaluados")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('advance')
                .withTitle("Valor Estándar (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('total')
                .withTitle("Valoración Items (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn(null)
                .withTitle("Estado")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Sin Iniciar';

                    if (parseInt(data.checked) == parseInt(data.items)) {
                        text = 'Completado';
                        label = 'label label-info';
                    }
                    else if (parseInt(data.checked) > 0) {
                        text = 'Iniciado';
                        label = 'label label-success';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
                .notSortable()
        ];


        $scope.dtInstanceEvaluationMinimumStandardSummaryCallback = function (instance) {
            $scope.dtInstanceEvaluationMinimumStandardSummary = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceEvaluationMinimumStandardSummary.reloadData();
        };

        $scope.onContinue = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", $scope.currentId);
            }
        };

        $scope.onConfig = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("config", "config", $scope.currentId);
            }
        };

        $scope.onViewReport = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("report", "report", $scope.currentId);
            }
        };

        $scope.onViewMonthlyReport = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("monthlyReport", "monthlyReport", $scope.currentId);
            }
        };

        $scope.onExportPdf = function () {
            kendo.drawing.drawDOM($(".minimun-standard-export-pdf"))
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
                        fileName: "Estandares-Minimos-Auto-Evaluacion.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onExportExcel = function () {
            jQuery("#download")[0].src = "api/customer/evaluation-minimum-standard/summary-export-excel?id=" + $scope.currentId;
        }

        $scope.onReportExportPdf = function () {
            jQuery("#download")[0].src = "api/customer/evaluation-minimum-standard-item/export-pdf?id=" + $scope.currentId;
        }

    }]);