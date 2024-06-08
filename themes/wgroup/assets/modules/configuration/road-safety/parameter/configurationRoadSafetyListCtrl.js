'use strict';
/**
 * controller for Customers
 */
app.controller('configurationRoadSafetyListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside) {

        var log = $log;
        var request = {};
        log.info("loading..configurationRoadSafetyListCtrl ");

        $scope.isNewRoadSafety = true;


        request.operation = "diagnostic";

        $scope.dtInstanceRoadSafety = {};
        $scope.dtOptionsRoadSafety = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/road-safety',
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

        $scope.dtColumnsRoadSafety = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 100).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play-circle"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';


                    actions += editTemplate;

                    actions += deleteTemplate;

                    return actions;
                }),

            DTColumnBuilder.newColumn('cycle.name').withTitle("Módulo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type.item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('parent.numeral').withTitle("Numeral (Parámetro)").withOption('width', 200).withOption('defaultContent', ''),
            //DTColumnBuilder.newColumn('numeral').withTitle("Numeral (Item)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200).withOption('defaultContent', ''),

            DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';

                    if (data || data == 1) {
                        label = 'label label-success';
                        text = 'Activo';
                    } else {
                        label = 'label label-danger';
                        text = 'Inactivo';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';


                    return status;
                }),
        ];

        var loadRow = function () {

            $("#dtRoadSafety a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editRoadSafety(id);
            });

            $("#dtRoadSafety a.delRow").on("click", function () {
                var id = $(this).data("id");

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
                                url: 'api/road-safety/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e, code) {
                                $log.error(e);
                                if (code == 500) {
                                    SweetAlert.swal("Error en la eliminación", e.message, "error");
                                } else {
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }
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
            $scope.dtInstanceRoadSafety.reloadData(null, false);
        };


        $scope.editRoadSafety = function (id) {
            onOpenModal(id);
        };

        $scope.onCreate = function () {
            onOpenModal();
        }

        var onOpenModal = function (id) {

            var roadSafety = {
                id: id ? id : 0
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_road_safety.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/configuration/road-safety/parameter/configuration_road_safety_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideRoadSafetyEditCtrl',
                scope: $scope,
                resolve: {
                    roadSafety: function () {
                        return roadSafety;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        }

    }]);

app.controller('ModalInstanceSideRoadSafetyEditCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, roadSafety, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;


    $scope.roadSafety = {};

    // Preparamos los parametros por grupo
    $scope.typeList = $rootScope.parameters("road_safety_type");
    $scope.cycleList = [];
    $scope.parentList = [];
    $scope.parentListAll = [];

    var loadList = function () {

        var req = {};

        return $http({
            method: 'POST',
            url: 'api/road-safety/list-data',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.cycleList = response.data.data.cycle;
                $scope.parentListAll = response.data.data.parent;
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    loadList();

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.roadSafety.id != 0) {
            var req = {
                id: $scope.roadSafety.id
            };
            $http({
                method: 'GET',
                url: 'api/road-safety',
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
                        $scope.roadSafety = response.data.result;
                    }, 400);

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);

                    $timeout(function () {
                        $document.scrollTop(40, 2000);
                    });
                });
        } else {
            $scope.loading = false;
        }
    }

    var init = function () {
        $scope.roadSafety = {
            id: roadSafety.id,
            type: null,
            cycle: null,
            parent: null,
            numeral: '',
            description: '',
            isActive: true
        };
    };

    init();

    $scope.onLoadRecord();

    $scope.$watch("roadSafety.cycle", function (newValue, oldValue, scope) {

        $scope.parentList = [];

        if (oldValue != null && !angular.equals(newValue, oldValue)) {
            $scope.roadSafety.parent = null;
        }

        if ($scope.roadSafety.cycle != null) {
            $scope.parentList = $filter('filter')($scope.parentListAll, {cycleId: $scope.roadSafety.cycle.id});
        }

    });

    $scope.master = $scope.roadSafety;
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
                log.info($scope.roadSafety);
                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                SweetAlert.swal("Validación exitosa", "Guardando información del centro de trabajo...", "success");
                //your code for submit
                //  log.info($scope.roadSafety);
                save();
            }

        },
        reset: function (form) {
            $scope.clear();
        }
    };

    var save = function () {

        var data = JSON.stringify($scope.roadSafety);
        var req = {
            data: Base64.encode(data)
        };
        return $http({
            method: 'POST',
            url: 'api/road-safety/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.onCloseModal()
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
        });
    };
});
