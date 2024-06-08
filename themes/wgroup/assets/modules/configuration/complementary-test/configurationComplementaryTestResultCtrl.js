'use strict';
/**
 * Controller for Actividad (Create, Edit)
 *
 * @author David Blandon <david.blandon@gmail.com>
 */
app.controller('ConfigurationComplementaryTestResultCtrl', ['$scope', '$stateParams', '$log',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$filter', 'ListService', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    function ($scope, $stateParams, $log, $compile, toaster,  $state,
              SweetAlert, $rootScope, $http, $timeout, $filter, ListService, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder) {

        var log = $log;

        $scope.dateConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        var initialize = function() {
            $scope.parameter = {
                id: 0,
                group: "work_medicine_complementary_test_result",
                namespace: "wgroup",
                code: null,
                item: "",
                value: null,
            };
        }

        initialize();

        getList();

        function getList() {
            var entities = [
                {name: 'work_medicine_complementary_test'}
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.complementaryTestList = response.data.data.work_medicine_complementary_test;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

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

                    angular.element('.ng-invalid[name=' + firstError + ']').focus();

                    $timeout(function () {
                        toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                    }, 500);

                    return;

                } else {
                    onSave();
                }

            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        var onSave = function () {
            var req = {};
            var data = JSON.stringify($scope.parameter);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/system-parameter/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $scope.onCancel();

                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Procediendo con el guardado...');
                }, 500);

            }).catch(function (e) {
                $log.error(e);
                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
            }).finally(function () {

            });

        };

        var onLoadRecord = function(id) {

            if (id) {

                var req = {
                    id: id
                };
                $http({
                    method: 'GET',
                    url: 'api/system-parameter/get',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            toaster.pop("error", "No Autorizado", "No esta autorizado para ver esta información.");

                            $timeout(function () {
                                $scope.onCancel();
                            }, 3000);
                        } else if (code == 404) {
                            toaster.pop("error", "Información no disponible", "Registro no encontrado.");

                            $timeout(function () {
                                $scope.onCancel();
                            });
                        } else {
                            toaster.pop("error", "Error", "Se ha presentado un error al intentar acceder a la información.");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.parameter = response.data.result;
                        });

                    }).finally(function () {

                    });
            } else {

            }
        }

        // Datatable configuration
        $scope.request = {
            operation: "parameter",
            namespace: "wgroup",
            group: "work_medicine_complementary_test_result",
            parent: "work_medicine_complementary_test",
        };

        $scope.dtInstanceComplementaryTestResult = {};
        $scope.dtOptionsComplementaryTestResult = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/system-parameter/relation',
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

        $scope.dtColumnsComplementaryTestResult = [
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

                    actions += viewTemplate;
                    actions += editTemplate;
                    actions += deleteTemplate;

                    return actions;
                }),

            DTColumnBuilder.newColumn('value').withTitle("Código").withOption('width', 200),
            DTColumnBuilder.newColumn('item').withTitle("Descripción"),
            DTColumnBuilder.newColumn('parent').withTitle("Prueba Complementaria").withOption('width', 200)
        ];

        var loadRow = function () {

            $("#dtComplementaryTestResult a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onEdit(id);
            });

            $("#dtComplementaryTestResult a.viewRow").on("click", function () {
                var id = $(this).data("id");
                $scope.parameter.id = id;
                $scope.onView(id);

            });

            $("#dtComplementaryTestResult a.delRow").on("click", function () {
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
                                url: 'api/system-parameter/delete',
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

        $scope.dtInstanceComplementaryTestResultCallback = function (instance) {
            $scope.dtInstanceComplementaryTestResult = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceComplementaryTestResult.reloadData();
        };

        $scope.onView = function (id) {
            $scope.isView = true;
            onLoadRecord(id);
        };

        $scope.onEdit = function(id){
            $scope.isView = false;
            onLoadRecord(id);
        };

        $scope.onCancel = function () {

            initialize();
            $scope.reloadData();

        }

        $scope.refreshComplementaryTest = function() {
            getList();
        }

    }]);
