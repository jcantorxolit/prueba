'use strict';
/**
 * controller for customerVrEmployeeRegisterExperienceMetricsRegisterModalCtrl
 */

app.controller('customerVrEmployeeRegisterExperienceMetricsRegisterModalCtrl',
function ($rootScope, $stateParams, $scope, $log, $timeout, $uibModalInstance,
    SweetAlert, $http, toaster, $filter, $compile, $aside, customerVrEmployeeService, ListService) {

        $scope.data = customerVrEmployeeService.getEntityExperience();
        var employeeExperience = customerVrEmployeeService.getId();
        $scope.experience = $scope.data.experience;
        $scope.nextEnable = false;
        $scope.finishEnable = false;
        $scope.reloadView = true;
        $scope.experienceListLength = $scope.data.employeeExperienceList.length;
        $scope.currentIndex = $scope.data.index;
        $scope.isOpen = true;
        $scope.isOpenObs = false;
        $scope.applicationOptions = [];
        $scope.observationTypes = [];

        var init = function() {
            $scope.entity = {
                id: $scope.data.id,
                registrationDate: $scope.data.registrationDate,
                customerVrEmployeeId: employeeExperience,
                experienceCode: $scope.data.experienceValue,
                questionList: [],
                observationType: null,
                observationValue: null
            }
        }
        init();

        function getList() {
            var entities = [
                { name: 'customer_vr_employee_list_options' },
                { name: 'customer_vr_employee_observation_options' },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.applicationOptions = response.data.data.applicationOptions;
                    $scope.observationTypes = response.data.data.observationOptions;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
        getList();

        var onLoadRecord = function () {

            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: 'api/customer-vr-employee-experience-answer/get-question',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.entity = response.data.result;
                    if($scope.entity.answers) {
                        $scope.data.answers = $scope.entity.answers;
                    }
                    validateNavigationButtonIsDisable();
                });
            }).catch(function (e) {
                SweetAlert.swal("Error", "Ocurrió un problema cargar la información.", "error");
            });
        }

        onLoadRecord();


        var validateNavigationButtonIsDisable = function() {
            if($scope.currentIndex < $scope.experienceListLength) {
                $scope.nextEnable = true;
                $scope.finishEnable = false;
                return;
            }

            if($scope.currentIndex == $scope.experienceListLength) {
                $scope.finishEnable = true;
                $scope.nextEnable = false;
                return;
            }

            $scope.nextEnable = false;
            $scope.finishEnable = false;
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

        var save = function () {

            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-vr-employee-experience-answer/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    if($scope.reloadView) {
                        onLoadRecord();
                    } else {
                        $scope.reloadView = true;
                    }
                });
            }).catch(function (e) {
                $log.error(e);
                var message = e.data && e.data.message ? e.data.message : "Error guardando el registro. Por favor verifique los datos ingresados!";
                SweetAlert.swal("Error de guardado", message, "error");
            });
        };

        $scope.onNext = function() {
            // $scope.currentIndex++;
            // console.log($scope.data.employeeExperienceList)
            var date = $scope.data.registrationDate;
            var employeeExperienceList = $scope.data.employeeExperienceList;
            $scope.data = $scope.data.employeeExperienceList[$scope.currentIndex];
            $scope.data.registrationDate = date;
            $scope.data.employeeExperienceList = employeeExperienceList;
            $scope.experience = $scope.data.experience;
            $scope.currentIndex = $scope.data.index;
            $scope.reloadView = false;
            $scope.form.submit(angular.element("form"));
            init();
            onLoadRecord();
        }


        $scope.onCancel = function () {

            if($scope.isView) {
                $uibModalInstance.close(1);
            } else {
                SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Esta acción no guardara ningún cambio, si continúa puede perder información que aún no se ha almacenado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Sí, cerrar!",
                    cancelButtonText: "No, continuar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        $uibModalInstance.close(1);
                    }
                });
            }

        };

        $scope.onFinish = function() {
            $uibModalInstance.close(1);
        }

});
