'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerTrackingEditCtrl', ['$scope', '$stateParams', '$log', '$compile', '$state', 'SweetAlert', 'DTOptionsBuilder', 'DTColumnBuilder',
    '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'ListService',
    function ($scope, $stateParams, $log, $compile, $state, SweetAlert, DTOptionsBuilder, DTColumnBuilder,
        $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, ListService) {

        var log = $log;

        var request = {};
        var currentId = $scope.$parent.currentTraking ? $scope.$parent.currentTraking : 0;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        // parametros para seguimientos
        $scope.agents = $rootScope.agents();
        $scope.typesTraking = $rootScope.parameters("tracking_tiposeg");
        $scope.statusTracking = $rootScope.parameters("tracking_status");
        $scope.isView = $scope.$parent.actionMode == "view";
        $scope.minDateCurrent = new Date();
        $scope.customerId = $stateParams.customerId;
        $scope.users = [];

        // parametros para alert
        $scope.typesAlert = $filter('orderBy')($rootScope.parameters("tracking_alert_type"), 'id', false);
        $scope.typesTime = $rootScope.parameters("tracking_alert_timeType");
        $scope.statusAlert = $rootScope.parameters("tracking_alert_status");
        $scope.perferencesAlert = $rootScope.parameters("tracking_alert_preference");

        function getList() {

            var entities = [
                { name: 'current_datetime', value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.tracking.createdAt = response.data.data.currentDateTime;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var initialize = function () {
            $scope.tracking = {
                id: currentId,
                customerId: $scope.customerId,
                agent: null,
                type: null,
                status: null,
                isVisible: $scope.isCustomer,
                isEventSchedule: 0,
                isCustomer: $scope.isCustomer,
                module: "tracking",
                observation: "",
                comment: [],
                comments: [],
                eventDate: new Date(),
                alerts: [],
                notifications: [],
                createdAt: null
            };

            initializeAlerts();
            initializeNotifications();
            getList();
        }

        var initializeAlerts = function () {
            if ($scope.tracking.alerts == null || $scope.tracking.alerts.length == 0) {
                $scope.tracking.alerts = [
                    {
                        id: 0,
                        type: null,
                        timeType: null,
                        time: 0,
                        preference: null,
                        sent: 0,
                        status: null
                    }
                ];
            }
        }

        var initializeNotifications = function () {
            if ($scope.tracking.notifications == null || $scope.tracking.notifications.length == 0) {
                $scope.tracking.notifications = [
                    {
                        id: 0,
                        trackingId: $scope.$parent.currentTraking,
                        user: null
                    }
                ];
            }
        }

        initialize();

        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy HH:mm"
        };


        $scope.cancelEdition = function (index) {
            if ($scope.isView) {
                if ($scope.$parent != null) {
                    $scope.$parent.navToSection("list", "list");
                }
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
                                if ($scope.$parent != null) {
                                    $scope.$parent.navToSection("list", "list");
                                }
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        var onLoadRecord = function () {
            if ($scope.tracking.id) {
                var req = {
                    id: currentId
                };

                $http({
                    method: 'GET',
                    url: 'api/tracking',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () { $state.go(messagered); }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Información no disponible", "Seguimiento no encontrado", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del seguimiento", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.tracking = response.data.result;
                            $scope.tracking.module = "tracking";
                            initializeDates();
                            initializeAlerts();
                            initializeNotifications();
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);
                    });
            }
        };

        var initializeDates = function () {
            if ($scope.tracking.eventDate != null) {
                $scope.tracking.eventDate = new Date($scope.tracking.eventDate.date);
            }
        }

        onLoadRecord();

        $scope.removeAlert = function (index) {

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
                            $scope.tracking.alerts.splice(index, 1);
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        };


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
                    log.info($scope.customer);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                    //your code for submit
                    log.info($scope.customer);
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
                url: 'api/tracking/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.tracking = response.data.result;
                    initializeDates();
                    if ($scope.tracking.id == 0) {
                        $scope.$parent.navToSection("list", "list");
                    } else {
                        $scope.reloadData();
                    }
                });
            }).catch(function (e) {
                $log.error(e);
                //SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                console.log($scope.tracking.id);
                if ($scope.tracking.id == 0) {
                    $scope.$parent.navToSection("list", "list");
                } else {
                    $scope.reloadData();
                }
            });

        };


        var onLoadAgent = function () {
            var req = {};
            req.customerId = $scope.customerId;
            //var data = JSON.stringify($scope.customer);
            //req.data = data;
            return $http({
                method: 'POST',
                url: 'api/tracking/agent',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {


                $timeout(function () {

                    if (response.data.data.length > 0) {
                        $scope.agents = response.data.data;
                        $scope.users = response.data.data;
                    }

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        }

        onLoadAgent();

        $scope.onAddNotification = function () {

            $timeout(function () {
                if ($scope.tracking.notifications == null) {
                    $scope.tracking.notifications = [];
                }
                $scope.tracking.notifications.push(
                    {
                        id: 0,
                        trackingId: $scope.$parent.currentTraking,
                        user: null
                    }
                );
            });
        };

        $scope.onAddAlert = function () {

            $timeout(function () {
                if ($scope.tracking.alerts == null) {
                    $scope.tracking.alerts = [];
                }

                $scope.tracking.alerts.push(
                    {
                        id: 0,
                        type: null,
                        timeType: null,
                        time: 0,
                        preference: null,
                        sent: 0,
                        status: null
                    }
                );
            }, 500);
        };

        $scope.onRemoveNotification = function (index) {
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
                            var data = $scope.tracking.notifications[index];

                            if (data.id != 0) {
                                var req = {};
                                req.id = data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/tracking-notification/delete',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");

                                    $scope.tracking.notifications.splice(index, 1);

                                }).catch(function (e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function () {

                                });
                            } else {
                                $scope.tracking.notifications.splice(index, 1);
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        $scope.onAddComment = function () {

            $timeout(function () {
                if ($scope.tracking.comments == null) {
                    $scope.tracking.comments = [];
                }
                $scope.tracking.comments.push(
                    {
                        id: 0,
                        customerTrackingId: 0,
                        comment: "",
                        responsible: "",
                        date: null,
                    }
                );
            });
        };


        $scope.dtInstanceCustomerTrackingComment = {};
        $scope.dtOptionsCustomerTrackingComment = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.isDeleted = 0;
                    d.customerTrackingId = currentId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-tracking-comment',
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

        $scope.dtColumnsCustomerTrackingComment = [
            DTColumnBuilder.newColumn('comment').withTitle("Comentario").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('createdBy').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('createdAt').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
        ];

        $scope.dtInstanceCustomerTrackingCommentCallback = function (instance) {
            $scope.dtInstanceCustomerTrackingComment = instance;
        };

        $scope.reloadData = function () {
            if ($scope.dtInstanceCustomerTrackingComment != null) {
                $scope.dtInstanceCustomerTrackingComment.reloadData();
            }
        };

    }]);
