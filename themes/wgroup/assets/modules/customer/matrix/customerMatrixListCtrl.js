'use strict';
/**
 * controller for Customers
 */
app.controller('customerMatrixListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert) {

        var log = $log;        

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        // Datatable configuration
        $scope.dtInstanceCustomerMatrix = {};
		$scope.dtOptionsCustomerMatrix = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;

                    return JSON.stringify(d);
                },
                url: 'api/customer-matrix',
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

        $scope.dtColumnsCustomerMatrix = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    if (data.status != null && (data.status == "Anulada" || data.status == "Cancelado")) {
                        disabled = 'disabled="disabled"';
                    }

                    var configureTemplate = '<a class="btn btn-purple btn-xs configureRow lnk" href="#" uib-tooltip="Configurar Matriz"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-gear"></i></a> ';

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Continuar Gestión Matriz"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play-circle"></i></a> ';

                    var cancelTemplate = '<a class="btn btn-blue btn-xs cancelRow lnk" href="#" uib-tooltip="Cancelar Matriz" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-ban"></i></a> ';

                    if ($scope.isAdmin) {
                        if ($rootScope.can("diagnostico_cancel")) {
                        }
                    }
                    actions += configureTemplate;

                    if ($rootScope.can("diagnostico_continue")) {
                    }
                    actions += editTemplate;

                    if ($rootScope.can("diagnostico_cancel")) {
                    }
                    actions += cancelTemplate;

                    return actions;
                }),            
            DTColumnBuilder.newColumn('id').withTitle("Consecutivo").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('createdAt').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('createdBy').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200).withOption('defaultContent', '')
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch (data.status) {
                        case "Creada":
                            label = 'label label-success';
                            break;

                        case "Iniciada":
                            label = 'label label-warning';
                            break;

                        case "Anulada":
                            label = 'label label-danger';
                            break;
                    }

                    return '<span class="' + label + '">' + data.status + '</span>';
                })
        ];

        var loadRow = function () {

            $("#dtCustomerMatrix a.configureRow").on("click", function () {
                var id = $(this).data("id");
                $scope.configureMatrix(id);
            });

            $("#dtCustomerMatrix a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.managementMatrix(id);
            });

            $("#dtCustomerMatrix a.delRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

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
                                url: 'api/customer/matrix/delete',
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

            $("a.cancelRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta cancelar el registro: " + id);

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
                                url: 'api/customer/matrix/delete',
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

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerMatrix.reloadData();
        };

        $scope.configureMatrix = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("config", "config", id);
            }
        };

        $scope.managementMatrix = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", id);
            }
        };

        $scope.onCreate = function () {
            var req = {};
            var request = {
                id: 0,
                customerId: $stateParams.customerId,
                status: {
                    id: 0,
                    item: "Creada",
                    value: "Creada"
                },
                description: "Matriz de impactos ambientales",
                type: {
                    id: 0,
                    item: "MAIA",
                    value: "MAIA"
                }
            };

            var data = JSON.stringify(request);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: 'api/customer/matrix/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                SweetAlert.swal("Validación exitosa", "Matriz creada satisfactoriamente", "success");
                $scope.reloadData();
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Creando", "Se ha presentado un error durante la creación de la gestión por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        };
    }]);