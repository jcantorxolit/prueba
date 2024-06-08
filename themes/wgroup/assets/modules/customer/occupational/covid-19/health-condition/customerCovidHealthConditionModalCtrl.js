'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidHealthConditionModalCtrl',
function ($scope, $stateParams, $log, $timeout, $http, SweetAlert, $document, CustomerCovidService, ListService, $uibModalInstance, $filter) {

        var $formInstance = null;
        var currentId = CustomerCovidService.getId();
        CustomerCovidService.setDailyId($scope.modalDailyId);
        $scope.riskLevelList = [];
        $scope.showBreathing = false;
        $scope.disableSymptoms = true;
        $scope.autoSave = false;

        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };


        var onInit = function () {
            $scope.entity = {
                id: $scope.modalDailyId,
                customerCovidHeadId: currentId,
                registrationDate: new Date(),
                riskLevel: {riskLevelValue: "B", riskLevelText: "Bajo", riskLevelColor: "#5CB85C"},
                origin: "MANUAL",
                questionList: [],
                conditionalRiskLevel: null
            }

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        }
        onInit();


        function getList() {

            var entities = [
                {name: 'covid_question_list', value: null},
                {name: 'covid_question_group_list', value: null},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.questionList = response.data.data.covidQuestionList;
                    $scope.questionGroupList = response.data.data.covidQuestionGroupList;

                    if ($scope.questionList && $scope.questionList.length > 0) {
                        $scope.entity.questionList = $scope.questionList.map(function(item) {
                            return {
                                id: 0,
                                customerCovidId: 0,
                                covidQuestionCode: item.code,
                                name: item.sort + " " + item.name,
                                isActive: false,
                                observation: null,
                                riskLevelText: item.riskLevelText,
                                riskLevelValue: item.riskLevelValue,
                                riskLevelColor: item.riskLevelColor,
                                riskLevelPriority: parseInt(item.riskLevelPriority)
                            };
                        })
                    }

                    $scope.onLoadRecord()
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
        getList();

        $scope.onLoadRecord = function () {
            if ($scope.entity.id != 0) {
                $http({
                    method: 'GET',
                    url: 'api/customer-covid-daily/get',
                    params: { id: $scope.entity.id }
                })
                .catch(function (e, code) {
                })
                .then(function (response) {
                    $scope.entity = response.data.result;
                    $scope.entity.registrationDate = new Date($scope.entity.registrationDate.date);
                    if($scope.autoSave) {
                        checkRiskLevel();
                    }
                }).finally(function () {
                    $scope.loading = false;
                    $document.scrollTop(40, 2000);
                });
            }
        }

        $scope.onChangeAnswer = function(item) {
            checkRiskLevel();
        }

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
                    checkRiskLevel();
                    setTimeout(function(){
                        $scope.onSave();
                    },500)
                }

            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        var checkRiskLevel = function() {
            var $riskLevelList = [];
            if ($scope.entity.conditionalRiskLevel) {
                var $riskLevel = $filter('filter')($scope.questionGroupList, { riskLevelValue: $scope.entity.conditionalRiskLevel }, true);
                if($riskLevel && $riskLevel.length) {
                    $scope.entity.riskLevel = $riskLevel[0];
                }
            } else {

                angular.forEach($scope.questionList, function ($question, key) {
                    var $answer = $scope.entity.questionList.find(function(item) {
                        return item.covidQuestionCode == $question.code;
                    });

                    if ($answer && $answer.isActive) {
                        $question.riskLevelPriority = parseInt($question.riskLevelPriority);
                        $riskLevelList.push($question);
                    }
                });

                var $riskLevelCountM = $filter('filter')($riskLevelList, { riskLevelValue: "M"}, true);
                if($riskLevelCountM && $riskLevelCountM.length >= 2) {
                    var $riskLevel = $filter('filter')($scope.questionGroupList, { riskLevelValue: "A" }, true);
                    if($riskLevel && $riskLevel.length){
                        $scope.entity.riskLevel = $riskLevel[0];
                        return;
                    }
                }

                var $riskLevelCountB = $filter('filter')($riskLevelList, { riskLevelValue: "B"}, true);
                if($riskLevelCountB && $riskLevelCountB.length >= 2 && $riskLevelCountM && $riskLevelCountM.length == 1){
                    var $riskLevel = $filter('filter')($scope.questionGroupList, { riskLevelValue: "A" }, true);
                    if($riskLevel && $riskLevel.length){
                        $scope.entity.riskLevel = $riskLevel[0];
                        return;
                    }
                }

                if( ($riskLevelCountB && $riskLevelCountB.length >= 3) || $riskLevelCountM.length == 1 && $riskLevelCountB.length == 1 ){
                    var $riskLevel = $filter('filter')($scope.questionGroupList, { riskLevelValue: "M" }, true);
                    if($riskLevel && $riskLevel.length){
                        $scope.entity.riskLevel = $riskLevel[0];
                        return;
                    }
                }

                var $riskLevel = $filter('filter')($scope.questionList, { riskLevelValue: "B" }, true);
                if ($riskLevel && $riskLevel.length) {
                    $scope.entity.riskLevel = $riskLevel[0];
                }
            }

            if($scope.autoSave){
                $scope.onSave();
            }
        }

        $scope.onSave = function () {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-covid-daily/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $scope.entity = response.data.result;
                $scope.entity.registrationDate = new Date($scope.entity.registrationDate.date);
                CustomerCovidService.setDailyId($scope.entity.id);
                if(!$scope.autoSave) {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                }
                $scope.autoSave = false;
            }).catch(function (response) {
                if(!$scope.autoSave) {
                    SweetAlert.swal("Error de guardado", response.data.message , "error");
                }
            }).finally(function () {
            });
        };

        $scope.onCloseModal = function () {
            $uibModalInstance.close(1);
        };

        $scope.$on('realoadQuestion', function (event, data) {
            $scope.onLoadRecord();
            $scope.autoSave = true;
        });

    }
);
