'use strict';
/**
 * controller for Customers
 */
app.controller('customerContactCtrl', ['$scope', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$document',
    function ($scope, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document) {

        var log = $log;
        var request = {};

        $scope.currentUser = $rootScope.currentUser();
        $scope.isAgent = $scope.currentUser.wg_type == "agent" ? true : false;
        $scope.isAdmin = $scope.currentUser.wg_type == "system" ? true : false;
        $scope.isCustomerAdmin = $scope.currentUser.wg_type == "customerAdmin" ? true : false;
        $scope.isCustomerUser = $scope.currentUser.wg_type == "customerUser" ? true : false;

        $scope.canEdit = $scope.isCustomerAdmin || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
        $rootScope.canEditRoot = $scope.canEdit;

        $scope.isView = $state.is("app.clientes.view");
        $scope.isCreate = $state.is("app.clientes.create");

        $scope.extrainfo = $rootScope.parameters("extrainfo");

        /*
        if ($scope.isCreate) {
            $scope.rolescontact = $filter('orderBy')($rootScope.parameters("rolescontact"), 'id', false);
        }
        */
        $scope.rolescontact = $filter('orderBy')($rootScope.parameters("rolescontact"), 'id', false);

        var onDestroyDataCustomer$ = $rootScope.$on('dataCustomer', function (event, args) {
            $scope.customer = args.newValue;
        });

        $scope.$on("$destroy", function() {
            onDestroyDataCustomer$();
        });

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
                url: 'api/customer/saveContacts',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Validación exitosa", "Guardando información del cliente...", "success");
                    $scope.customer = response.data.result;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.addInfoContact = function (index, mainContact) {
            // show modal
            $scope.openModal(mainContact);
        };

        $scope.openModal = function (mainContact) {

            var modalInstance = $uibModal.open({
                //templateUrl: 'app_modal_infocontact.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/customer_modal_info_contact.htm",
                controller: 'ModalContactInstanceCtrl',
                backdrop: 'static',
                windowTopClass: 'top-modal',
                scope: $scope,
                resolve: {
                    mainContact: function () {
                        return mainContact;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
                log.info("despues de cerrar....");
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        };

        $scope.addMainContact = function() {
            if ($scope.customer.maincontacts == null) {
                $scope.customer.maincontacts = [];
            }

            $timeout(function () {
                $scope.customer.maincontacts.push(
                    {
                        id: 0,
                        name: "",
                        firstname: "",
                        lastname: "",
                        value: "",
                        info: [],
                        role: null
                    }
                );
            }, 500);
        }

        $scope.removeMainContact = function (index) {
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

                            var contact = $scope.customer.maincontacts[index];

                            if (contact.id == 0) {
                                $scope.customer.maincontacts.splice(index, 1);
                            } else {
                                var req = {};
                                req.id = contact.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/contact/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    toaster.pop('success', 'Operación Exitosa', 'Registro eliminado');
                                    // eliminamos el registro en la posicion seleccionada
                                    $scope.customer.maincontacts.splice(index, 1);
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

    }]);

app.controller('ModalContactInstanceCtrl', function ($scope, $uibModalInstance, mainContact, $log, $timeout, SweetAlert, isView, $http) {

    if (mainContact.info === null || mainContact.info === undefined || mainContact.info.length === 0) {
        mainContact.info = [
            {
                id: 0,
                value: "",
                type: {
                    item: "-- Seleccionar --",
                    value: "-S-"
                }
            }
        ];
    }

    $scope.mainContact = mainContact;
    $scope.isView = isView;

    $scope.ok = function () {
        $uibModalInstance.close(1);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.clone = function () {
        $timeout(function () {
            $scope.mainContact.info.push(
                {
                    id: 0,
                    value: "",
                    type: {
                        item: "-- Seleccionar --",
                        value: "-S-"
                    }
                }
            );
        });
    };

    $scope.addInfo = function (){
        mainContact.info.push(
            {
                id: 0,
                value: "",
                type: {
                    item: "-- Seleccionar --",
                    value: "-S-"
                }
            }
        );
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
                        //$scope.mainContact.info.splice(index, 1);

                        var contact = $scope.mainContact.info[index];
                        if (contact.id == 0) {
                            $scope.mainContact.info.splice(index, 1);
                        } else {
                            var req = {};
                            req.id = contact.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/info-detail/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (data) {
                                swal("Operación Exitosa", "Registro eliminado", "success");

                                $scope.mainContact.info.splice(index, 1);
                            }).catch(function (e) {
                                $log.error(e);

                                if (e.message == "Action plan related.") {
                                    swal("Cancelación", "No se puede eliminar el registro. El contacto tiene planes de acción relacionados", "error");
                                } else {
                                    swal("Cancelación", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo", "error");
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


});
