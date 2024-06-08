'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidIndicatorEmployeeCtrl', 
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, $compile, ListService, ChartService, $aside, $rootScope, CustomerCovidService) {

        var log = $log;

        $scope.options = {
            unit: "",
            readOnly: true,
            barCap: 25,
            trackWidth: 20,
            barWidth: 20,
            trackColor: 'rgba(92,184,92,.1)',
            barColor: '#5BC01E',
            textColor: '#000'
        };

        $scope.filter = {
            type: '1',
            selectedDay: null,
            selectedPeriod: null,
            selectedRiskLevel: null,
            selectedWorkplace: null,
        }

        function getOtherList(value, type) {
            var criteriaParams = {
                customerId: $stateParams.customerId,
                isEmployee: true,
            };
            if (type == "day") {
                criteriaParams.day = value;
            }
            if (type == "period") {
                criteriaParams.period = value;
            }
            var entities = [
                {
                    name: 'customer_covid_workplace',
                    criteria: criteriaParams
                },
            ];
            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.workplaceList = response.data.data.covidWorkplaceList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getListTypes() {
            var criteriaParams = {
                customerId: $stateParams.customerId,
                isEmployee: true,
            };
            var entities = [
                {
                    name: 'customer_covid_period_list',
                    criteria: criteriaParams
                },
                {
                    name: 'customer_covid_date_list',
                    criteria: criteriaParams
                },
                { name: 'customer_covid_risk_level_list', value: null },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.periodList = response.data.data.customerCovidPeriodList;
                    $scope.dayList = response.data.data.customerCovidDateList;
                    $scope.riskLevelList = response.data.data.customerCovidRiskLevelList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.chart = {
            bar: { options: null },
            pie: { options: null },
            genre: { data: null },
            pregnant: { data: null },
            fever: { data: null },
            employee: { data: null },
            riskLevel: { data: null },
            oximetria: { data: null },
            pulsometria: { data: null },
        };

        function getCharts() {
            var $criteria = {
                customerId: $stateParams.customerId,
                type: $scope.filter.type
            };
            $criteria.isEmployee = true;

            if ($scope.filter.type == 1) {
                $criteria.day = $scope.filter.selectedDay ? $scope.filter.selectedDay.value : null;
            }
            if ($scope.filter.type == 0) {
                $criteria.period = $scope.filter.selectedPeriod ? $scope.filter.selectedPeriod.value : null;
            }
            if ($scope.filter.selectedWorkplace) {
                $criteria.workplaceId = $scope.filter.selectedWorkplace.id;
            }
            
            var entities = [
                { name: 'chart_bar_options', criteria: $criteria },
                { name: 'chart_pie_options', criteria: $criteria },
                { name: 'customer_covid_indicators', criteria: $criteria }
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.pie.options = angular.copy(response.data.data.chartPieOptions);
                    $scope.chart.pie.options.tooltips = {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                log.info(data);
                                log.info(tooltipItem);

                                var label = data.labels[tooltipItem.index] || '';

                                return label;
                            }
                        }
                    };

                    $scope.chart.bar.options.legend.position = 'bottom';
                    $scope.chart.pie.options.legend.position = 'bottom';
                    $scope.chart.bar.options.maintainAspectRatio = false;
                    $scope.chart.bar.options.responsive = false;
                    $scope.chart.pie.options.maintainAspectRatio = false;
                    $scope.chart.pie.options.responsive = false;

                    $scope.chart.genre.data = response.data.data.covidGenre;
                    $scope.chart.pregnant.data = response.data.data.covidPregnant;
                    $scope.options = {
                        unit: "",
                        readOnly: true,
                        displayPrevious: true,
                        barCap: 25,
                        trackWidth: 20,
                        barWidth: 20,
                        trackColor: 'rgba(92,184,92,.1)',
                        barColor: '#5BC01E',
                        textColor: '#000'
                    };
                    $scope.chart.fever.data = response.data.data.covidFever;
                    $scope.chart.employee.data = response.data.data.covidEmployee;
                    $scope.chart.riskLevel.data = response.data.data.covidRiskLevel;
                    $scope.chart.oximetria.data = response.data.data.covidOximetria;
                    $scope.chart.pulsometria.data = response.data.data.covidPulsometria;

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.onSelectDay = function () {
            $scope.filter.selectedPeriod = null;
            getCharts();
            $scope.reloadData();
            getOtherList($scope.filter.selectedDay.value, "day");
        }
        
        $scope.onSelectPeriod = function () {
            $scope.filter.selectedDay = null;
            getCharts();
            $scope.reloadData();
            getOtherList($scope.filter.selectedPeriod.value, "period");
        }

        $scope.onSelectRiskLevel = function () {
            $scope.reloadData();
        }

        $scope.onSelectWorkplace = function () {
            getCharts();
            $scope.reloadData();
        }

        $scope.onClearWorkplace = function () {
            if ($scope.filter.selectedWorkplace != null) {
                $scope.filter.workplaceId = null;
                $scope.filter.selectedWorkplace = null;
                getCharts();
                $scope.reloadData();
            }
        }

        $scope.onExportPdf = function () {
            kendo.drawing.drawDOM($(".covid-indicator-export-pdf"))
                .then(function (group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: { left: "1cm", top: "1cm", right: "1cm", bottom: "1cm" }
                    });
                })
                .done(function (data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Covid_19_Indicadores.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        //-------------------------------------------------------RISK LEVEL DATABLE
        $scope.dtOptionsCustomerCovidRiskLevel = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    d.riskLevel = $scope.filter.selectedRiskLevel ? $scope.filter.selectedRiskLevel.value : null;
                    d.isExternal = 0;

                    if ($scope.filter.selectedWorkplace) {
                        d.workplaceId = $scope.filter.selectedWorkplace.id;
                    }

                    if ($scope.filter.type == 1) {
                        d.day = $scope.filter.selectedDay ? $scope.filter.selectedDay.value : null;
                    }

                    if ($scope.filter.type == 0) {
                        d.period = $scope.filter.selectedPeriod ? $scope.filter.selectedPeriod.value : null;
                    }

                    return JSON.stringify(d);
                },
                url: 'api/customer-covid-indicator',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();   
            })
            .withOption('language', {
            })
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);

            });

        $scope.dtColumnsCustomerCovidRiskLevel = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 70).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    if (data.hasPersons == 1) {
                        var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Personas Cercanas" data-id="' + data.id + '" >' +
                                            '<i class="fa fa-users"></i></a> ';
                        actions += viewTemplate;
                    }
                    return actions;
            }),
            DTColumnBuilder.newColumn('registrationDate').withTitle("Fecha").withOption('width', 180).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('fullName').withTitle("Empleado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('questions').withTitle("Sintomas").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Nivel Riesgo").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label';
                    return '<span class="' + label + '" style="background-color:' + data.riskLevelColor + '">' + data.riskLevelText + '</span>';
                }),
        ];

        var loadRow = function () {
            angular.element("#dtCustomerCovidRiskLevel a.viewRow").on("click", function () {                
                var id = angular.element(this).data("id");                
                onShowPersons(id);
            });
        }

        var onShowPersons = function(id) {
            CustomerCovidService.setDailyId(id);
            $scope.exportExcel = true;
            $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/covid-19/health-condition/person-near/customer_covid_person_near.htm",
                placement: 'right',
                backdrop: 'static',
                size: 'lg',
                scope: $scope
            });
        }

        $scope.dtInstanceCustomerCovidRiskLevelCallback = function (instance) {
            $scope.dtInstanceCustomerCovidRiskLevel = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerCovidRiskLevel.reloadData();
        };

        $scope.onClearRiskLevel = function () {
            $scope.filter.selectedRiskLevel = null;
            $scope.reloadData()
        };

        getCharts();
        getListTypes();


    }
);