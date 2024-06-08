'use strict';
/**
 * controller for Customers
 */
app.controller('configurationMinimumStandardItemCriterionEdit0312Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$document',
    'ListService', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, ListService, $aside) {

        var log = $log;
		var $formInstance = null;
        var request = {};
        $scope.currentParentId = $scope.$parent.currentId ? $scope.$parent.currentId : 0;

        var onDestroyMinimumStandardChanged$ = $rootScope.$on('onMinimumStandardChanged', function (event, args) {
            if ($scope.currentParentId == 0) {
                $scope.currentParentId = args.newValue;
                init();
                $scope.reloadData();
            }
        });

        $scope.$on("$destroy", function() {
            onDestroyMinimumStandardChanged$();
        });

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        getList();

        function getList() {
            var entities = [
                { name: 'wg_customer_employee_number', value: null },
                { name: 'wg_customer_risk_level', value: null },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.sizeList = response.data.data.wg_customer_employee_number;
                    $scope.riskLevelList = response.data.data.wg_customer_risk_level;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $scope.isView = $scope.$parent.modeDsp == "view";


		var init = function() {
			$scope.entity = {
                id: 0,
				minimumStandardItemId: $scope.currentParentId,
                size: null,
                riskLevel: null,
                description: '',
			}
		}

		init();

        $scope.onLoadRecord = function (id) {
            if (id != 0) {
                var req = {
                    id: id
                };
                $http({
                    method: 'GET',
                    url: 'api/minimum-standard-item-criterion-0312/get',
                    params: req
                })
                    .catch(function (response) {
                        if (response.data.status == 403) {
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            }, 3000);
                        } else if (response.data.status == 404) {
                            SweetAlert.swal("Información no disponible", "Registro no encontrado", "error");

                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del registro", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.entity = response.data.result;
                            $scope.canShow = true;
                        });

                    }).finally(function () {

                    });
            }
        }

        $scope.form = {

            submit: function (form) {
				$formInstance = form;

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
                form.$setPristine(true);
            }
        };

        var save = function () {
            var req = {};

            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/minimum-standard-item-criterion-0312/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
					SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $scope.onCancel();
                    $scope.reloadData();
                });
            }).catch(function (response) {
                $log.error(response);
                SweetAlert.swal("Error de guardado", response.data.message, "error");
            }).finally(function () {
            });
        };

        $scope.dtOptionsMinimumStandardItemCriterion0312 = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
			.withOption('responsive', true)
            .withOption('ajax', {
				data: function (d) {
                    d.minimumStandardItemId = $scope.currentParentId;
                    return JSON.stringify(d);
                },
                url: 'api/minimum-standard-item-criterion-0312',
				contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
			.withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
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

        $scope.dtColumnsMinimumStandardItemCriterion0312 = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var disabled = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    var configTemplate = ' | <a class="btn btn-info btn-xs configRow lnk" href="#"  uib-tooltip="Configurar modos de verificación" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-cog"></i></a> ';

                    actions += editTemplate;
                    actions += deleteTemplate;
                    actions += configTemplate;

                    return actions;
                }),
            DTColumnBuilder.newColumn('size').withTitle("Tamaño Empresa").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('riskLevel').withTitle("Clase de Riesgo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Criterio").withOption('width', 200).withOption('defaultContent', '')
        ];

        var loadRow = function () {

            $("#dtMinimumStandardItemCriterion0312 a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onLoadRecord(id);
            });

            $("#dtMinimumStandardItemCriterion0312 a.configRow").on("click", function () {
                var id = $(this).data("id");
                onOpenModal(id);
            });

            $("#dtMinimumStandardItemCriterion0312 a.delRow").on("click", function () {
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
                                url: 'api/minimum-standard-item-criterion-0312/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (data) {
                                swal("Eliminado", "Regitro eliminado satisfactoriamente", "info");
                            }).catch(function (response) {
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                            }).finally(function () {
                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelado", "Operacion cancelada", "error");
                        }
                    });
            });

        };

		$scope.dtInstanceMinimumStandardItemCriterion0312Callback = function (instance) {
            $scope.dtInstanceMinimumStandardItemCriterion0312 = instance;
        };

        $scope.reloadData = function () {
			if ($scope.dtInstanceMinimumStandardItemCriterion0312 != null) {
				$scope.dtInstanceMinimumStandardItemCriterion0312.reloadData();
			}
        };

        $scope.onCancel = function () {
           init();
        };

        var onOpenModal = function (id) {

            var entity = {
                id: id ? id : 0
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_minimum_standard_association.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/configuration/minimum-standard-0312/item/configuration_minimum_standard_item_criterion_edit_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideMinimumStandardItemCriterionDetailEdit0312Ctrl',
                scope: $scope,
                resolve: {
                    entity: function () {
                        return entity;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {

            });
        }

    }
]);


app.controller('ModalInstanceSideMinimumStandardItemCriterionDetailEdit0312Ctrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, entity, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var $formInstance = null;

    $scope.criterion = {
        id: entity.id
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close();
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {
        onInit();
    };

    var onInit = function() {
        $scope.entity = {
            id: 0,
            minimumStandardItemCriterionId: entity.id,
            type: 'verification-mode',
            description: '',
        }

        if ($formInstance != null) {
            $formInstance.$setPristine(true);
        }
    }

    onInit();

    var onLoadParentRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id
            };
            $http({
                method: 'GET',
                url: 'api/minimum-standard-item-criterion-0312/get',
                params: req
            })
                .catch(function (response) {
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.criterion = response.data.result;
                    });

                }).finally(function () {
                });
        }
    }

    onLoadParentRecord(entity.id)

    var onLoadRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id
            };
            $http({
                method: 'GET',
                url: 'api/minimum-standard-item-criterion-detail-0312/get',
                params: req
            })
                .catch(function (response) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.entity = response.data.result;
                    });

                }).finally(function () {

                });
        }
    }

    $scope.form = {

        submit: function (form) {
            $formInstance = form;

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
                //your code for submit
                save();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    var save = function () {
        var req = {};

        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/minimum-standard-item-criterion-detail-0312/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $scope.onClear();
                $scope.reloadData();
            });
        }).catch(function (response) {
            $log.error(response);
            SweetAlert.swal("Error de guardado", "Error guardando el registro por favor verifique los datos ingresados!", "error");
        }).finally(function () {
        });

    };

    $scope.dtOptionsMinimumStandardItemCriterionDetail0312 = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d.minimumStandardItemCriterionId = entity.id;
                return JSON.stringify(d);
            },
            url: 'api/minimum-standard-item-criterion-detail-0312',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
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

    $scope.dtColumnsMinimumStandardItemCriterionDetail0312 = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                actions += editTemplate;
                actions += deleteTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('description').withTitle("").withOption('defaultContent', ''),
    ];

    var loadRow = function () {

        $("#dtMinimumStandardItemCriterionDetail0312 a.editRow").on("click", function () {
            var id = $(this).data("id");
            onLoadRecord(id);
        });

        $("#dtMinimumStandardItemCriterionDetail0312 a.delRow").on("click", function () {
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
                            url: 'api/minimum-standard-item-criterion-detail-0312/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (data) {
                            swal("Eliminado", "Regitro eliminado satisfactoriamente", "info");
                        }).catch(function (response) {
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                        }).finally(function () {
                            $scope.reloadData();
                        });

                    } else {
                        swal("Cancelado", "Operacion cancelada", "error");
                    }
                });
        });

    };

    $scope.dtInstanceMinimumStandardItemCriterionDetail0312Callback = function (instance) {
        $scope.dtInstanceMinimumStandardItemCriterionDetail0312 = instance;
    };

    $scope.reloadData = function () {
        if ($scope.dtInstanceMinimumStandardItemCriterionDetail0312 != null) {
            $scope.dtInstanceMinimumStandardItemCriterionDetail0312.reloadData();
        }
    };

});
