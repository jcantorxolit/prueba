'use strict';
/**
 * controller for Vendor
 */
app.controller('mainContactEditCtrl',
    function ($scope, $aside, $stateParams, $log, $compile, toaster, $state, SweetAlert, $rootScope, $http, $timeout, $uibModal) {

        var log = $log;
        var request = {};
        var cloneContacts = [];

        $scope.contactTypesList = $rootScope.parameters("rolescontact");
        $scope.entity = {
            vendorId: $stateParams.vendorId,
            maincontacts: []
        }

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
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
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
                url: 'api/positiva-fgn-vendor-main-contact/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Validación exitosa", "Guardando información del cliente...", "success");
                    $scope.entity = response.data.result;
                    cloneContacts = angular.copy(response.data.result.maincontacts);
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            })

        };

        $scope.onLoadRecord = function (){
            if($scope.entity.vendorId > 0) {
                var req = {
                    vendorId: $scope.entity.vendorId
                };

                $http({
                    method: 'GET',
                    url: 'api/positiva-fgn-vendor-main-contact/get',
                    params: req
                })
                .catch(function(e, code){
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
                })
                .then(function (response) {
                    $timeout(function(){
                        $scope.entity.maincontacts = response.data.result;
                        cloneContacts = angular.copy(response.data.result);
                    });
                });
            }
        }
        $scope.onLoadRecord();

        $scope.addInfoContact = function (mainContact) {
            $scope.openModal(mainContact);
        };

        $scope.openModal = function (mainContact) {
            var modalInstance = $uibModal.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/vendor/tabs/main-contact/info_contact.htm",
                controller: 'ModalContactInstanceCtrl',
                backdrop: 'static',
                windowTopClass: 'top-modal',
                scope: $scope,
                resolve: {
                    mainContact: function () {
                        return mainContact;
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
            $scope.entity.maincontacts.push({
                id: 0,
                name: "",
                firstLastName: "",
                secondLastName: "",
                name: "",
                info: [],
                contactType: null
            });
        }

        $scope.removeMainContact = function (index, contact) {
            if (contact.id == 0) {
                $scope.entity.maincontacts.splice(index, 1);
                return;
            }
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
                            var contact = $scope.entity.maincontacts[index];
                            var req = {};
                            req.id = contact.id;
                            $http({
                                method: 'POST',
                                url: 'api/positiva-fgn-vendor-main-contact/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
                                toaster.pop('success', 'Operación Exitosa', 'Registro eliminado');
                                $scope.entity.maincontacts.splice(index, 1);
                            }).catch(function (e) {
                                $log.error(e);
                                toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                            });
                        });
                    }
                });
        };

        $scope.onCancel = function() {
            $scope.entity.maincontacts = angular.copy(cloneContacts);
        }

});

app.controller('ModalContactInstanceCtrl', function ($scope, $uibModalInstance, mainContact, $log, $timeout, $rootScope, SweetAlert, $http) {

    $scope.extrainfo = $rootScope.parameters("extrainfo");
    $scope.mainContact = mainContact;
    if ($scope.mainContact.info.length == 0) {
        mainContact.info = [
            {
                id: 0,
                type: null,
                value: null
            }
        ];
    }

    $scope.form = {
        submit: function (form) {
            var firstError = null;
            $scope.Form = form;
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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
                return;
            } else {
                $uibModalInstance.close(1);
            }
        },
        reset: function () {
            $scope.Form.$setPristine(true);
        }
    };


    $scope.addInfo = function (){
        $scope.mainContact.info.push({
            id: 0,
            type: null,
            value: null
        });
    }

    $scope.removeContact = function (index, info) {
        if (info.id == 0) {
            $scope.mainContact.info.splice(index, 1);
            return;
        }

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
                        var info = $scope.mainContact.info[index];
                        var req = {};
                        req.id = info.id;
                        $http({
                            method: 'POST',
                            url: 'api/positiva-fgn-vendor-main-contact/deleteInfo',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (data) {
                            swal("Operación Exitosa", "Registro eliminado", "success");
                            $scope.mainContact.info.splice(index, 1);
                        }).catch(function (e) {
                            $log.error(e);
                            swal("Cancelación", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo", "error");
                        });
                    });
                }
            });

    };

});
