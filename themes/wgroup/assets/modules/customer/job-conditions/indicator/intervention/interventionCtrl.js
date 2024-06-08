'use strict';
/**
 * controller for Customers
 */
app.controller('CustomerJobConditionsIndicatorInterventionCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, CustomerJobConditionsIndicatorService) {

        $scope.currentIndex = 0;

        $scope.filter = {
            view: 'C'
        }

        CustomerJobConditionsIndicatorService.setView($scope.filter.view);

        var init = function() {
            $scope.entity = {
                evaluationId: CustomerJobConditionsIndicatorService.getEvaluationId(),
                classificationId: CustomerJobConditionsIndicatorService.getClassificationId(),
                customerId: $stateParams.customerId,
                classification: null,
                questions: []
            }
        }

        init();

        $scope.$on('refreshQuestions', function (event) {
            onLoadRecord();
        });


        var onLoadRecord = function() {
            $rootScope.$emit('clearIntervention', { message: 'onClearIntervention' });

            var criteria = {
                id: $scope.entity.id,
                evaluationId: $scope.entity.evaluationId,
                classificationId: $scope.entity.classificationId,
                isHistorical: $scope.filter.view === 'H',
            };

            var data = JSON.stringify(criteria);
            var req = {
                data: Base64.encode(data)
            };

            $http({
                method: 'POST',
                url: 'api/customer-jobconditions/intervention/',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $timeout(function() {
                    var questions = response.data.result;

                    $scope.entity.questions = questions;
                    CustomerJobConditionsIndicatorService.setQuestions(questions);

                    if (questions.length > 0) {
                        $scope.entity.classification = $scope.entity.questions[0].classification;
                        $scope.onSelectQuestion(questions[0], 0);
                    }
                });

            }).catch(function(e) {
                SweetAlert.swal("Error de guardado", "Error al consultar el registro. Por favor verifique los datos ingresados!", "error");
            });
        }

        onLoadRecord();

        $scope.onSelectQuestion = function(question, $index) {
            $scope.currentIndex = $index;
            CustomerJobConditionsIndicatorService.setCurrentQuestion(question);
            $rootScope.$emit('selectQuestion');
        }

        $scope.onChangeView = function() {
            $scope.currentIndex = -1;
            CustomerJobConditionsIndicatorService.setView($scope.filter.view);
            onLoadRecord();
        }

        $scope.onBack = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("dashboard", "list", 0);
            }
        }

        var onDestroyRefreshHazardList$ = $rootScope.$on('refreshHazardList', function(event, args) {
            console.log('CustomerJobConditionsIndicatorInterventionCtrl refreshHazardList');
            onLoadRecord();
        });

        $scope.$on("$destroy", function() {
            onDestroyRefreshHazardList$();
        });

    });