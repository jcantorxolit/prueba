'use strict';
/**
 * controller for Customers
 */
app.controller('customerVrEmployeeRegisterExperienceMetricsRegisterCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $aside, customerVrEmployeeService, ListService, ChartService) {

    var currentEmployee = customerVrEmployeeService.getEntity();
    $scope.employeeExperienceList = [];
    $scope.isView = $scope.$parent.editMode == "view";
    $scope.experienceStats = {percentage: 0, answers: 0};
    $scope.goToFormVrEmployee = true;
    $scope.disabledDate = false;
    $scope.entity = {};

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
    };

    $scope.options = {
        unit: "%",
        readOnly: true,
        displayPrevious: true,
        barCap: 25,
        trackWidth: 20,
        barWidth: 20,
        trackColor: 'rgba(92,184,92,.1)',
        barColor: '#5BC01E',
        textColor: '#000'
    };

    $scope.chart = {
        pie: { options: null },
    };

    getCharts();

    var onInit = function () {
        $scope.entity = currentEmployee;
        if ($scope.entity.registrationDate && $scope.entity.registrationDate.date) {
            $scope.entity.registrationDate = new Date($scope.entity.registrationDate.date)
        }
    }


    $scope.$watch("entity.registrationDate", function () {
        if ($scope.entity.registrationDate) {
            getList();
        }
    });

    if (currentEmployee) {
        if (currentEmployee.registrationDate) {
            $scope.disabledDate = true;
        }
        onInit();
        getList();
    } else {
        $scope.goToFormVrEmployee = false;
        currentEmployee = {
            id: $scope.$parent.currentId || 0,
            customerId: $stateParams.customerId,
            isActive: 1,
            employee: {
                id: 0,
                customerId: null,
                documentType: null,
                documentNumber: "",
                firstName: "",
                lastName: "",
                gender: null,
                logo: "",
                entity: {id: null}
            },
        };

        var req = {id: $scope.$parent.currentId};
        $http({
            method: 'GET',
            url: 'api/customer-vr-employee/get',
            params: req
        })
            .catch(function (e, code) {
            })
            .then(function (response) {
                $timeout(function () {
                    currentEmployee = response.data.result;
                    customerVrEmployeeService.setId(currentEmployee.id);
                    customerVrEmployeeService.setEntity(currentEmployee);
                    onInit();
                    getList();
                });
            }).finally(function () {
            $timeout(function () {
                $scope.loading = false;
            });
        });
    }


    function getList() {

        if ($scope.entity.registrationDate) {
            var entities = [
                {
                    name: 'customer_vr_employee_experience_list',
                    criteria: {cvreid: $scope.entity.id, registrationDate: $scope.entity.registrationDate}
                },
                {
                    name: 'customer_vr_employee_experience_stats',
                    criteria: {cvreid: $scope.entity.id, registrationDate: $scope.entity.registrationDate}
                },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.employeeExperienceList = response.data.data.employeeExperienceList;
                    $scope.experienceStats = response.data.data.experienceStats;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
    }

    $scope.onAnswerQuestions = function (entity) {
        entity.employeeExperienceList = $scope.employeeExperienceList;
        entity.registrationDate = $scope.entity.registrationDate;
        customerVrEmployeeService.setEntityExperience(entity);
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/vr-employee/register/experience-metrics/register/customer_vr_employee_register_experience_metrics_register_modal.htm",
            placement: 'right',
            windowTopClass: 'top-modal',
            size: 'lg',
            backdrop: 'static',
            controller: "customerVrEmployeeRegisterExperienceMetricsRegisterModalCtrl",
            scope: $scope
        });

        modalInstance.result.then(function () {
            if (!$scope.isView) {
                getList();
            }
        });
    }

    $scope.onBack = function (id) {
        if ($scope.$parent != null) {
            $scope.$parent.navToSection("form", $scope.$parent.editMode, $scope.entity.id);
        }
    };

    $scope.onContinue = function (id) {
        $scope.$emit('resumeTab');
    };

    $scope.onHome = function (id) {
        if ($scope.$parent != null) {
            $scope.$parent.navToSection("list", "list");
        }
    };


    function getCharts() {
        var entities = [
            {name: 'chart_pie_options', criteria: null},
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.pie.options = angular.copy(response.data.data.chartPieOptions);
                $scope.chart.pie.options.legend.position = 'bottom';
            });

    }

});