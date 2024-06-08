'use strict';
/**
 * controller for Customers
 */
app.controller('customerImprovementPlanAuditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', 'SweetAlert', '$http', '$filter', '$document',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
              $compile, toaster, $state, $rootScope, $timeout, SweetAlert, $http, $filter, $document) {

        var log = $log;

        $scope.request = {};

        log.info("loading..customerImprovementPlanAuditCtrl ");

        $scope.criteria = [
            {
                name: "Igual",
                value: "="
            }, {
                name: "Contiene",
                value: "LIKE"
            }, {
                name: "Diferente",
                value: "<>"
            }, {
                name: "Mayor que",
                value: ">"
            }, {
                name: "Menor que",
                value: "<"
            }
        ];

        $scope.conditions = [
            {
                name: "Y",
                value: "AND"
            }, {
                name: "O",
                value: "OR"
            }
        ];


        $scope.audit = {
            fields: [
                {
                    name: "date",
                    alias: "Fecha"
                }, {
                    name: "fullName",
                    alias: "Usurio"
                }, {
                    name: "userType",
                    alias: "Tipo Usuario"
                }, {
                    name: "action",
                    alias: "Acción"
                }, {
                    name: "observation",
                    alias: "Descripción"
                }
            ],
            filters: [],
        };

        // Datatable configuration
        $scope.request.operation = "audit";
        $scope.request.customer_id = $stateParams.customerId;
        $scope.request.data = "";

        $scope.dtInstanceAudit = {};
        $scope.dtOptionsAudit = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/audit',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
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
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })

            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsAudit = [
            DTColumnBuilder.newColumn('date').withTitle("Fecha").withOption('width', 200),
            DTColumnBuilder.newColumn('userType').withTitle("Tipo Usuario").withOption('width', 200),
            DTColumnBuilder.newColumn('user').withTitle("Usuario").withOption('width', 200),
            DTColumnBuilder.newColumn('action').withTitle("Acción").withOption('width', 200),
            DTColumnBuilder.newColumn('shortObservation').withTitle("Descripción")
        ];

        $scope.reloadData = function () {
            $scope.dtInstanceAudit.reloadData();
        };

        $scope.addFilter = function () {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }
            $scope.audit.filters.push(
                {
                    id: 0,
                    field: null,
                    criteria: null,
                    condition: null,
                    value: ""
                }
            );
        };

        $scope.onFilter = function () {
            $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

            $scope.reloadData();
        }

        $scope.removeFilter = function (index) {
            $scope.audit.filters.splice(index, 1);
        }

    }]);