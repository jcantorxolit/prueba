'use strict';
/**
 * controller for Express Matrix
 */
app.controller('ModalInstanceSideCustomerDiagnosticExpressMatrixHazardCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, entity, hazardList, isView, $log, $timeout, SweetAlert,
    $http, toaster, $filter, $aside, $document, $compile, ListService) {

    $scope.totalEmployee = 0;

    $scope.isNavigationButtonsDisabled = true;
    $scope.hazardListLength = hazardList.length;
    $scope.currentHazardIndex = 0;

    $scope.onCloseModal = function () {
        //save();
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        //save();
        $uibModalInstance.dismiss('cancel');
    };

    $uibModalInstance.closed.then(function() {
        if (!$scope.isView) {
            save(false);
        }
    });

    var init = function() {
        $scope.entity = {
            id: entity.id,
            name: entity.name,
            customerId: $stateParams.customerId,
            workplaceId: entity.workplaceId,
            questionMasterList: [],
            questionList: [],
            child:[]
        }
    }

    init();

    var onLoadRecord = function () {
        if ($scope.entity.id) {

            var $criteria = {
                id: $scope.entity.id,
                customerId: $scope.entity.customerId,
                workplaceId: $scope.entity.workplaceId
            };

            $http({
                method: 'POST',
                url: 'api/customer-config-question-express-hazard',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param({
                    data: JSON.stringify($criteria)
                })
            }).then(function (response) {
                $timeout(function () {
                    $scope.entity = response.data.result;

                    validateNavigationButtonIsDisable();
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        }
    }

    onLoadRecord();


    var getQuestionList = function($includeMaster) {
        var $questions = [];

        var $hazardMasterQuestion = null;

        $scope.entity.questionMasterList.forEach(function(question) {
            $hazardMasterQuestion = question;
            if ($includeMaster) {
                $questions.push(question);
            }
        });

        $scope.entity.questionList.forEach(function(question) {
            if ($hazardMasterQuestion && $hazardMasterQuestion.rate == 'N') {
                question.rate = 'NA';
            }

            $questions.push(question);
        });

        $scope.entity.child.forEach(function(child) {
            var $childMasterQuestion = null;

            child.questionMasterList.forEach(function(question){
                $childMasterQuestion = question;
                if ($includeMaster) {
                    $questions.push(question);
                }
            });

            child.questionList.forEach(function(question) {
                if (($hazardMasterQuestion && $hazardMasterQuestion.rate == 'N') || ($childMasterQuestion && $childMasterQuestion.rate == 'N')) {
                    question.rate = 'NA';
                }
                $questions.push(question);
            });
        });

        return $questions;
    }

    var validateHazardIndex = function(entity) {
        $scope.currentHazardIndex = hazardList.findIndex(function(element) {
            return element.id == entity.id;
        });
    }

    validateHazardIndex(entity);

    var validateNavigationButtonIsDisable = function() {
        $scope.isNavigationButtonsDisabled = true;

        var $questions = getQuestionList(false);

        $scope.isNavigationButtonsDisabled = entity.questions != $questions.filter(function(question) {
            return question.rate != null;
        }).length;
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

    var save = function (showMessage) {

        var $questions = getQuestionList(true);

        var data = JSON.stringify({
            id: $scope.entity.id,
            customerId: $scope.entity.customerId,
            workplaceId: $scope.entity.workplaceId,
            questions: $questions
        });

        var req = {
            data: Base64.encode(data)
        };

        return $http({
            method: 'POST',
            url: 'api/customer-config-question-express/batch',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                showMessage = showMessage === undefined ? true : showMessage;

                if (showMessage) {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                }

                $rootScope.$emit('modalClosedEvent');

            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    };

    $scope.onChangeQuestionMaster = function(entity) {
        var $masterQuestionCount = entity.questionMasterList.length;
        var $masterQuestionYesCount = entity.questionMasterList.filter(function (question) {
            return question.rate == 'S';
        }).length;

        entity.showQuestions = $masterQuestionCount == 0 || ($masterQuestionCount == $masterQuestionYesCount);

        validateNavigationButtonIsDisable();
    }

    $scope.onChangeQuestion = function() {
        validateNavigationButtonIsDisable();
    }

    $scope.onNext = function(form) {
        if (form.$dirty) {
            save(false);
        }

        $timeout( function() {
            $scope.currentHazardIndex++;
            entity = hazardList[$scope.currentHazardIndex];
            init();
            onLoadRecord();
        }, 100 );
    }

    $scope.onPrevious = function(form) {
        if (form.$dirty) {
            save(false);
        }

        $timeout( function() {
            $scope.currentHazardIndex--;
            entity = hazardList[$scope.currentHazardIndex];
            init();
            onLoadRecord();
        }, 100 );
    }

});
