'use strict';
/**
 * controller for Customers
 */
app.controller('pollCustomerCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter) {

        var log = $log;

        $scope.request_collection = {};

        $scope.fields = [];



        $scope.pollCustomer = {
            id: 0,
            poll: $scope.poll,
            filters: [],
            customers: []
        }

        $scope.pollCustomer.filters.push(
            {
                id: 0,
                field: null,
                criteria: null,
                condition: null,
                value: ""
            }
        );

        $scope.clear = function(){
            $timeout(function () {
                $scope.pollCustomer = {
                    id: 0,
                    poll: $scope.poll,
                    filters: [],
                    customers: []
                }
            });
        }

        $scope.criteria = [
            {
                name: "Igual",
                value: "="
            }, {
                name: "Contiene",
                value: "LIKE"
            }, {
                name: "Diferente",
                value: "<>"
            }, {
                name: "Mayor que",
                value: ">"
            }, {
                name: "Menor que",
                value: "<"
            }
        ];

        $scope.conditions = [
            {
                name: "Y",
                value: "AND"
            }, {
                name: "O",
                value: "OR"
            }
        ];


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
                    log.info($scope.poll);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Generando información...", "success");
                    //your code for submit
                    log.info($scope.poll);
                    //save();
                    var data = JSON.stringify($scope.pollCustomer);

                    $scope.request_collection.operation = "report-calculated";
                    $scope.request_collection.report_id = $stateParams.reportId;
                    $scope.request_collection.data = Base64.encode(data);

                    $scope.dtPollCustomerResult.reloadData();
                }

            },
            reset: function (form) {

                $scope.pollCustomer = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.pollCustomer);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/poll/generate',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    //$scope.onLoadRecord();
                    $scope.pollCustomer.customers = response.data.data;
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.onCancel = function () {
            if ($scope.isview) {
                $state.go('app.poll.list');
            } else {
                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Perderá todos los cambios realizados en este formulario.",
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
                            $timeout(function () {
                                $state.go('app.poll.list');
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        $scope.onLoadRecord = function()
        {

        }

        $scope.onLoadRecord();

        $scope.addFilter = function()
        {
            if ($scope.pollCustomer.filters == null) {
                $scope.pollCustomer.filters = [];
            }
            $scope.pollCustomer.filters.push(
                {
                    id: 0,
                    field: null,
                    criteria: null,
                    condition: null,
                    value: ""
                }
            );
        }

        $scope.removeFilter = function(index)
        {
            $scope.pollCustomer.filters.splice(index, 1);
        }


        var request = {};
        request.operation = "poll-question";
        request.poll_id = $stateParams.id;

        $scope.dtInstanceCustomerPoll = null;

        $scope.dtPollCustomerOptions = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/poll/summary',
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

        $scope.dtPollCustomerColumns = [
            DTColumnBuilder.newColumn(null).withTitle("Acción").withOption('width', 100).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar participante" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if($rootScope.can("poll_delete")){
                        actions += deleteTemplate;
                    }

                    return actions;
                }),

            DTColumnBuilder.newColumn('customer.businessName')
                .withOption('defaultContent', '')
                .withTitle("Razón Social")
                .withOption('width'),
            DTColumnBuilder.newColumn('customer.type.item')
                .withOption('defaultContent', '')
                .withTitle("Tipo de Cliente")
                .withOption('width', 200),
        ];

        var loadRow = function () {
            $("#dtPollCustomerOptions a.delRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminará la pregunta seleccionada.",
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
                                url: 'api/customer/poll/delete',
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

        $scope.dtInstanceCustomerPollCallback = function (instance) {
            $scope.dtInstanceCustomerPoll = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerPoll.reloadData();
        };


        var data = JSON.stringify($scope.pollCustomer);

        $scope.request_collection.operation = "report-calculated";
        $scope.request_collection.report_id = $stateParams.reportId;
        $scope.request_collection.data = Base64.encode(data);

        $scope.dtPollCustomerResult = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request_collection,
                url: 'api/customer/poll/generate',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {
                    $timeout(function () {
                        //$scope.$parent.setDataSetting(data.responseJSON.data);
                    });
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
                loadRowBinding();
                //Pace.stop();

            })
            .withDOM('tr')
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })


            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtPollCustomerResultColumns = [
            DTColumnBuilder.newColumn('Nombre')
                .withTitle("Cliente"),

            DTColumnBuilder.newColumn(null).withTitle('Acciones').withOption('width', 150).notSortable()
                .renderWith(function(data, type, full, meta) {

                    var actions = "";

                    var checked = (data.Activo == "1") ? "checked" : ""

                    var editTemplate = '<input bs-switch ng-model="isSelected" type="checkbox" switch-active="true" ng-click="edit(' + data.Identificacion + ')" name="' + data.Identificacion + '" ' +
                        'ng-true-value="true" ng-false-value="false" switch-on-text="Si" switch-off-text="No"> ';

                    var editTemplate = '<div class="checkbox clip-check check-success ">' +
                        '<input class="editRow" type="checkbox" id="chk_' + data.Identificacion + '" data-id="' + data.Identificacion + '" data-value="' + data.Activo + '" ' + checked + ' ><label for="chk_' + data.Identificacion +'"> Seleccionar </label></div>';
                    actions += editTemplate;

                    return actions;
                })

        ];

        var loadRowBinding = function () {

            $("input[type=checkbox]").on("change", function () {
                var id = $(this).data("id");
                var value = $(this).data("value");

                var result = $filter('filter')($scope.pollCustomer.customers, {id: id});

                if (result.length == 0) {
                    var customer = {
                        id: id
                    }
                    $scope.pollCustomer.customers.push(customer);
                } else {
                    angular.forEach($scope.pollCustomer.customers, function(customer, index){
                        if (customer.id === id) {
                            $scope.pollCustomer.customers.splice(index, 1);
                            return;
                        };
                    });
                }

                $log.info($scope.pollCustomer.customers);

            });

        };

        $scope.onGenerate = function()
        {
            if ($scope.pollCustomer.customers.length == 0) {
                $timeout(function () {
                    toaster.pop('error', 'Validación', 'Debe seleccionar al menos un cliente.');
                });
            } else {
                var req = {};
                var data = JSON.stringify($scope.pollCustomer);
                req.data = Base64.encode(data);
                return $http({
                    method: 'POST',
                    url: 'api/customer/poll/save',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {

                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Generación exitosa.');
                        $scope.reloadData();
                    });

                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {

                });
            }

        }

    }]);



