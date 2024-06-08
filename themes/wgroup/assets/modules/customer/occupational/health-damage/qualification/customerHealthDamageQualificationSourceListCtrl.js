'use strict';
/**
  * controller for Customers
*/
app.controller('customerHealthDamageQualificationSourceListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','SweetAlert','$http', '$filter', '$document', '$aside',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
          $compile, toaster, $state, $rootScope,$timeout, SweetAlert, $http, $filter, $document, $aside) {

    var log = $log;
    log.info("loading..customerWorkMedicineListCtrl ");

   // $rootScope.tabname = "tracking";

    // default view
   // $rootScope.tracking_section = "list";

    // Datatable configuration

    $scope.agents = $rootScope.agents();

    $scope.dtInstanceHealthDamageQualificationSource = {};
		$scope.dtOptionsHealthDamageQualificationSource = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerId = $stateParams.customerId;;
                return JSON.stringify(d);
                },
            url: 'api/customer-health-damage-qs',
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

    $scope.dtColumnsHealthDamageQualificationSource = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                var downloadTemplate = '<a class="btn btn-light-purple btn-xs downloadDocumentRow lnk" href="#" uib-tooltip="Descargar anexos" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-cloud-download"></i></a> ';

                if($rootScope.can("seguimiento_view")){
                }
                actions += viewTemplate;

                if($rootScope.can("seguimiento_edit")){
                }
                actions += editTemplate;

                if($rootScope.can("seguimiento_delete")){
                }

                actions += deleteTemplate
                actions += downloadTemplate;

                return actions;
            }),
        DTColumnBuilder.newColumn('createdAt').withTitle("Fecha Registro").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('occupation').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', '')
    ];

    var loadRow = function () {

        $("#dtHealthDamageQualificationSource a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editWorkMedicine(id);
        });

        $("#dtHealthDamageQualificationSource a.viewRow").on("click", function () {
            var id = $(this).data("id");
            $scope.viewWorkMedicine(id);
        });

        $("#dtHealthDamageQualificationSource a.downloadDocumentRow").on("click", function () {
            var id = $(this).data("id");
            openModal(id);
        });

        $("#dtHealthDamageQualificationSource a.delRow").on("click", function () {
            var id = $(this).data("id");

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
                            url: 'api/customer/health-damage/qs/delete',
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
        $scope.dtInstanceHealthDamageQualificationSource.reloadData();
    };


    $scope.editWorkMedicine = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "edit", id);
        }
    };

    $scope.viewWorkMedicine = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "view", id);
        }
    };

    function openModal(id) {
        var modalInstance = $aside.open({
            //templateUrl: 'app_modal_customer_health_damage_download.htm',
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification/customer_health_damage_download_modal.htm",
            placement: 'right',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideHealthDamageQualificationSourceDownloadDocument',
            scope: $scope,
            resolve: {
                qs: function () {
                    return {id: id};
                },
                isView: function () {
                    return $scope.isView;
                }
            }
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        });
    };

}]);

app.controller('ModalInstanceSideHealthDamageQualificationSourceDownloadDocument', function ($rootScope, $scope, $uibModalInstance,
                                                                                           qs, $log, $timeout, SweetAlert, isView,
                                                                                           DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
                                                                                           $filter, FileUploader, $http, $compile) {

    var request = {};

    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy hh:mm tt"
        //value: $scope.project.deliveryDate.date
    };

    $scope.qs = qs;
    $scope.isView = isView;


    $scope.onClose = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };


    $scope.dtInstanceCustomerHealthDamageDocument = {};
    $scope.dtOptionsCustomerHealthDamageDocument = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customer_health_damage_qs_id = $scope.qs.id;
            },
            url: 'api/customer/health-damage/qs/document-all',
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
            loadRowHealthDamageDocument();
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

    $scope.dtColumnsCustomerHealthDamageDocument = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var url = data.document != null ? data.document.path : "";

                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                var downloadTemplate = '<a target="_self" class="btn btn-info btn-xs downloadRow lnk" href="#" uib-tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                    '   <i class="fa fa-download"></i></a> ';

                if (url != '') {
                    actions += downloadTemplate;
                }
                
                return actions;
            }),
        DTColumnBuilder.newColumn('createdAt').withTitle("Fecha registro").withOption('width', 120).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('entityName').withTitle("Módulo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('type.item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('status.item').withTitle("Estado").withOption('width', 150)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = '';

                if (data == 'Activo') {
                    text = data;
                    label = 'label label-success';
                } else {
                    text = data;
                    label = 'label label-danger';
                }

                return '<span class="' + label + '">' + text + '</span>';
            })
    ];

    var loadRowHealthDamageDocument = function () {

        $("#dtCustomerHealthDamageDocument a.downloadRow").on("click", function () {
            var id = $(this).data("id");
            var url = $(this).data("url");

            if (url == "") {
                toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
            } else {
                jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download?id=" + id;
            }
        });

    };

    $scope.reloadData = function () {
        $scope.dtInstanceCustomerHealthDamageDocument.reloadData();
    };

    $scope.onDownload = function () {
        jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download-all?id=" + qs.id;
    };
});