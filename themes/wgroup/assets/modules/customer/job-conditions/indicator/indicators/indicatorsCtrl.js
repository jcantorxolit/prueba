'use strict';
/**
 * controller for Customers
 */
app.controller('jobConditionsIndicatorCtrl',
    function($scope, $stateParams, $log, $compile, toaster, $state, $rootScope, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage,
        $timeout, $http, ngNotify, SweetAlert, $aside, ChartService, ListService, ModuleListService) {

        // Filter
        $scope.yearList = [];
        $scope.locationList = [];
        // Validation compliance period
        $scope.yearListCompliancePeriod = [];
        $scope.compareYearListCompliancePeriod = [];

        var initialize = function() {
            $scope.entity = {
                customerId: $stateParams.customerId,
                year: null,
                location: null,
                addYearPeriod: null
            };

            $scope.chart = {
                doughnut: { options: null },
                bar: { options: null },
                line: { options: null },
                data: {
                    levelRisks: [],
                    complianceByPeriod: [],
                    intervention: {
                        chartPie: {},
                        percent: 0,
                        budget: 0
                    }
                }
            };

            $scope.generalStats = {
                highPriorityPercent: 0,
                mediumPriorityPercent: 0,
                lowPriorityPercent: 0,
                highPriority: 0,
                mediumPriority: 0,
                lowPriority: 0
            };
        };

        initialize();

        function getList() {
            var entities = [
                { name: 'general_indicators_get_years', customerId: $stateParams.customerId },
            ];

            ModuleListService.getDataList('/customer-jobconditions/config', entities).then(function(response) {
                $scope.yearList = response.data.result.yearsGeneralIndicators;
            });
        }

        getList();

        function getCharts() {
            var criteria = {
                customerId: $scope.entity.customerId,
                year: $scope.entity.year ? $scope.entity.year.year : null,
                years: $scope.compareYearListCompliancePeriod.concat([$scope.entity.year ? $scope.entity.year.year : null]),
                location: $scope.entity.location ? $scope.entity.location.value : null
            }

            var entities = [
                { name: 'chart_bar_options', criteria: null },
                { name: 'chart_doughnut_options', criteria: null },
                { name: 'chart_line_options', criteria: null },
                { name: 'customer_job_condition_indicators', criteria: criteria },
            ];

            ChartService.getDataChart(entities)
                .then(function(response) {
                    // Graphics Bar Settings
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.bar.options.legend.position = 'bottom';
                    // Graphics Lineal Settings
                    $scope.chart.line.options = response.data.data.chartLineOptions;
                    // Graphics Doughnut Settings
                    $scope.chart.doughnut.options = angular.copy(response.data.data.chartDoughnutOptions);
                    $scope.chart.doughnut.options.legend.position = 'bottom';
                    $scope.chart.doughnut.options.maintainAspectRatio = false;
                    $scope.chart.doughnut.options.responsive = false;
                    $scope.chart.doughnut.options.cutoutPercentage = 70;
                    //Data
                    $scope.chart.data.intervention = response.data.data.customerJobConditionIndicatorsInterventions;
                    $scope.chart.data.levelRisks = response.data.data.customerJobConditionIndicatorsLevelRiskByMonth;
                    $scope.chart.data.complianceByPeriod = response.data.data.customerJobConditionIndicatorsComplianceByPeriod;

                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        // Filters
        function initialYearGraphic() {
            $scope.yearListGraphic = $scope.yearList.filter(function(element) {
                return element.year != $scope.entity.year.year;
            });
        }

        $scope.onChangeYear = function() {
            var entities = [
                { name: 'general_indicators_get_locations', customerId: $stateParams.customerId, year: $scope.entity.year.year },
            ];

            ModuleListService.getDataList('/customer-jobconditions/config', entities).then(function(response) {
                $scope.locationList = response.data.result.locationsGeneralIndicators;
            });

            $scope.entity.location = null;
            initialYearGraphic();
            refreshIndicators();
        };

        $scope.onChangeLocation = function() {
            refreshIndicators();
        };

        // Filter compare compliance period
        $scope.onAddCompareYear = function() {
            $scope.compareYearListCompliancePeriod.push($scope.entity.addYearPeriod.year);
            $scope.yearListGraphic = $scope.yearListGraphic.filter(function(element) {
                return element.year != $scope.entity.addYearPeriod.year;
            });
            $scope.entity.addYearPeriod = null;
            getCharts();
        }

        $scope.onRemoveCompareYear = function() {
            $scope.compareYearListCompliancePeriod = [];
            $scope.yearListGraphic = $scope.yearList;
            initialYearGraphic();
            getCharts();
        }

        function refreshIndicators() {
            $scope.dtInstanceJobConditionsIndicatorsLevelRisksByMonths.reloadData();
            $scope.dtInstanceJobConditionsIndicatorsInterventions.reloadData();
            $scope.dtInstanceJobConditionsIndicatorsResponsibles.reloadData();
            getGeneralStats();
            getCharts();
        }

        $scope.onGoToDashboard = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("dashboard", "list", 0);
            }
        };

        /********  GENERAL STAST  *************** */
        function getGeneralStats() {
            var $criteria = {
                customerId: $stateParams.customerId,
                year: $scope.entity.year ? $scope.entity.year.year : null,
                location: $scope.entity.location ? $scope.entity.location.value : null
            };

            var entities = [
                { name: 'customer_job_conditions_intervention_list', criteria: $criteria },
            ];

            ModuleListService.getDataList('/customer-jobconditions/config', entities).then(function(response) {
                $scope.generalStats = response.data.result.jobConditionsInterventionGetStast;
            });
        }


        /********  LIST  LEVEL RISKS BY MONTHS  *************** */

        var storeDatatableLevelRisksByMonths = 'jobConditionsIndicatorsLevelRisksByMonthsListCtrl-' + window.currentUser.id;
        $scope.dtInstanceJobConditionsIndicatorsLevelRisksByMonths = {};
        $scope.dtOptionsJobConditionsIndicatorsLevelRisksByMonths = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.customerId = $scope.entity.customerId;
                    d.year = $scope.entity.year ? $scope.entity.year.year : null;
                    d.location = $scope.entity.location ? $scope.entity.location.value : null;
                    return JSON.stringify(d);
                },
                url: 'api/customer-jobconditions/indicators/get-level-risks-by-months-list',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function() {},
                complete: function(data) {}
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function(settings, data) {
                $localStorage[storeDatatableLevelRisksByMonths] = data;
            })
            .withOption('stateLoadCallback', function() {
                return $localStorage[storeDatatableLevelRisksByMonths];
            })
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('fnDrawCallback', function() {})
            .withOption('info', false)
            .withOption('ordering', false)
            .withOption('paging', false)
            .withOption('searching', false)
            .withOption('createdRow', function(row) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsJobConditionsIndicatorsLevelRisksByMonths = [
            DTColumnBuilder.newColumn('indicator').withTitle("INDICADORES").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('JAN').withTitle("ENE").withOption('width', 200),
            DTColumnBuilder.newColumn('FEB').withTitle("FEB").withOption('width', 200),
            DTColumnBuilder.newColumn('MAR').withTitle("MAR").withOption('width', 200),
            DTColumnBuilder.newColumn('APR').withTitle("ABR").withOption('width', 200),
            DTColumnBuilder.newColumn('MAY').withTitle("MAY").withOption('width', 200),
            DTColumnBuilder.newColumn('JUN').withTitle("JUN").withOption('width', 200),
            DTColumnBuilder.newColumn('JUL').withTitle("JUL").withOption('width', 200),
            DTColumnBuilder.newColumn('AUG').withTitle("AGO").withOption('width', 200),
            DTColumnBuilder.newColumn('SEP').withTitle("SEP").withOption('width', 200),
            DTColumnBuilder.newColumn('OCT').withTitle("OCT").withOption('width', 200),
            DTColumnBuilder.newColumn('NOV').withTitle("NOV").withOption('width', 200),
            DTColumnBuilder.newColumn('DEC').withTitle("DIC").withOption('width', 200),
        ];




        /********  LIST  INTERVENTIONS  *************** */

        var storeDatatableInterventionsList = 'jobConditionsIndicatorsInterventionsListCtrl-' + window.currentUser.id;
        $scope.dtInstanceJobConditionsIndicatorsInterventions = {};
        $scope.dtOptionsJobConditionsIndicatorsInterventions = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.customerId = $scope.entity.customerId;
                    d.year = $scope.entity.year ? $scope.entity.year.year : null;
                    d.location = $scope.entity.location ? $scope.entity.location.value : null;
                    return JSON.stringify(d);
                },
                url: 'api/customer-jobconditions/indicators/get-interventions',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function(settings, data) {
                $localStorage[storeDatatableInterventionsList] = data;
            })
            .withOption('stateLoadCallback', function() {
                return $localStorage[storeDatatableInterventionsList];
            })
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('fnDrawCallback', function() {
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsJobConditionsIndicatorsInterventions = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 20).notSortable()
            .renderWith(function(data) {
                return '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-classification="' + data.classificationId + '" data-question="' + data.questionId + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';
            }),
            DTColumnBuilder.newColumn('classification').withTitle("Condiciones").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('question').withTitle("Pregunta").withOption('width', 400).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('totalPlans').withTitle("Total Planes").withOption('width', 100).withOption('defaultContent', '')
        ];


        var loadRow = function() {
            angular.element("#dtJobConditionsIndicatorsInterventions a.viewRow").on("click", function() {
                var classificationId = angular.element(this).data("classification");
                var questionId = angular.element(this).data("question");

                var modalInstance = $aside.open({
                    templateUrl: $rootScope.app.views.urlRoot + "modules/customer/job-conditions/indicator/indicators/intervention_list_modal.htm",
                    placement: 'right',
                    windowTopClass: 'top-modal',
                    size: 'lg',
                    backdrop: true,
                    controller: 'JobConditionsIndicatorIndicatorsInterventionsListCtrlModalInstanceSide',
                    scope: $scope,
                    resolve: {
                        dataSource: {
                            customerId: $scope.entity.customerId,
                            classificationId: classificationId,
                            questionId: questionId
                        }
                    }
                });

                modalInstance.result.then(function() {
                    refreshIndicators();
                });
            });
        };


        /********  LIST  RESPONSIBLES  *************** */

        var storeDatatableResponsiblesList = 'jobConditionsIndicatorsResponsiblesListCtrl-' + window.currentUser.id;
        $scope.dtInstanceJobConditionsIndicatorsResponsibles = {};
        $scope.dtOptionsJobConditionsIndicatorsResponsibles = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.customerId = $scope.entity.customerId;
                    d.year = $scope.entity.year ? $scope.entity.year.year : null;
                    d.location = $scope.entity.location ? $scope.entity.location.value : null;
                    return JSON.stringify(d);
                },
                url: 'api/customer-jobconditions/indicators/interventions-by-responsibles',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function(settings, data) {
                $localStorage[storeDatatableResponsiblesList] = data;
            })
            .withOption('stateLoadCallback', function() {
                return $localStorage[storeDatatableResponsiblesList];
            })
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsJobConditionsIndicatorsResponsibles = [
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('email').withTitle("Correo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('assignedPlans').withTitle("Planes Asignados").withOption('width', 200).withOption('defaultContent', '')
        ];


        //----------------------------------------------------------------------------EXPORT
        $scope.onExportPdf = function() {
            kendo.drawing.drawDOM($(".job-indicator-general-export-pdf"))
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
                        fileName: "TABLERO_INDICADORES_GENERAL_DE_CONDICIONES_INSEGURAS" + ".pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onExportExcel = function(entity) {
            var criteria = {
                customerId: entity.customerId,
                year: entity.year ? entity.year.year : null,
                location: entity.location ? entity.location.value : null,
                typeIndicator: 'general'
            };

            var data = JSON.stringify(criteria);
            jQuery("#downloadIndicatorGeneralExcel")[0].src = "api/customer-jobconditions/indicators/export-excel?data=" + Base64.encode(data);

        }

        $scope.onRemoveIndicators = function() {
            initialize();

            $scope.dtInstanceJobConditionsIndicatorsLevelRisksByMonths.reloadData();
            $scope.dtInstanceJobConditionsIndicatorsInterventions.reloadData();
            $scope.dtInstanceJobConditionsIndicatorsResponsibles.reloadData();

        }

    });