'use strict';
/**
 * controller for VrEmployee
 */
app.controller('customerVrEmployeeExperienceConfigCtrl',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $aside, customerVrEmployeeService, ListService) {

        var $formInstance = null;
        $scope.isView = $scope.$parent.editMode == "view";
        var currentId = customerVrEmployeeService.getId();
        $scope.experienceList = [];
        $scope.applicationOptions = [];

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
                { name: 'customer_vr_employee_list', criteria: { customerId: $stateParams.customerId, vrEmployeeId: currentId } },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.experienceList = response.data.data.experienceList;
                    $scope.applicationOptions = response.data.data.applicationOptions;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
        getList();


        $scope.form = {
            submit: function (form) {
                save();
            }
        };



        var save = function () {
            $scope.experienceList.forEach(function (experience) {
                experience.scenes.map(function (scene) {
                    if (experience.isActive && experience.isActive === true) {
                        scene.application = {
                            value: "SI",
                            item: "Sí"
                        }
                    } else {
                        scene.application = {
                            value: "NO",
                            item: "No"
                        }
                    }
                })
            })
            $scope.$emit("reloadConfigVr", $scope.experienceList);
        };

    }
);