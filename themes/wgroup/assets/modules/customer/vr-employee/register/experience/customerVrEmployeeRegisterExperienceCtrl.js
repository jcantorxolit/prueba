'use strict';
/**
 * controller for Customers
 */
app.controller('customerVrEmployeeRegisterExperienceCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $aside, customerVrEmployeeService, ListService) {

        $scope.isView = $scope.$parent.editMode == "view";
        var currentId = customerVrEmployeeService.getId();
        $scope.applicationOptions = [];
        $scope.saveForm = $scope.saveForm ? $scope.saveForm : false;
        var $formInstance = null;

        var onInit = function () {
            $scope.entity = {
                vrEmployeeId: currentId,
                experienceList: []
            }

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        }
        onInit();

        function getList() {

            var entities = [
                { name: 'customer_vr_employee_list_options', criteria:{ customerId: $stateParams.customerId, vrEmployeeId: currentId }},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.applicationOptions = response.data.data.applicationOptions;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
        getList();


        $scope.form = {

            submit: function (form) {
                $formInstance = form;
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


        $scope.updateForm = function() {
            $scope.saveForm = true;
        }

        var save = function () {
            $scope.entity.experienceList = $scope.experienceList;
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            if($scope.saveForm) {
                return $http({
                        method: 'POST',
                        url: 'api/customer-vr-employee-experience/save',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param(req)
                    }).then(function (response) {
                        $timeout(function () {
                            if ($scope.$parent != null) {
                                $scope.$parent.navToSection("metrics", $scope.$parent.editMode);
                            }
                        });
                    }).catch(function (response) {
                        SweetAlert.swal("Error de guardado", response.data.message , "error");
                    });

            } else {
                if ($scope.$parent != null) {
                    $scope.$parent.navToSection("metrics", $scope.$parent.editMode);
                }
            }
        };

    }
);
