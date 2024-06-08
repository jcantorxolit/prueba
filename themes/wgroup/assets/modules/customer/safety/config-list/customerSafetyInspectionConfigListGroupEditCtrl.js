'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerSafetyInspectionConfigListGroupEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$document',
    function ($scope, $stateParams, $log, $compile,  $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $aside, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $document) {

        var log = $log;
        var request = {};
        var currentId = $scope.$parent.currentConfigListId;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.loading = true;
        $scope.customerId = $stateParams.customerId;
        console.log();

        var modeDsp = ($scope.$parent.$parent.$parent.$parent.$parent.$parent.modeDsp).toString();
        if (modeDsp.toString() === "view"){
            $scope.isView = true;
        } else {
            $scope.isView = false;
        }

        console.log($scope.isView);
        $scope.item = {};

        $scope.groups = [];

        $scope.onLoadRecord = function () {
            if ($scope.item.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.item.id);
                var req = {
                    id: $scope.item.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/safety-inspection-config-list-item',
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
                            $scope.item = response.data.result;
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

        var loadList = function () {

            var req = {};
            req.operation = "list";
            req.listId = currentId;

            return $http({
                method: 'POST',
                url: 'api/customer/safety-inspection-config-list-group/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.groups = response.data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        var init = function () {
            $scope.item = {
                id: 0,
                group: null,
                description: "",
                isActive: true
            };
        };

        init();

        loadList();

        $scope.master = $scope.process;
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
                    log.info($scope.process);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información del centro de trabajo...", "success");
                    //your code for submit
                    //  log.info($scope.process);
                    $scope.save();
                }

            },
            reset: function (form) {
                $scope.clear();
            }
        };

        $scope.onClear = function () {
            $timeout(function () {
                init();
            });

            $scope.isView = false;
        };

        $scope.save = function () {
            var req = {};
            var data = JSON.stringify($scope.item);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/safety-inspection-config-list-item/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.item = response.data.result;

                    SweetAlert.swal("Validación exitosa", "Información guardada", "success");

                    $scope.reloadData();

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.onClear();
            });

        };


        request.operation = "list";
        request.listId = currentId;

        $scope.dtInstanceConfigListGroupItem = {};
		$scope.dtOptionsConfigListGroupItem = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/safety-inspection-config-list-item',
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

        $scope.dtColumnsConfigListGroupItem = [
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


                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_delete")) {
                        actions += deleteTemplate;
                    }

                    return $scope.isView ? '' : actions;
                }),

            DTColumnBuilder.newColumn('group.description').withTitle("Grupo").withOption('width', 400).withOption('defaultContent', ""),
            DTColumnBuilder.newColumn('description').withTitle("Item"),
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

        $scope.editItem = function (id) {
            $scope.item.id = id;
            $scope.isView = false;
            $scope.onLoadRecord()
        };

        $scope.viewItem = function (id) {
            $scope.process.id = id;
            $scope.isView = true;
            $scope.onLoadRecord();
        };

        $scope.cancelEdition = function (index) {
            if($scope.$parent != null){
                $scope.$parent.navToSection("list", "list");
            }
        };

        var loadRow = function () {

            $("#dtConfigListGroupItem a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editItem(id);
            });

            $("#dtConfigListGroupItem a.viewRow").on("click", function () {
                var id = $(this).data("id");

                $scope.process.id = id;
                $scope.viewItem(id);

            });

            $("#dtConfigListGroupItem a.delRow").on("click", function () {
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
                                url: 'api/customer/safety-inspection-config-list-item/delete',
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
            $scope.dtInstanceConfigListGroupItem.reloadData();
        };

        $scope.refreshGroup = function()
        {
            loadList();
        }

    }]);
