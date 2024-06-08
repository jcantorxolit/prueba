'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticObservationListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter) {

        var log = $log;
        var request = {};
        log.info("loading..customerDiagnosticObservationListCtrl ");

        $scope.loading = true;
        $scope.isView = false;
        $scope.agents = $rootScope.agents();
        $scope.diagnostic_id = $scope.$parent.currentDiagnostic;
        $scope.observation = {
            id: "0",
            diagnosticId: $scope.diagnostic_id,
            currentDate: $filter('date')(new Date(), "dd/MM/yyyy HH:mm"),
            agent: {
                id: "0",
                name: "- Seleccionar -"
            },
            description: ""
        };

        $scope.onLoadRecord = function () {
            if ($scope.observation.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.observation.id);
                var req = {
                    id: $scope.observation.id,
                    diagnostic_id: $scope.diagnostic_id
                };
                $http({
                    method: 'GET',
                    url: 'api/diagnostic/observation/get',
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
                            SweetAlert.swal("Información no disponible", "Observación no encontrada", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información de la observación", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.observation = response.data.result;
                            $scope.observation.diagnosticId = $scope.diagnostic_id;
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

        $scope.onLoadRecord();

        var errorMessage = function (i) {
            toaster.pop('error', 'Error', 'Por favor diligencie los campos requeridos en este paso, antes de continuar al siguiente nivel.');
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.diagnostic_id);
            }
        };

        $scope.master = $scope.observation;
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
                    log.info($scope.observation);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información de la observación...", "success");
                    //your code for submit
                    $scope.save();
                }

            },
            reset: function (form) {

                $scope.clear();

            }
        };

        $scope.save = function () {
            var req = {};
            var data = JSON.stringify($scope.observation);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/diagnostic/observation/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.observation = response.data.result;

                    $scope.reloadData();

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando la observación por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.clear();
            });

        };

        $scope.clear = function(){
            $timeout(function () {
                $scope.observation = {
                    id: "0",
                    diagnosticId: $scope.diagnostic_id,
                    currentDate: Date.today().toString("dd/MM/yyyy HH:mm"),
                    agent: {
                        id: "0",
                        name: "- Seleccionar -"
                    },

                    description: ""
                };
            });

            $scope.isView = false;
        };

        // Datatable configuration
        request.operation = "diagnostic";
        request.diagnostic_id = $scope.diagnostic_id;

        $scope.dtInstanceDiagnosticObservation = {};
		$scope.dtOptionsDiagnosticObservation = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/diagnostic/observation',
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

        $scope.dtColumnsDiagnosticObservation = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

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
            DTColumnBuilder.newColumn('currentDate').withTitle("Fecha").withOption('width', 200),
            DTColumnBuilder.newColumn('agent.name').withTitle("Usuario").withOption('width', 200),
            DTColumnBuilder.newColumn('description').withTitle("Observación").withOption('width', 200)
        ];

        var loadRow = function () {

            $("#dtol a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editDiagnosticObservation(id);


            });

            $("#dtol a.viewRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                //$state.go("app.clientes.view", {"diagnosticId":id});

                $scope.observation.id = id;
                $scope.viewDiagnosticObservation(id);
            });

            $("#dtol a.delRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminará la observación seleccionada.",
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
                                url: 'api/diagnostic/observation/delete',
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
            $scope.dtInstanceDiagnosticObservation.reloadData();
        };


        $scope.editDiagnosticObservation = function (id) {
            $scope.observation.id = id;
            $scope.isView = false;
            $scope.onLoadRecord();
        };

        $scope.viewDiagnosticObservation = function (id) {
            $scope.observation.id = id;
            $scope.isView = true;
            $scope.onLoadRecord();
        };

        $scope.createDiagnosticObservation = function () {
            var req = {};
            var request = {
                id: 0,
                customerId: $stateParams.customerId,
                status: {
                    id: 0,
                    item: "Iniciado",
                    value: "iniciado"
                }
            };

            var data = JSON.stringify(request);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: 'api/diagnostic/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                if ($scope.$parent != null) {
                    $scope.reloadData();
                    swal("Creado", "Observación adicionada satisfactoriamente", "info");
                }
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Creando", "Se ha presentado un error durante la creación de la observación por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        };

    }]);
