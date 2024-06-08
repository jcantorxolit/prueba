'use strict';
/**
  * controller for Customers
*/
app.controller('customerSafetyInspectionConfigListListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','SweetAlert','$http', '$filter', '$document',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
          $compile, toaster, $state, $rootScope,$timeout, SweetAlert, $http, $filter, $document) {

    var log = $log;
    var request = {};
        log.info("loading..customerSafetyInspectionConfigListListCtrl ");

    // Datatable configuration
    request.operation = "safety";
    request.customer_id = $stateParams.customerId;

    $scope.dtInstanceSafetyInspectionConfigList = {};
		$scope.dtOptionsSafetyInspectionConfigList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/customer/safety-inspection-config-list',
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

    $scope.dtColumnsSafetyInspectionConfigList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if($rootScope.can("seguimiento_view")){
                    actions += viewTemplate;
                }

                if($rootScope.can("seguimiento_edit")){
                    actions += editTemplate;
                }

                if($rootScope.can("seguimiento_delete")){
                    actions += deleteTemplate;
                }

                return actions;
            }),
        DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 300),
        DTColumnBuilder.newColumn('description').withTitle("Descripción"),
        DTColumnBuilder.newColumn('dateFromText').withTitle("Fecha Creación").withOption('width', 150).withOption('defaultContent', ""),
        DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 100),
        DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 100)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = '';

                if (data) {
                    label = 'label label-success';
                    text = 'Activo';
                } else {
                    label = 'label label-danger';
                    text = 'Inactivo';
                }

                var status = '<span class="' + label +'">' + text + '</span>';

                return status;
            })
    ];

    var loadRow = function () {

        $("#dtSafetyInspectionConfigList a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.onEditRecord(id);
        });

        $("#dtSafetyInspectionConfigList a.downloadRow").on("click", function () {
            var id = $(this).data("id");
            var url = $(this).data("url");
            //$scope.editTracking(id);
            if (url == "") {
                jQuery("#downloadDocument")[0].src = "api/customer/safety-inspection-config-list/download?id=" + id;
            }
        });

        $("#dtSafetyInspectionConfigList a.viewRow").on("click", function () {
            var id = $(this).data("id");
            $scope.onViewRecord(id);
        });

        $("#dtSafetyInspectionConfigList a.delRow").on("click", function () {
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
                            url: 'api/customer/safety-inspection-config-list/delete',
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
        $scope.dtInstanceSafetyInspectionConfigList.reloadData();
    };

    $scope.onCreateNew = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "edit", 0);
        }
    };

    $scope.onEditRecord = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "edit", id);
        }
    };

    $scope.onViewRecord = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "view", id);
        }
    };


}]);