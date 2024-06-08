'use strict';
/**
 * controller for interventions from modals
 */
app.controller('ModalInstanceSideCustomerJobConditionsIndicatorInterventionDetailEditCtrl',
    function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster,
              $filter, $aside, $document, $compile, ListService, FileUploader, entity, isView) {

    var attachmentUploadedId = entity;

    $scope.responsibleList = [];

    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
    };

    $scope.onCancel = function () {
        $uibModalInstance.close(1);
    };

    var init = function() {
        $scope.entity = {
            interventionId: entity,
            name: null,
            description: null,
            budget: null,
            responsible: null
        };
    };

    init();


    var initializeUploader = function() {
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

        $scope.fileUploader = uploader;

        $scope.fileUploader.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = {id: attachmentUploadedId};
            item.formData.push(formData);
        };


        $scope.fileUploader.onCompleteItem = function (fileItem, response, status, headers) {
            if (response && response.result) {
                $scope.entity = response.result;
                $scope.entity.interventionId = entity;
            }
        };

        $scope.fileUploader.onCompleteAll = function () {
            console.info('onCompleteAll');
        };

        $scope.fileUploader.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });
    }

    initializeUploader();



    var load = function() {
        var req = {};
        var data = JSON.stringify({ id: $scope.entity.interventionId });
        req.data = Base64.encode(data);

        return $http({
            method: 'post',
            url: 'api/customer-jobconditions/intervention/show',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            $scope.entity = response.data.result;
            $scope.entity.interventionId = entity;
        }).catch(function(e) {
            $log.error(e);
            SweetAlert.swal("Error al consultar la información.", e.data.message, "error");
        });
    }

    load();


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
        var data = JSON.stringify($scope.entity);
        var req = {
            data: Base64.encode(data)
        };

        return $http({
            method: 'POST',
            url: 'api/customer-jobconditions/intervention/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $scope.fileUploader.uploadAll();
            $scope.onCancel();
            SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        });
    };

});
