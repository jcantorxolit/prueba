'use strict';
/**
  * controller for Customers
*/
app.controller('customerTrackingListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','SweetAlert','$http', '$filter', '$document',
    '$aside', '$uibModal',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
          $compile, toaster, $state, $rootScope,$timeout, SweetAlert, $http, $filter, $document, $aside, $uibModal) {

    var log = $log;

    $scope.agents = $rootScope.agents();
    var isCustomer = $rootScope.isCustomer();

    $scope.dtInstanceTracking = {};
    $scope.dtOptionsTracking = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {

                d.customerId = $stateParams.customerId;

                if (isCustomer) {
                    d.isVisible = 1;
                }

                if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                    d.filter =
                        {
                            filters: $.map($scope.audit.filters, function (filter) {
                                return {
                                    field: filter.field.name,
                                    operator: filter.criteria.value,
                                    condition: filter.condition.value,
                                    value: filter.value
                                };
                            })
                        };
                }
                return JSON.stringify(d);
            },
            url: 'api/customer-tracking',
            contentType: 'application/json',
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

    $scope.dtColumnsTracking = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                var documentTemplate = '<a class="btn btn-success btn-xs documentRow lnk" href="#" uib-tooltip="Anexos" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-paperclip"></i></a> ';                    

                if($rootScope.can("seguimiento_view")){
                    actions += viewTemplate;
                }

                if($rootScope.can("seguimiento_edit")){
                    actions += editTemplate;
                }

                if($rootScope.can("seguimiento_delete")){
                    actions += deleteTemplate;
                }

                if($rootScope.can("seguimiento_document")){
                    actions += documentTemplate;
                }

                return actions;
            }),
        DTColumnBuilder.newColumn('id').withTitle("ID").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('type').withTitle("Tipo de Seguimiento").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('responsible').withTitle("Responsable").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('eventDateTime').withTitle("Fecha Evento").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('observation').withTitle("Observación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('createdBy').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                switch  (data) {
                    case "Iniciado":
                        label = 'label label-success';
                        break;

                    case "Cancelado":
                        label = 'label label-danger';
                        break;

                    case "Retirado":
                        label = 'label label-warning';
                        break;

                    case "Completado":
                        label = 'label label-info';
                        break;
                }

                return '<span class="' + label +'">' + data + '</span>';
            }),
        DTColumnBuilder.newColumn(null).withTitle("Anexos").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-warning';
                var text = 0;

                if (data.countAttachment != null || data.countAttachment != undefined) {
                    text = data.countAttachment;
                }

                var status = '<span class="' + label + '">' + text + ' Anexos </span>';

                return status;
            }),
    ];

    var loadRow = function () {

        angular.element("#dtCustomerTracking a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.editTracking(id);
        });

        angular.element("#dtCustomerTracking a.viewRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.viewTracking(id);
        });

        angular.element("#dtCustomerTracking a.documentRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.onAddAttachment(id);
        });

        $scope.onAttachment = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("attachment", "attachment", id);
            }
        };

        angular.element("#dtCustomerTracking a.delRow").on("click", function () {
            var id = angular.element(this).data("id");

            // Aqui se debe hacer la redireccion al formulario de edicion del customer
            log.info("intenta eliminar el registro: " + id);

            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, eliminar!",
                    cancelButtonText: "No, continuar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/tracking/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function(e){
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        }).finally(function(){

                            $scope.reloadData();
                        });

                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });

    };

    $scope.reloadData = function () {
        $scope.dtInstanceTracking.reloadData();
    };


    $scope.editTracking = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "edit", id);
        }
    };

    $scope.viewTracking = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "view", id);
        }
    };

     //----------------------------------------------------------------------------ATTACHMENTS
     $scope.onAddAttachment = function (id) {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/tracking/customer_tracking_document_modal.htm",
            placement: 'right',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideCustomerTrackingAttachmentCtrl',
            scope: $scope,
            resolve: {
                dataItem: function () {
                    return { id: id };
                },
                isView: function () {
                    return $scope.$parent.editMode == 'view';//$scope.isView;
                }
            }
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        }, function () {
            $scope.reloadData();
            $log.info('Modal dismissed at: ' + new Date());
        });
    };

    $scope.onDownloadAttachment = function () {            
        var modalInstance = $uibModal.open({                
            templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/document_filter_modal.htm',
            controller: 'ModalInstanceSideCustomerTrackingDownloadAttachmentCtrl',
            windowTopClass: 'top-modal',
            resolve: {
                improvement: function () {
                    return { id : 0 };
                },
                title: function() {
                    return "Descargar anexos de seguimiento"
                },
                action: function () {
                    return "Cancelar";
                }
            }
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        }, function() {

        });
    };

}]);

app.controller('ModalInstanceSideCustomerTrackingAttachmentCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, dataItem, isView, $log, $timeout, SweetAlert, $filter, FileUploader, $http, DTColumnBuilder, DTOptionsBuilder, $compile, ListService) {

    var attachmentUploadedId = 0;
    var lastLabel = 'M';

    var isCustomer = $rootScope.isCustomer();

    $scope.isView = isCustomer ? false : isView;

    $scope.documentClassification = $rootScope.parameters("customer_document_classification");
    $scope.documentStatus = $rootScope.parameters("customer_document_status");

    getList();

    function getList() {

        var entities = [
            { name: 'customer_document_type', value: $stateParams.customerId }
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.documentType = response.data.data.customerDocumentType;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    var init = function () {
        $scope.attachment = {
            id: 0,
            customerTrackingId: dataItem.id,
            type: null,
            classification: null,
            description: "",
            status: $scope.documentStatus ? $scope.documentStatus[0] : null,
            version: 1,            
            label: lastLabel
        };
    }

    init();

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {
        init();
    };

    var uploader = $scope.uploader = new FileUploader({
        url: 'api/customer-tracking-document/upload',
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
    uploader.onBeforeUploadItem = function (item) {
        var formData = { id: attachmentUploadedId };
        item.formData.push(formData);
    };

    uploader.onCompleteAll = function () {
        $scope.reloadData();
        $scope.onClear();
    };

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

        lastLabel = $scope.attachment.label;

        var req = {};
        var data = JSON.stringify($scope.attachment);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-tracking-document/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

    $scope.dtOptionsCustomerDocumentModal = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customerTrackingId = dataItem.id;                
                d.statusCode = '2'
                return JSON.stringify(d);
            },
            url: 'api/customer-tracking-document',
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

    $scope.dtColumnsCustomerDocumentModal = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.documentUrl ? data.documentUrl : '';
                var downloadUrl = "api/customer-tracking-document/download?id=" + data.id;

                var actions = "";
                var editTemplate = '<a target="_self" class="btn btn-primary btn-xs downloadDocumentRow lnk" href="' + downloadUrl + '" uib-tooltip="Descargar anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-download"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs openDocumentRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-folder-open-o"></i></a> ';

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
            }),
        DTColumnBuilder.newColumn('label').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
    ];

    var loadRow = function () {

        $("#dtCustomerDocumentModal a.editRow").on("click", function () {
            var id = $(this).data("id");
            var url = $(this).data("url");
            //$scope.editTracking(id);
            if (url == "") {
                SweetAlert.swal("Error en la descarga", "No existe un anexo para descargar", "error");
            }
            else {
                jQuery("#downloadDocument")[0].src = "api/customer-tracking-document/download?id=" + id;
            }
        });
    };

    $scope.dtInstanceCustomerDocumentModalCallback = function (instance) {
        $scope.dtInstanceCustomerDocumentModal = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceCustomerDocumentModal.reloadData();
    };



    //-------------------------------------------------------------
    // HISTORICAL PREVIOUS PERIOD
    //-------------------------------------------------------------

    $scope.dtOptionsCustomerMinimunStandardItemDocumentModal = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customerId = $stateParams.customerId;
                d.roadSafetyItemId = dataItem.roadSafetyItemId;
                d.customerId = $stateParams.customerId;
                d.statusCode = '2'
                return JSON.stringify(d);
            },
            url: 'api/customer-tracking-document-available-previous',
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
            loadRowHistorical();
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

    $scope.dtColumnsCustomerMinimunStandardItemDocumentModal = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.documentUrl ? data.documentUrl : '';
                var downloadUrl = "api/customer-tracking-document/download?id=" + data.id;

                var actions = "";

                var addTemplate = '<a class="btn btn-success btn-xs addRow lnk" uib-tooltip="Adicionar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-plus"></i></a> ';

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
                        actions += addTemplate;
                    }
                }

                return isButtonVisible ? actions : "";
            }),
        DTColumnBuilder.newColumn('period').withTitle("Periodo").withOption('width', 200),
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
            }),        
    ];

    var loadRowHistorical = function () {
        $("#dtCustomerMinimunStandardItemDocumentModal a.addRow").on("click", function () {
            var id = $(this).data("id");
            onImportHistorical(id);
        });
    };

    $scope.dtInstanceCustomerMinimunStandardItemDocumentModalCallback = function (instance) {
        $scope.dtInstanceCustomerMinimunStandardItemDocumentModal = instance;
    };

    $scope.reloadHistoricalData = function () {
        $scope.dtInstanceCustomerMinimunStandardItemDocumentModal.reloadData();
    };

    var onImportHistorical = function (id) {

        lastLabel = $scope.attachment.label;

        var data = JSON.stringify({
            id: id,
            customerTrackingId: dataItem.id
        });

        return $http({
            method: 'POST',
            url: 'api/customer-tracking-document/import-historical',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param({
                data: Base64.encode(data)
            }),
        }).then(function (response) {
            $timeout(function () {
                init();
                $scope.reloadHistoricalData();
                $scope.reloadData();
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
        });
    };


});


app.controller('ModalInstanceSideCustomerTrackingDownloadAttachmentCtrl', function ($rootScope, $stateParams, $scope, $uibModal, $uibModalInstance, improvement, title, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile, ListService, ngNotify) {

    var log = $log;

    $scope.loading = true;
    $scope.title = title;

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onSelectYear = function () {
        getList();
    };

    var init = function () {
        $scope.filter = {
            id: $stateParams.customerId
        }
    }

    init();

    getList();

    function getList() {

        var $year = $scope.filter.year ? $scope.filter.year.value : null;

        var entities = [
            { name: 'customer_document_type', value: $stateParams.customerId },
            { name: 'customer_tracking_document_periods', value: $stateParams.customerId, year:  $year}
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.documentType =response.data.data.customerDocumentType;
                $scope.years =response.data.data.customerTrackingDocumentPeriod.years;
                $scope.months =response.data.data.customerTrackingDocumentPeriod.months;                
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
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

                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                if (!$scope.filter.type && !$scope.filter.year && !$scope.filter.month) {
                    SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione al menos un filtro e intentelo nuevamente.", "error");
                    return;
                }
                onFIlter();
            }

        },
        reset: function (form) {

        }
    };

    var onFIlter = function () {
        ngNotify.set('El archivo se está generando.', {
            position: 'bottom',
            sticky: true,
            button: false,
            html: true
        });

        var entity = {
            customerId: $stateParams.customerId,
            audit: $scope.filter
        };

        var req = {};
        var data = JSON.stringify(entity);
        req.data = Base64.encode(data);

        $http({
            method: 'POST',
            url: 'api/customer-tracking-document/export',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            var $url = response.data.path + response.data.filename;
            var $link = '<div class="row"> <div class="col-sm-12 text-center">Por favor espere y verifique su correo y la bandeja de mensajes!</div> </div>';
            ngNotify.set($link, {
                position: 'bottom',
                sticky: true,
                type: 'success',
                button: true,
                html: true
            });

            $scope.onCloseModal();

        }).catch(function (response) {
            ngNotify.set(response.data.message, {
                position: 'bottom',
                sticky: true,
                type: 'error',
                button: true,
                html: true
            });
        }).finally(function () {

        });
    };    

});