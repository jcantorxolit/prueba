'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeAuditListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', 'SweetAlert', '$http', '$filter', '$document',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
              $compile, toaster, $state, $rootScope, $timeout, SweetAlert, $http, $filter, $document) {

        var log = $log;

        $scope.request = {};

        log.info("loading..customerEmployeeAuditListCtrl ");

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
        $scope.dtInstanceCustomerEmployeeAudit = null;
        $scope.dtOptionsCustomerEmployeeAudit = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "audit";;
                    d.customer_employee_id = $scope.$parent.currentEmployee;
                    d.data = Base64.encode(JSON.stringify($scope.audit));
                    return d;
                },
                url: 'api/customer-employee/audit',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
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

        $scope.dtColumnsCustomerEmployeeAudit = [
            DTColumnBuilder.newColumn('date').withTitle("Fecha").withOption('width', 200),
            DTColumnBuilder.newColumn('userType').withTitle("Tipo Usuario").withOption('width', 200),
            DTColumnBuilder.newColumn('user').withTitle("Usuario").withOption('width', 200),
            DTColumnBuilder.newColumn('action').withTitle("Acción").withOption('width', 200),
            DTColumnBuilder.newColumn('shortObservation').withTitle("Descripción")
        ];

        $scope.dtInstanceCustomerEmployeeAuditCallback = function (instance) {
            $scope.dtInstanceCustomerEmployeeAudit = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerEmployeeAudit.reloadData();
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
            $scope.reloadData();
        }

        $scope.removeFilter = function (index) {
            $scope.audit.filters.splice(index, 1);
        }

        $scope.onCleanFilter = function () {
            $scope.audit.filters = [];
            $scope.reloadData()
        }

    }]);