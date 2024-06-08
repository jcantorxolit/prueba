'use strict';
/**
 * controller for Express Matrix
 */
app.controller('ModalInstanceSideCustomerDiagnosticExpressMatrixWorkplaceCtrl', function ($rootScope, $stateParams, $scope,
    $uibModalInstance, entity, isView, closeAfterCreate, $log, $timeout, SweetAlert,
    $http, toaster, $filter, $aside, $document, $compile, ListService, ExpressMatrixService) {

    $scope.totalEmployee = 0;
    $scope.isView = isView;

    $scope.onNext = function ($form) {
        if ($form.$dirty) {
            save(false, 'nextButtom');
        } else {
            $scope.onCancel();
            ExpressMatrixService.setWorkplaceId($scope.entity.id);
            $rootScope.$emit('wizardGoTo', { newValue: 1 });
        }
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var init = function() {
        $scope.entity = {
            id: entity.id,
            customerId: $stateParams.customerId,
            country: null,
            state: null,
            city: null,
            name: null,
            economicActivity: null,
            address: '',
            employeeDirect: 0,
            employeeContractor: 0,
            employeeMision: 0,
            isActive: true,
            processList: []
        }

        initializeButtonText();
    }

    var initializeButtonText = function() {
        $scope.submitText = $scope.entity.id == 0 ? 'Crear Centro de Trabajo' : 'Guardar Centro de Trabajo';
    }

    init()

    getList();

    function getList() {
        var entities = [
            {name: 'country', value: null},
            {name: 'state', value: $scope.entity.country ? $scope.entity.country.id : 68},
            {name: 'city', value: $scope.entity.state ? $scope.entity.state.id : 0},
            {name: 'economic_activity', value: null},
            {name: 'customer_express_matrix_process_list', criteria: { customerId: $stateParams.customerId} },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.countryList = response.data.data.countryList;
                $scope.stateList = response.data.data.stateList;
                $scope.cityList = response.data.data.cityList;
                $scope.economicActivityList = response.data.data.economicActivityList;
                $scope.availableProcessList = response.data.data.customerExpressMatrixProcessList;

                if ($scope.entity.country == null) {
                    var $country = $filter('filter')($scope.countryList, {code: 'CO'}, true);
                    $scope.entity.country = $country.length > 0 ? $country[0] : null;
                }

                if ($scope.entity.economicActivity == null) {
                    var economicActivity = $scope.economicActivityList.find(function(element) {
                        return element.id == (($scope.customer.economicActivity) ? $scope.customer.economicActivity.id : 0);
                    });

                    $scope.entity.economicActivity = economicActivity ? economicActivity : null;
                    $scope.disableEconomicActivity = economicActivity != null;
                }

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    var onLoadRecord = function () {
        if ($scope.entity.id) {
            var req = {
                id: $scope.entity.id
            };

            $http({
                method: 'GET',
                url: 'api/customer-config-workplace/get',
                params: req
            })
            .catch(function (e, code) {

            })
            .then(function (response) {
                $scope.entity = response.data.result;
                var economicActivityId = $scope.entity.economicActivity ? $scope.entity.economicActivity.id : -1;
                $scope.disableEconomicActivity = $scope.customer.economicActivity == null || ($scope.customer.economicActivity.id == economicActivityId);
                $scope.onChangeEmployeeNumber();
                getList();
            }).finally(function () {

            });
        }
    }

    onLoadRecord();

    $scope.onChangeEmployeeNumber = function() {
        var direct = parseInt($scope.entity.employeeDirect);
        var contractor = parseInt($scope.entity.employeeContractor);
        var mision = parseInt($scope.entity.employeeMision);

        $scope.totalEmployee = direct + contractor + mision;
    }

    $scope.onSelectCountry = function () {
        $scope.entity.state = null;
        $scope.entity.city = null;
        getList();
    };

    $scope.onSelectState = function () {
        $scope.entity.city = null;
        getList();
    };

    $scope.onAddProcess = function () {
        $timeout(function () {
            if ($scope.entity.processList == null) {
                $scope.entity.processList = [];
            }
            $scope.entity.processList.push({
                    id: 0,
                    customerId: $stateParams.customerId,
                    name: null
            });
        });
    };

    $scope.onRemoveProcess = function (index) {
        SweetAlert.swal({
                title: "¿Está seguro de eliminar el registro seleccionado?",
                text: "Esta acción no se podrá deshacer.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Eliminar",
                cancelButtonText: "Cancelar",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm) {
                    $timeout(function () {
                        // eliminamos el registro en la posicion seleccionada
                        var data = $scope.entity.processList[index];

                        if (data.id != 0) {
                            var req = {
                                id: data.id
                            };
                            $http({
                                method: 'POST',
                                url: 'api/customer-config-process-express-relation/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                $scope.entity.processList.splice(index, 1);
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                            });
                        } else {
                            $scope.entity.processList.splice(index, 1);
                        }
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    }

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
                log.info($scope.standard);
                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                save();
            }

        },
        reset: function (form) {

        }
    };

    var save = function (showMessage, target) {
        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-config-workplace/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                showMessage = showMessage === undefined ? true : showMessage;
                target = target === undefined ? 'submitButtom' : 'nextButton';

                $scope.customer.economicActivity = $scope.entity.economicActivity;

                if (showMessage) {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                }

                $scope.entity = response.data.result;

                initializeButtonText();

                if (target == 'nextButton') {
                    $scope.onCancel();
                    ExpressMatrixService.setWorkplaceId($scope.entity.id);
                    $rootScope.$emit('wizardGoTo', { newValue: 1 });
                }

                if (closeAfterCreate) {
                    $uibModalInstance.close(response.data.result);
                }
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    };

});
