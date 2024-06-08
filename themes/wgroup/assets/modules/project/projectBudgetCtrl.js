'use strict';
/**
 * controller for Customers
 */
app.controller('projectBudgetCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside) {

        var log = $log;

        // Datatable configuration
        $scope.dtInstanceBudget = {};
        $scope.dtOptionsBudget = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    return JSON.stringify(d);
                },
                url: 'api/budget-v2',
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

        $scope.dtColumnsBudget = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 220).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("clientes_edit")) {
                    }
                    actions += editTemplate;

                    if ($rootScope.can("clientes_edit")) {
                    }
                    actions += deleteTemplate;

                    return actions;
                }),

            DTColumnBuilder.newColumn('item').withTitle("Rubro").withOption('width', 200),
            DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200),
            DTColumnBuilder.newColumn('description').withTitle("Descripción"),
            DTColumnBuilder.newColumn('year').withTitle("Año").withOption('width', 200),
            DTColumnBuilder.newColumn(null).withTitle("Valor").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {

                    return $filter('currency')(data.amount, "$ ", 2);
                    ;
                })
        ];

        var loadRow = function () {
           angular.element("#dtBudget a.editRow").on("click", function () {
                var id =angular.element(this).data("id");
                openModal({ id: id })
            });

           angular.element("#dtBudget a.delRow").on("click", function () {
                var id =angular.element(this).data("id");

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
                                url: 'api/budget/delete',
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
        }

        $scope.dtInstanceBudgetCallback = function(instance) {
            $scope.dtInstanceBudget = instance;
        }

        $scope.reloadData = function () {
            $scope.dtInstanceBudget.reloadData();
        };

        $scope.onCreateNew = function () {
            openModal({ id: 0 })
        };

        var openModal = function (entity) {

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_project_budget.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/project/project_budget_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: 'static',
                controller: 'modalSideBudgetCtrl',
                scope: $scope,
                resolve: {
                    budget: function () {
                        return entity;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {
                $scope.reloadData();
            });
        }

    }]);

app.controller('modalSideBudgetCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, budget, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile, ListService) {

    var log = $log;
    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;

    $scope.isView = false;
    $scope.isCreate = true;

    $scope.types = $rootScope.parameters("project_type");

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var init = function () {
        $scope.budget = {
            id: budget.id,
            item: '',
            description: '',
            classification: null,
        };
    };

    init();

    getList();

    function getList() {
        var entities = [
            {name: 'project_budget_year', value: null},
            {name: 'month', value: null},
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.years = $filter('orderBy')(response.data.data.project_budget_year, 'value', true);
                $scope.months = response.data.data.month;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.onLoadRecord = function () {
        if ($scope.budget.id != 0) {

            var req = {
                id: $scope.budget.id
            };
            $http({
                method: 'GET',
                url: 'api/budget',
                params: req
            })
                .catch(function (e, code) {

                })
                .then(function (response) {
                    $scope.budget = response.data.result;
                }).finally(function () {

                    $timeout(function () {
                        $document.scrollTop(40, 2000);
                    });

                });
        }
    }

    $scope.onLoadRecord();

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
                log.info($scope.budget);
                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                save();
            }

        },
        reset: function (form) {
            $scope.onClear();
        }
    };

    $scope.onClear = function () {
        $scope.isView = false;
        init();
    };

    var save = function () {
        var req = {};

        var data = JSON.stringify($scope.budget);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/budget/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            SweetAlert.swal("Validación exitosa", "El registro se guardó satisfactoriamente", "success");

            $scope.budget = response.data.result;

        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.onClearDetail();
        });

    };


    //-------------------------------------------------------------- DETAIL

    var initDetail = function () {
        $scope.detail = {
            id: 0,
            budgetId: $scope.budget.id,
            year: null,
            month: null,
            amount: 0,
        };
    };

    initDetail();

    $scope.onAddDetail = function () {
        var validateDateMessage = '';

        if ($scope.detail.year == null) {
            validateDateMessage += "Debe seleccionar el año del periodo \n";
        }

        if ($scope.detail.month == null) {
            validateDateMessage += "Debe seleccionar el mes del periodo \n";
        }

        if (validateDateMessage != '') {
            SweetAlert.swal({
                html: false,
                title: "Error de validación",
                text: validateDateMessage,
                type: "error"
            });
            return;
        }

        var req = {};

        var data = JSON.stringify($scope.detail);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/budget-detail/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $scope.onClearDetail();
            $scope.reloadData();
        }).catch(function (response) {
            $log.error(response);
            SweetAlert.swal("Error de guardado", response.data.message, "error");
        }).finally(function () {
            $scope.onClearDetail();
        });
    }

    $scope.onClearDetail = function () {
        initDetail();
    }

    var onLoadRecordDetail = function (id) {
        if (id != 0) {
            $http({
                method: 'GET',
                url: 'api/budget-detail/get',
                params: { id: id }
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.detail = response.data.result;
                    });

                }).finally(function () {

                    $timeout(function () {
                        $document.scrollTop(40, 2000);
                    });

                });
        }
    }

    $scope.dtInstanceBudgetDetail = {};
    $scope.dtOptionsBudgetDetail = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.budgetId = $scope.budget.id;
                return JSON.stringify(d);
            },
            url: 'api/budget-detail',
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
            loadRow();
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

    $scope.dtColumnsBudgetDetail = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 100).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-edit"></i></a> ';

                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if ($rootScope.can("clientes_edit")) {
                }
                actions += editTemplate;

                if ($rootScope.can("clientes_edit")) {
                }
                actions += deleteTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('period').withTitle("Periodo"),
        DTColumnBuilder.newColumn(null).withTitle("Valor").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {

                return $filter('currency')(data.amount, "$ ", 2);
                ;
            })
    ];

    var loadRow = function () {
       angular.element("#dtBudgetDetail a.editRow").on("click", function () {
            var id =angular.element(this).data("id");
            onEdit(id);
        });

       angular.element("#dtBudgetDetail a.delRow").on("click", function () {
            var id =angular.element(this).data("id");
            // Aqui se debe hacer la redireccion al formulario de edicion del customer

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
                            url: 'api/budget-detail/delete',
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
    }

    $scope.dtInstanceBudgetDetailCallback = function(instance) {
        $scope.dtInstanceBudgetDetail = instance;
    }

    $scope.reloadData = function () {
        $scope.dtInstanceBudgetDetail.reloadData();
    };

    var onEdit = function (id) {
        onLoadRecordDetail(id);
    };
});
