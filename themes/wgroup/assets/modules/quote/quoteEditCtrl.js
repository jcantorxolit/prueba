'use strict';
/**
 * controller for Customers
 */
app.controller('quoteEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter) {

        var log = $log;
        var request = {};



        $scope.loading = true;
        $scope.isView = $state.is("app.cotizaciones.view");
        $scope.isCreate = $state.is("app.cotizaciones.create");
        $scope.format = 'dd-MM-yyyy';
        $scope.minDate = new Date() - 1;

        $scope.summary = {
            subTotal: 0,
            expenses: 0,
            tax: 0,
            total: 0
        };

        $scope.customers = [];

        $scope.quote = {
            id: $scope.isCreate ? 0 : $stateParams.quoteId,
            customer: null,
            details: [],
            expenses: 0,
            tax: 0,
            deadline: new Date(),
            total: 0,
            totalModified: 0,
            observation: "",
            status: null,
            responsible: null
        };

        // Preparamos los parametros por grupo
        $scope.statusQuote = $rootScope.parameters("quote_status");

        $scope.open = function($event) {
            $event.preventDefault();
            $event.stopPropagation();

            $scope.opened = true;
        };

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };


        if ($scope.quote.id) {
            // se debe cargar primero la información actual del cliente..
            log.info("editando cliente con código: " + $scope.quote.id);
            var req = {
                id: $scope.quote.id
            };
            $http({
                method: 'GET',
                url: 'api/quote',
                params: req
            })
                .catch(function (e, code) {
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.asesores.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Cotización no encontrada", "error");
                        $timeout(function () {
                            $state.go('app.cotizaciones.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.quote = response.data.result;
                        loadResponsible($scope.quote.customer.id)
                    });
                }).finally(function () {
                    $timeout(function () {
                        afterInit();
                        $scope.loading = false;
                    }, 400);
                });


        } else {
            //Se creara nuevo cliente
            log.info("creacion de nuevo asesor ");
            $scope.loading = false;
        }


        var errorMessage = function (i) {
            toaster.pop('error', 'Error', 'Por favor diligencie los campos requeridos en este paso, antes de continuar al siguiente nivel.');
        };

        $scope.removeImage = function () {
            $scope.noImage = true;
        };

        $scope.master = $scope.quote;
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
                    log.info($scope.quote);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información del asesor...", "success");
                    //your code for submit
                    log.info($scope.quote);
                    save();
                }

            },
            reset: function (form) {

                $scope.quote = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.quote);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/quote/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $state.go("app.cotizaciones.list");
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.cancelEdition = function()
        {
            if ($scope.isview) {
                $state.go('app.cotizaciones.list');
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
                                $state.go('app.cotizaciones.list');
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        }

        $scope.onRemoveDetail = function (index) {

            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado",
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
                            $scope.quote.details.splice(index, 1);
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });


        };

        $scope.onAddService = function () {

            if ($scope.quote.service == null) {

                SweetAlert.swal("Error", "Debe seleccionar el servicio. Por favor verifique! !", "error");

            } else {

                var result = $filter('filter')($scope.quote.details, {serviceId: $scope.quote.service.id});

                if (result.length == 0)
                {
                    var detail = {
                        id: 0,
                        quoteId: $scope.isCreate ? 0 : $stateParams.quoteId,
                        serviceId: $scope.quote.service.id,
                        service: $scope.quote.service,
                        quantity: 0,
                        hour: 0,
                        total: 0,
                        totalModified: 0
                    }
                    $scope.quote.details.push(detail);
                }
            }

        };

        $timeout(function () {
            afterInit();
        }, 10);

        var afterInit = function () {

            log.info("editando cliente con código: " + $scope.quote.id);
            var req = {
                id: $scope.quote.id
            };
            $http({
                method: 'POST',
                url: 'api/quote-service',
                params: req
            })
                .catch(function (e, code) {
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.asesores.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Cotización no encontrada", "error");
                        $timeout(function () {
                            $state.go('app.cotizaciones.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.services = response.data.data;
                    });

                }).finally(function () {

                });

            var req = {};
            var data = JSON.stringify($scope.customer);
            req.data = data;
            return $http({
                method: 'POST',
                url: 'api/project/customer',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {

                    $scope.customers = response.data.data;

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.$watch("quote.details", function () {

            $log.info($scope.quote.details.length);

            $scope.quote.total = 0;
            $scope.quote.totalModified = 0;

            if ($scope.quote.details.length > 0) {
                angular.forEach($scope.quote.details, function(detail) {
                    detail.total = detail.quantity * detail.service.unitValue;
                    //detail.totalModified = detail.quantity * detail.service.unitValue;

                    $scope.quote.total += detail.total;
                    $scope.quote.totalModified += (parseFloat(detail.totalModified) == 0) ? detail.total : parseFloat(detail.totalModified);

                });
            }

            $scope.summary.subTotal = $filter('currency')($scope.quote.totalModified, "$ ", 0);
            $scope.summary.expenses = $filter('currency')($scope.quote.expenses, "$ ", 0);
            $scope.summary.tax = $filter('currency')(0, "$ ", 0);
            $scope.summary.total = $filter('currency')(parseFloat($scope.quote.totalModified) + parseFloat($scope.quote.expenses), "$ ", 0);

        }, true);

        $scope.$watch("quote.expenses", function () {

            $scope.summary.subTotal = $filter('currency')($scope.quote.totalModified, "$ ", 0);
            $scope.summary.expenses = $filter('currency')($scope.quote.expenses, "$ ", 0);
            $scope.summary.tax = $filter('currency')(0, "$ ", 0);
            $scope.summary.total = $filter('currency')(parseFloat($scope.quote.totalModified) + parseFloat($scope.quote.expenses), "$ ", 0);

        }, true);

        $scope.onCancel = function () {
            if ($scope.isview) {
                $state.go('app.cotizaciones.list');
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
                                $state.go('app.cotizaciones.list');
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        $scope.onRemoveService = function(index)
        {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado",
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
                            var detail = $scope.quote.details[index];

                            $scope.quote.details.splice(index, 1);

                            if (detail.id != 0) {
                                var req = {};
                                req.id = detail.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/quote-service/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function(e){
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function(){


                                });
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }


        var loadResponsible = function(customerId)
        {
            var req = {
                customer_id: customerId
            };
            $http({
                method: 'POST',
                url: 'api/quote/responsible',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.quote.responsible = response.data.data;
                    });

                }).finally(function () {

                });
        };

        $scope.changeCustomer = function (item, model) {
            $timeout(function () {
                loadResponsible(item.id);
            });
        };

    }]);



