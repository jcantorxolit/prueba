'use strict';
/**
 * controller for Express Matrix
 */
app.controller('ModalInstanceSideWorkplaceMatrixGTC45Ctrl', function ($rootScope, $stateParams, $scope,
    $uibModalInstance, entity, isView, closeAfterCreate, $log, $timeout, SweetAlert,
    $http, toaster, $filter, $aside, $document, $compile, ListService) {

    $scope.totalEmployee = 0;
    $scope.isView = isView;

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
            address: '',
            economicActivity: 0,
            employeeDirect: 0,
            employeeContractor: 0,
            employeeMision: 0,
            risk1: 0,
            risk2: 0,
            risk3: 0,
            risk4: 0,
            risk5: 0,
            status: null
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
            {name: 'wg_structure_type', value: null},
            {name: 'config_workplace_status', value: null},
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.countryList = response.data.data.countryList;
                $scope.stateList = response.data.data.stateList;
                $scope.cityList = response.data.data.cityList;
                $scope.typeList = response.data.data.wg_structure_type;
                $scope.statusList = response.data.data.config_workplace_status;

                if ($scope.entity.country == null) {
                    var $country = $filter('filter')($scope.countryList, {code: 'CO'}, true);
                    $scope.entity.country = $country.length > 0 ? $country[0] : null;
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
                url: 'api/customer-config-workplace/get-gtc',
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
        $scope.entity.total = parseInt($scope.entity.risk1)
                                + parseInt($scope.entity.risk2)
                                + parseInt($scope.entity.risk3)
                                + parseInt($scope.entity.risk4)
                                + parseInt($scope.entity.risk5);

        $scope.totalEmployee = $scope.entity.total;
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
            url: 'api/customer-config-workplace/save-gtc',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                showMessage = showMessage === undefined ? true : showMessage;
                target = target === undefined ? 'submitButtom' : 'nextButton';

                if (showMessage) {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                }

                $scope.entity = response.data.result;

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
