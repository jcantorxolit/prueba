'use strict';
/**
 * controller for Customers
 */
app.controller('CustomerVrEmployeeSatisfactionIndicatorListCtrl',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $aside, ListService, ChartService) {

        $scope.yearList = [];

        var init = function () {
            $scope.entity = {
                year: null
            }
        }

        $scope.chart = {
            line: {options: null},
            doughnut: {options: null},
            bar: {options: null},
            data: {
                registeredVsParticipants: null,
                amountBySatisfaction: [],
            }
        };

        init();
        getList();
        getCharts();

        $scope.dtInstanceCustomerVRSatisfactionList = {};
        $scope.dtOptionsCustomerVRSatisfactionList = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.consultantId = $scope.consultantId;
                    d.customerId = $stateParams.customerId;
                    d.year = $scope.entity.year.year;
                    return JSON.stringify(d);
                },
                url: 'api/customer-vr-employee/satisfaction-indicator',
                contentType: 'application/json',
                type: 'POST',
            })
            .withDataProp('data')
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return $scope.entity.year != null;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsCustomerVRSatisfactionList = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
                .renderWith(function (data) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" ' +
                        'data-date="' + data.date + '"  ' +
                        'data-participants="' + data.participants + '"  >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    actions += editTemplate;
                    return actions;
                }),

            DTColumnBuilder.newColumn('date').withTitle("Fecha").withOption('width', 200),
            DTColumnBuilder.newColumn('participants').withTitle("Número de Participantes en la encuesta").withOption('width', 200),
        ];

        var loadRow = function () {
            $("#dtCustomerVRSatisfactionList a.editRow").on("click", function () {
                var date = $(this).data("date");
                var participants = $(this).data("participants");

                if ($scope.$parent != null) {
                    $scope.$parent.navToSection("indicators", date, participants);
                }
            });
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerVRSatisfactionList.reloadData();
        };


        $scope.onUpload = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalCustomerVRSatisfactionImportCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function () {
                getList();
                getCharts();
            });
        };


        $scope.onChangeYear = function () {
            $scope.reloadData();
        };

        function getList() {
            var entities = [{
                name: 'customer_vr_employee_satisfaction_indicator_years',
                criteria: { customerId: $stateParams.customerId }
            }];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.yearList = response.data.data.customerVrSatisfactionIndicatorYears;
                    if ($scope.yearList.length) {
                        $scope.entity.year = $scope.yearList[0];
                        $scope.reloadData();
                    }

                }, function (error) {
                    $scope.status = "Unable to load customer data: " + error.message;
                });
        }


        function getCharts() {
            var entities = [
                {name: 'chart_bar_with_scales_options', criteria: null},
                {name: 'customer_vr_satisfaction_general', criteria: {customerId: $stateParams.customerId}},
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {
                    // Graphics Bar Settings
                    $scope.chart.bar.options = response.data.data.chartBarOptionsWithScales;
                    $scope.chart.bar.options.legend.position = 'right';

                    $scope.chart.data.registeredVsParticipants = response.data.data.registeredVsParticipants;
                    $scope.chart.data.amountBySatisfaction = response.data.data.amountBySatisfaction;

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


    });
