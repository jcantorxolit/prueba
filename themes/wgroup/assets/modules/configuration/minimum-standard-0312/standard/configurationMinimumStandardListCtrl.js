'use strict';
/**
 * controller for Customers
 */
app.controller('configurationMinimumStandardList0312Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside) {

        $scope.isNewMinimumStandard = true;

        $scope.dtOptionsMinimumStandard0312 = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    return JSON.stringify(d);
                },
                url: 'api/minimum-standard-0312',
                contentType: 'application/json',
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

        $scope.dtColumnsMinimumStandard0312 = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
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

            DTColumnBuilder.newColumn('cycle').withTitle("Ciclo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('parentNumeral').withTitle("Numeral (Padre)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('numeral').withTitle("Numeral (Hijo)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Estándar").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Activo").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = data.status;

                    if (data || data.isActive == 1) {
                        label = 'label label-success';
                    } else {
                        label = 'label label-danger';
                    }

                    return '<span class="' + label + '">' + text + '</span>';
                }),
        ];

        var loadRow = function () {

            $("#dtMinimumStandard0312 a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onEdit(id);
            });

            $("#dtMinimumStandard0312 a.delRow").on("click", function () {
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
                                url: 'api/minimum-standard-0312/delete',
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

        $scope.dtInstanceMinimumStandard0312Callback = function (instance) {
            $scope.dtInstanceMinimumStandard0312 = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceMinimumStandard0312.reloadData(null, false);
        };

        $scope.onEdit = function (id) {
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
                templateUrl: $rootScope.app.views.urlRoot + 'modules/configuration/minimum-standard-0312/standard/configuration_minimum_standard_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideMinimumStandardEdit0312Ctrl',
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

app.controller('ModalInstanceSideMinimumStandardEdit0312Ctrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, standard, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile, ListService) {

    var log = $log;

    $scope.parentList = [];

    getList();

    function getList() {
        var entities = [
            { name: 'minimum-standard-cycle-0312', value: null },
            { name: 'minimum-standard-parent-0312', value: null },
            { name: 'minimum_standard_type', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.cycleList = response.data.data.minimum_standard_cycle_0312;
                $scope.parentListAll = response.data.data.minimum_standard_parent_0312;
                $scope.typeList = response.data.data.minimum_standard_type;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    var onInit = function () {
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

    onInit();

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
                url: 'api/minimum-standard-0312/get',
                params: req
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.standard = response.data.result;
                        $scope.onSelectCycle();
                    }, 400);

                }).finally(function () {
                    $timeout(function () {
                        $document.scrollTop(40, 2000);
                    });
                });
        }
    }

    $scope.onLoadRecord();

    $scope.onSelectCycle = function () {
        $scope.parentList = [];

        if ($scope.standard.cycle != null) {
            $scope.parentList = $filter('filter')($scope.parentListAll, {cycleId: $scope.standard.cycle.id});
        }
    };

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
            url: 'api/minimum-standard-0312/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
            $timeout(function () {
                $scope.onCloseModal()
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
        });
    };
});
