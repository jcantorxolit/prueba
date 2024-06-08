'use strict';
/**
 * controller for Customers
 */
app.controller('customerConfigJobActivityHazardCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document',
    '$aside', '$location', 'ListService', '$filter', 'moment', '$translate',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $aside, $location, ListService, $filter, moment, $translate) {

        var log = $log;
        var request = {};
        var $formInstance = null;

        $scope.loading = false;

        var init = function() {
            $scope.entity = {
                id: 0,
                customerId: $stateParams.customerId,
                workplace: null,
                macroprocess: null,
                process: null,
                job: null,
                activityList: [],
            }

            if ($formInstance !== null) {
                $formInstance.$setPristine(true);

                $scope.macroprocessList =[];
                $scope.processList = [];
                $scope.jobList = [];
            }
        }

        init();

        getList();

        function getList() {
            var $criteria = {
                customerId: $stateParams.customerId,
                workplaceId: $scope.entity.workplace ? $scope.entity.workplace.id : 0,
                macroprocessId: $scope.entity.macroprocess ? $scope.entity.macroprocess.id : 0,
            }

            var entities = [
                {name: 'customer_workplace', value: $stateParams.customerId, criteria: $criteria},
                {name: 'customer_macroprocess', value: $stateParams.customerId, criteria: $criteria},
                {name: 'customer_process', value: $stateParams.customerId, criteria: $criteria},
                {name: 'customer_job', value: $stateParams.customerId, criteria: $criteria},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.workplaceList = response.data.data.workplaceList;
                    $scope.macroprocessList = response.data.data.macroprocessList;
                    $scope.processList = response.data.data.processList;
                    $scope.jobList = response.data.data.jobList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var onLoadRecord = function (id) {
            if (id != 0) {
                var req = {
                    id: id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer-config-job-activity/get',
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
                            $scope.entity = response.data.result;
                            getList();
                        });

                    }).finally(function () {
                    });
            } else {
                $scope.loading = false;
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
                    log.info($scope.job);
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

            var validateMessage = '';

            if ($scope.entity.activityList == null || $scope.entity.activityList.length == 0) {
                validateMessage += "Debe seleccionar al menos una actividad";
            }

            if (validateMessage != '') {
                SweetAlert.swal({
                    html: true,
                    title: "Error de validación",
                    text: validateMessage,
                    type: "error"
                });
                return;
            }

            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-config-job-activity/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    if (response.data.result.sucess !== undefined && response.data.result.error === undefined) {
                        SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                        $scope.reloadData();
                        $scope.onClear();
                    } else if (response.data.result.sucess !== undefined && response.data.result.error !== undefined) {
                        SweetAlert.swal({
                            html: false,
                            title: "Operación exitosa con excepciones",
                            text: "La información ha sido guardada satisfactoriamente, excepto para:\n\n" + response.data.result.error.join('.'),
                            type: "success"
                        });
                        $scope.reloadData();
                        $scope.onClear();
                    } else {
                        SweetAlert.swal({
                            html: false,
                            title: "Error de validación",
                            text: response.data.result.error.join('.'),
                            type: "error"
                        });
                    }
                });
            }).catch(function (response) {
                SweetAlert.swal("Error de guardado", response.data.message, "error");
            }).finally(function () {

            });
        };

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", 0);
            }
        };

        $scope.onClear = function() {
            init();
        }

        $scope.refreshWorkPlace = function () {
            getList();
        }

        $scope.refreshMacro = function () {
            getList();
        }

        $scope.refreshProcess = function () {
            getList();
        }

        $scope.onRefreshJob = function () {
            getList();
        }

        $scope.onRefreshActivity = function () {
            getList();
        }

        $scope.onSelectWorkplace = function() {
            getList();
        }

        $scope.onSelectMacroprocess = function() {
            getList();
        }

        $scope.onSelectProcess = function() {
            getList();
        }

        //----------------------------------------------------------------ACTIVITIES
        $scope.onAddActivity = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/data_table_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerConfigActivityListCtrl',
                scope: $scope
            });
            modalInstance.result.then(function () {

            }, function() {

            });
        };

        $scope.initializeActivity = function(activity) {
            if ($scope.entity.activityList === undefined || $scope.entity.activityList == null) {
                $scope.entity.activityList = [];
            }

            var result = $filter('filter')($scope.entity.activityList, {id: activity.id});

            if (result.length == 0) {
                toaster.pop("success", "Adición", "El registro ha sido adicionada satisfactoriamente");
                $scope.entity.activityList.push(
                    {
                        id: activity.id,
                        name: activity.name,
                        isRoutine: false,
                    }
                );
            }
        }

        $scope.onRemoveActivity = function (index) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Desea confirmar la eliminación de este registro?",
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
                            $scope.entity.activityList.splice(index, 1);
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }


        //-------------------------------------------------LIST
        $scope.dtOptionsCustomerConfigJobActivity = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
			.withOption('responsive', true)
            .withOption('ajax', {
				data: function (d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-config-job-activity',
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

        $scope.dtColumnsCustomerConfigJobActivity = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var configHazardTemplate = '<a class="btn btn-info btn-xs hazardRow lnk" href="#"  uib-tooltip="Adicionar Peligro Existente" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-cog"></i></a> ';
                    var editdTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#"  uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("entity_view")) {
                    }
                    actions += configHazardTemplate;

                    if ($rootScope.can("entity_edit")) {
                    }

                    if ($rootScope.can("entity_delete")) {
                    }

                    actions += editdTemplate;
                    actions += deleteTemplate;

                    return actions;
                }),
            DTColumnBuilder.newColumn('workplace').withTitle($translate.instant('grid.matrix.WORK-PLACE')).withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('macroprocess').withTitle($translate.instant('grid.matrix.MACROPROCESS')).withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('process').withTitle($translate.instant('grid.matrix.PROCESS')).withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activity').withTitle($translate.instant('grid.matrix.ACTIVITY')).withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('updatedBy').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Fecha Última Actualización").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
               if (typeof data.updatedAt == 'object' && data.updatedAt) {
                    var $updatedAt = new moment(data.updatedAt.date);
                    return $updatedAt.format('DD-MM-YYYY HH:mm');
               }
                return data.updatedAt;
            }),
        ];

        var loadRow = function () {

            angular.element("#dtCustomerConfigJobActivity a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                onLoadRecord(id);
            });

            angular.element("#dtCustomerConfigJobActivity a.hazardRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onAddHazard({ id:id });
            });

            angular.element("#dtCustomerConfigJobActivity a.delRow").on("click", function () {
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
                                url: 'api/customer-config-job-activity/delete',
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

		$scope.dtInstanceCustomerConfigJobActivityCallback = function (instance) {
            $scope.dtInstanceCustomerConfigJobActivity = instance;
        };

        $scope.reloadData = function () {
			if ($scope.dtInstanceCustomerConfigJobActivity != null) {
				$scope.dtInstanceCustomerConfigJobActivity.reloadData();
			}
        };

        $scope.onAddHazard = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/sgsst/hazard/customer_profile_config_sgsst_job_activity_hazard_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerConfigJobActivityHazardCtrl',
                scope: $scope,
                resolve: {
                    activity: function () {
                        return dataItem;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {

            });
        };
    }
]);

app.controller('ModalInstanceSideCustomerConfigActivityListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $translate) {

    $scope.title = $translate.instant("views.MATRIX-ACTIVITY-AVAILABLE");

    $scope.onCloseModal = function () {
        $uibModalInstance.close();
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var onLoadRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id,
            };
            $http({
                method: 'GET',
                url: 'api/customer/config-sgsst/activity/get',
                params: req
            })
                .catch(function (response) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.initializeActivity(response.data.result);
                    });

                }).finally(function () {

                });

        } else {
            $scope.loading = false;
        }
    }

    $scope.dtInstanceCommonDataTableList = {};
    $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerId = $stateParams.customerId;
                return d;
            },
            url: 'api/customer/config-sgsst/activity',
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

    $scope.dtColumnsCommonDataTableList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar"  data-id="' + data.id + '"  data-text="' + data.name + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

            DTColumnBuilder.newColumn('name').withTitle($translate.instant("grid.matrix.ACTIVITY")),
            DTColumnBuilder.newColumn('isCritical').withTitle("Crítica").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';

                    if (data) {
                        label = 'label label-info';
                        text = 'Si';
                    } else {
                        label = 'label label-warning';
                        text = 'No';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';


                    return status;
                }),
            DTColumnBuilder.newColumn('status.item').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch (data) {
                        case "Activo":
                            label = 'label label-success';
                            break;

                        case "Inactivo":
                            label = 'label label-danger';
                            break;

                        case "Retirado":
                            label = 'label label-warning';
                            break;
                    }

                    var status = '<span class="' + label + '">' + data + '</span>';


                    return status;
            }),
    ];

    var loadRow = function () {
        $("#dtCommonDataTableList a.editRow").on("click", function () {
            var id = $(this).data("id");
            var name = $(this).data("text");
            $scope.initializeActivity( {id: id, name: name} );
        });
    };

    $scope.dtInstanceCommonDataTableListCallback = function (instance) {
        $scope.dtInstanceCommonDataTableList = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceCommonDataTableList.reloadData();
    };

});
