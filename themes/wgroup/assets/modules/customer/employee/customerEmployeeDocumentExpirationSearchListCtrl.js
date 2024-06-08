'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeDocumentExpirationSearchListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', '$timeout', 'ListService', '$localStorage',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, SweetAlert, $http, $timeout, ListService, $localStorage) {

        var log = $log;
 
        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.$storage = $localStorage.$default({
            hideExpiredAttachment: true
        });

        $scope.audit = {
            fields: [],
            filters: [],
        };

        getList();

        function getList() {
            var entities = [
                {name: 'criteria_operators', value: null},
                {name: 'criteria_conditions', value: null},
                {name: 'customer_employee_document_expiration_custom_filter_field', value: null},                
                {name: 'month_options', value: null}
            ];

            ListService.getDataList(entities)
                .then(function (response) {                    
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.customerEmployeeDocumentExpirationCustomFilterField;      
                    $scope.months =response.data.data.monthOptions;      
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }       

        //------------------------------------------------------FILTERS 
        $scope.addFilter = function () {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }
            $scope.audit.filters.push(
                {
                    id: 0,
                    field: null,
                    criteria: $scope.criteria.length > 0 ? $scope.criteria[1] : null,
                    condition: $scope.conditions.length > 0 ? $scope.conditions[1] : null,
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

        $scope.onShowExpirtedChange = function () {            
            $scope.reloadData();
        }

        $scope.dtInstanceCertificateExpiration = {};
		$scope.dtOptionsCertificateExpiration = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.operation = "document";
                    d.customerId = $stateParams.customerId;
                    d.statusCode = ['1', '3']

                    if ($scope.$storage.hideExpiredAttachment) {
                        d.statusCode = ['1']
                    }

                    if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                        d.filter =
                        {
                            filters: $.map($scope.audit.filters, function (filter) {
                                return {
                                    field: filter.field.name,
                                    operator: filter.criteria.value,
                                    condition: filter.condition.value,
                                    value: filter.value
                                };
                            })
                        };
                    }

                    return JSON.stringify(d);
                },
                url: 'api/customer-employee-document-expiration',
                contentType: "application/json",
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
                loadRow();
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

        $scope.dtColumnsCertificateExpiration = [
            DTColumnBuilder.newColumn(null).withTitle("").withOption('width', 60).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var url = data.documentUrl != null ? data.documentUrl : "";
                    var actions = "";
                    var editTemplate = '<a target="_blank" class="btn btn-primary btn-xs editRow lnk" href="' + url + '" uib-tooltip="Descargar anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-download"></i></a> ';

                    if (url.length > 0) {
                        actions += editTemplate;
                    }

                    //TODO
                    $scope.isView = false;

                    return !$scope.isView ? actions : '';
                }),

            DTColumnBuilder.newColumn('documentType').withTitle("Tipo Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('fullName').withTitle("Nombre").withOption('width', 250),
            DTColumnBuilder.newColumn('workplace').withTitle("Centro de Trabajo").withOption('width', 250),
            DTColumnBuilder.newColumn('requirement').withTitle("Tipo Documento").withOption('width', 200),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 250),
            DTColumnBuilder.newColumn('startDate').withTitle("Fecha de Inicio Vigencia").withOption('width', 200),
            DTColumnBuilder.newColumn('endDate').withTitle("Fecha de Expiración Vigencia").withOption('width', 200),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 150),
            DTColumnBuilder.newColumn(null).withTitle('Requerido').withOption('width', 150).notSortable()
                .renderWith(function(data, type, full, meta) {

                    var actions = "";

                    var checked = (data.isRequiredCode == '1' || data.isRequiredCode) ? "checked" : ""

                    var label = (data.isRequiredCode == '1' || data.isRequiredCode) ? "Si" : "No"

                    var editTemplate = '<div class="checkbox clip-check check-success ">' +
                        '<input class="editRow" ng-disabled="true" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label for="chk_' + data.id +'">' + label + '</label></div>';

                    actions += editTemplate;

                    return actions;
                })
                .notSortable(),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 150)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch  (data)
                    {
                        case "Vigente":
                            label = 'label label-success';
                            break;

                        case "Vencido":
                            label = 'label label-danger';
                            break;
                    }

                    var status = '<span class="' + label +'">' + data + '</span>';

                    return status;
                })
        ];

        $scope.dtInstanceCertificateExpirationCallback = function (instance) {            
            $scope.dtInstanceCertificateExpiration = instance;
        };

        $scope.reloadData = function () {            
            $scope.dtInstanceCertificateExpiration.reloadData();
        };

        var loadRow = function () {

        };

        $scope.onExportExcel = function () {
            jQuery("#downloadDocument")[0].src = "api/customer-employee-document-expiration-export?id=" + $stateParams.customerId + "&data=" + Base64.encode(JSON.stringify($scope.audit));
        }

        $scope.onExportPdf = function () {
            jQuery("#downloadDocument")[0].src = "api/customer-employee/document/search-expiration-export-pdf?id=" + $stateParams.customerId + "&data=" + Base64.encode(JSON.stringify($scope.audit));
        }

    }]);
