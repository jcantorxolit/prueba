'use strict';
/**
 * controller for User Profile Example
 */
app.controller('internalProjectAttachmentCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', 'SweetAlert', '$http', '$filter', '$document', '$aside',
    'FileUploader', '$localStorage', '$ngConfirm', 'ngNotify', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
            $compile, toaster, $state, $rootScope, $timeout, SweetAlert, $http, $filter, $document, $aside,
            FileUploader, $localStorage, $ngConfirm, ngNotify, ListService) {

        var log = $log;

        var attachmentUploadedId = 0;
        var $formInstance = null;

        $scope.customerList = [];

        $scope.currentCustomerId = $rootScope.isCustomer() ? $rootScope.currentUser().company : 0;

        $scope.$storage = $localStorage.$default({
            hideCustomerInternalProjectAttachmentCanceled: true
        });

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        //$scope.documentType = $scope.customer.documentsType;

        $scope.documentClassification = $rootScope.parameters("customer_document_classification");
        $scope.documentStatus = $rootScope.parameters("customer_document_status");
        $scope.isView = $scope.$parent.modeDsp == "view";

        $scope.filter = {
            selectedCustomer: null
        };

        $scope.downloadUrl = "";

        getList();

        function getList() {

            var entities = [
                {name: 'customer_document_type', value: $scope.currentCustomerId},
                {name: 'current_customer', value: $scope.currentCustomerId},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.documentType = response.data.data.customerDocumentType;
                    $scope.currentCustomer = response.data.data.currentCustomer;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var onInit = function() {
            $scope.attachment = {
                id: 0,
                customerInternalProjectId: 0,
                type: null,
                classification: null,
                status: $scope.documentStatus ? $scope.documentStatus[0] : null,
                version: 1,
                description: null
            };

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        }

        onInit();

        var onLoadRecord = function (id) {
            $http({
                method: 'GET',
                url: 'api/customer-internal-project-document/get',
                params: {
                    id: id
                }
            }).catch(function (e, code) {
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Anexo no encontrado", "error");
                        $timeout(function () {
                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del anexo", "error");
                    }
                }).then(function (response) {
                    $timeout(function () {
                        $scope.attachment = response.data.result;
                        $scope.attachment.version = parseInt($scope.attachment.version) + 1;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        };

        $scope.onSelectCustomer = function() {
            $scope.currentCustomerId = $scope.filter.selectedCustomer.value;
            onInit();
            $scope.reloadData();
        }

        $scope.onSearchCustomer = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideProjectInternalSearchCustomerCtrl',
                scope: $scope,
                windowTopClass: 'top-modal',
                resolve: {
                }
            });
            modalInstance.result.then(function (customer) {
                var result = $filter('filter')($scope.customerList, {id: customer.id});

                if (result.length == 0) {
                    $scope.customerList.push(customer);
                }

                $scope.filter.selectedCustomer = customer;
                $scope.onSelectCustomer();
            }, function() {

            });
        };

        $scope.onClearCustomer = function() {
            $scope.filter.selectedCustomer = null;
            $scope.currentCustomerId = 0;
            onInit();
            $scope.reloadData();
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
                url: 'api/customer-internal-project-document/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    if (uploader.queue.length > 0) {
                        attachmentUploadedId = response.data.result.id;
                        uploader.uploadAll();
                    } else {
                        SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                        $scope.reloadData();
                        $scope.onClear();
                    }
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });

        };

        var uploader = $scope.uploader = new FileUploader({
            url: 'api/customer-internal-project-document/upload',
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
            SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
        };

        $scope.dtInstanceCustomerInternalProjectDocument = {};
		$scope.dtOptionsCustomerInternalProjectDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = 'document';
                    d.customerId = $scope.filter.selectedCustomer ? $scope.filter.selectedCustomer.id : $scope.currentCustomerId;
                    if ($scope.$storage.hideCustomerInternalProjectAttachmentCanceled) {
                        d.statusCode = '2'
                    }
                    return JSON.stringify(d);
                },
                url: 'api/customer-internal-project-document',
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
            .withOption('serverSide', true).withOption('processing', true)
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

        $scope.dtColumnsCustomerInternalProjectDocument = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var url = data.documentUrl ? data.documentUrl : '';
                    var downloadRoute = 'api/customer-internal-project-document/download?id=' + data.id;

                    var actions = "";
                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs downloadDocumentRow lnk" href="' + downloadRoute + '" uib-tooltip="Descargar anexo" data-id="' + data.id + '" >' +
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

                    if ($rootScope.can("clientes_anexo_invalidate")) {
                        actions += deleteTemplate;
                    }

                    return isButtonVisible ? actions : "";
                }),

            DTColumnBuilder.newColumn('documentType').withTitle("Tipo de documento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('createdAt').withTitle("Fecha Creación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('createdBy').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
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

            angular.element("#dtCustomerInternalProjectDocument a.delDocumentRow").on("click", function () {
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
                                    url: 'api/customer-internal-project-document/delete',
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

        $scope.dtInstanceCustomerInternalProjectDocumentCallback = function(instance) {
            $scope.dtInstanceCustomerInternalProjectDocument = instance;
        }

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerInternalProjectDocument.reloadData();
        };

        $scope.onEdit = function (id) {
            $scope.isView = false;
            onLoadRecord(id);
        };

        $scope.onShowCancelledChange = function () {
            $scope.reloadData();
        }

        $scope.onClear = function () {
            onInit();
        };


    }
]);
