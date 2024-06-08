'use strict';
/**
 * controller for Customers
 */
app.controller('customerManagementSettingCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ListService',
    '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ListService, $aside) {

        $scope.record = {};
        $scope.currentEntity = {};

        var onInit = function() {
            $scope.entity = {
                id: 0,
                customerId: $stateParams.customerId,
                economicSector: null,
                programEconomicSector: null,
                customerWorkplace: null,
                programId: null,
                active: true
            };
        }

        onInit();

        var getList = function() {
            var entities = [
                { name: 'economic_sector',  value: null },
                { name: 'customer_workplace',  value: $stateParams.customerId }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.economicSectorList = response.data.data.economicSectorList;
                    $scope.workplaceList = response.data.data.workplaceList;
                }, function (error) {

                });
        }

        getList();

        $scope.onSelectEconomicSector = function() {
            $scope.reloadData();
        }

        $scope.onSelectWorkplace = function() {
            $scope.reloadData();
        }

        $scope.onCreate = function() {
            if ($scope.customer.matrixType == 'E') {
                onCreateForExpressMatrix();
            } else if ($scope.customer.matrixType == 'G') {
                onCreateForGTC45Matrix();
            } else {
                toaster.pop('error', 'Atención!', 'No se ha configurado el tipo de matríz de peligros en la empresa.');
            }
        }

        $scope.onRefresh = function() {
            getList();
        }

        var onCreateForExpressMatrix = function()
        {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_express_matrix_work_place_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: 'static',
                controller: 'ModalInstanceSideCustomerDiagnosticExpressMatrixWorkplaceCtrl',
                scope: $scope,
                resolve: {
                    entity: function () {
                        return { id : 0 };
                    },
                    isView: function () {
                        return $scope.isView;
                    },
                    closeAfterCreate: function() {
                        return true;
                    }
                }
            });
            modalInstance.result.then(function (entity) {
                $scope.workplaceList.push(entity);
                $scope.entity.customerWorkplace = entity;
                $scope.reloadData();
            }, function () {
                getList();
            });
        }

        var onCreateForGTC45Matrix = function()
        {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_gtc_matrix_work_place_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: 'static',
                controller: 'ModalInstanceSideWorkplaceMatrixGTC45Ctrl',
                scope: $scope,
                resolve: {
                    entity: function () {
                        return { id : 0 };
                    },
                    isView: function () {
                        return $scope.isView;
                    },
                    closeAfterCreate: function() {
                        return true;
                    }
                }
            });
            modalInstance.result.then(function (entity) {
                $scope.workplaceList.push(entity);
                $scope.entity.customerWorkplace = entity;
                $scope.reloadData();
            }, function () {
                getList();
            });
        }

		$scope.dtOptionsManagementSetting = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    d.economicSectorId = $scope.entity.economicSector ? $scope.entity.economicSector.id : -1;
                    if ($scope.entity.customerWorkplace) {
                        d.workplaceId = $scope.entity.customerWorkplace ? $scope.entity.customerWorkplace.id : -1;
                    }
                    return JSON.stringify(d);
                },
                url: 'api/customer-management-setting',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                },
                complete: function (data) {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
            })
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });


        $scope.dtColumnsManagementSetting = [
            DTColumnBuilder.newColumn('abbreviation')
                .withTitle("Código")
                .withOption('width', 200)
                .withOption('defaultContent', ''),

            DTColumnBuilder.newColumn('name')
                .withTitle("Programa")
                .withOption('defaultContent', ''),

            DTColumnBuilder.newColumn(null).withOption('width', 200).withTitle('Acciones').notSortable()
                .renderWith(function(data, type, full, meta) {

                    $scope.record[data.id] = data;
                    var actions = "";

                    var checked = $scope.currentEntity && $scope.currentEntity.id == data.customerManagementId ? "checked " : ""

                    var editTemplate = '<div class="radio clip-radio radio-success"> ' +
                        '<input type="radio" id="checked_' + data.id + '" ' + checked + '' +
                        'name="selected_program" value="' + data.id + '"  ng-click="select(record[' + data.id + '])">' +
                    '<label class="text-bold" for="checked_' + data.id + '"> Seleccionar </label> </div>';
                    actions += editTemplate;

                    return actions;
                })
                .notSortable()
        ];

        $scope.select = function(data) {
            $scope.entity.id = data.status == 'iniciado' ? data.customerManagementId : 0;
            $scope.entity.programId = data.programId;
            $scope.entity.customerId = $stateParams.customerId;
            $scope.entity.programEconomicSector = {
                id: data.id
            };
        }

        $scope.dtInstanceManagementSetting = function(instance) {
            $scope.dtInstanceManagementSetting = instance;
        }

        $scope.reloadData = function () {
            $scope.dtInstanceManagementSetting.reloadData();
        };

        $scope.form = {

            submit: function (form) {
                var firstError = null;

                if (form.$invalid) {

                    var field = null, firstError = null;
                    for (field in form) {
                        if (field[0] != '$') {
                            if (firstError === null && !form[field].$valid) {
                                firstError = form[field].$name;
                            }

                            if (form[field].$pristine) {
                                form[field].$dirty = true;
                            }
                        }
                    }
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    onSave();
                }

            },
            reset: function (form) {
                $scope.employee = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };



        var onSave = function () {

            if ($scope.entity.programEconomicSector == null) {
                SweetAlert.swal("Atención!", "Por favor seleccione un programa.", "error");
                return;
            }

            var data = JSON.stringify($scope.entity);

            return $http({
                method: 'POST',
                url: 'api/customer-management/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param({
                    data: Base64.encode(data)
                })
            }).then(function (response) {
                $scope.currentEntity = response.data.result;
                $scope.entity.id = response.data.result.id;
                $scope.reloadData();
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Actualización exitosa.');
                });

            }).catch(function (error) {
                if (error.status == 400) {
                    toaster.pop("error", "Error", error.data.message);
                } else {
                    toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
                }
            }).finally(function () {

            });

        };

        $scope.onContinue = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.entity.id);
            }
        };

        $scope.onCancel = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        $scope.editManagement = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("report", "report", $scope.entity.id);
            }
        };

    }]);
