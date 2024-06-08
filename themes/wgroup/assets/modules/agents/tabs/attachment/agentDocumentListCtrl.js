'use strict';
/**
 * controller for Customers
 */
app.controller('agentDocumentListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','SweetAlert','$http', '$filter', '$document', 'FileUploader', '$localStorage',
    '$ngConfirm', 'ngNotify',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
              $compile, toaster, $state, $rootScope,$timeout, SweetAlert, $http, $filter, $document, FileUploader, $localStorage, $ngConfirm, ngNotify) {

        var log = $log;

        var attachmentUploadedId = 0;
        log.info("loading..customerDocumentListCtrl ");

        //hideAgentAttachmentCanceled
        $scope.$storage = $localStorage.$default({
            hideAgentAttachmentCanceled: true
        });

        // parametros para seguimientos
        $scope.agents = $rootScope.agents();
        $scope.documentType =  $rootScope.parameters("agent_document_type");
        $scope.documentClassification =  $rootScope.parameters("agent_document_classification");
        $scope.documentStatus =  $rootScope.parameters("agent_document_status");
        $scope.isView =  $scope.$parent.modeDsp == "view";
        $scope.customerId = $stateParams.customerId;
        $scope.downloadUrl = "";

        var init = function() {
            $scope.attachment = {
                id : 0,
                created_at : $filter('date')(new Date(), "dd/MM/yyyy HH:mm"),
                agentId : $stateParams.agentId,
                isSelected : 1,
                agent : null,
                type :  null,
                classification :  null,
                status :  $scope.documentStatus.length > 0 ? $scope.documentStatus[0] : null,
                startDate : null,
                endDate : null,
                version : 1,
                description : ""
            };
        }

        init();

        var loadRecord = function(id) {
            // se debe cargar primero la información actual del cliente..
            var req = {
                id: id
            };

            $http({
                method: 'GET',
                url: 'api/agent/document',
                params: req
            })
                .catch(function(e, code){
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () { $state.go(messagered); }, 3000);
                    } else if (code == 404)
                    {
                        SweetAlert.swal("Información no disponible", "Anexo no encontrado", "error");
                        $timeout(function () {
                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del anexo", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function(){
                        $scope.attachment = response.data.result;
                        $scope.attachment.version = parseInt($scope.attachment.version) + 1;
                        initializeDates();
                    });

                }).finally(function () {
                    $timeout(function(){
                        afterInit();
                        $scope.loading =  false;
                    }, 400);
                });


        };

        var initializeDates = function() {
            if ($scope.attachment.startDate != null && $scope.attachment.startDate != "") {
                $scope.attachment.startDate =  new Date($scope.attachment.startDate.date);
            }

            if ($scope.attachment.endDate != null &&  $scope.attachment.endDate != "") {
                $scope.attachment.endDate =  new Date($scope.attachment.endDate.date);
            }
        }

        $scope.master = $scope.attachment;

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
                    //log.info($scope.customer);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;

                } else {
                    save();
                }

            },
            reset: function (form) {

                $scope.attachment = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.attachment);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/agent/document/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function(){
                    if (uploader.queue.length > 0) {
                        attachmentUploadedId = response.data.result.id;
                        uploader.uploadAll();
                    } else {
                        SweetAlert.swal("Registro", "La información se ha guardado satisfactoriamente", "success");
                        $scope.reloadData();
                        $scope.onClear();
                    }
                });
            }).catch(function(e){
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function(){
            });

        };

        var uploader = $scope.uploader = new FileUploader({
            url: 'api/agent/document/upload',
            formData:[],
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
            var formData = { id: attachmentUploadedId };
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
            SweetAlert.swal("Registro", "La información se ha guardado satisfactoriamente", "success");
            $scope.reloadData();
            $scope.onClear();
        };

        $scope.onClear = function(){
            init();
            $scope.isView = false;
        };

		$scope.dtInstanceDocument = {};
		$scope.dtOptionsDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "document";
                    d.agent_id = $stateParams.agentId;
                    d.hideCanceled = $scope.$storage.hideAgentAttachmentCanceled ? 1 : 0;
                    return d;
                },
                url: 'api/agent/documents',
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

        $scope.dtColumnsDocument = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var url = data.document != null ? data.document.path : "";
                    var downloadRoute = 'api/agent/document/download?id=' + data.id;

                    var actions = "";
                    var downloadTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="' + downloadRoute + '" uib-tooltip="Descargar anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-download"></i></a> ';
                    var openTemplate = '<a class="btn btn-info btn-xs viewRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-folder-open-o"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Anular anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-ban"></i></a> ';

                    if (url != "") {
                        actions += openTemplate;
                    }

                    if (url != "") {
                        actions += downloadTemplate;
                    }

                    actions += deleteTemplate;

                    return actions;
                }),
            DTColumnBuilder.newColumn('type.item').withTitle("Tipo de documento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('classification.item').withTitle("Clasificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('startDateText').withTitle("Fecha de Inicio Vigencia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('endDateText').withTitle("Fecha de Finalización Vigencia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status.item').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch  (data)
                    {
                        case "Vigente":
                            label = 'label label-success';
                            break;

                        case "Anulado":
                            label = 'label label-danger';
                            break;
                    }

                    var status = '<span class="' + label +'">' + data + '</span>';

                    return status;
                })
        ];

        var loadRow = function () {

            angular.element("#dtDocument a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                jQuery("#download")[0].src = "api/document/download?id=" + id;
            });

            angular.element("#dtDocument a.delRow").on("click", function () {
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
                                $scope.isView=false;
                                loadRecord(id);
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
                                    url: 'api/customer/contract-detail-document/delete',
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

        $scope.dtInstanceDocumentCallback = function (instace) {
            $scope.dtInstanceDocument = instace;
        }

        $scope.reloadData = function () {
            $scope.dtInstanceDocument.reloadData();
        };

        $scope.onEdit = function(id){
            $scope.attachment.id = id;
            $scope.isView=false;
            loadRecord(id);
        };

        $scope.onShowCancelledChange = function()
        {
            request.hideCanceled = $scope.$storage.hideAgentAttachmentCanceled ? 1 : 0;
            $scope.reloadData();
        }

    }]);
