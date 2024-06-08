'use strict';
/**
 * controller for Customers
 */
app.controller('customerContractSummaryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document',
    'ChartService', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, ChartService, ListService) {


        var log = $log;
        var request = {};
        var currentId = $scope.$parent.currentContract;

        $scope.criteria = {
            period: null
        }

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
                contractorId: currentId
            };

            var entities = [            
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_doughnut_options', criteria: null},
                { name: 'customer_contract', criteria: $criteria }
            ];
    
            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.doughnut.options = response.data.data.chartLineOptions;  
                    $scope.chart.programs.data = response.data.data.customerContractPeriod;
                    $scope.chart.progress.data = response.data.data.customerContractProgress;        
                    $scope.chart.progress.total = response.data.data.customerContractAverage;             
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();

        function getList() {
            var entities = [
                {name: 'customer_contract_period', value: currentId},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.periodList = response.data.data.customerContractPeriod;

                    if (!$scope.criteria.period && $scope.periodList.length > 0) {
                        $scope.criteria.period = $scope.periodList[0];
                        $scope.reloadSummaryData();
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }        



        var onLoadRecordContractor = function () {
            var rq = {
                id: currentId
            };

            $http({
                method: 'GET',
                url: 'api/customer/contractor',
                params: rq
            })
                .catch(function (e, code) {
                })
                .then(function (response) {
                    $timeout(function () {
                        $scope.contractor = response.data.result;
                    });
                }).finally(function () {
                    $timeout(function () {
                        $document.scrollTop(0, 2000);
                    });
                });
        };

        onLoadRecordContractor();

        $scope.dtInstanceContractSummary = {};
		$scope.dtOptionsContractSummary = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "management";
                    d.customer_id = $stateParams.customerId;
                    d.contract_id = $scope.$parent.currentContract;

                    return d;
                },
                url: 'api/customer/contractor/summary',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {
                    $timeout(function () {
                        $scope.$parent.setDataSummary(data.responseJSON.data);
                    });
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

            })
            .withDOM('tr')
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });
        ;

        $scope.dtColumnsContractSummary = [
            DTColumnBuilder.newColumn('period')
                .withTitle("Periodo")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('requirements')
                .withTitle("Nro Requisitos")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('answers')
                .withTitle("Nro Ejecutados")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('advance')
                .withTitle("Avance (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('average')
                .withTitle("Promedio Total (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('startDate')
                .withTitle("Fecha Inicio")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('endDate')
                .withTitle("Fecha Finaliza")
                .withOption('width', 200),

            DTColumnBuilder.newColumn(null)
                .withTitle("Estado")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Sin Iniciar';

                    if (parseInt(data.answers) == parseInt(data.requirements))
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

        $scope.reloadData = function () {
            $scope.dtInstanceContractSummary.reloadData();
        };


        $scope.onContinue = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", $scope.$parent.currentContract);
            }
        };

        $scope.onCancel = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        $scope.dtInstanceContractInfoSummary = null;
		$scope.dtOptionsContractInfoSummary = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    var $audit = {
                        operation: "info",
                        contract_id: currentId,
                        period: $scope.criteria.period ? $scope.criteria.period.value : 0
                    };
            
                    d.data = Base64.encode(JSON.stringify($audit));

                    return d;
                },
                url: 'api/customer/contractor/infoSummary',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {

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
                //loadRow();
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

        $scope.dtColumnsContractInfoSummary = [
            DTColumnBuilder.newColumn('requirement')
                .withTitle("Requisito"),
            DTColumnBuilder.newColumn(null).withTitle('Anexos').notSortable()
                .renderWith(function(data, type, full, meta) {

                    var actions = "";

                    var checked = (data.hasAttachment == true) ? "checked" : ""
                    var label = (data.hasAttachment == true) ? "Si" : "No"

                    var editTemplate = '<div class="checkbox clip-check check-success ">' +
                        '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id +'">' + label + '</label></div>';

                    actions += editTemplate;

                    return actions;
                })
                .notSortable().withOption('width', 200),            
            DTColumnBuilder.newColumn(null)
                .withTitle("Estado")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Sin Contestar';

                    if (data.rate == 'Cumple') {
                        text = data.rate;
                        label = 'label label-success';
                    }  else if (data.rate == 'Cumple Parcial') {
                        text = data.rate;
                        label = 'label label-warning';
                    } else if (data.rate == 'No Cumple') {
                        text = data.rate;
                        label = 'label label-danger';
                    } else if (data.rate == 'No Aplica') {
                        text = data.rate;
                        label = 'label label-info';
                    } else {
                        text = data.rate;
                        label = 'label label-inverse';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
                .notSortable()
        ];

        $scope.dtInstanceContractInfoSummaryCallback = function (instance) {
            $scope.dtInstanceContractInfoSummary = instance;
        };

        $scope.reloadSummaryData = function () {
            if ($scope.dtInstanceContractInfoSummary != null) {
                $scope.dtInstanceContractInfoSummary.reloadData();
            }
        };

        $scope.onSelectPeriod = function () {
            $timeout(function () { 
                $scope.reloadSummaryData();
            });
        };

        $scope.onClearPeriod = function() {
            $timeout(function () {
                $scope.criteria.period = null;
                $scope.reloadSummaryData();
            });
        }

        $scope.onExport = function()
        {
            var $audit = {
                operation: "info",
                contract_id: currentId,
                period: $scope.criteria.period ? $scope.criteria.period.value : 0
            }

            var data = Base64.encode(JSON.stringify($audit));

            jQuery("#download")[0].src = "api/customer/contractor/export-excel?data=" + data;
        }

    }]);