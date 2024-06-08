'use strict';
/**
 * controller for Customers
 */
app.controller('customerRoadSafetySummaryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ChartService) {


        var log = $log;
        var request = {};
      
        $scope.currentId = 0;

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
                customerId: $stateParams.customerId,
                customerRoadSafetyId: $scope.currentId
            };

            var entities = [           
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_doughnut_options', criteria: null}, 
                { name: 'customer_road_safety', criteria: $criteria }
            ];
    
            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.doughnut.options = response.data.data.chartLineOptions; 
                    $scope.chart.programs.data = response.data.data.customerRoadSafetyCycle;
                    $scope.chart.progress.data = response.data.data.customerRoadSafetyProgress;
                    $scope.chart.progress.total = response.data.data.customerRoadSafetyAverage;

                    $scope.currentId = response.data.data.customerRoadSafetyId;     
                    $scope.$parent.currentId = response.data.data.customerRoadSafetyId;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }        

        $scope.dtInstanceRoadSafetySummary = {};
        $scope.dtOptionsRoadSafetySummary = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customer_id = $stateParams.customerId;
                    d.road_safety_id = $scope.currentId;

                    return d;
                },
                url: 'api/customer/road-safety/summary',
                type: 'POST',
                beforeSend: function () {
                },
                complete: function (data) {
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
                loadRow();
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

        $scope.dtColumnsRoadSafetySummary = [
            DTColumnBuilder.newColumn('name')
                .withTitle("Módulo")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('description')
                .withTitle("Parámetro")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('items')
                .withTitle("Criterios")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('checked')
                .withTitle("Evaluados")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('advance')
                .withTitle("Valor Criterio (%)")
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

        $scope.dtInstanceRoadSafetySummaryCallback = function (intance) {
            $scope.dtInstanceRoadSafetySummary = intance;
        };

        var loadRow = function () {
        };

        $scope.reloadData = function () {
            $scope.dtInstanceRoadSafetySummary.reloadData();
        };


        //----------------------------------------------WEIGHTED
        $scope.dtInstanceRoadSafetySummaryWeighted = {};
        $scope.dtOptionsRoadSafetySummaryWeighted = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customer_id = $stateParams.customerId;
                    d.road_safety_id = $scope.currentId;

                    return d;
                },
                url: 'api/customer/road-safety/summary-weighted',
                type: 'POST',
                beforeSend: function () {
                },
                complete: function (data) {
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
                loadRow();
            })
            .withOption('footerCallback', function (tfoot, data) {
                var _this = this;
                if (data.length > 0) {
                    // Need to call $apply in order to call the next digest
                    $scope.sumTotal = 0;
                    $scope.sumWeightedValue = 0;

                    $scope.$apply(function () {
                        angular.forEach(data, function (row, key) {
                            $scope.sumTotal += parseFloat(row.result);
                            $scope.sumWeightedValue += parseFloat(row.weightedValue);
                        });
                    });
                }
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

        $scope.dtColumnsRoadSafetySummaryWeighted = [
            DTColumnBuilder.newColumn('name')
                .withTitle("Módulo")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('total')
                .withTitle("Valor Obtenido")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('weightedValue')
                .withTitle("Valoración Ponderado (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('result')
                .withTitle("Resultado")
                .withOption('width', 200),
        ];

        $scope.dtInstanceRoadSafetySummaryWeightedCallback = function (instance) {
            $scope.dtInstanceRoadSafetySummaryWeighted = instance;
        };

        $scope.reloadDataWeighted = function () {
            $scope.dtInstanceRoadSafetySummaryWeighted.reloadData();
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

        $scope.onSummaryExportPdf = function () {
            kendo.drawing.drawDOM($(".road-safety-export-pdf"))
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
                        fileName: "Seguridad-Vial-Auto-Evaluacion.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryExportExcel = function () {
            angular.element("#download")[0].src = "api/customer/road-safety/summary-export-excel?id=" + $scope.currentId;
        }

        $scope.onReportExportPdf = function () {
            angular.element("#download")[0].src = "api/customer/road-safety-item/export-pdf?id=" + $scope.currentId;
        }

    }]);