'use strict';
/**
 * controller for Express Matrix
 */
app.controller('ModalInstanceSideCustomerDiagnosticExpressMatrixInterventionDetailCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, entity, isView, $log, $timeout, SweetAlert,
    $http, toaster, $filter, $aside, $document, $compile, ListService, ExpressMatrixService, FileUploader) {

    var attachmentUploadedId = null;

    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var init = function() {
        $scope.entity = entity
    }

    init()

    getList();

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

    var initializeUploader = function() {
        var uploader = new FileUploader({
            url: 'api/customer-config-question-express-intervention/upload',
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

        $scope.fileUploader = uploader;

        $scope.fileUploader.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = {id: attachmentUploadedId};
            item.formData.push(formData);
        };

        $scope.fileUploader.onCompleteItem = function (fileItem, response, status, headers) {
            if (response && response.result) {
                $scope.entity = response.result;
                initializeDates();
            }
        };

        $scope.fileUploader.onCompleteAll = function () {
            console.info('onCompleteAll');
            $rootScope.$emit('refreshHazardList', { message: 'onRefresh Hazard List' });
        };

        $scope.fileUploader.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });
    }

    initializeUploader();

    var initializeDates = function() {
        if ($scope.entity.executionDate != null) {
            $scope.entity.executionDate = new Date($scope.entity.executionDate.date);
        }
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

    var save = function () {

        if ($scope.entity.executionDate == null || $scope.entity.executionDate == '') {
            SweetAlert.swal("El formulario contiene errores!", "Por favor selecciona la fecha de ejecución.", "error");
            return;
        }

        var data = JSON.stringify($scope.entity);

        var req = {
            data: Base64.encode(data)
        };

        return $http({
            method: 'POST',
            url: 'api/customer-config-question-express-intervention/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.entity = response.data.result;

                $scope.entity.factor = entity.factor;
                $scope.entity.subfactor = entity.subfactor;
                $scope.entity.hazard = entity.hazard;

                initializeDates();

                if ($scope.fileUploader.queue.length > 0) {
                    attachmentUploadedId = response.data.result.id;
                    $scope.fileUploader.uploadAll();
                }

                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    };

    $scope.onDownload = function ($id) {
        angular.element("#downloadIntervention")[0].src = "api/customer-config-question-express-intervention/download?id=" + $id;
    }

});
