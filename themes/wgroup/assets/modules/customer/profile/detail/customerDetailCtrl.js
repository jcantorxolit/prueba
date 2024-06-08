'use strict';
/**
 * controller for Customers
 */
app.controller('customerDetailCtrl', ['$scope', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
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
        $scope.isView = $state.is("app.clientes.view");
        $scope.isCreate = $state.is("app.clientes.create");

        $rootScope.canEditRoot = $scope.canEdit;

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
                url: 'api/customer/saveInfoDetail',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

              $timeout(function () {
                    $scope.customer = response.data.result;
                    SweetAlert.swal("Validación exitosa", "Guardando información del cliente...", "success");

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

        $scope.addContact = function() {
            $timeout(function () {
                if ($scope.customer.contacts == null) {
                    $scope.customer.contacts = [];
                }
                $scope.customer.contacts.push(
                    {
                        id: 0,
                        value: "",
                        type: null
                    }
                );
            });
        }

        $scope.removeContact = function (index) {

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
                            var contact = $scope.customer.contacts[index];

                            if (contact.id == 0) {
                                $scope.customer.contacts.splice(index, 1);
                            } else {
                                var req = {};
                                req.id = contact.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/info-detail/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    toaster.pop('success', 'Operación Exitosa', 'Registro eliminado');

                                    $scope.customer.contacts.splice(index, 1);
                                }).catch(function (e) {
                                    $log.error(e);

                                    if (e.message == "Action plan related.") {
                                        toaster.pop("error", "Error", "No se puede eliminar el registro. El contacto tiene planes de acción relacionados");
                                    } else {
                                        toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                                    }
                                }).finally(function () {
                                });
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        };
    }]);
