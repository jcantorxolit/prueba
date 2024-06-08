'use strict';
/**
 * controller for Customers
 */
app.controller('CustomerJobConditionsRegisterCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout', 'SweetAlert', 'ModuleListService', 'jobConditionRegisterNavigationService',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, SweetAlert, ModuleListService, jobConditionRegisterNavigationService ) {

        $scope.isView = false;

        if ($rootScope.isCustomerUser()) {
            getJobIndicatorById();
        } else {
            $scope.section = "list";
            $scope.currentId = 0;
        }


        $scope.navToSection = function (section, title, currentId) {
            $timeout(function () {
                $scope.section = section;
                $scope.isView = title;
                $scope.currentId = currentId;
            });
        };


        function getJobIndicatorById() {
             var entities = [
                { name: 'job_condition_by_current_user' }
            ];

            ModuleListService.getDataList('/customer-jobconditions/config', entities)
                .then(function (response) {
                    var data = response.data.result.jobConditionByCurrentUser;

                    if (data && data.jobConditionId) {
                        $scope.currentId = data.jobConditionId;
                        jobConditionRegisterNavigationService.setJobConditionId(data.jobConditionId);
                        $scope.section = "edit";

                    } else if (!data.jobConditionId && data.employee) {
                        jobConditionRegisterNavigationService.setEmployeeTemp(data.employee)
                        $scope.section = "edit";

                    } else if (!data.jobConditionId && !data.employee) {
                        SweetAlert.swal("Advertencia!", "El usuario no tiene configurado un empleado del cliente actual.", "warning");

                    } else {
                        $scope.section = "list";
                        $scope.currentId = 0;
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

    }
]);
