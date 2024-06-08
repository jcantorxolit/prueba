'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidHealthConditionCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $aside, CustomerCovidService, ListService, ChartService) {

        var currentId = CustomerCovidService.getId();
        $scope.dailyList = [];
        $scope.initLoad = true;
        $scope.filter = {selectedMonth: null}
        $scope.chart = {
            line: { options: null },
            daysvstemperature: { data: null },
        };

        var onInit = function () {
            $scope.entity = {
                id: 0,
                customerCovidId: currentId,
                temperature: null,
                observation: null,
                registrationDate: new Date()
            }
        }
        onInit();

        function getList() {

            var entities = [
                { name: 'customer_covid_date_list', criteria: { covidHeadId: currentId } },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.dailyList = response.data.data.customerCovidDateList;
                    if($scope.dailyList.length){
                        $scope.filter.selectedMonth = $scope.dailyList[0];
                    }
                    $scope.initLoad = false;
                    $scope.reloadData();
                    getChart();
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });

            var chart = [
                {
                    name: "chart_line_options", value: null
                }
            ]

            ChartService.getDataChart(chart)
                .then(function (response) {
                    $scope.chart.line.options = response.data.data.chartLineOptions;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
        getList();


        function getChart() {
            
            var chart = [{
                name: "customer_covid_daily_form",
                criteria: { 
                    month : $scope.filter.selectedMonth ? $scope.filter.selectedMonth.value : null, 
                    entityid : currentId
                }
            }];

            ChartService.getDataChart(chart)
                .then(function (response) {
                    $scope.chart.daysvstemperature.data = response.data.data.chartDailyForm;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.onSelectMonth = function() {
            $scope.reloadData();
            getChart();
        }

        $scope.onClearFilter = function() {
            $scope.filter.selectedMonth = null;
            $scope.reloadData();
            getChart();
        }

        $scope.dtInstance = {};
        $scope.dtOptions = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerCovidHeadId = currentId;
                    if($scope.filter.selectedMonth) {
                        d.selectedMonth = $scope.filter.selectedMonth.value;
                    }
                    return JSON.stringify(d);
                },
                url: 'api/customer-covid-daily',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    if($scope.initLoad){
                        return false;
                    }
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[1, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumns = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 15).notSortable()
                .renderWith(function (data) {
                    var actions = "";
                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var editTemplate = "";
                    if(!$scope.isView) {
                        editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                            '   <i class="fa fa-edit"></i></a> ';
                    }
                    actions += viewTemplate;
                    actions += editTemplate;

                    return actions;
                }),
            DTColumnBuilder.newColumn('registrationDate').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('symptoms').withTitle("Síntomas").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Nivel de Riesgo").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label';               
                return '<span class="' + label + '" style="background-color:' + data.riskLevelColor + '">' + data.riskLevel + '</span>';
            }), 
            DTColumnBuilder.newColumn('origin').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRow = function () {
            $("#dtCovid a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.openHealthCondition(id, true);
            });
            
            $("#dtCovid a.viewRow").on("click", function () {
                var id = $(this).data("id");
                $scope.openHealthCondition(id, false);
            });
        };

        $scope.dtInstanceCallback = function (instance) {
            $scope.dtInstance = instance;
        };

        $scope.reloadData = function () {
            if ($scope.dtInstance != null) {
                $scope.dtInstance.reloadData();
            }
        };

        $scope.onExportExcel = function()
        {
            var param = {
                customerId: $stateParams.customerId,
                customerCovidHeadId: currentId,
                period: $scope.filter.selectedMonth.value
            };

            angular.element("#downloadDocument")[0].src = "api/customer-covid-daily/export?data=" + Base64.encode(JSON.stringify(param));
        }

        $scope.openHealthCondition = function (id, edit) {
            $scope.modalDailyId = id;
            $scope.isView = !edit;
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/covid-19/health-condition/customer_covid_health_condition_modal.htm",
                placement: 'right',
                backdrop: 'static',
                size: 'lg',
                controller: 'customerCovidHealthConditionModalCtrl',
                scope: $scope
            });
            modalInstance.result.then(function () {
                getList();
            }, function () {
            });
        };
        


    }
);