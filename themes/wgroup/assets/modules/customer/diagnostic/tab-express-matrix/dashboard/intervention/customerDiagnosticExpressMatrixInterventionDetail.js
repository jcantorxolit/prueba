'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticExpressMatrixInterventionDetailCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    '$document', '$location', '$translate', '$aside', 'ListService', 'ExpressDashboardService', 'FileUploader',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $document, $location, $translate, $aside, ListService, ExpressDashboardService, FileUploader) {

        console.log('customerDiagnosticExpressMatrixInterventionDetailCtrl');

        var attachmentUploadedId = 0;

        $scope.minDate = new Date();
        $scope.minDate.setDate($scope.minDate.getDate() - 1);

        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        var onDestroySelectedQuestion$ = $rootScope.$on('selectQuestion', function(event, args) {
            console.log('customerDiagnosticExpressMatrixInterventionDetailCtrl selectQuestion');
            init();
            getList();
        });

        var onDestroyClearIntervention$ = $rootScope.$on('clearIntervention', function(event, args) {
            console.log('customerDiagnosticExpressMatrixInterventionDetailCtrl clearIntervention');
            $scope.interventionList = [];
            $scope.filter.selectedQuestion = null;
        });

        $scope.$on("$destroy", function() {
            onDestroySelectedQuestion$();
            onDestroyClearIntervention$();
        });

        var init = function() {
            $scope.filter = {
                selectedView: ExpressDashboardService.getView(),
                selectedWorkplace: ExpressDashboardService.getWorkplace(),
                selectedHazard: ExpressDashboardService.getHazard(),
                selectedQuestion: ExpressDashboardService.getQuestion(),
            }

            $scope.entity = {
                id: $scope.filter.selectedQuestion ? $scope.filter.selectedQuestion.id : 0,
                name: null,
                customerId: $stateParams.customerId,
                workplaceId: $scope.filter.selectedWorkplace ? $scope.filter.selectedWorkplace.id : 0,
                questionList: []
            }
        }

        var initializeDates = function() {
            if ($scope.interventionList !== undefined && $scope.interventionList !== null) {
                angular.forEach($scope.interventionList, function(model, key) {
                    if (model.executionDate != null) {
                        model.executionDate = new Date(model.executionDate.date);
                    }

                    initializeUploader();
                });
            }
        }

        var initializeUploader = function() {

            if ($scope.fileUploader === undefined || $scope.fileUploader == null) {
                $scope.fileUploader = [];
            }

            var uploader = new FileUploader({
                url: 'api/customer-config-question-express-intervention/upload',
                formData: [],
                removeAfterUpload: true
            });

            uploader.filters.push({
                name: 'enforceMaxFileSize',
                fn: function(item) {
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

            $scope.fileUploader[$scope.fileUploader.length - 1].onBeforeUploadItem = function(item) {
                console.info('onBeforeUploadItem', item);
                var formData = { id: attachmentUploadedId };
                item.formData.push(formData);
            };

            $scope.fileUploader[$scope.fileUploader.length - 1].onCompleteItem = function(fileItem, response, status, headers) {
                if (response && response.result) {
                    var $index = $scope.interventionList.findIndex(function(element) {
                        return element.id == response.result.id;
                    });

                    if ($index != -1) {
                        $scope.interventionList[$index] = response.result;

                        if ($scope.interventionList[$index].executionDate != null) {
                            $scope.interventionList[$index].executionDate = new Date($scope.interventionList[$index].executionDate.date);
                        }
                    }
                }
            };

            $scope.fileUploader[$scope.fileUploader.length - 1].onCompleteAll = function() {
                console.info('onCompleteAll');
                $rootScope.$emit('refreshHazardList', { message: 'onRefresh Hazard List' });
            };

            $scope.fileUploader[$scope.fileUploader.length - 1].filters.push({
                name: 'customFilter',
                fn: function(item /*{File|FileLikeObject}*/ , options) {
                    return this.queue.length < 10;
                }
            });
        }

        function getList() {

            var $criteria = {
                id: $scope.entity.id,
                customerId: $scope.entity.customerId,
                workplaceId: $scope.entity.workplaceId,
                isHistorical: $scope.filter.selectedView == 'H'
            };

            var entities = [
                { name: 'customer_express_matrix_question_intervention_list', criteria: $criteria },
                { name: 'customer_related_agent_user', value: $stateParams.customerId },
            ];

            ListService.getDataList(entities)
                .then(function(response) {
                    $scope.interventionList = response.data.data.customerExpressMatrixQuestionInterventionList;
                    $scope.responsibleList = response.data.data.customerRelatedAgentAndUserList;

                    initializeDates();
                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.onAddIntervention = function() {
            if ($scope.interventionList == null) {
                $scope.interventionList = [];
            }

            $scope.interventionList.push({
                id: 0,
                customerId: $stateParams.customerId,
                customerQuestionExpressId: $scope.entity.id,
                name: '',
                description: '',
                responsible: null,
                amount: 0,
                executionDate: null,
                isClosed: false,
                status: false,
                files: [],
                isOpen: true
            });

            initializeUploader();
        };

        $scope.onDownload = function($id) {
            angular.element("#downloadIntervention")[0].src = "api/customer-config-question-express-intervention/download?id=" + $id;
        }

        $scope.onChangeExecutionDate = function() {

        }

        $scope.onChangeView = function() {

        }

        $scope.onSubmit = function(scope, $index, intervention) {
            var form = scope['FormIntervention' + $index];

            if (form.$invalid) {

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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                save($index, intervention)
            }
        }

        $scope.getForm = function($index) {
            return this['Form' + $index];
        }

        var save = function($index, intervention) {

            if (intervention.executionDate == null || intervention.executionDate == '') {
                SweetAlert.swal("El formulario contiene errores!", "Por favor selecciona la fecha de ejecución.", "error");
                return;
            }

            var data = JSON.stringify(intervention);

            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-config-question-express-intervention/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $timeout(function() {
                    $scope.interventionList[$index] = response.data.result;

                    if ($scope.interventionList[$index].executionDate != null) {
                        $scope.interventionList[$index].executionDate = new Date($scope.interventionList[$index].executionDate.date);
                    }

                    if ($scope.fileUploader[$index].queue.length > 0) {
                        attachmentUploadedId = response.data.result.id;
                        $scope.fileUploader[$index].uploadAll();
                    } else {
                        $rootScope.$emit('refreshHazardList', { message: 'onRefresh Hazard List' });
                    }

                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                });
            }).catch(function(e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function() {

            });
        };

    }
]);
