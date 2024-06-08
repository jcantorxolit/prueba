'use strict';
/**
 * controller for Customers
 */
app.controller('customerContractRequirementCtrl', ['$scope', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$document',
    function ($scope, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document) {

        var log = $log;

        $scope.currentUser = $rootScope.currentUser();
        $scope.isAgent = $scope.currentUser.wg_type == "agent";
        $scope.isAdmin = $scope.currentUser.wg_type == "system";
        $scope.isCustomerAdmin = $scope.currentUser.wg_type == "customerAdmin";
        $scope.isCustomerUser = $scope.currentUser.wg_type == "customerUser";

        $scope.canEdit = $scope.isCustomerAdmin || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
        $rootScope.canEditRoot = $scope.canEdit;

        $scope.contractorTypeList = [];

        $scope.$watchCollection("customer.contractorTypeList", function (newValue, oldValue, scope) {

            $scope.contractorTypeList = $scope.customer.contractorTypeList ? $filter('filter')($scope.customer.contractorTypeList, {isActive: true}, true) : [];

        });

        var init = function() {
            $scope.requirement = {
                id: 0,
                customerId: $stateParams.customerId,
                value: "",
                isActive: true,
                jan: false,
                feb: false,
                mar: false,
                apr: false,
                may: false,
                jun: false,
                jul: false,
                aug: false,
                sep: false,
                oct: false,
                nov: false,
                dec: false,
                contractorTypeList: []
            }
        }

        init();

        $scope.isView = $state.is("app.clientes.view");
        $scope.isCreate = $state.is("app.clientes.create");

        $scope.onLoadRecord = function () {
            if ($scope.requirement.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.requirement.id);
                var req = {
                    id: $scope.requirement.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/periodic-requirement',
                    params: req
                })
                    .catch(function (response) {
                        if (response.status == 403) {
                            var messagered = response.data.message !== null && response.data.message !== undefined ? response.data.message : 'app.clientes.list';
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (response.status == 404) {
                            SweetAlert.swal("Información no disponible", "Aporte no encontrado", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del aporte", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.requirement = response.data.result;
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $document.scrollTop(0, 2000);
                        });
                    });
            }
        }

        $scope.master = $scope.requirement;
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
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    save();
                }

            },
            reset: function (form) {

                $scope.requirement = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {

            if ($scope.requirement.value == "") {
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;
            }

            var req = {};
            var data = JSON.stringify($scope.requirement);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/periodic-requirement/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.requirement = response.data.result;
                    SweetAlert.swal("Validación exitosa", "Guardando información del contratista...", "success");
                    $scope.reloadDataPeriodicRequirement();
                    $scope.clearPeriodicRequirement();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        $scope.cancelEdition = function (index) {
            if ($scope.isView) {
                $state.go('app.clientes.list');
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
                                $state.go('app.clientes.list');
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        //Periodic Requirement
        $scope.onAddRequirementContractType = function () {
            $timeout(function () {
                if ($scope.requirement.contractorTypeList == null) {
                    $scope.requirement.contractorTypeList = [];
                }
                $scope.requirement.contractorTypeList.push(
                    {
                        id: 0,
                        customerContractorType: null
                    }
                );
            });
        };

        $scope.onRemoveRequirementContractType = function (index) {
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
                        $timeout(function () {
                            // eliminamos el registro en la posicion seleccionada
                            var date = $scope.requirement.contractorTypeList[index];

                            $scope.requirement.contractorTypeList.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/periodic-requirement-contractor-type/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function (e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function () {

                                });
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        var request = { customer_id: $scope.customer.id };

        $scope.dtInstancePeriodicRequirement = {};
        $scope.dtOptionsPeriodicRequirement = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/periodic-requirement',
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

        $scope.dtColumnsPeriodicRequirement = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("clientes_edit")) {
                    }
                    actions += editTemplate;

                    if ($rootScope.can("clientes_delete")) {
                    }
                    actions += deleteTemplate;

                    return (($scope.isAdmin || $scope.isAgent || $scope.isCustomerAdmin)) ? actions : '';
                }),
            DTColumnBuilder.newColumn('value').withTitle("Requisito"),
            DTColumnBuilder.newColumn(null).withTitle("ENE").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.jan != null || data.jan != undefined) {
                        if (data.jan) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("FEB").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.feb != null || data.feb != undefined) {
                        if (data.feb) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("MAR").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.mar != null || data.mar != undefined) {
                        if (data.mar) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("ABR").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.apr != null || data.apr != undefined) {
                        if (data.apr) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("MAY").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.may != null || data.may != undefined) {
                        if (data.may) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("JUN").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.jun != null || data.jun != undefined) {
                        if (data.jun) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("JUL").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.jul != null || data.jul != undefined) {
                        if (data.jul) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("AGO").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.aug != null || data.aug != undefined) {
                        if (data.aug) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("SEP").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.sep != null || data.sep != undefined) {
                        if (data.sep) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("OCT").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.oct != null || data.oct != undefined) {
                        if (data.oct) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("NOV").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.nov != null || data.nov != undefined) {
                        if (data.nov) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("DIC").withOption('width', 80)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'No';

                    if (data.dec != null || data.dec != undefined) {
                        if (data.dec) {
                            label = 'label label-success';
                            text = 'Si';
                        } else {
                            label = 'label label-danger';
                            text = 'No';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Inactivo';

                    if (data.isActive != null || data.isActive != undefined) {
                        if (data.isActive) {
                            label = 'label label-success';
                            text = 'Activo';
                        } else {
                            label = 'label label-danger';
                            text = 'Inactivo';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
        ];

        var loadRow = function () {

            angular.element("#dtContractPeriodicRequirement a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onEdit(id);
            });

            angular.element("#dtContractPeriodicRequirement a.delRow").on("click", function () {
                var id = angular.element(this).data("id");

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
                                url: 'api/customer/periodic-requirement/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataPeriodicRequirement();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.onEdit = function (id) {
            $scope.requirement.id = id;
            $scope.isView = false;
            $scope.onLoadRecord();
        };

        $scope.dtInstancePeriodicRequirementCallback = function (instance) {
            $scope.dtInstancePeriodicRequirement = instance;
        };

        $scope.reloadDataPeriodicRequirement = function () {
            $scope.dtInstancePeriodicRequirement.reloadData();
        };

        $scope.clearPeriodicRequirement = function () {
            init();
        };

}]);
