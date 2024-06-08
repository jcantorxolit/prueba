'use strict';
/**
 * controller for Customers
 */
app.controller('configurationMinimumStandardListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside) {

        var log = $log;
        var request = {};
        log.info("loading..configurationMinimumStandardListCtrl ");

        $scope.isNewMinimumStandard = true;


        request.operation = "diagnostic";

        $scope.dtInstanceMinimumStandard = {};
        $scope.dtOptionsMinimumStandard = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/minimum-standard',
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

        $scope.dtColumnsMinimumStandard = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play-circle"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("diagnostico_continue")) {
                    }
                    actions += editTemplate;

                    if ($rootScope.can("clientes_delete")) {
                    }
                    actions += deleteTemplate;

                    return actions;
                }),

            DTColumnBuilder.newColumn('cycle.name').withTitle("Ciclo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('parent.numeral').withTitle("Numeral (Padre)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('numeral').withTitle("Numeral (Hijo)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Estándar").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type.item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
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

            $("#dtMinimumStandard a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editMinimumStandard(id);
            });

            $("#dtMinimumStandard a.delRow").on("click", function () {
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
                                url: 'api/minimum-standard/delete',
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
            $scope.dtInstanceMinimumStandard.reloadData(null, false);
        };


        $scope.editMinimumStandard = function (id) {
            onOpenModal(id);
        };

        $scope.onCreate = function () {
            onOpenModal();
        }

        var onOpenModal = function (id) {

            var standard = {
                id: id ? id : 0
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_minimum_standard.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/configuration/minimum-standard/standard/configuration_minimum_standard_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideMinimumStandardEditCtrl',
                scope: $scope,
                resolve: {
                    standard: function () {
                        return standard;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        }

    }]);

app.controller('ModalInstanceSideMinimumStandardEditCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, standard, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;


    $scope.standard = {};

    // Preparamos los parametros por grupo
    $scope.typeList = $rootScope.parameters("minimum_standard_type");
    $scope.cycleList = [];
    $scope.parentList = [];
    $scope.parentListAll = [];

    var loadList = function () {

        var req = {};

        return $http({
            method: 'POST',
            url: 'api/minimum-standard/list-data',
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
        if ($scope.standard.id != 0) {
            var req = {
                id: $scope.standard.id
            };
            $http({
                method: 'GET',
                url: 'api/minimum-standard',
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
                        $scope.standard = response.data.result;
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
        $scope.standard = {
            id: standard.id,
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

    $scope.$watch("standard.cycle", function (newValue, oldValue, scope) {

        $scope.parentList = [];

        if (oldValue != null && !angular.equals(newValue, oldValue)) {
            $scope.standard.parent = null;
        }

        if ($scope.standard.cycle != null) {
            $scope.parentList = $filter('filter')($scope.parentListAll, {cycleId: $scope.standard.cycle.id});
        }

    });

    $scope.master = $scope.standard;
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
                log.info($scope.standard);
                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                SweetAlert.swal("Validación exitosa", "Guardando información del centro de trabajo...", "success");
                //your code for submit
                //  log.info($scope.standard);
                save();
            }

        },
        reset: function (form) {
            $scope.clear();
        }
    };

    var save = function () {

        var data = JSON.stringify($scope.standard);
        var req = {
            data: Base64.encode(data)
        };
        return $http({
            method: 'POST',
            url: 'api/minimum-standard/save',
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
