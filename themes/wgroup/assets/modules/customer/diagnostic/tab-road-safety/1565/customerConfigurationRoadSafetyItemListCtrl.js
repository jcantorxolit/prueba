'use strict';
/**
 * controller for Customers
 */
app.controller('customerConfigurationRoadSafetyItemListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside) {

        var log = $log;
        var request = {};
        log.info("loading..configurationRoadSafetyItemItemListCtrl ");

        $scope.isNewRoadSafetyItem = true;


        request.operation = "diagnostic";

        $scope.dtInstanceCustomerConfigRoadSafetyItem = {};
        $scope.dtOptionsCustomerConfigRoadSafetyItem = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/road-safety-item',
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

        $scope.dtColumnsCustomerConfigRoadSafetyItem = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-danger btn-xs editRow lnk" href="#" uib-tooltip="Configurar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-cog"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("diagnostico_continue")) {
                    }
                    actions += editTemplate;

                    if ($rootScope.can("clientes_delete")) {
                    }

                    return actions;
                }),

            DTColumnBuilder.newColumn('minimumStandard.cycle.name').withTitle("Ciclo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('minimumStandard.parent.numeral').withTitle("Numeral (Padre)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('minimumStandard.parent.description').withTitle("Estándar (Padre)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('minimumStandard.numeral').withTitle("Numeral (Hijo)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('minimumStandard.description').withTitle("Estandar (Hijo)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('numeral').withTitle("Numeral").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Item").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('value').withTitle("Valor").withOption('width', 200).withOption('defaultContent', ''),
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

            $("#dtCustomerConfigRoadSafetyItem a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editRoadSafetyItem(id);
            });

            $("#dtCustomerConfigRoadSafetyItem a.delRow").on("click", function () {
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
                                url: 'api/road-safety-item/delete',
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

        $scope.dtInstanceCustomerConfigRoadSafetyItemCallback = function (instance){
            $scope.dtInstanceCustomerConfigRoadSafetyItem = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerConfigRoadSafetyItem.reloadData();
        };


        $scope.editRoadSafetyItem = function (id) {
            onOpenModal(id);
        };


        var onOpenModal = function (id) {

            var standard = {
                id: id ? id : 0
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_customer_configuration_minimum_standard_item.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/diagnostic/tab-road-safety/customer_configuration_minimum_standard_item_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerConfigurationRoadSafetyItemEditCtrl',
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

        $scope.onReturn = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.$parent.currentId);
            }
        };

    }]);

app.controller('ModalInstanceSideCustomerConfigurationRoadSafetyItemEditCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, standard, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    $scope.isView = true;

    $scope.config = {
        customerId: $stateParams.customerId,
        minimumStandardItemId: standard.id,
        verificationList: []
    };

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;


    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var initialize = function () {
        $scope.standard = {
            id: standard.id,
            minimumStandard: null,
            minimumStandardParent: null,
            numeral: "",
            description: "",
            value: 0,
            criterion: '',
            isActive: true,
            legalFrameworkList: [],
            verificationModeList: []
        };
    }

    initialize();

    var loadList = function () {

        var req = {

        };

        return $http({
            method: 'POST',
            url: 'api/road-safety/list-data',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.parentList = response.data.data.parent;
                $scope.standardListAll = response.data.data.standard;
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    loadList();

    var initializeActiveList = function() {
        angular.forEach($scope.config.verificationList, function (model, key) {
            model.isActive = model.isActive == 1;
        });
    }

    var loadListVerificationMode = function () {

        var req = {
            minimum_standard_item_id: standard.id,
            customer_id: $stateParams.customerId
        };

        return $http({
            method: 'POST',
            url: 'api/customer/configuration-road-safety-item-detail/list-data',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.config.verificationList = response.data.data.verificationList;

                initializeActiveList();
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    loadListVerificationMode();

    $scope.onLoadRecord = function () {
        if ($scope.standard.id) {
            var req = {
                id: $scope.standard.id
            };

            $http({
                method: 'GET',
                url: 'api/road-safety-item',
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
                        SweetAlert.swal("Información no disponible", "Anexo no encontrado", "error");
                        $timeout(function () {

                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del anexo", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.standard = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        }
    }

    $scope.$watch("standard.minimumStandardParent", function (newValue, oldValue, scope) {

        $scope.standardList = [];

        if (oldValue != null && !angular.equals(newValue, oldValue)) {
            $scope.standard.minimumStandard = null;
        }

        if ($scope.standard.minimumStandardParent != null) {
            $scope.standardList = $filter('filter')($scope.standardListAll, {parentId: $scope.standard.minimumStandardParent.id});
        }

    });

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

    $scope.master = $scope.config;
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
        var req = {};
        var data = JSON.stringify($scope.config);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer/configuration-road-safety-item-detail/insert',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                $scope.standard = response.data.result;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.reloadData();
        });
    };

    $scope.onSelectAll = function() {
        angular.forEach($scope.config.verificationList, function (model, key) {
            model.isActive = true;
        });
    }

    $scope.onClearAll = function() {
        angular.forEach($scope.config.verificationList, function (model, key) {
            model.isActive = false;
        });
    }

});
