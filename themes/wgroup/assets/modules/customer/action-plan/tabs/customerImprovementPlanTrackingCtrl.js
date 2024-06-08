'use strict';
/**
 * controller for Customers
 */
app.controller('customerImprovementPlanTrackingCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'FileUploader', '$localStorage', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, FileUploader, $localStorage, $aside) {


        $scope.loading = true;
        $scope.isCreate = $scope.$parent.currentId == 0;
        $scope.isView = $scope.$parent.editMode == "view";

        $scope.responsibleList = [];

        $scope.typesAlert = $filter('orderBy')($rootScope.parameters("tracking_alert_type"), 'id', false);
        $scope.typesTime = $rootScope.parameters("tracking_alert_timeType");
        $scope.statusAlert = $rootScope.parameters("tracking_alert_status");
        $scope.preferencesAlert = $rootScope.parameters("tracking_alert_preference");

        $scope.typeList = $rootScope.parameters("improvement_plan_type");

        var loadList = function () {

            var req = {
                customer_id: $stateParams.customerId
            };

            return $http({
                method: 'POST',
                url: 'api/customer/improvement-plan/list-data',
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

        $scope.master = $scope.tracking;

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

                $scope.tracking = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.tracking);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer/improvement-plan-tracking/save',
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
        var request = {
            improvement_id: $scope.$parent.currentId
        }

        $scope.dtInstanceCustomerImprovementPlanTracking = {};
        $scope.dtOptionsCustomerImprovementPlanTracking = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/improvement-plan-tracking',
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

        $scope.dtColumnsCustomerImprovementPlanTracking = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    actions += editTemplate;

                    return !$scope.isView ? actions : null;
                }),
            DTColumnBuilder.newColumn('startDateFormat').withTitle("Fecha").withOption('width', 150).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {

                    if (data.status == null) {
                        return '';
                    }

                    var label = '';
                    switch  (data.status.value) {
                        case "A":
                            label = 'label label-success';
                            break;

                        case "F":
                            label = 'label label-danger';
                            break;
                    }

                    var status = '<span class="' + label +'">' + data.status.item + '</span>';


                    return status;
                }),
            DTColumnBuilder.newColumn('responsible.name').withTitle("Responsable").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('updated_at').withTitle("Fecha modificación").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observation').withTitle("Observación").withOption('width', 100).withOption('defaultContent', ''),
        ];

        var loadRow = function () {

            $("#dtCustomerImprovementPlanTracking a.editRow").on("click", function () {
                var id = $(this).data("id");
                onOpenTrackingModal(id);
            });

            $("#dtCustomerImprovementPlanTracking a.delRow").on("click", function () {
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
                                url: 'api/customer/improvement-plan-tracking/delete',
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

        $scope.dtInstanceCustomerImprovementPlanTrackingCallback = function (dtInstance) {
            $scope.dtInstanceCustomerImprovementPlanTracking = dtInstance;
        }

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerImprovementPlanTracking.reloadData();
        };

        $scope.onAddTracking = function() {
            onOpenTrackingModal();
        }

        //----------------------------------------------------------------EDIT - CREATE TRACKING
        var onOpenTrackingModal = function (id) {

            var tracking = {
                id: id ? id : 0,
                customerImprovementPlanId: $scope.$parent.currentId
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_improvement_plan_tracking.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/action-plan/tabs/customer_improvement_plan_tracking_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideImprovementPlanTrackingCtrl',
                scope: $scope,
                resolve: {
                    tracking: function () {
                        return tracking;
                    },
                    improvement: function () {
                        return { id: $scope.$parent.currentId };
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        }

    }]);

app.controller('ModalInstanceSideImprovementPlanTrackingCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, tracking, improvement, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    if (tracking.id == 0) {
        $scope.maxDate = new Date();
    }

    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;

    // Preparamos los parametros por grupo
    $scope.responsibleList = [];
    $scope.statusList = $rootScope.parameters("improvement_plan_tracking_status");;

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var init = function () {
        $scope.tracking = {
            id: tracking.id,
            customerImprovementPlanId: tracking.customerImprovementPlanId,
            startDate: null,
            responsible: null,
            observation: '',
            status: null
        };
    }

    init();

    var loadList = function () {

        var req = {
            customer_id: $stateParams.customerId,
        };

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan/list-data',
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
                url: 'api/customer/improvement-plan-tracking',
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
                        $scope.tracking = response.data.result;
                        initializeDates();
                    }, 400);

                }).finally(function () {

                });
        }
    }

    var init = function () {
        $scope.tracking = {
            id: tracking.id,
            customerImprovementPlanId: improvement.id,
            startDate: null,
            responsible: null,
            observation: '',
            status: null
        };
    };

    init();

    var initializeDates = function () {
        if ($scope.tracking.startDate != null) {
            $scope.tracking.startDate = new Date($scope.tracking.startDate.date);
            $scope.maxDate = $scope.tracking.startDate;
        }
    }

    $scope.onLoadRecord($scope.tracking.id);

    $scope.master = $scope.tracking;
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

        var data = JSON.stringify($scope.tracking);
        var req = {
            data: Base64.encode(data)
        };
        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan-tracking/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
        });
    };

});
