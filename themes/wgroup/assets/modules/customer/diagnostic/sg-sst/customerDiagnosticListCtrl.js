'use strict';
/**
  * controller for Customers
*/
app.controller('customerDiagnosticListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','$http','SweetAlert',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
          $rootScope,$timeout, $http, SweetAlert) {

    var log = $log;
    
    $scope.canCreate = false;

    $scope.dtInstanceDiagnostic = {};
    $scope.dtOptionsDiagnostic = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {                
                d.customerId = $stateParams.customerId;
                return JSON.stringify(d);
            },
            url: 'api/customer-diagnostic',
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

    $scope.dtColumnsDiagnostic = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                if (data.status == "Completado" || data.status == "Cancelado") {
                    disabled = 'disabled="disabled"';
                }

                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Continuar"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-play-circle"></i></a> ';

                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                var cancelTemplate = '<a class="btn btn-blue btn-xs cancelRow lnk" href="#" uib-tooltip="Cancelar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-ban"></i></a> ';

                if($rootScope.can("diagnostico_view")){
                    //actions += viewTemplate;
                }

                if ($rootScope.can("diagnostico_continue")) {
                    actions += editTemplate;
                }

                if ($rootScope.can("clientes_delete")) {
                    actions += deleteTemplate;
                }

                if ($rootScope.can("diagnostico_cancel")) {
                    actions += cancelTemplate;
                }

                return actions;
            }),
        DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
            var label = '';
            switch  (data)
            {
                case "Completado":
                    label = 'label label-success';
                    break;

                case "Cancelado":
                    label = 'label label-inverse';
                    break;

                case "Iniciado":
                    label = 'label label-warning';
                    break;
            }

            var status = '<span class="' + label +'">' + data + '</span>';


            return status;
        }),
        DTColumnBuilder.newColumn('createdBy').withTitle("Asesor"),
        DTColumnBuilder.newColumn('createdAt').withTitle("Fecha Creación").withOption('width', 200),
        DTColumnBuilder.newColumn('endDate').withTitle("Fecha Finalización").withOption('width', 200)
    ];

    var loadRow = function () {
        angular.element("#dtCustomerDiagnostic a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.onEdit(id);
        });

        angular.element("#dtCustomerDiagnostic a.delRow").on("click", function () {
            var id = angular.element(this).data("id");

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
                            url: 'api/diagnostic/delete',
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

        angular.element("#dtCustomerDiagnostic a.cancelRow").on("click", function () {
            var id = angular.element(this).data("id");

            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Cancelará el diagnóstico seleccionado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, cancelar!",
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
                            url: 'api/diagnostic/cancel',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
                            swal("Eliminado", "Diagnóstico cancelado satisfactoriamente", "info");
                        }).catch(function(e){
                            $log.error(e);
                            SweetAlert.swal("Error en la cancelación", "Se ha presentado un error durante la cancelacón del diagnóstico por favor intentelo de nuevo", "error");
                        }).finally(function(){

                            $scope.reloadData();
                        });

                    } else {
                        swal("Terminado", "Operación terminada", "error");
                    }
                });
        });
    };

    $scope.dtInstanceDiagnosticCallback = function (instance) {
        $scope.dtInstanceDiagnostic = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDiagnostic.reloadData();
        canCreate();
    };


    $scope.onEdit = function(id) {
        if($scope.$parent != null) {
            $scope.$parent.navToSection("summary", "summary", id);
        }
    };

    $scope.onCreate = function() {
        var req = {};
        var request = {
            id: 0,
            customerId: $stateParams.customerId,
            status: {
                id: 0,
                item: "Iniciado",
                value: "iniciado"
            },
            arlActivity: null,
            arlIntermediaryActivity: null,
            arlIntermediaryNit: null,
            arlIntermediaryName: null,
            arlIntermediaryLicence: null,
            arlIntermediaryRegister: null,
            dateFrom: null,
            dateTo: null
        };

        var data = JSON.stringify(request);
        req.data = Base64.encode(data);

        $http({
            method: 'POST',
            url: 'api/diagnostic/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            if($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", response.data.result.id);
            }
        }).catch(function(e){
            log.error(e);
            SweetAlert.swal("Error Creando", "Se ha presentado un error durante la creación del diagnóstico por favor intentelo de nuevo", "error");
        }).finally(function(){

        });
    };

    var canCreate = function()
    {
        var req = {};
        req.customer_id = $stateParams.customerId;

        $http({
            method: 'POST',
            url: 'api/diagnostic/canCreate',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function(response){
            $scope.canCreate = response.data.data
        }).catch(function(e){

        }).finally(function(){

        });
    }

    canCreate();

}]);