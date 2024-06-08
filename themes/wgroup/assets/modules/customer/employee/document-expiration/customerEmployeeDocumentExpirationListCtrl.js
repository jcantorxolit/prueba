'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeDocumentExpirationListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', '$timeout', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http, $timeout, ListService) {

        var log = $log;
        
        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.isView = $scope.$parent.isCustomerContractor || $scope.$parent.editMode == "view";

        $scope.filter = {
            selectedMonth: null,
            selectedYear: null,
        };

        getList();

        function getList() {
    
            var entities = [
                {name: 'month_options', value: null}
            ];
    
            ListService.getDataList(entities)
                .then(function (response) {                
                    $scope.months =response.data.data.monthOptions;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
        
        $scope.dtInstanceCertificateExpiration = {};
		$scope.dtOptionsCertificateExpiration = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.operation = "document";
                    d.customerEmployeeId = $scope.$parent.currentEmployee;
                    d.statusCode = '2'

                    if ($scope.filter.selectedYear !== null && $scope.filter.selectedYear.trim() !== '') {
                        d.year = $scope.filter.selectedYear;
                    }

                    if ($scope.filter.selectedMonth !== null) {
                        d.month = $scope.filter.selectedMonth.value;
                    }
                    
                    return JSON.stringify(d);
                },
                url: 'api/customer-employee-document',
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
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var url = data.documentUrl != null ? data.documentUrl : "";
                    var actions = "";
                    var editTemplate = '<a target="_blank" class="btn btn-primary btn-xs editRow lnk" href="' + url + '" uib-tooltip="Descargar anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-download"></i></a> ';

                    if (url.length > 0) {
                        actions += editTemplate;
                    }

                    return !$scope.isView ? actions : '';
                }),

            DTColumnBuilder.newColumn('requirement').withTitle("Tipo Documento").withOption('width', 200),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200),
            DTColumnBuilder.newColumn('startDate').withTitle("Fecha de Inicio Vigencia").withOption('width', 200),
            DTColumnBuilder.newColumn('endDate').withTitle("Fecha de Expiración Vigencia").withOption('width', 200),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200),
            DTColumnBuilder.newColumn(null).withTitle('Requerido').withOption('width', 200).notSortable()
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
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch  (data)
                    {
                        case "Vigente":
                            label = 'label label-success';
                            break;

                        case "Anulado":
                            label = 'label label-danger';
                            break;
                    }

                    var status = '<span class="' + label +'">' + data + '</span>';

                    return status;
                })
        ];

        var loadRow = function () {
   
        };

        $scope.dtInstanceCertificateExpirationCallback = function(instance) {
            $scope.dtInstanceCertificateExpiration = instance;
        }

        $scope.reloadData = function () {            
            $scope.dtInstanceCertificateExpiration.reloadData();
        };        

        $scope.onSelectMonth = function (item, model) {
            $timeout(function () {                
                $scope.reloadData();
            });
        };

        $scope.onClearMonth = function()
        {
            $timeout(function () {                
                $scope.filter.selectedMonth = null;
                $scope.reloadData();
            });
        }

        $scope.onSearchYear = function () {
            $timeout(function () {
                $scope.reloadData();
            });
        };

        $scope.onClearYear = function () {
            $timeout(function () {
                $scope.filter.selectedYear = null;
                $scope.reloadData();
            });
        };

    }]);
