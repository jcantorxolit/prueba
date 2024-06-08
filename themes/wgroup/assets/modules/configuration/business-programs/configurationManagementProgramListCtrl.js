'use strict';
/**
 * controller for Customers
 */
app.controller('configurationManagementProgramListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document) {

        var log = $log;
        var request = {};
        log.info("loading..customerConfigWorkPlaceListCtrl ");

        $scope.loading = true;
        $scope.customerId = $stateParams.customerId;;
        $scope.isview = false;
        $scope.program = {};

        $scope.status = $rootScope.parameters("config_workplace_status");

        $scope.onLoadRecord = function () {
            if ($scope.program.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.program.id);
                var req = {
                    id: $scope.program.id
                };
                $http({
                    method: 'GET',
                    url: 'api/configuration/management-program',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Información no disponible", "Centro de trabajo no encontrado", "error");

                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del centro de trabajo", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.program = response.data.result;
                            $scope.program.isWeighted = $scope.program.isWeighted == "Sí"; 
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            $document.scrollTop(40, 2000);
                        });
                    });
            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        }

        var setDefault = function () {
            $scope.program = {
                id: 0,
                name: "",
                abbreviation: "",
                status: null,
                color: '#5cb85c',
                highlightColor: '#5cb85c',
                version: '1',
                isWeighted: false,
            };
        };

        setDefault();

        $scope.onLoadRecord();

        var errorMessage = function (i) {
            toaster.pop('error', 'Error', 'Por favor diligencie los campos requeridos en este paso, antes de continuar al siguiente nivel.');
        };

        $scope.master = $scope.program;
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
                    log.info($scope.program);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información del centro de trabajo...", "success");
                    //your code for submit
                    //  log.info($scope.program);
                    save();
                }

            },
            reset: function (form) {
                $scope.clear();
            }
        };

        $scope.clear = function () {
            $timeout(function () {
                setDefault();
            });

            $scope.isview = false;
        };

        $scope.save = function () {
            var req = {};
            var data = JSON.stringify($scope.program);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/configuration/management-program/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.program = response.data.result;

                    SweetAlert.swal("Validación exitosa", "Información guardada", "success");

                    $scope.reloadData();

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.clear();
            });

        };

        // default view
        // $rootScope.tracking_section = "list";

        // Datatable configuration
        request.operation = "diagnostic";
        request.customerId = $scope.customerId;

        $scope.dtInstanceConfigManagementProgram = {};
		$scope.dtOptionsConfigManagementProgram = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/configuration/management-program',
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

        $scope.dtColumnsConfigManagementProgram = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Inactivar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-ban"></i></a> ';

                    if ($rootScope.can("clientes_view")) {
                        actions += viewTemplate;
                    }

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_delete")) {
                        actions += deleteTemplate;
                    }


                    return actions;
                }),

            DTColumnBuilder.newColumn('name').withTitle("Programa").withOption('width', 200),
            DTColumnBuilder.newColumn('abbreviation').withTitle("Abreviación").withOption('width', 200),
            DTColumnBuilder.newColumn('color').withTitle("Color 1").withOption('width', 200),
            DTColumnBuilder.newColumn('color').withTitle("Color 2").withOption('width', 200),
            DTColumnBuilder.newColumn('status.item').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch  (data)
                    {
                        case "Activo":
                            label = 'label label-success';
                            break;

                        case "Inactivo":
                            label = 'label label-danger';
                            break;

                        case "Retirado":
                            label = 'label label-warning';
                            break;
                    }

                    var status = '<span class="' + label +'">' + data + '</span>';


                    return status;
                }),
                DTColumnBuilder.newColumn('isWeighted').withTitle("Ponderado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch  (data)
                    {
                        case "Sí":
                            label = 'label label-success';
                            break;

                        case "No":
                            label = 'label label-danger';
                            break;
                    }

                    var status = '<span class="' + label +'">' + data + '</span>';


                    return status;
                }),                
        ];

        $scope.viewConfigManagementProgram = function (id) {
            $scope.program.id = id;
            $scope.isview = true;
            $scope.onLoadRecord();
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.customerId);
            }
        };

        var loadRow = function () {

            $("#dtConfigManagementProgram a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editConfigManagementProgram(id);
            });

            $("#dtConfigManagementProgram a.viewRow").on("click", function () {
                var id = $(this).data("id");

                $scope.program.id = id;
                $scope.viewConfigManagementProgram(id);

            });

            $("#dtConfigManagementProgram a.delRow").on("click", function () {
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
                                url: 'api/configuration/management-program/delete',
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
        };

        $scope.reloadData = function () {
            $scope.dtInstanceConfigManagementProgram.reloadData();
        };


        $scope.editConfigManagementProgram = function (id) {
            $scope.program.id = id;
            $scope.isview = false;
            $scope.onLoadRecord()
        };

    }]);
