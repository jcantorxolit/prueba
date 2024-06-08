'use strict';
/**
 * controller for Customers
 */
app.controller('customerRoadSafetyAttachment40595Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'FileUploader', '$localStorage', '$aside', 'ListService', '$ngConfirm',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, FileUploader,
              $localStorage, $aside, ListService, $ngConfirm) {

        console.log("editMode attachment", $scope.$parent.editMode);

        $scope.isVisible =  $scope.$parent.editMode != 'view';

        var $formInstance = null;

        var attachmentUploadedId = 0;

        $scope.currentId = $scope.$parent.currentId;

        $scope.documentClassification = $rootScope.parameters("customer_document_classification");
        $scope.documentStatus = $rootScope.parameters("customer_document_status");

        $scope.$storage = $localStorage.$default({
            hideMinimumStandardAttachmentCanceled: true
        });

        getList();

        function getList() {

            var entities = [
                {name: 'customer_document_type', value: $stateParams.customerId}
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.documentType =response.data.data.customerDocumentType;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.currentId);
            }
        };

        $scope.onClear = function () {
            onInit();
        };

        var onInit = function() {

            $scope.attachment = {
                id: 0,
                customerRoadSafetyItemId: 0,
                type: null,
                classification: null,
                description: "",
                status: $scope.documentStatus ? $scope.documentStatus[0] : null,
                version: 1
            };

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        }

        onInit();

        var onLoadRecord = function (id) {

            var req = {
                id: id
            };

            $http({
                method: 'GET',
                url: 'api/customer-road-safety-item-document-40595/get',
                params: req
            })
                .catch(function (e, code) {

                })
                .then(function (response) {
                    $timeout(function () {
                        $scope.attachment = response.data.result;
                        $scope.attachment.version = parseInt($scope.attachment.version) + 1;
                    });
                }).finally(function () {
                });
        };

        var uploader = $scope.uploader = new FileUploader({
            url: 'api/customer-road-safety-item-document-40595/upload',
            formData: [],
            removeAfterUpload: true
        });

        uploader.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS

        uploader.onWhenAddingFileFailed = function (item/*{File|FileLikeObject}*/, filter, options) {
            console.info('onWhenAddingFileFailed', item, filter, options);
        };
        uploader.onAfterAddingFile = function (fileItem) {
            console.info('onAfterAddingFile', fileItem);
        };
        uploader.onAfterAddingAll = function (addedFileItems) {
            console.info('onAfterAddingAll', addedFileItems);
        };
        uploader.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = {id: attachmentUploadedId};
            item.formData.push(formData);
        };
        uploader.onProgressItem = function (fileItem, progress) {
            console.info('onProgressItem', fileItem, progress);
        };
        uploader.onProgressAll = function (progress) {
            console.info('onProgressAll', progress);
        };
        uploader.onSuccessItem = function (fileItem, response, status, headers) {
            console.info('onSuccessItem', fileItem, response, status, headers);
        };
        uploader.onErrorItem = function (fileItem, response, status, headers) {
            console.info('onErrorItem', fileItem, response, status, headers);
        };
        uploader.onCancelItem = function (fileItem, response, status, headers) {
            console.info('onCancelItem', fileItem, response, status, headers);
        };
        uploader.onCompleteItem = function (fileItem, response, status, headers) {
            console.info('onCompleteItem', fileItem, response, status, headers);
        };
        uploader.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.reloadData();
            $scope.onClear();
        };

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
                    SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                    save();
                }

            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.attachment);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-road-safety-item-document-40595/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    if (uploader.queue.length > 0) {
                        attachmentUploadedId = response.data.result.id;
                        uploader.uploadAll();
                    } else {
                        $scope.reloadData();
                        $scope.onClear();
                    }
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.reloadData();
            });

        };

        $scope.dtOptionsMinimumStandardDocument40595 = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerRoadSafetyId = $scope.currentId;
                    //d.program = cycle.abbreviation;
                    if ($scope.$storage.hideMinimumStandardAttachmentCanceled) {
                        d.statusCode = '2'
                    }
                    return JSON.stringify(d);
                },
                url: 'api/customer-road-safety-item-document-40595',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })

            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsMinimumStandardDocument40595 = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var url = data.documentUrl ? data.documentUrl : '';
                    var downloadUrl = "api/customer-road-safety-item-document-40595/download?id=" + data.id;

                    var actions = "";
                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs downloadDocumentRow lnk" href="' + downloadUrl +'" uib-tooltip="Descargar anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-download"></i></a> ';
                    var viewTemplate = '<a class="btn btn-info btn-xs openDocumentRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-folder-open-o"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delDocumentRow lnk" href="#" uib-tooltip="Anular anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-ban"></i></a> ';

                    var isButtonVisible = false;

                    if (data.protectionType == null) {
                        isButtonVisible = true;
                    } else if (data.protectionType == "public") {
                        isButtonVisible = true;
                    } else if (data.protectionType == "private" && data.hasPermission == 1) {
                        isButtonVisible = true;
                    }

                    if ($rootScope.can("clientes_anexo_open")) {
                        if (url != '') {
                            actions += viewTemplate;
                        }
                    }

                    if ($rootScope.can("clientes_anexo_download")) {
                        if (url != '') {
                            actions += editTemplate;
                        }
                    }

                    if ($rootScope.can("clientes_anexo_invalidate") && $scope.isVisible && data.statusCode != '2') {
                        actions += deleteTemplate;
                    }


                    return isButtonVisible ? actions : "";
                }),
            DTColumnBuilder.newColumn('documentType').withTitle("Tipo de documento").withOption('width', 200),
            DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200),
            DTColumnBuilder.newColumn('createdAt').withTitle("Fecha Creación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch (data) {
                        case "Vigente":
                            label = 'label label-success';
                            break;

                        case "Anulado":
                            label = 'label label-danger';
                            break;
                    }

                    var status = '<span class="' + label + '">' + data + '</span>';

                    return status;
                })
        ];

        var loadRow = function () {

            $("#dtMinimumStandardDocument40595 a.editRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");
                //$scope.editTracking(id);
                if (url == "") {
                    SweetAlert.swal("Error en la descarga", "No existe un anexo para descargar", "error");
                }
                else {
                    jQuery("#downloadDocument")[0].src = "api/customer-road-safety-item-document-40595/download?id=" + id;
                }
            });

            angular.element("#dtMinimumStandardDocument40595 a.delDocumentRow").on("click", function () {
                var id = angular.element(this).data("id");

                $ngConfirm({
                    boxWidth: '35%',
                    useBootstrap: false,
                    animation: 'bottom',
                    closeAnimation: 'scale',
                    title: 'Está seguro?',
                    content: '<strong>Anulará</strong> el anexo seleccionado',
                    scope: $scope,
                    type: 'red',
                    typeAnimated: true,
                    buttons: {
                        cancel: {
                            text: 'Anular y cambiar versión',
                            btnClass: 'btn-primary',
                            action: function(scope, button){
                                $scope.attachment.id = id;
                                $scope.isView = false;
                                onLoadRecord(id);
                                toaster.pop('info', 'Anulación Anexo', 'El anexo se cargó satisfactoriamente, seleccione una nueva versión del documento');
                            }
                        },
                        fullCancel: {
                            text: 'Anular definitivamente',
                            btnClass: 'btn-red',
                            action: function(scope, button) {
                                var req = {};
                                req.id = id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-road-safety-item-document-40595/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Anexo anulado satisfactoriamente", "info");
                                    $scope.reloadData();
                                }).catch(function (e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function () {
                                });
                            }
                        },
                        closeAll:  {
                            text: 'Cancelar',
                            btnClass: 'btn-default',
                            action: function(scope, button){

                            }
                        },
                    }
                });
            });
        };

        $scope.dtInstanceMinimumStandardDocument40595Callback = function (instance) {
            $scope.dtInstanceMinimumStandardDocument40595 = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceMinimumStandardDocument40595.reloadData();
        };

        $scope.onShowCancelledChange = function () {
            $scope.reloadData();
        }

    }
]);
