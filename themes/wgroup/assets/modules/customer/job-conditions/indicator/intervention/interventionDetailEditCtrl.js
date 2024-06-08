'use strict';
/**
 * controller for Customers
 */
app.controller('CustomerJobConditionsIndicatorInterventionDetailEditCtrl',
    function ($scope, $stateParams, $log, $compile, toaster, $state, $rootScope, $timeout, $http, SweetAlert,
              $document, $location, $translate, $aside, ListService, FileUploader, CustomerJobConditionsIndicatorService) {

        var attachmentUploadedId = 0;

        $scope.responsibleList = [];

        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        var onDestroySelectedQuestion$ = $rootScope.$on('selectQuestion', function (event, args) {
            init();
            getList();
        });

        var onDestroyClearIntervention$ = $rootScope.$on('clearIntervention', function (event, args) {
            $scope.interventionList = [];
            $scope.filter.selectedQuestion = null;
            $scope.filter.selectedView = CustomerJobConditionsIndicatorService.getView();
        });

        $scope.$on("$destroy", function() {
            onDestroySelectedQuestion$();
            onDestroyClearIntervention$();
        });


        var init = function() {
            $scope.filter = {
                selectedView: CustomerJobConditionsIndicatorService.getView(),
                selectedQuestion: CustomerJobConditionsIndicatorService.getCurrentQuestion() || null,
            }

            $scope.entity = {
                id: $scope.filter.selectedQuestion.selfEvaluationAnswerId,
                name: null
            }

            $scope.interventionList = $scope.filter.selectedQuestion.interventions;
            initializeUploader();
        }


        var initializeUploader = function() {

            if ($scope.fileUploader === undefined || $scope.fileUploader == null) {
                $scope.fileUploader = [];
            }

            var uploader = new FileUploader({
                url: 'api/customer-jobconditions/intervention/upload',
                formData: [],
                removeAfterUpload: true
            });

            uploader.filters.push({
                name: 'enforceMaxFileSize',
                fn: function (item) {
                    return item.size <= 10485760; // 10 MiB to bytes
                }
            });

            uploader.filters.push({
                name: 'enforceExtension',
                fn: function(item) {
                    return (/\.(jpg|jpeg|png|pdf|doc|docx|xls|xlsx)$/i).test(item.name)
                }
            });

            $scope.fileUploader.push(uploader);

            $scope.fileUploader[$scope.fileUploader.length - 1].onBeforeUploadItem = function (item) {
                console.info('onBeforeUploadItem', item);
                var formData = {id: attachmentUploadedId};
                item.formData.push(formData);
            };

            $scope.fileUploader[$scope.fileUploader.length - 1].onCompleteItem = function (fileItem, response, status, headers) {
                if (response && response.result) {
                    $scope.$emit('refreshQuestions');
                }
            };

            $scope.fileUploader[$scope.fileUploader.length - 1].onCompleteAll = function () {
                console.info('onCompleteAll');
            };

            $scope.fileUploader[$scope.fileUploader.length - 1].filters.push({
                name: 'customFilter',
                fn: function (item/*{File|FileLikeObject}*/, options) {
                    return this.queue.length < 10;
                }
            });
        }
        initializeUploader();

        $scope.onAddIntervention = function () {
            if ($scope.interventionList == null) {
                $scope.interventionList = [];
            }

            var formNewPlanOpened = $scope.interventionList.some(function (intervention) {
                return intervention.interventionId === 0;
            });

            if (formNewPlanOpened) {
                return;
            }

            $scope.interventionList.push({
                interventionId: 0,
                selfEvaluationAnswerId: $scope.entity.id,
                name: '',
                description: '',
                responsible: null,
                budget: 0,
                executionDate: null,
                isClosed: false,
                isClosedOriginal: false,
                status: false,
                files: [],
                isOpen: true
            });

            initializeUploader();
        };

        $scope.onDownload = function ($id) {
            angular.element("#downloadIntervention")[0].src = "api/customer-config-question-express-intervention/download?id=" + $id;
        }

        $scope.onSubmit = function(scope, $index, intervention) {
            var form = scope['FormIntervention' + $index];

            if (form.$valid) {
                save($index, intervention);
                return;
            }

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
        }

        $scope.getForm = function($index) {
            return this['Form' + $index];
        }

        var save = function ($index, intervention) {
            var data = JSON.stringify(intervention);
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-jobconditions/intervention/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.interventionList[$index] = response.data.result;

                    if ($scope.fileUploader[$index].queue.length > 0) {
                        attachmentUploadedId = response.data.result.interventionId;
                        $scope.fileUploader[$index].uploadAll();
                    } else {
                        $scope.$emit('refreshQuestions');
                    }

                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                });

            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            });
        };


        function getList() {
            var entities = [
                {  name: 'customer_related_agent_user',  value: $stateParams.customerId },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.responsibleList = response.data.data.customerRelatedAgentAndUserList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();
    }
);
