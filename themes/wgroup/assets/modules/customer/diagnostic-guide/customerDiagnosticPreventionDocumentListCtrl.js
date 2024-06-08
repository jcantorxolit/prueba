'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticPreventionDocumentListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http) {

        var log = $log;
        var request = {};

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        if ($scope.isAgent) {
            $state.go("app.clientes.list");
        } else if ($scope.isCustomer) {
            log.info("Step 15")
            $state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }

        // Datatable configuration
        request.operation = "quotes";
        request.customer_id = $stateParams.customerId ? $stateParams.customerId : 0;

        $scope.dtInstanceCustomerDiagnosticPreventionDocument = {};
		$scope.dtOptionsCustomerDiagnosticPreventionDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/diagnostic-prevention-document',
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

        $scope.dtColumnsCustomerDiagnosticPreventionDocument = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 200).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var url = data.document != null ? data.document.path : "";
                    var downloadUrl = "api/customer/diagnostic-prevention-document/download?id=" + data.id;
                    var actions = "";
                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" tooltip="Editar" data-id="' + data.id + '">' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var downloadTemplate = '<a target="_self" class="btn btn-info btn-xs downloadRow lnk" href="' + downloadUrl + '" tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                        '   <i class="fa fa-download"></i></a> ';


                    var deleteTemplate = '<a class="btn btn-light-red btn-xs delRow lnk" href="#" tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash"></i></a> ';

                    if (url != '') {
                        actions += downloadTemplate;
                    }

                    actions += editTemplate;
                    actions += deleteTemplate;

                    if ($scope.isAdmin || $scope.isAgent || $scope.$parent.isCustomerContractor) {

                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200),
            DTColumnBuilder.newColumn('startDate').withTitle("Fecha de Inicio Vigencia").withOption('width', 200),
            DTColumnBuilder.newColumn('endDate').withTitle("Fecha de Finalización Vigencia").withOption('width', 200),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
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

            $("#dtCustomerDiagnosticPreventionDocument a.editRow").on("click", function () {
                var id = $(this).data("id");

                $scope.onEdit(id);
            });

            $("#dtCustomerDiagnosticPreventionDocument a.downloadRow").on("click", function () {

            });

            $("#dtCustomerDiagnosticPreventionDocument a.delRow").on("click", function () {
                var id = $(this).data("id");

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminará el registro seleccionado.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Si, eliminar!",
                        cancelButtonText: "No, cancelar!",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function (isConfirm) {
                        if (isConfirm) {
                            var req = {};
                            req.id = id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/diagnostic-prevention-document/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function(response){
                                swal("Eliminado", "asesor eliminado satisfactoriamente", "info");
                            }).catch(function(e){
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                            }).finally(function(){

                                $scope.reloadData();
                            });
                        } else {
                            swal("Cancelado", "Operacion cancelada", "error");
                        }
                    });
            });
        };


        $scope.onCreate = function(){
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", 0);
            }
        };

        $scope.onEdit = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", id);
            }
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerDiagnosticPreventionDocument.reloadData();
        };

    }]);
