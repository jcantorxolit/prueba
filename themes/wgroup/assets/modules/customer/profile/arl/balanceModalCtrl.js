'use strict';
/**
 * controller for customerContributionBalanceModalCtrl
 */
app.controller('customerContributionBalanceModalCtrl',
    function ($rootScope, $stateParams, $scope, $log, $timeout, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder,
              DTColumnDefBuilder, $http, $filter, toaster, $compile, $aside, ngNotify, ListService, dataSource) {

        $scope.year = dataSource.year;

        $scope.monthList = [];
        $scope.typeList = [];
        $scope.conceptList = [];
        $scope.classificationList = [];

        $scope.concepts = $rootScope.parameters("project_concepts");
        $scope.classifications = $rootScope.parameters("project_classifications");

        var init = function () {
            $scope.filters = {
                month: null,
                type: null,
                concept: null,
                classification: null
            }

            $scope.conceptList = [];
            $scope.classificationList = [];
        }

        init();
        getList();

        $scope.onClose = function () {
            $uibModalInstance.close(1);
        };

        $scope.onCancel = function () {
            init();
            $scope.reloadData();
        }

        $scope.onChangeClassification = function () {
            console.log('on Change classification')
            $scope.reloadData();
        }

        $scope.onChangeConcept = function () {
            $scope.filters.classification = null;

            $scope.classificationList = $filter('filter')($scope.classifications, { code: $scope.filters.concept.value });
            $scope.reloadData();
        }

        $scope.onChangeType = function () {
            $scope.filters.concept = null;
            $scope.filters.classification = null;

            $scope.classificationList = [];

            $scope.conceptList = $filter('filter')($scope.concepts, { code: $scope.filters.type.value });
            $scope.reloadData();
        }

        $scope.onChangeMonth = function () {
            $scope.reloadData();
        }

        $scope.onExport = function () {
            exportExcel();
        };


        $scope.dtInstanceCustomerContributionBalanceDetail = {};
        $scope.dtOptionsCustomerContributionBalanceDetail = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    d.year = $scope.year;

                    d.period = $scope.filters.month ? $scope.filters.month.item : null;
                    d.type = $scope.filters.type ? $scope.filters.type.item : null;
                    d.concept = $scope.filters.concept ? $scope.filters.concept.item : null;
                    d.classification = $scope.filters.classification ? $scope.filters.classification.item : null;

                    return JSON.stringify(d);
                },
                url: 'api/customer-contributions/get-detail-balanace',
                type: 'POST'
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsCustomerContributionBalanceDetail = [
            DTColumnBuilder.newColumn('period').withTitle("Periodo"),
            DTColumnBuilder.newColumn('type').withTitle("Tipo"),
            DTColumnBuilder.newColumn('activity').withTitle("Actividad"),
            DTColumnBuilder.newColumn('concept').withTitle("Concepto"),
            DTColumnBuilder.newColumn('classification').withTitle("Clasificación"),
            DTColumnBuilder.newColumn('total').withTitle("Valor Total").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
        ];

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerContributionBalanceDetail.reloadData();
        };


        function getList() {
            var entities = [
                { name: 'export_url', value: null },
                {
                    name: 'customer_contributions_detail_balance_months',
                    criteria: {
                        customerId: $stateParams.customerId,
                        year: $scope.year
                    }
                }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.monthList = response.data.data.customerContributionsDetailBalanceMonths;
                    $scope.typeList = response.data.data.customerContributionsDetailBalanceTypes;
                    $scope.exportUrl = response.data.data.exportUrl.item;
                });
        }



        function exportExcel() {
            var data = JSON.stringify({
                userId: $rootScope.currentUser().id,
                customerId: $stateParams.customerId,
                year: $scope.year,

                month: $scope.filters.month ? $scope.filters.month.value : null,
                type: $scope.filters.type ? $scope.filters.type.item : null,
                concept: $scope.filters.concept ? $scope.filters.concept.item : null,
                classification: $scope.filters.classification ? $scope.filters.classification.item : null,
            });

            var req = {
                data: Base64.encode(data)
            };


            var url = $scope.exportUrl + 'api/v1/customer-contribution/detail-balance-export';

            return $http({
                method: 'POST',
                url: url,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                var $url = $scope.exportUrl + 'storage/' + response.data.filename;
                var $link = '<a class="btn btn-wide btn-default" href="' + $url + '" target="_self"><i class="glyphicon glyphicon-download"></i> Descargar el reporte</a>';

                if (response.data.isQueue) {
                    $url = 'app/user/messages';
                    $link = response.data.message + ' <a  class="btn btn-wide btn-default" href="' + $url + '" translate="Ver mensajes"> Ver mensajes </a>';
                }

                ngNotify.set($link, {
                    position: 'bottom',
                    sticky: true,
                    type: response.data.isQueue ? 'info' : 'success',
                    button: true,
                    html: true
                });

            }).catch(function (e) {
                $log.error(e);

                if (response.data != null && response.data.message !== undefined) {
                    ngNotify.set(response.data.message, {
                        position: 'bottom',
                        sticky: true,
                        type: 'error',
                        button: true,
                        html: true
                    });
                } else {
                    ngNotify.set("Lo sentimos, ha ocurrido un error en la generación del reporte", {
                        position: 'bottom',
                        sticky: true,
                        type: 'error',
                        button: true,
                        html: true
                    });
                }
            });

        }
    });
