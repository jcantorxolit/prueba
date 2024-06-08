app.controller('ModalInstanceSideCustomerProfileLicenseEditCtrl',
    function ($rootScope, $stateParams, $scope, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
              $compile, $log, SweetAlert, $http, ListService, $timeout, $filter, data) {

        $scope.isView = data.isView;
        $scope.textProfile = null;

        $scope.agentList = data.agentList;
        $scope.licenseList = $rootScope.parameters("wg_customer_licenses_types");
        $scope.stateList = $rootScope.parameters("wg_customer_licenses_states");

        $scope.minDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        };


        var initialize = function () {
            $scope.entity = {
                id: data.id || 0,
                customerId: $stateParams.customerId,
                license: null,
                startDate: null,
                endDate: null,
                agentId: null,
                value: null,
                state: $scope.stateList.find(function (state) {
                    return state.value == 'LS001';
                }),
                reason: null
            };
        };

        initialize();
        load();
        removeFinishOptionInStates();

        $scope.onSelectLicense = function () {
            $timeout(function () {
                $scope.textProfile = $scope.entity.license.code;
            });
        };


        $scope.form = {
            submit: function (form) {
                $scope.Form = form;

                if (form.$valid) {
                    save();
                    return;
                }

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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            }
        };


        var save = function () {
            var data = JSON.stringify($scope.entity);
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-licenses/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function () {
                $uibModalInstance.close();
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message, "error");
            });
        };


        $scope.reloadData = function () {
            $scope.dtInstanceCustomerInfoLicenseLogs.reloadData();
        };


        $scope.dtInstanceCustomerInfoLicenseLogs = {};
        $scope.dtOptionsCustomerInfoLicenseLogs = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.licenseId = $scope.entity.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-licenses/logs',
                type: 'POST',
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return $scope.entity.id != 0;
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsCustomerInfoLicenseLogs = [
            DTColumnBuilder.newColumn('date').withTitle("Fecha").withOption('width', 200),
            DTColumnBuilder.newColumn('field').withTitle("Campo Modificado").withOption('width', 200),
            DTColumnBuilder.newColumn(null).withTitle("Valor Anterior").withOption('width', 200)
                .renderWith(function (data) {
                    if (data.field == "Valor Licencia") {
                        return "$ " + $filter('number')(data.beforeValue, 2);
                    }

                    return data.beforeValue
                }),
            DTColumnBuilder.newColumn(null).withTitle("Valor Nuevo").withOption('width', 200)
                .renderWith(function (data) {
                    if (data.field == "Valor Licencia") {
                        return "$ " + $filter('number')(data.afterValue, 2);
                    }

                    return data.afterValue
                }),
            DTColumnBuilder.newColumn('user').withTitle("Usuario").withOption('width', 200),
            DTColumnBuilder.newColumn('reason').withTitle("Motivo").withOption('width', 200),
        ];


        function load() {
            if (!$scope.entity.id) {

                if ($scope.agentList.length > 0) {
                    $scope.entity.agentId = $scope.agentList[0];
                }

                return;
            }

            var data = {id: $scope.entity.id}
            var req = {
                data: Base64.encode(JSON.stringify(data))
            }

            return $http({
                method: 'post',
                url: 'api/customer-licenses/show',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.entity = response.data.result;
                $scope.entity.reason = null;
                $scope.entity.agentId = $scope.agentList.find(function (item) {
                    return item.id == $scope.entity.agentId
                });

                $scope.reloadData();

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error al consultar la información.", e.data.message, "error");
            });
        }

        function removeFinishOptionInStates() {
            if ($scope.stateList.length && $scope.isView == false) {
                $scope.stateList = $scope.stateList.filter(function (item) {
                    return item.value != 'LS003';
                });
            }
        }

    });
