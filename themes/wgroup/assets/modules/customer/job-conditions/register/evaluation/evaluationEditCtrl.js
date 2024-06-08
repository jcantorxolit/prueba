'use strict';
/**
 * controller for Customers - Job Conditions - Evaluations
 */
app.controller('customerJobConditionsEvaluationEditCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, ListService, ModuleListService, $aside, $filter,
        jobConditionRegisterNavigationService, jobConditionRegisterService) {

        $scope.isView = $scope.$parent.isView;

        $scope.statsEvaluation = {
            percent: 0,
            countAnswers: 0,
            countQuestions: 0
        };

        $scope.workplaceList = [];
        $scope.occupationList = [];
        $scope.classificationList = [];
        $scope.workModelList = $rootScope.parameters("wg_customer_job_conditions_work_model");
        $scope.locationList = $rootScope.parameters("wg_customer_job_conditions_location");
        $scope.stateSaved = null;

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

        var initialize = function() {
            $scope.entity = {
                id: $scope.$parent.currentId || 0,
                jobConditionId: jobConditionRegisterNavigationService.getJobConditionId(),
                date: null,
                workModel: null,
                location: null,
                occupation: null,
                workplace: null,
                state: true,
            };
        };

        initialize();

        $scope.$on('$destroy', function (event) {
            console.log('pasa por acá');
            $scope.options = null;
            $scope.statsEvaluation = null;
        });


        function getList() {
            var entities = [
                { name: 'occupations', customerId: $stateParams.customerId },
                { name: 'workplaces' }
            ];

            if ($scope.entity.id == 0) {
                entities.push({ name: 'occupationEmployee', jobConditionId: jobConditionRegisterNavigationService.getJobConditionId() });
            }

            ModuleListService.getDataList('/customer-jobconditions/config', entities)
                .then(function(response) {
                    var result = response.data.result;
                    $scope.workplaceList = result.workplaces;
                    $scope.occupationList = result.occupations;

                    if (result.occupationEmployee) {
                        $scope.entity.occupation = result.occupationEmployee;
                    }

                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();

        $scope.form = {
            submit: function(form) {
                $scope.Form = form;

                if (form.$valid) {
                    save();
                    return;
                }

                var field = null,
                    firstError = null;
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
            },
            reset: function() {
                $scope.Form.$setPristine(true);
                initialize();
            }
        };

        var save = function() {
            var data = JSON.stringify($scope.entity);
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-jobconditions/evaluation/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $scope.entity = response.data.result;
                $scope.stateSaved = $scope.entity.state;
                jobConditionRegisterNavigationService.setEvaluationId($scope.entity.id);
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");

                loadClassification();
            }).catch(function(e) {
                $log.error(e);
                SweetAlert.swal("Error al guardar", e.data.message, "error");
            });
        };

        $scope.onBack = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", $scope.$parent.isView, jobConditionRegisterNavigationService.getJobConditionId());
            }
        }

        var load = function() {
            if ($scope.entity.id == 0) {
                return;
            }

            var req = {};
            var data = JSON.stringify({ id: $scope.entity.id });
            req.data = Base64.encode(data);

            return $http({
                method: 'post',
                url: 'api/customer-jobconditions/evaluation/show',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $scope.entity = response.data.result;
                $scope.stateSaved = $scope.entity.state;
                jobConditionRegisterNavigationService.setEvaluationId($scope.entity.id);

                loadClassification();

            }).catch(function(e) {
                $log.error(e);
                SweetAlert.swal("Error al consultar la información.", e.data.message, "error");
            });
        }

        $scope.onAnswerQuestions = function(classification) {
            if (classification.answered == "pending" && classification.index > 0) {
                return;
            }

            jobConditionRegisterService.setCurrentClassification(classification);

            var isView = $scope.isView;
            if ($scope.stateSaved == 0) {
                isView = true;
            }

            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/job-conditions/register/evaluation/evaluation_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: 'static',
                controller: "customerJobConditionsEvaluationModalCtrl",
                scope: $scope,
                resolve: {
                    dataSource: {
                        isView: isView
                    }
                }
            });

            modalInstance.result.then(function() {
                if (!isView) {
                    loadClassification();
                    loadStats();
                }
            });
        };


        function loadClassification() {
            var entities = [
                { name: 'classifications', evaluationId: $scope.entity.id },
            ];

            ModuleListService.getDataList('/customer-jobconditions/config', entities)
                .then(function(response) {
                    $scope.classificationList = response.data.result.classifications;
                    jobConditionRegisterService.setClassifications($scope.classificationList);

                    loadStats();

                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function loadStats() {
            var data = JSON.stringify({ id: $scope.entity.id });
            var req = {
                data: Base64.encode(data)
            };

            $http({
                method: 'POST',
                url: 'api/customer-jobconditions/evaluation/stats',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $scope.statsEvaluation.percent = response.data.result.percent;
                $scope.statsEvaluation.countAnswers = response.data.result.countAnswers;
                $scope.statsEvaluation.countQuestions = response.data.result.countQuestions;

            }).catch(function(e) {
                $log.error(e);
                SweetAlert.swal("Error al obtener las estadísticas", e.data.message, "error");
            });
        }

        load();
    });