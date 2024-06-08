'use strict';
/**
 * controller for Customers
 */
app.controller('customerManagementListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert) {

        var log = $log;
        var request = {};
        

        $scope.isNew = false;
      
        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.dtInstanceManagement = {};
        $scope.dtOptionsManagement = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;

                    return JSON.stringify(d);
                },
                url: 'api/customer-management',
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

        $scope.dtColumnsManagement = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    if (data.statusCode == "completado" || data.statusCode == "cancelado") {
                        disabled = 'ng-disabled="true"';
                    }

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Continuar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play-circle"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    var cancelTemplate = '<a class="btn btn-blue btn-xs cancelRow lnk" href="#" uib-tooltip="Cancelar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-ban"></i></a> ';

    
                    if ($rootScope.can("programa_empresarial_continue")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("programa_empresarial_delete")) {
                        actions += deleteTemplate;
                    }

                    if ($rootScope.can("programa_empresarial_cancel")) {
                        actions += cancelTemplate;
                    }

                    return actions;
                }),           
            DTColumnBuilder.newColumn('workplace').withTitle("Centro de Trabajo").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('economicSector').withTitle("Sector Económico").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('program').withTitle("Programa Empresarial").withOption('defaultContent', ''),            
            DTColumnBuilder.newColumn('createdAt').withTitle("Fecha Creación").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                if (typeof data == 'object' && data != null) {                    
                    return moment(data.date).format('DD/MM/YYYY');
                }
                return data != null ? moment(data).format('DD/MM/YYYY') : '';
            }),
            //DTColumnBuilder.newColumn('endDate').withTitle("Fecha Finalización").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('createdBy').withTitle("Responsable Seguimiento").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                switch (data.statusCode) {
                    case "completado":
                        label = 'label label-success';
                        break;

                    case "cancelado":
                        label = 'label label-inverse';
                        break;

                    case "iniciado":
                        label = 'label label-warning';
                        break;
                }

                var status = '<span class="' + label + '">' + data.status + '</span>';


                return status;
            })             

        ];

        var loadRow = function () {

            angular.element("#dtCustomerManagement a.editRow").on("click", function () {
                if ($(this).is("[disabled]")) {
                    return;
                }
                var id = angular.element(this).data("id");
                $scope.onEdit(id);
            });


            angular.element("#dtCustomerManagement a.delRow").on("click", function () {
                if ($(this).is("[disabled]")) {
                    return;
                }
                var id = angular.element(this).data("id");

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminará la gestión seleccionada.",
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
                                url: 'api/management/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

            angular.element("a.cancelRow").on("click", function () {
                if ($(this).is("[disabled]")) {
                    return;
                }                
                var id = angular.element(this).data("id");          

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Cancelar el diagnóstico seleccionado.",
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
                                url: 'api/management/cancel',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Gestión cancelada satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la cancelación", "Se ha presentado un error durante la cancelacón la gestión por favor intentelo de nuevo", "error");
                            }).finally(function () {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Terminado", "Operación terminada", "error");
                        }
                    });
            });
        };

        $scope.dtInstanceManagementCallback = function (instance) {
            $scope.dtInstanceManagement = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceManagement.reloadData();
            canCreate();
        };

        $scope.onConfig = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("setting", "setting");
            }
        };

        $scope.onEdit = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", id);
            }
        };
    }
]);