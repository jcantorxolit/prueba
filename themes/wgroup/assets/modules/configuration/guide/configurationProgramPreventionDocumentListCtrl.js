'use strict';
/**
 * controller for Customers
 */
app.controller('configurationProgramPreventionDocumentListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
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
            log.info("Step 7")
            $state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }

        // Datatable configuration
        request.operation = "quotes";

        $scope.dtInstanceProgramPreventionDocument = {};
		$scope.dtOptionsProgramPreventionDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/configuration/program-prevention-document',
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

        $scope.dtColumnsProgramPreventionDocument = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 200).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var url = data.document != null ? data.document.path : "";
                    var actions = "";
                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '">' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var downloadTemplate = '<a target="_self" class="btn btn-info btn-xs downloadRow lnk" href="#" uib-tooltip="Descargar" data-id="' + data.id + '" data-url="' + url + '" >' +
                        '   <i class="fa fa-download"></i></a> ';


                    var deleteTemplate = '<a class="btn btn-light-red btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-ban"></i></a> ';


                    actions += downloadTemplate;
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

            $("#dtProgramPreventionDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $state.go("app.program-prevention-document.edit", {"id":id});
            });

            $("#dtProgramPreventionDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src  = "api/configuration/program-prevention-document/download?id=" + id;
                }
            });

            $("#dtProgramPreventionDocument a.delRow").on("click", function () {
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
                                url: 'api/configuration/program-prevention-document/delete',
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


        $scope.onCreate = function(){
            $state.go("app.program-prevention-document.create");
        };

        $scope.reloadData = function () {
            $scope.dtInstanceProgramPreventionDocument.reloadData();
        };

    }]);
