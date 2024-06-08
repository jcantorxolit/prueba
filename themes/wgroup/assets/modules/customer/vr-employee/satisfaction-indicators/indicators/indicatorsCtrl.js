'use strict';
/**
 * controller for Customers
 */
app.controller('CustomerVrEmployeeSatisfactionIndicatorIndicatorsCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, ChartService) {

        var init = function() {
            $scope.entity = {
                customerId: $stateParams.customerId,
                date: $scope.$parent.date,
                participants: $scope.$parent.participants
            }
        }

        $scope.chart = {
            bar: { options: null },
            pie: { options: null },
            data: {
                indicators: null
            }
        };

        init();
        getCharts();

        $scope.dtInstanceCustomerVRSatisfactionIndicators = {};
        $scope.dtOptionsCustomerVRSatisfactionIndicators = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.consultantId = $scope.consultantId;
                    d.date = $scope.date;
                    return JSON.stringify(d);
                },
                url: 'api/customer-vr-employee/satisfaction-indicator/valuation',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('searching', false)
            .withOption('ordering', false)
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('fnDrawCallback', function() {})
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function(row) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsCustomerVRSatisfactionIndicators = [
            DTColumnBuilder.newColumn('experience').withTitle("Experiencia").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('valuationAvailable').withTitle("Valoración Disponible").withOption('width', 200).notSortable()
                .renderWith(function (data) {
                    var text = "No";
                    var color = 'text-danger';
                    var icon = ' <i class=" fa fa-ban"></i>';

                    if (data > 0) {
                       text = "Si";
                       color = 'text-success';
                       icon =  ' <i class=" fa fa-check-circle-o"></i>'
                   }

                    return text + ' <span class="'+ color +'">' + icon + '</span>';
                }),
            DTColumnBuilder.newColumn('amount').withTitle("Número de registros").withOption('width', 200).notSortable(),
        ];

        $scope.onBack = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", null, null);
            }
        }

        $scope.reloadData = function() {
            $scope.dtInstanceCustomerVRSatisfactionIndicators.reloadData();
        };


        function getCharts() {
            var entities = [
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_pie_options', criteria: null},
                {name: 'customer_vr_satisfaction_by_responses', criteria: {
                    customerId: $stateParams.customerId,
                    date: $scope.entity.date
                }}
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {
                    // Graphics Bar Settings
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.bar.options.legend.position = 'bottom';

                    $scope.chart.pie.options = response.data.data.chartPieOptions;
                    $scope.chart.pie.options.legend.position = 'bottom';

                    // data
                    $scope.chart.data.indicators = response.data.data.customerVrSatisfactionByResponses;

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

    });
