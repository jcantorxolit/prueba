'use strict';
/**
 * controller for Customers
 */
app.controller('customerAbsenteeismDisabilityAnalysisCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter', '$aside',
    'ListService', 'ChartService',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService, ChartService) {

        var log = $log;

        $scope.filter = {
            canCompare: false,
            selectedCause: null,
            selectedYear: null,
            compareYearList: [],
            workplace: null
        };

        $scope.compareYearList = [];
        $scope.workplaceList = [];
        $scope.years = [];

        $scope.chart = {
            line: { options: null },
            months: { data: null },
        };

        function getCharts() {

            var $compareYearList = $scope.filter.compareYearList.filter(function(year) {
                return year != null && year.value != null && $scope.filter.canCompare;
            }).map(function(year, index, array) {
                return year.value.value;
            });

            if ($compareYearList === undefined || $compareYearList === null) {
                $compareYearList = [];
            }

            $compareYearList.push($scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0);

            var $criteria = {
                customerId: $stateParams.customerId,
                cause: $scope.filter.selectedCause,
                workplaceId: $scope.filter.workplace ? $scope.filter.workplace.id : null,
                yearList: $compareYearList
            };

            var entities = [
                { name: 'chart_line_options', criteria: null },
                //{ name: 'customer_absenteeism_disability', criteria: $criteria }
                { name: 'customer_absenteeism_disability_general_event', criteria: $criteria }
            ];

            ChartService.getDataChart(entities)
                .then(function(response) {
                    $scope.chart.line.options = response.data.data.chartLineOptions;
                    //DB->20190413: Replace customerAbsenteeismDisabilitySummary chart with customerAbsenteeismDisabilityGeneralEvent
                    //$scope.chart.months.data = response.data.data.customerAbsenteeismDisabilitySummary;
                    $scope.chart.months.data = response.data.data.customerAbsenteeismDisabilityGeneralEvent;
                }, function(error) {

                });
        }

        function getList() {
            var entities = [
                { name: 'absenteeism_disability_causes_only' },
                { name: 'current_year' },
                { name: 'absenteeism_disability_years', value: $stateParams.customerId },
            ];

            ListService.getDataList(entities)
                .then(function(response) {
                    $scope.causes = response.data.data.absenteeism_disability_causes_only;
                    $scope.years = response.data.data.absenteeism_disability_causes_years;
                    var $currentYear = response.data.data.currentYear;

                    var $result = $filter('filter')($scope.years, { value: $currentYear });

                    $scope.filter.selectedYear = $result.length ? $result[0] : null;

                    fillCompareYearList();
                    getCharts();
                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();


        function getWorkplaces() {
            var entities = [{
                name: 'absenteeism_disability_customer_workplace', criteria: {
                    customerId: $stateParams.customerId,
                    cause: $scope.filter.selectedCause.value,
                    years: getAllYears()
                }
            }];

            ListService.getDataList(entities)
                .then(function(response) {
                    $scope.workplaceList = response.data.data.workplaceList;
                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $scope.dtOptionsCustomerDisabilityAnalysis = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    d.cause = $scope.filter.selectedCause ? $scope.filter.selectedCause.value : '';
                    d.workplaceId = $scope.filter.workplace ? $scope.filter.workplace.id : '';
                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-disability-general',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function() {
                    // Aqui inicia el loader indicator
                },
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function() {

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })

        .withPaginationType('full_numbers')
            .withOption('createdRow', function(row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });

        $scope.dtColumnsCustomerDisabilityAnalysis = [
            DTColumnBuilder.newColumn('year').withTitle("Año").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('jan').withTitle("ENE").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('feb').withTitle("FEB").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('mar').withTitle("MAR").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('apr').withTitle("ABR").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('may').withTitle("MAY").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('jun').withTitle("JUN").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('jul').withTitle("JUL").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('aug').withTitle("AGO").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('sep').withTitle("SEP").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('oct').withTitle("OCT").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('nov').withTitle("NOV").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('dec').withTitle("DIC").withOption('defaultContent', '')
        ];

        $scope.dtInstanceCustomerDisabilityAnalysisCauseCallback = function(instance) {
            $scope.dtInstanceCustomerDisabilityAnalysisCause = instance;
        };

        $scope.reloadData = function(instance) {
            $scope.dtInstanceCustomerDisabilityAnalysisCause.reloadData();
        };

        $scope.onSelectCause = function() {
            $timeout(function() {
                getWorkplaces();
                getCharts();
                $scope.reloadData();
            });
        };

        $scope.onClearCause = function() {
            $timeout(function() {
                $scope.filter.selectedCause = null;
                getCharts();
                $scope.reloadData();
            });
        };

        $scope.onClearWorkplace = function() {
            $timeout(function() {
                $scope.filter.workplace = null;
                getCharts();
                $scope.reloadData();
            });
        };

        $scope.onSelectYear = function() {
            fillCompareYearList();
            getWorkplaces();
            getCharts();
            $scope.reloadData();
        };

        $scope.onChangeCompare = function() {
            getWorkplaces();
            getCharts();
        }

        $scope.onAddCompareYear = function() {
            $scope.filter.compareYearList.push({});
        }

        $scope.onRemoveCompareYear = function(index) {
            $scope.filter.compareYearList.splice(index, 1);
            getWorkplaces();
            getCharts();
        }

        $scope.onExportPdf = function() {
            kendo.drawing.drawDOM($(".export-pdf-disability-general"))
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
                        fileName: "AUSENTISMO_GENERAL.pdf"
                    });
                });
        }

        $scope.onExportExcel = function() {
            var $cause = $scope.filter.selectedCause ? $scope.filter.selectedCause.value : '';
            angular.element("#download")[0].src = "api/customer-absenteeism-disability/export-general?customerId=" + $stateParams.customerId + "&cause=" + $cause;
        }

        var fillCompareYearList = function() {
            $scope.compareYearList = $scope.years.filter(function($year) {
                return $year.value != ($scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0);
            });
        }

        var getAllYears = function () {
            var $compareYearList = $scope.filter.compareYearList.filter(function(year) {
                return year != null && year.value != null && $scope.filter.canCompare;
            }).map(function(year, index, array) {
                return year.value.value;
            });

            if ($compareYearList === undefined || $compareYearList === null) {
                $compareYearList = [];
            }

            $compareYearList.push($scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0);

            return $compareYearList;
        }
    }
]);
