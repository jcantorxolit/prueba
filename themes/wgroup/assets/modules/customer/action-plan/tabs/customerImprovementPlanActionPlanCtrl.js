'use strict';
/**
 * controller for Customers
 */
app.controller('customerImprovementPlanActionPlanCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'FileUploader', '$localStorage', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, FileUploader, $localStorage, $aside) {


        $scope.loading = true;
        $scope.isCreate = $scope.$parent.currentId == 0;
        $scope.isView = $scope.$parent.editMode == "view";
        $scope.canShowPanel = false;

        console.log($scope.$parent.editMode);

        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy HH:mm"
        };

        $scope.responsibleList = [];

        $scope.typesAlert = $filter('orderBy')($rootScope.parameters("tracking_alert_type"), 'id', false);
        $scope.typesTime = $rootScope.parameters("tracking_alert_timeType");
        $scope.statusAlert = $rootScope.parameters("tracking_alert_status");
        $scope.preferencesAlert = $rootScope.parameters("tracking_alert_preference");

        $scope.typeList = $rootScope.parameters("improvement_plan_type");

        var loadList = function () {
            var req = {
                customer_id: $stateParams.customerId,
                improvement_id: $scope.$parent.currentId
            };

            return $http({
                method: 'POST',
                url: 'api/customer/improvement-plan-action-plan/list-data',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.responsibleList = response.data.data.responsible;
                    $scope.causeList = response.data.data.improvementCauseList;
                    $scope.entryList = response.data.data.entry;
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });


        };

        loadList();

        $scope.onLoadRecord = function (id) {
            if (id != 0) {
                var req = {
                    id: id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/improvement-plan-action-plan',
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
                        console.log(response);
                        $timeout(function () {
                            $scope.plan = response.data.result;

                            if ($scope.plan.endDate != null) {
                                $scope.plan.endDate = new Date($scope.plan.endDate.date);
                            }
                        }, 400);

                    }).finally(function () {
                        $document.scrollTop(40, 2000);
                    });
            }
        }

        $scope.onLoadImprovementPlanRecord = function (id) {
            if (id != 0) {
                var req = {
                    id: id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/improvement-plan',
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
                            $scope.improvement = response.data.result;

                            $scope.canShowPanel = true;
                        }, 400);

                    }).finally(function () {

                    });
            }
        }

        var init = function () {
            $scope.plan = {
                id: 0,
                customerImprovementPlanId: $scope.$parent.currentId,
                cause: null,
                rootCause: null,
                activity: '',
                entry: null,
                amount: 0,
                endDate: null,
                responsible: null,
                status: null,
                notifiedList: []
            };

            $scope.isView = $scope.$parent.editMode == "view" ? true : false;
        };

        init();


        $scope.improvement = {
            id: $scope.$parent.currentId
        }

        $scope.onLoadImprovementPlanRecord($scope.improvement.id);

        $scope.master = $scope.plan;
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
                $scope.clear();
            }
        };

        var save = function () {

            var data = JSON.stringify($scope.plan);
            var req = {
                data: Base64.encode(data)
            };
            return $http({
                method: 'POST',
                url: 'api/customer/improvement-plan-action-plan/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    init();
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.reloadData();
            });
        };

        var update = function (id) {

            var data = JSON.stringify(
                {
                    id: id,
                    reason: null,
                    status: { value: 'CO' }
                }
            );
            var req = {
                data: Base64.encode(data)
            };
            return $http({
                method: 'POST',
                url: 'api/customer-improvement-plan-action-plan/update',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Actualización exitosa.');
                    $scope.reloadData();
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.reloadData();
            });
        };


        //----------------------------------------------------------------CAUSES
        $scope.dtOptionsCustomerImprovementPlanActionPlan = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    d.customerImprovementPlanId = $scope.$parent.currentId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-improvement-plan-action-plan',
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
            .withOption('serverSide', true)
            .withOption('processing', true)
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

        $scope.dtColumnsCustomerImprovementPlanActionPlan = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var isView = data.statusCode == 'AB' ? 0 : 1;

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var cancelTemplate = '<a class="btn btn-danger btn-xs cancelRow lnk" href="#" uib-tooltip="Cancelar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-ban"></i></a> ';

                    var completeTemplate = ' | <a class="btn btn-success btn-xs completeRow lnk" href="#" uib-tooltip="Completar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-check-circle-o"></i></a> ';

                    var openTemplate = ' | <a class="btn btn-dark-azure btn-xs openRow lnk" href="#" uib-tooltip="Reabrir" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-refresh"></i></a> ';

                    var taskTemplate = ' | <a class="btn btn-green btn-xs taskRow lnk" href="#" uib-tooltip="Tareas" data-id="' + data.id + '" data-view="' + isView + '">' +
                        '   <i class="fa fa-list"></i></a> ';

                    if (data.statusCode == 'CO' || data.statusCode == 'CA') {
                        actions += viewTemplate;
                    } else {
                        actions += editTemplate;
                    }

                    actions += taskTemplate;

                    if (data.statusCode == 'AB') {
                        if ($rootScope.can('plan_accion_complete')) {
                            actions += completeTemplate;
                        }
                        if ($rootScope.can('plan_accion_cancel')) {
                            actions += cancelTemplate;
                        }
                    }

                    if (data.statusCode == 'CO' && ($scope.improvement.status == null || $scope.improvement.status.value == 'AB')) {
                        if ($rootScope.can('plan_accion_reopen')) {
                            actions += openTemplate;
                        }
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn(null).withTitle("Fecha Cierre").withOption('width', 150).withOption('defaultContent', '')
                .renderWith(function (data, type, full, meta) {
                    return data.endDate ? moment(data.endDate).format('DD/MM/YYYY') : null;
                }),
            DTColumnBuilder.newColumn('activity').withTitle("Actividad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('responsible').withTitle("Responsable").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('entry').withTitle("Rubro").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('amount').withTitle("Valor").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200).withOption('defaultContent', '')
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-success';
                var text = data.status;

                switch (data.statusCode) {
                    case "AB":
                        label = 'label label-dark-azure'
                        break;

                    case "CO":
                        label = 'label label-success'
                        break;

                    case "CA":
                        label = 'label label-danger'
                        break;
                }

                return '<span class="' + label + '">' + text + '</span>';
            })
        ];

        var loadRow = function () {

            angular.element("#dtCustomerImprovementPlanActionPlan a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.isView = true;
                $scope.onLoadRecord(id);
            });

            angular.element("#dtCustomerImprovementPlanActionPlan a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.isView = false;
                $scope.onLoadRecord(id);
            });

            angular.element("#dtCustomerImprovementPlanActionPlan a.taskRow").on("click", function () {
                var id = angular.element(this).data("id");
                var isView = angular.element(this).data("view") == 1;
                onOpenTaskModal( {id: id}, isView);
            });

            angular.element("#dtCustomerImprovementPlanActionPlan a.cancelRow").on("click", function () {
                var id = angular.element(this).data("id");
                onOpenUpdateModal( {id: id, status: 'CA' });
            });

            angular.element("#dtCustomerImprovementPlanActionPlan a.openRow").on("click", function () {
                var id = angular.element(this).data("id");
                onOpenUpdateModal( {id: id, status: 'AB' });
            });

            angular.element("#dtCustomerImprovementPlanActionPlan a.completeRow").on("click", function () {
                var id = angular.element(this).data("id");
                update(id);
            });

            angular.element("#dtCustomerImprovementPlanActionPlan a.delRow").on("click", function () {
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
                                url: 'api/customer/improvement-plan-action-plan/delete',
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

        $scope.dtInstanceCustomerImprovementPlanActionPlanCallback = function (dtInstance) {
            $scope.dtInstanceCustomerImprovementPlanActionPlan = dtInstance;
        }

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerImprovementPlanActionPlan.reloadData();
        };


        //----------------------------------------------------------------NOTIFIED
        $scope.onAddNotified = function () {

            $timeout(function () {
                if ($scope.plan.notifiedList == null) {
                    $scope.plan.notifiedList = [];
                }else {
                    console.log($scope.plan.notifiedList);
                    $scope.plan.notifiedList.push(
                        {
                            id: 0,
                            customerImprovementPlanActionPlanId: 0,
                            responsible: null
                        }
                    );
                }

            });
        };

        $scope.onRemoveNotified = function (index) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Desea confirmar la eliminación de este registro ?",
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
                            var date = $scope.plan.notifiedList[index];

                            $scope.plan.notifiedList.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/improvement-plan-action-plan-notified/delete',
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


        //----------------------------------------------------------------TASK
        var onOpenTaskModal = function (plan, isView) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_improvement_plan_action_plan_task.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/action-plan/tabs/customer_improvement_plan_action_plan_task_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideImprovementPlanActionPlanTaskCtrl',
                scope: $scope,
                resolve: {
                    plan: function () {
                        return plan;
                    },
                    isView: function () {
                        return isView;
                    },
                    improvement: function () {
                        return {id: $scope.$parent.currentId};
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {
                $scope.reloadData();
            });
        }

        //----------------------------------------------------------------RE OPEN
        var onOpenUpdateModal = function (plan) {

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_improvement_plan_action_plan_task.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/action-plan/tabs/customer_improvement_plan_action_plan_comment_edit_modal.htm',
                placement: 'right',
                size: 'sm',
                backdrop: true,
                controller: 'ModalInstanceSideImprovementPlanActionPlanUpdateCtrl',
                scope: $scope,
                resolve: {
                    plan: function () {
                        return plan;
                    },
                    improvement: function () {
                        return {id: $scope.$parent.currentId};
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {
                $scope.reloadData();
            });
        }

        $scope.$watch("$parent.reload", function () {
            if ($scope.$parent.reload) {
                $scope.onLoadImprovementPlanRecord($scope.$parent.currentId);
                $scope.$parent.reload = false;
            }
        });

        $scope.cancelEdition = function () {
            init();
        }

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.$parent.$parent.$parent.$parent.navToSection("list", "list", 0);
            }
        }

        $scope.refreshCause = function () {
            loadList();
        }

    }
]);


app.controller('ModalInstanceSideImprovementPlanActionPlanTaskCtrl', function ($rootScope, $stateParams, $scope, $uibModal, $uibModalInstance, plan, isView, improvement, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    //$scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;
    $scope.isView = isView;

    // Preparamos los parametros por grupo
    $scope.responsibleList = [];
    $scope.taskTypeList = $rootScope.parameters("improvement_plan_action_plan_task_type");
    $scope.statusList = $rootScope.parameters("improvement_plan_action_plan_task_status");
    ;

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var loadList = function () {

        var req = {
            customer_id: $stateParams.customerId,
            improvement_id: improvement.id,
        };

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan-action-plan/list-data',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.responsibleList = response.data.data.responsible;
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    loadList();

    $scope.onLoadRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id
            };
            $http({
                method: 'GET',
                url: 'api/customer/improvement-plan-action-plan-task',
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
                        $scope.task = response.data.result;
                        initializeDates();
                    }, 400);

                }).finally(function () {

                });
        }
    }

    $scope.onLoadActionPlanRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id
            };
            $http({
                method: 'GET',
                url: 'api/customer/improvement-plan-action-plan',
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
                        $scope.plan = response.data.result;
                    }, 400);

                }).finally(function () {

                });
        }
    }

    var init = function () {
        $scope.task = {
            id: 0,
            customerImprovementPlanActionPlanId: plan.id,
            description: '',
            type: null,
            responsible: null,
            startDate: null,
            endDate: null,
            duration: null,
            status: null
        };
    };

    init();

    var initActionPlan = function () {
        $scope.plan = {
            id: plan.id,
            cause: null,
            rootCause: null,
            activity: '',
        };
    };

    initActionPlan();

    var initializeDates = function () {
        if ($scope.task.startDate != null) {
            $scope.task.startDate = new Date($scope.task.startDate.date);
            //$scope.maxDate = $scope.tracking.startDate;
        }

        if ($scope.task.endDate != null) {
            $scope.task.endDate = new Date($scope.task.endDate.date);
            //$scope.maxDate = $scope.tracking.startDate;
        }
    }

    $scope.onLoadActionPlanRecord($scope.plan.id);

    $scope.master = $scope.task;
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
            $scope.clear();
        }
    };

    var save = function () {

        var data = JSON.stringify($scope.task);
        var req = {
            data: Base64.encode(data)
        };
        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan-action-plan-task/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                init();
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.reloadData();
        });
    };

    var onUpdate = function (task) {

        var data = JSON.stringify(task);
        var req = {
            data: Base64.encode(data)
        };
        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan-action-plan-task/update',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                init();
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.reloadData();
        });
    };

    //----------------------------------------------------------------TASKS
    var request = {
        improvement_action_plan_id: plan.id
    }

    $scope.dtInstanceCustomerImprovementPlanActionPlanTask = {};
    $scope.dtOptionsCustomerImprovementPlanActionPlanTask = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/customer/improvement-plan-action-plan-task',
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

    $scope.dtColumnsCustomerImprovementPlanActionPlanTask = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";

                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';

                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';

                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Cancelar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-ban"></i></a> ';

                var reScheduleTemplate = ' | <a class="btn btn-info btn-xs reScheduleRow lnk" href="#" uib-tooltip="Reprogramar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-bell-o"></i></a> ';

                var approvedTemplate = ' | <a class="btn btn-success btn-xs approvedRow lnk" href="#" uib-tooltip="Aprobar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-check-circle"></i></a> ';

                var reOpenTemplate = '<a class="btn btn-warning btn-xs reOpenRow lnk" href="#" uib-tooltip="Reabrir" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-recycle"></i></a> ';

                if (!$scope.isView) {
                    if (data.status.value == "A" || data.status.value == "R") {
                        actions += editTemplate;

                        actions += deleteTemplate;

                        actions += reScheduleTemplate;

                        actions += approvedTemplate;
                    } else {
                        actions += reOpenTemplate;
                    }
                } else {
                    actions += viewTemplate;
                }

                return actions;
            }),
        DTColumnBuilder.newColumn('type.item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('description').withTitle("Tarea").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('startDateFormat').withTitle("Inicio").withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('endDateFormat').withTitle("Fin").withOption('defaultContent', ''),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 150)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = '';

                if (data.status) {

                    switch (data.status.value) {
                        case 'A':
                            label = 'label label-success';
                            break;

                        case 'R':
                            label = 'label label-warning';
                            break;

                        case 'F':
                            label = 'label label-info';
                            break;

                        case 'C':
                            label = 'label label-danger';
                            break;
                    }
                }

                var status = '<span class="' + label + '">' + data.status.item + '</span>';

                return status;
            }),
    ];

    var loadRow = function () {

        angular.element("#dtCustomerImprovementPlanActionPlanTask a.viewRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.onLoadRecord(id);
        });

        angular.element("#dtCustomerImprovementPlanActionPlanTask a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.onLoadRecord(id);
        });

        angular.element("#dtCustomerImprovementPlanActionPlanTask a.reScheduleRow").on("click", function () {
            var id = angular.element(this).data("id");
            onReScheduleTask(id);
        });

        angular.element("#dtCustomerImprovementPlanActionPlanTask a.approvedRow").on("click", function () {
            var id = angular.element(this).data("id");
            onCompleteTask(id);
        });

        angular.element("#dtCustomerImprovementPlanActionPlanTask a.reOpenRow").on("click", function () {
            var id = angular.element(this).data("id");
            onReOpenTask(id);
        });

        angular.element("#dtCustomerImprovementPlanActionPlanTask a.delRow").on("click", function () {
            var id = angular.element(this).data("id");

            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Cancelará la tarea seleccionada.",
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

                        onCancelTask(id);

                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });

    };

    $scope.dtInstanceCustomerImprovementPlanActionPlanTaskCallback = function (dtInstance) {
        $scope.dtInstanceCustomerImprovementPlanActionPlanTask = dtInstance;
    }

    $scope.reloadData = function () {
        $scope.dtInstanceCustomerImprovementPlanActionPlanTask.reloadData();
    };

    var onCancelTask = function (id) {
        var task = {
            id: id,
            status: "C"
        }

        //var modalInstance = $uibModal.open({
        var modalInstance = $uibModal.open({
            //templateUrl: 'app_modal_improvement_plan_action_plan_task_tracking_actions.html',
            templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/action-plan/tabs/customer_improvement_plan_action_plan_task_tracking_modal.htm',
            controller: 'ModalInstanceSideImprovementPlanActionPlanTaskTrackingCtrl',
            windowTopClass: 'top-modal',
            resolve: {
                task: function () {
                    return task;
                },
                action: function () {
                    return "Cancelar";
                }
            }
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        }, function() {

        });
    };

    var onCompleteTask = function (id) {
        var task = {
            id: id,
            status: "F"
        }

        onUpdate(task);
    };

    var onReScheduleTask = function (id) {

        var task = {
            id: id,
            status: "R"
        }

        //var modalInstance = $uibModal.open({
        /*var modalInstance = $aside.open({
            templateUrl: 'app_modal_improvement_plan_action_plan_task_tracking_actions.html',
            controller: 'ModalInstanceSideImprovementPlanActionPlanTaskTrackingCtrl',
            resolve: {
                task: function () {
                    return task;
                },
                action: function () {
                    return "Reprogramar";
                }
            }
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        });*/

        var modalInstance = $uibModal.open({
            //templateUrl: 'app_modal_improvement_plan_action_plan_task_tracking_actions.htm',
            templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/action-plan/tabs/customer_improvement_plan_action_plan_task_tracking_modal.htm',
            placement: 'right',
            windowTopClass: 'top-modal',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideImprovementPlanActionPlanTaskTrackingCtrl',
            scope: $scope,
            resolve: {
                task: function () {
                    return task;
                },
                action: function () {
                    return "Reprogramar";
                }
            }
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        }, function() {

        });

    };

    var onReOpenTask = function (id) {

        var task = {
            id: id,
            status: "A"
        }

        //var modalInstance = $uibModal.open({
        /*var modalInstance = $aside.open({
            templateUrl: 'app_modal_improvement_plan_action_plan_task_tracking_actions.html',
            controller: 'ModalInstanceSideImprovementPlanActionPlanTaskTrackingCtrl',
            resolve: {
                task: function () {
                    return task;
                },
                action: function () {
                    return "Reabrir";
                }
            }
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        });*/

        var modalInstance = $uibModal.open({
            //templateUrl: 'app_modal_improvement_plan_action_plan_task_tracking_actions.htm',
            templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/action-plan/tabs/customer_improvement_plan_action_plan_task_tracking_modal.htm',
            placement: 'right',
            size: 'lg',
            backdrop: true,
            windowTopClass: 'top-modal',
            controller: 'ModalInstanceSideImprovementPlanActionPlanTaskTrackingCtrl',
            scope: $scope,
            resolve: {
                task: function () {
                    return task;
                },
                action: function () {
                    return "Reabrir";
                }
            }
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        }, function() {

        });
    };

    $scope.$watch("task.endDate - task.startDate", function () {
        var timeDiff = Math.abs($scope.task.endDate - $scope.task.startDate);
        var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24));
        console.log(diffDays);
        if(diffDays > 0 && diffDays < 2000 ){
            $scope.task.duration = diffDays;
        }

    });

});


app.controller('ModalInstanceSideImprovementPlanActionPlanTaskTrackingCtrl', function ($scope, $uibModalInstance, task, action, $log, $timeout, SweetAlert, $http) {

    $scope.task = {
        id: task.id,
        status: task.status,
        tracking: {
            action: action,
            description: "",
        }
    }

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.master = $scope.task;
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
            $scope.clear();
        }
    };

    var save = function () {
        var req = {};

        var data = JSON.stringify($scope.task);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan-action-plan-task/update',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };
});


app.controller('ModalInstanceSideImprovementPlanActionPlanUpdateCtrl', function ($rootScope, $stateParams, $scope, $uibModal, $uibModalInstance, plan, improvement, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    $scope.loading = true;
    $scope.isView = false;

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var init = function() {
        $scope.entity = {
            id: plan.id,
            reason: '',
            status: { value: plan.status }
        }
    }

    init();

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

        }
    };

    var save = function () {

        var data = JSON.stringify($scope.entity);
        var req = {
            data: Base64.encode(data)
        };
        return $http({
            method: 'POST',
            url: 'api/customer-improvement-plan-action-plan/update',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                toaster.pop('success', 'Operación Exitosa', 'Actualización exitosa.');
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    };

});
