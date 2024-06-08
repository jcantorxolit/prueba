'use strict';
/**
 * controller for Customers
 */
app.controller('customerUnitCtrl', ['$scope', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$document',
    function ($scope, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document) {

        var log = $log;
        var request = {};

        $scope.currentUser = $rootScope.currentUser();
        $scope.isAgent = $scope.currentUser.wg_type == "agent";
        $scope.isAdmin = $scope.currentUser.wg_type == "system";
        $scope.isCustomerAdmin = $scope.currentUser.wg_type == "customerAdmin";
        $scope.isCustomerUser = $scope.currentUser.wg_type == "customerUser";

        $scope.canEdit = $scope.isCustomerAdmin || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
        $rootScope.canEditRoot = $scope.canEdit;

        $scope.isView = $state.is("app.clientes.view");;
        $scope.isCreate = $state.is("app.clientes.create");

        $scope.unities = $rootScope.parameters("bunit");
        $scope.agents = $rootScope.agents();

        var init = function() {
            $scope.customer = {
                id: $scope.isCreate ? 0 : $stateParams.customerId,
            };
        };

        init();

        angular.forEach($scope.customer.unities, function (v, k) {
            v.agents = [
                {
                    "selected": null
                }
            ];
        });

        console.log($scope.customer.unities);

        $scope.master = $scope.customer;
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
                    save();
                }

            },
            reset: function (form) {

                $scope.customer = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.customer);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/saveUnit',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Validación exitosa", "Guardando información del cliente...", "success");
                    $scope.customer = response.data.result;
                    initializeUnit();
                });
            }).catch(function (e) {

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

        $scope.addAgent = function (bunit) {
            var unit = bunit;
            if (unit.agents == null) {
                unit.agents = [];
            }
            unit.agents.push(
                {
                    "id": 0,
                    "name": ""
                }
            );
        };

        $scope.removeAgent = function (bunit, index) {
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
                            bunit.agents.splice(index, 1);
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
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


        $scope.onLoadRecord = function () {
            if ($scope.customer.id) {
                var req = {
                    id: $scope.customer.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/unit',
                    params: req
                })
                    .catch(function (response) {
                        if (response.status == 403) {
                            var messagered = 'app.clientes.list';
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (response.status == 404) {
                            SweetAlert.swal("Información no disponible", "Cliente no encontrado", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.customer = response.data.result;

                            if ($scope.isCustomerAdmin) {
                                if ($scope.currentUser.company != $scope.customer.id) {
                                    $scope.canEdit = true;
                                } else {
                                    $scope.canEdit = true;
                                }
                            }

                            $rootScope.canEditRoot = $scope.canEdit;

                            initializeUnit();
                        });

                    }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });


            } else {
                $scope.loading = false;
            }
        };

        $scope.onLoadRecord();

        var initializeUnit = function() {
            // si no tiene unidades de negocio entonces se debe cargar las default
            if ($scope.customer.unities == null || $scope.customer.unities.length == 0) {
                $scope.customer.unities = $scope.unities;
                angular.forEach($scope.customer.unities, function (v, k) {
                    v.agents = [
                        { selected: null }
                    ];
                });
            }
            else if ($scope.customer.unities.length < $scope.unities.length) {
                angular.forEach($scope.unities, function (v, k) {
                    var result = $filter('filter')($scope.customer.unities, {id: v.id});
                    if (result.length == 0) {
                        $scope.customer.unities.push(v);
                        v.agents = [
                            { selected: null }
                        ];
                    }
                });
            }
        }
    }
]);

