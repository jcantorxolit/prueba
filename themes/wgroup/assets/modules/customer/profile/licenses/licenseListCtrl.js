'use strict';
/**
 * controller for Customers
 */
app.controller('CustomerInfoLicenseListCtrl',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $http, SweetAlert, $aside, $document, $cookies, $filter, ModuleListService, ListService) {

        $scope.currentLicense = {
            license: null,
            startDate: null,
            endDate: null,
            state: null
        }

        $scope.agentList = [];

        getCurrentState();
        getLists();


        $scope.reloadData = function () {
            $scope.dtInstanceCustomerProfileLicenseList.reloadData();
        };

        $scope.dtInstanceCustomerProfileLicenseList = {};
        $scope.dtOptionsCustomerProfileLicenseList = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-licenses',
                type: 'POST',
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsCustomerProfileLicenseList = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
                .renderWith(function (data) {
                    var actions = "";
                    var disabled = ""

                    var editTemplate = "";
                    var trashTemplate = "";

                    var viewTemplate = '<a class="btn btn-azure btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '"' +
                        disabled + ' > <i class="fa fa-eye"></i></a> ';

                    if (data.state != "Finalizada") {
                        editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '"' +
                            disabled + ' > <i class="fa fa-edit"></i></a> ';

                        trashTemplate = '<a class="btn btn-danger btn-xs trashRow lnk" href="#" uib-tooltip="Finalizar" data-id="' + data.id + '"' +
                            disabled + '>  <i class="fa fa-gear"></i></a> ';
                    }

                    actions += viewTemplate;
                    actions += editTemplate;
                    actions += trashTemplate;
                    return actions;
                }),
            DTColumnBuilder.newColumn('license').withTitle("Licencia").withOption('width', 200),
            DTColumnBuilder.newColumn('startDate').withTitle("Fecha Inicio").withOption('width', 200),
            DTColumnBuilder.newColumn('endDate').withTitle("Fecha Finalización").withOption('width', 200),
            DTColumnBuilder.newColumn('agent').withTitle("Comercial Asignado").withOption('width', 200),
            DTColumnBuilder.newColumn('value').withTitle("Valor").withOption('width', 200)
                .renderWith(function (data) {
                    return "$ " + $filter('number')(data, 2);
                }),
            DTColumnBuilder.newColumn('state').withTitle("Estado").withOption('width', 200),
        ];


        var loadRow = function () {
            $("#dtCustomerProfileLicenseList a.viewRow").on("click", function () {
                var id = $(this).data("id");
                save(id, true);
            });

            $("#dtCustomerProfileLicenseList a.editRow").on("click", function () {
                var id = $(this).data("id");
                save(id, false);
            });

            $("#dtCustomerProfileLicenseList a.trashRow").on("click", function () {
                var id = $(this).data("id");
                finish(id);
            });
        };


        $scope.onCreate = function () {
            save(0, false);
        };


        function save(id, isView) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/licenses/license_edit_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: false,
                controller: 'ModalInstanceSideCustomerProfileLicenseEditCtrl',
                scope: $scope,
                resolve: {
                    data: {
                        id: id,
                        isView: isView,
                        agentList: $scope.agentList
                    }
                }
            });

            modalInstance.result.then(function () {
                getCurrentState();
                $scope.reloadData();
            });
        }


        function finish(id) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/licenses/license_finish_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'md',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerProfileLicenseFinishCtrl',
                scope: $scope,
                resolve: {
                    data: {
                        id: id
                    }
                }
            });

            modalInstance.result.then(function () {
                getCurrentState();
                $scope.reloadData();
            });
        }


        function getCurrentState () {
            var entities = { customerId: $stateParams.customerId };

            ModuleListService.getDataList('/customer-licenses/current-license', entities)
                .then(function (response) {
                    $scope.currentLicense = response.data.result;
                }, function (error) {
                    $scope.status = 'Unable to load protocol data: ' + error.message;
                });
        }



        function getLists() {
            var entities = [{
                name: 'customer_comercial_agents',
                criteria: { customerId: $stateParams.customerId }
            }];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.agentList = response.data.data.CustomerComercialAgents;
                });
        }

    });
