'use strict';
/**
 * controller for Customers
 */
app.controller('customerImprovementPlanEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'FileUploader', '$localStorage', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, FileUploader, $localStorage, $aside) {


        //DISABLE

        var vm = this;
        vm.btnDisabled = false;

        $scope.loading = true;
        $scope.isCreate = $scope.$parent.currentId == 0;
        $scope.isView = $scope.$parent.editMode == "view";

        $scope.fishBoneUrl = 'fish-bone-improvement/' + $scope.$parent.currentId + '/' + Math.random();

        console.log($scope.fishBoneUrl);

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

        $scope.causeList = [];
        $scope.subCauseList = [];
        $scope.subCauseListAll = [];

        var init = function () {
            $scope.improvement = {
                id: $scope.$parent.currentId,
                customerId: null,
                entity: null,
                entityId: null,
                type: null,
                endDate: null,
                description: '',
                observation: '',
                responsible: null,
                isRequiresAnalysis: false,
                status: null,
                trackingList: [],
                alertList: []
            };
        }

        init();

        var initCause = function () {
            $scope.cause = {
                id: 0,
                customerImprovementPlanId: $scope.$parent.currentId,
                cause: null,
                subCauseList: [],
                rootCauseList: []
            };
        }

        initCause();

        $scope.onLoadRecord = function () {
            if ($scope.improvement.id != 0) {
                var req = {
                    id: $scope.improvement.id,
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
                            if (response.data.result != null && response.data.result != '') {
                                $scope.improvement = response.data.result;

                                initializeDates();
                            }
                        }, 400);

                    }).finally(function () {

                    });
            } else {
                $scope.loading = false;
            }
        }

        $scope.onLoadRecord();

        $scope.onLoadCauseRecord = function (id) {
            if (id != 0) {
                var req = {
                    id: id,
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/improvement-plan-cause',
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
                            if (response.data.result != null && response.data.result != '') {
                                $scope.cause = response.data.result;
                            }
                        }, 400);

                    }).finally(function () {

                    });
            } else {
                $scope.loading = false;
            }
        }

        var loadList = function () {

            var req = {
                customer_id: $scope.customerId
            };

            return $http({
                method: 'POST',
                url: 'api/customer/improvement-plan/list-data',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.responsibleList = response.data.data.responsible;
                    $scope.causeList = response.data.data.causeList;
                    $scope.subCauseListAll = response.data.data.subCauseList;
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        loadList();

        $scope.master = $scope.cause;

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

                $scope.cause = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.cause);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer/improvement-plan-cause/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    initCause();
                    $scope.$parent.reload = true;
                });
            }).catch(function (e) {

                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.reloadData();
            });
        };

        var saveImprovementPlan = function () {
            var req = {};
            var data = JSON.stringify($scope.improvement);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer/improvement-plan/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

            }).catch(function (e) {

                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        var initializeDates = function () {
            if ($scope.improvement.endDate != null) {
                $scope.improvement.endDate = new Date($scope.improvement.endDate.date);
            }

            angular.forEach($scope.improvement.trackingList, function (model, key) {
                if (model.startDate != null) {
                    model.startDate = new Date(model.startDate.date);
                }
            });
        }

        //----------------------------------------------------------------TRACKING
        $scope.onAddTracking = function () {

            if (vm.btnDisabled){
                $timeout(function () {
                    if ($scope.improvement.trackingList == null) {
                        $scope.improvement.trackingList = [];
                    }
                    $scope.improvement.trackingList.push(
                        {
                            id: 0,
                            customerImprovementPlanId: 0,
                            responsible: null,
                            startDate: null,
                        }
                    );
                });
            }

        };

        $scope.onRemoveTracking = function (index) {
            if (vm.btnDisabled){

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
                                var date = $scope.improvement.trackingList[index];

                                $scope.improvement.trackingList.splice(index, 1);

                                if (date.id != 0) {
                                    var req = {};
                                    req.id = date.id;
                                    $http({
                                        method: 'POST',
                                        url: 'api/customer/improvement-plan-tracking/delete',
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
        }

        //----------------------------------------------------------------VERIFICATION MODE
        $scope.onAddAlert = function () {

            $timeout(function () {

                if (vm.btnDisabled){
                    if ($scope.improvement.alertList == null) {
                        $scope.improvement.alertList = [];
                    }
                    $scope.improvement.alertList.push(
                        {
                            id: 0,
                            customerImprovementPlanId: 0,
                            type: null,
                            preference: null,
                            time: 0,
                            timeType: null,
                            status: null,
                        }
                    );
                }

            });
        };

        $scope.onRemoveAlert = function (index) {

            if (vm.btnDisabled){
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
                                var date = $scope.improvement.alertList[index];

                                $scope.improvement.alertList.splice(index, 1);

                                if (date.id != 0) {
                                    var req = {};
                                    req.id = date.id;
                                    $http({
                                        method: 'POST',
                                        url: 'api/customer/improvement-plan-alert/delete',
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

        }


        //----------------------------------------------------------------SUB-CAUSE
        $scope.onAddSubCause = function () {

            $timeout(function () {
                if ($scope.cause.subCauseList == null) {
                    $scope.cause.subCauseList = [];
                }
                $scope.cause.subCauseList.push(
                    {
                        id: 0,
                        customerImprovementPlanCauseId: 0,
                        cause: null
                    }
                );
            });
        };

        $scope.onRemoveSubCause = function (index) {
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
                            var date = $scope.cause.subCauseList[index];

                            $scope.cause.subCauseList.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/improvement-plan-cause-sub-cause/delete',
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

        //----------------------------------------------------------------ROOT CAUSE
        $scope.onAddRootCause = function () {

            $timeout(function () {
                if ($scope.cause.rootCauseList == null) {
                    $scope.cause.rootCauseList = [];
                }
                $scope.cause.rootCauseList.push(
                    {
                        id: 0,
                        customerImprovementPlanCauseId: 0,
                        cause: null,
                        probabilityOccurrence: null,
                        effect: null,
                        detectionLevel: null,
                        factor: 0,
                    }
                );
            });
        };

        $scope.onRemoveRootCause = function (index) {
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
                            var date = $scope.cause.rootCauseList[index];

                            $scope.cause.rootCauseList.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/improvement-plan-cause-root-cause/delete',
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


        //----------------------------------------------------------------CAUSES
        var request = {
            improvement_id: $scope.improvement.id
        }

        $scope.dtInstanceImprovementPlanCause = {};
        $scope.dtOptionsImprovementPlanCause = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/improvement-plan-cause',
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

        $scope.dtColumnsImprovementPlanCause = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-root-id="' + data.rootCauseId + '" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    var configTemplate = ' | <a class="btn btn-info btn-xs configRow lnk" href="#" uib-tooltip="Configurar factor de priorización" data-root-id="' + data.rootCauseId + '"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-cog"></i></a> ';

                    var actionPlanTemplate = '<a class="btn btn-dark-orange btn-xs actionPlanRow lnk" href="#" uib-tooltip="Planes de Acción" data-root-id="' + data.rootCauseId + '"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-plus-square"></i></a> ';

                    actions += editTemplate;

                    if (!$scope.isView) {
                        actions += deleteTemplate;
                    }

                    if (data.rootCauseId != null) {
                        actions += configTemplate;
                        actions += actionPlanTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('causeCreatedAt').withTitle("Fecha").withOption('width', 150).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('cause').withTitle("Causa").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('rootCause').withTitle("Causa Raíz").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('factor').withTitle("Factor de Priorización").withOption('width', 100).withOption('defaultContent', ''),
        ];

        var loadRow = function () {

            $("#dtImprovementPlanCause a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onLoadCauseRecord(id);
            });

            $("#dtImprovementPlanCause a.configRow").on("click", function () {
                var id = $(this).data("id");
                var rootId = $(this).data("root-id");
                onOpenConfigModal(rootId);
            });

            $("#dtImprovementPlanCause a.actionPlanRow").on("click", function () {
                var id = $(this).data("id");
                var rootId = $(this).data("root-id");
                onOpenActionPlanModal(id, rootId);
            });

            $("#dtImprovementPlanCause a.delRow").on("click", function () {
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
                                url: 'api/customer/improvement-plan-cause/delete',
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

        $scope.dtInstanceImprovementPlanCauseCallback = function (dtInstance) {
            $scope.dtInstanceImprovementPlanCause = dtInstance;
        }

        $scope.reloadData = function () {
            $scope.dtInstanceImprovementPlanCause.reloadData();
            $scope.fishBoneUrl = 'fish-bone-improvement/' + $scope.improvement.id + '/' + Math.random();
        };


        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                console.log($scope.$parent);
                $scope.$parent.$parent.$parent.$parent.$parent.$parent.navToSection("list", "list", 0);
            }
        }

        $scope.onClear = function () {
            initCause();
        }


        //----------------------------------------------------------------CONFIG ROOT CAUSE PRIORIZATION
        var onOpenConfigModal = function (id) {

            var root = {
                id: id ? id : 0
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_improvement_plan_cause_root_cause.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/action-plan/tabs/customer_improvement_plan_cause_root_cause_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideImprovementPlanCauseRootCauseConfigCtrl',
                scope: $scope,
                resolve: {
                    root: function () {
                        return root;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        }

        //----------------------------------------------------------------CONFIG ROOT CAUSE ACTION PLAN
        var onOpenActionPlanModal = function (id, rootId) {

            var root = {
                id: rootId ? rootId : 0,
                customerImprovementPlanCauseId: id ? id : 0
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_improvement_plan_action_plan.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/action-plan/tabs/customer_improvement_plan_action_plan_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideImprovementPlanActionPlanCtrl',
                scope: $scope,
                resolve: {
                    root: function () {
                        return root;
                    },
                    improvement: function () {
                        return $scope.improvement;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {

            });
        }


        //----------------------------------------------------------------$WATCH
        $scope.$watch("improvement.isRequiresAnalysis", function (newValue, oldValue, scope) {

            if (oldValue != null && !angular.equals(newValue, oldValue)) {
                saveImprovementPlan();
            }

        });

        $scope.$watch("cause.cause", function (newValue, oldValue, scope) {

            $scope.subCauseList = [];

            if (oldValue != null && !angular.equals(newValue, oldValue)) {
                angular.forEach($scope.cause.subCauseList, function (model, key) {
                    model.cause = null;
                });
            }

            if ($scope.cause.cause != null) {
                $scope.subCauseList = $filter('filter')($scope.subCauseListAll, {improvementPlanCauseCategoryId: $scope.cause.cause.id});
            }
        });

    }]);

app.controller('ModalInstanceSideImprovementPlanCauseRootCauseConfigCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, root, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;


    $scope.cause = {};

    // Preparamos los parametros por grupo
    $scope.probabilityOccurrenceList = $rootScope.parameters("improvement_plan_root_cause_probability_occur");
    $scope.effectList = $rootScope.parameters("improvement_plan_root_cause_effect");
    $scope.levelList = $rootScope.parameters("improvement_plan_root_detection_level");
    $scope.prioritizationFactorList = $rootScope.parameters("improvement_plan_root_prioritization_factor");
    $scope.prioritizationFactorResultText = "";

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.cause.id != 0) {
            var req = {
                id: $scope.cause.id
            };
            $http({
                method: 'GET',
                url: 'api/customer/improvement-plan-cause-root-cause',
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
                        $scope.cause = response.data.result;
                    }, 400);

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        } else {
            $scope.loading = false;
        }
    }

    var init = function () {
        $scope.cause = {
            id: root.id,
            customerImprovementPlanCauseId: null,
            cause: null,
            probabilityOccurrence: null,
            effect: null,
            detectionLevel: null,
            factor: 0
        };
    };

    init();

    $scope.onLoadRecord();

    $scope.$watch("cause.probabilityOccurrence", function (newValue, oldValue, scope) {
        calculateFactor();
    });

    $scope.$watch("cause.effect", function (newValue, oldValue, scope) {
        calculateFactor();
    });

    $scope.$watch("cause.detectionLevel", function (newValue, oldValue, scope) {
        calculateFactor();
    });

    var calculateFactor = function () {
        var probability = $scope.cause.probabilityOccurrence ? parseInt($scope.cause.probabilityOccurrence.value) : 0
        var effect = $scope.cause.effect ? parseInt($scope.cause.effect.value) : 0
        var detectionLevel = $scope.cause.detectionLevel ? parseInt($scope.cause.detectionLevel.value) : 0

        $scope.cause.factor = probability * effect * detectionLevel

        var $result = $filter("filter")($scope.prioritizationFactorList, {value: $scope.cause.factor.toString()}, true);

        if ($result.length > 0) {
            $scope.prioritizationFactorResultText = $result[0].item;
        }
    }

    $scope.master = $scope.cause;
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

        var data = JSON.stringify($scope.cause);
        var req = {
            data: Base64.encode(data)
        };
        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan-cause-root-cause/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $scope.onCloseModal()
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
        });
    };
});

app.controller('ModalInstanceSideImprovementPlanActionPlanCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, root, improvement, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;


    $scope.cause = {
        id: root.id
    };

    // Preparamos los parametros por grupo
    $scope.responsibleList = [];
    $scope.entryList = [];

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var loadList = function () {

        var req = {
            customer_id: $stateParams.customerId,
            cause_id: root.customerImprovementPlanCauseId,
        };

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan-action-plan/list-data',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.responsibleList = response.data.data.responsible;
                $scope.causeList = response.data.data.causeList;
                $scope.rootCauseList = response.data.data.rootCauseList;
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

                    $timeout(function () {
                        $scope.plan = response.data.result;

                        if ($scope.plan.endDate != null) {
                            $scope.plan.endDate = new Date($scope.plan.endDate.date);
                        }
                    }, 400);

                }).finally(function () {

                });
        }
    }

    $scope.onLoadCauseRecord = function () {
        if ($scope.cause.id != 0) {
            var req = {
                id: $scope.cause.id
            };
            $http({
                method: 'GET',
                url: 'api/customer/improvement-plan-cause-root-cause',
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
                        $scope.cause = response.data.result;
                        $scope.plan.cause = $scope.cause.parent ? $scope.cause.parent : null;
                        $scope.plan.rootCause = $scope.cause ? $scope.cause : null;
                    }, 400);

                }).finally(function () {
                });
        }
    }

    $scope.onLoadCauseRecord();

    var init = function () {
        $scope.plan = {
            id: root.id,
            customerImprovementPlanId: improvement.id,
            cause: $scope.cause.parent ? $scope.cause.parent : null,
            rootCause: $scope.cause ? $scope.cause : null,
            activity: '',
            entry: null,
            amount: 0,
            endDate: null,
            responsible: null,
            status: null,
            notifiedList: []
        };
    };

    init();

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


    //----------------------------------------------------------------CAUSES
    $scope.dtOptionsImprovementPlanActionPlan = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerId = $stateParams.customerId;
                d.customerImprovementPlanCauseRootCauseId = root.id;
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

    $scope.dtColumnsImprovementPlanActionPlan = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                actions += editTemplate;

                if (!$scope.isView) {
                    actions += deleteTemplate;
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
                        label = 'label label-info'
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

        $("#dtImprovementPlanActionPlan a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.onLoadRecord(id);
        });

        $("#dtImprovementPlanActionPlan a.delRow").on("click", function () {
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

    $scope.dtInstanceImprovementPlanActionPlanCallback = function (dtInstance) {
        $scope.dtInstanceImprovementPlanActionPlan = dtInstance;
    }

    $scope.reloadData = function () {
        $scope.dtInstanceImprovementPlanActionPlan.reloadData();
    };


    //----------------------------------------------------------------NOTIFIED
    $scope.onAddNotified = function () {

        $timeout(function () {
            if ($scope.plan.notifiedList == null) {
                $scope.plan.notifiedList = [];
            }
            $scope.plan.notifiedList.push(
                {
                    id: 0,
                    customerImprovementPlanActionPlanId: 0,
                    responsible: null
                }
            );
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
});
