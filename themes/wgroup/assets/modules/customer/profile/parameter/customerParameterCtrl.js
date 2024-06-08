'use strict';
/**
 * controller for Customers
 */
app.controller('customerParameterCtrl', ['$scope', '$aside', '$stateParams', '$log',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal',
    '$filter', '$document', 'ListService', 'SupportService',
    function ($scope, $aside, $stateParams, $log, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, $filter, $document, ListService, SupportService) {

        var log = $log;

        $scope.$on('vAccordion:onReady', function () {
            //console.log('vAccordion', args)
            console.log('vAccordion')
        });

        var gotoParameter = function() {
            switch (SupportService.getCurrentStep()) {
                case 3:
                    //$scope.secondAccordionControl.expand(0);
                    break;

                case 4:
                    //$scope.secondAccordionControl.expand(11)
                    break;
            }
        }

        var onDestroySupport$ = $rootScope.$on('navigateToSupport', function (event, args) {
            gotoParameter();
        });

        $scope.$on("$destroy", function () {
            onDestroySupport$();
        });

        if (SupportService.getShouldRedirect()) {
            gotoParameter()
        }

        $scope.currentUser = $rootScope.currentUser();
        $scope.isAgent = $scope.currentUser.wg_type == "agent";
        $scope.isAdmin = $scope.currentUser.wg_type == "system";
        $scope.isCustomerAdmin = $scope.currentUser.wg_type == "customerAdmin";
        $scope.isCustomerUser = $scope.currentUser.wg_type == "customerUser";

        $scope.canEdit = $scope.isCustomerAdmin || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
        $rootScope.canEditRoot = $scope.canEdit;

        $scope.isView = $state.is("app.clientes.view");
        $scope.isCreate = $state.is("app.clientes.create");

        $scope.users = [];
        $scope.experienceVRList = [];

        $scope.hasMatrixSpecial = $scope.customer.matrixType == "S";

        var init = function() {
            var $matrixType = $scope.customer.matrixType;
            var $valueCovid = null;
            if ($scope.customer != null && $scope.customer.covidBolivarRegister && $scope.customer.covidBolivarRegister.length) {
                $valueCovid = $scope.customer.covidBolivarRegister[0].item
            }

            $scope.canEditMatrix = $scope.customer.matrixType == null || $scope.customer.matrixType == '';

            if (($scope.customer.matrixType == null || $scope.customer.matrixType == '') && $scope.customer.productivityMatrix == 'G') {
                $matrixType = $scope.customer.productivityMatrix;
                $scope.canEditMatrix = false;
            }

            $scope.entity = {
                id: $stateParams.customerId,
                matrixType: $matrixType,
                covidBolivarRegister: $valueCovid
            }
        };

        init();

        getList();

        function getList() {
            var entities = [
                {name: 'customer_related_agent_user', value: $stateParams.customerId},
                {name: 'experience_vr', value: null},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.users = response.data.data.customerRelatedAgentAndUserList;
                    $scope.experienceVRList = response.data.data.experience_vr;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        if ($scope.customer.assignedHours == null || $scope.customer.assignedHours.length == 0) {
            $scope.customer.assignedHours = [];
            $scope.customer.assignedHours.push(
                {
                    id: 0,
                    customerId: $stateParams.customerId,
                    namespace: "wgroup",
                    group: "economicGroupAssignedHours",
                    isActive: false,
                    value: 0
                }
            );
        }

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
                    $scope.onUpdateCovidBolivarRegister()
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
                url: 'api/customer/saveParameters',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.customer = response.data.result;
                    $rootScope.$emit('dataCustomer', { newValue: $scope.customer, id: $scope.customer.id, message: 'Data Customer has been changed!' });

                    if ($rootScope.app.supportHelp) {
                        $rootScope.app.supportHelp.hasNotificationUser = $scope.customer.userNotificationList.length > 0;
                    }

                    SweetAlert.swal("Validación exitosa", "Guardando información del cliente...", "success");
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.onUpdateMatrix = function () {
            SweetAlert.swal({
                title: "Está seguro?",
                text: "Una vez seleccionado el tipo de matríz no será posible hacer cambio de matríz.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Si, continuar!",
                cancelButtonText: "No, cancelar!",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm) {
                    var req = {};
                    var data = JSON.stringify($scope.entity);
                    req.data = Base64.encode(data);
                    return $http({
                        method: 'POST',
                        url: 'api/customer/update-matrix',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param(req)
                    }).then(function (response) {
                        $timeout(function () {
                            $scope.customer.matrixType = response.data.result.matrixType;

                            $scope.canEditMatrix = $scope.customer.matrixType == null || $scope.customer.matrixType == '';

                            if ($rootScope.app.supportHelp) {
                                $rootScope.app.supportHelp.hasMatrix = $scope.customer.matrixType != null;
                            }

                            $rootScope.$emit('customerMatrixUpdated', { newValue: $scope.customer.matrixType, id: $scope.customer.id, message: 'Customer Matrix has been changed!' });
                            SweetAlert.swal("Validación exitosa", "Guardando información del cliente...", "success");
                        });
                    }).catch(function (e) {
                        $log.error(e);
                        SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                    }).finally(function () {

                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
        };

        $scope.onUpdateCovidBolivarRegister = function () {

            var covidBolivarRegister = {
                id: $scope.customer.covidBolivarRegister.length ? $scope.customer.covidBolivarRegister[0].id : 0,
                namespace: "wgroup",
                group: "covidBolivarRegister",
                isActive: $scope.entity.covidBolivarRegister,
                value: null
            }

            var req = {};
            var data = JSON.stringify(covidBolivarRegister);
            req.data = Base64.encode(data);
            req.customerId = $stateParams.customerId;

            return $http({
                method: 'POST',
                url: 'api/customer-parameter/update/covid-register',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro.", "error");
                return;
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

/*START------------------------------------*/
        $scope.onAddOfficeTypeMatrixSpecial = function () {

            $timeout(function () {
                if ($scope.customer.officeTypeMatrixSpecialList == null) {
                    $scope.customer.officeTypeMatrixSpecialList = [];
                }
                $scope.customer.officeTypeMatrixSpecialList.push(
                    {
                        id: 0,
                        customerId: $stateParams.customerId,
                        namespace: "wgroup",
                        group: "officeTypeMatrixSpecial",
                        isActive: true,
                        value: ""
                    }
                );
            });
        };

        $scope.onRemoveOfficeTypeMatrixSpecial = function (index) {
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
                            var $data = $scope.customer.officeTypeMatrixSpecialList[index];

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/destroy',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    $scope.customer.officeTypeMatrixSpecialList.splice(index, 1);
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function (e) {
                                    SweetAlert.swal("Error en la eliminación", e.data.message, "error");
                                }).finally(function () {

                                });
                            } else {
                                $scope.customer.officeTypeMatrixSpecialList.splice(index, 1);
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

/* END------------------------------------*/


/*START------------------------------------*/
        $scope.onAddUnitBusinessMatrixSpecial = function () {

            $timeout(function () {
                if ($scope.customer.businessUnitMatrixSpecialList == null) {
                    $scope.customer.businessUnitMatrixSpecialList = [];
                }
                $scope.customer.businessUnitMatrixSpecialList.push(
                    {
                        id: 0,
                        customerId: $stateParams.customerId,
                        namespace: "wgroup",
                        group: "businessUnitMatrixSpecial",
                        isActive: true,
                        value: ""
                    }
                );
            });
        };

        $scope.onRemoveUnitBusinessMatrixSpecial = function (index) {
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
                            var $data = $scope.customer.businessUnitMatrixSpecialList[index];

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/destroy',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    $scope.customer.businessUnitMatrixSpecialList.splice(index, 1);
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function (e) {
                                    SweetAlert.swal("Error en la eliminación", e.data.message, "error");
                                }).finally(function () {

                                });
                            } else {
                                $scope.customer.businessUnitMatrixSpecialList.splice(index, 1);
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

/* END------------------------------------*/


        $scope.onAddEmployeeDocumentType = function () {

            $timeout(function () {
                if ($scope.customer.employeeDocumentsTypeList == null) {
                    $scope.customer.employeeDocumentsTypeList = [];
                }
                $scope.customer.employeeDocumentsTypeList.push(
                    {
                        id: 0,
                        customerId: 0,
                        namespace: "wgroup",
                        group: "employeeDocumentType",
                        isActive: false,
                        isVisible: false,
                        value: ""
                    }
                );
            });
        };

        $scope.onRemoveEmployeeDocumentType = function (index) {
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
                            var $data = $scope.customer.employeeDocumentsTypeList[index];

                            $scope.customer.employeeDocumentsTypeList.splice(index, 1);

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/delete',
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


        $scope.onAddCustomerDocumentType = function () {

            $timeout(function () {
                if ($scope.customer.documentsTypeList == null) {
                    $scope.customer.documentsTypeList = [];
                }
                $scope.customer.documentsTypeList.push(
                    {
                        id: 0,
                        customerId: 0,
                        namespace: "wgroup",
                        group: "customerDocumentType",
                        isActive: false,
                        value: ""
                    }
                );
            });
        };

        $scope.onRemoveCustomerDocumentType = function (index) {
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
                            var $data = $scope.customer.documentsTypeList[index];

                            $scope.customer.documentsTypeList.splice(index, 1);

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/delete',
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


        $scope.onAddExtraContactInformation = function () {

            $timeout(function () {
                if ($scope.customer.extraContactInformation == null) {
                    $scope.customer.extraContactInformation = [];
                }
                $scope.customer.extraContactInformation.push(
                    {
                        id: 0,
                        customerId: 0,
                        namespace: "wgroup",
                        group: "extraContactInformation",
                        isActive: false,
                        value: ""
                    }
                );
            });
        };

        $scope.onRemoveExtraContactInformation = function (index) {
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
                            var $data = $scope.customer.extraContactInformation[index];

                            $scope.customer.extraContactInformation.splice(index, 1);

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/delete',
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


        $scope.onAddContactType = function () {

            $timeout(function () {
                if ($scope.customer.contactTypeList == null) {
                    $scope.customer.contactTypeList = [];
                }
                $scope.customer.contactTypeList.push(
                    {
                        id: 0,
                        customerId: 0,
                        namespace: "wgroup",
                        group: "contactTypes",
                        isActive: false,
                        value: ""
                    }
                );
            });
        };

        $scope.onRemoveContactType = function (index) {
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
                            var $data = $scope.customer.contactTypeList[index];

                            $scope.customer.contactTypeList.splice(index, 1);

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/delete',
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


        $scope.onAddContractorType = function () {

            $timeout(function () {
                if ($scope.customer.contractorTypeList == null) {
                    $scope.customer.contractorTypeList = [];
                }
                $scope.customer.contractorTypeList.push(
                    {
                        id: 0,
                        customerId: 0,
                        namespace: "wgroup",
                        group: "contractorTypes",
                        isActive: false,
                        value: ""
                    }
                );
            });
        };

        $scope.onRemoveContractorType = function (index) {
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
                            var $data = $scope.customer.contractorTypeList[index];

                            $scope.customer.contractorTypeList.splice(index, 1);

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/delete',
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


        $scope.onAddProjectType = function () {

            $timeout(function () {
                if ($scope.customer.projectTypes == null) {
                    $scope.customer.projectTypes = [];
                }
                $scope.customer.projectTypes.push(
                    {
                        id: 0,
                        customerId: 0,
                        namespace: "wgroup",
                        group: "projectType",
                        isActive: true,
                        value: ""
                    }
                );
            });
        };

        $scope.onRemoveProjectType = function (index) {
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
                            var $data = $scope.customer.projectTypes[index];

                            $scope.customer.projectTypes.splice(index, 1);

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/delete',
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


        $scope.onAddProjectTaskType = function () {

            $timeout(function () {
                if ($scope.customer.projectTaskTypes == null) {
                    $scope.customer.projectTaskTypes = [];
                }
                $scope.customer.projectTaskTypes.push(
                    {
                        id: 0,
                        customerId: 0,
                        namespace: "wgroup",
                        group: "projectTaskType",
                        isActive: true,
                        value: "",
                        data: 0
                    }
                );
            });
        };

        $scope.onRemoveProjectTaskType = function (index) {
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
                            var data = $scope.customer.projectTaskTypes[index];


                            if (data.id != 0) {
                                var req = {};
                                req.id = data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                    $scope.customer.projectTaskTypes.splice(index, 1);
                                }).catch(function (e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function () {

                                });
                            } else {
                                $scope.customer.projectTaskTypes.splice(index, 1);
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }


        $scope.onAddUserSkill = function () {

            $timeout(function () {
                if ($scope.customer.userSkills == null) {
                    $scope.customer.userSkills = [];
                }
                $scope.customer.userSkills.push(
                    {
                        id: 0,
                        customerId: 0,
                        namespace: "wgroup",
                        group: "userSkill",
                        isActive: true,
                        value: ""
                    }
                );
            });
        };

        $scope.onRemoveUserSkill = function (index) {
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
                            var $data = $scope.customer.userSkills[index];

                            $scope.customer.userSkills.splice(index, 1);

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/delete',
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


        $scope.onAddUserNotification = function () {

            $timeout(function () {
                if ($scope.customer.userNotificationList == null) {
                    $scope.customer.userNotificationList = [];
                }
                $scope.customer.userNotificationList.push(
                    {
                        id: 0,
                        customerId: 0,
                        namespace: "wgroup",
                        group: "userNotification",
                        isActive: false,
                        value: null,
                    }
                );
            });
        };

        $scope.onRemoveUserNotification = function (index) {
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
                            var $data = $scope.customer.userNotificationList[index];

                            $scope.customer.userNotificationList.splice(index, 1);

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/delete',
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

        $scope.onAddExperience = function () {

            $timeout(function () {
                if ($scope.customer.experienceVRList == null) {
                    $scope.customer.experienceVRList = [];
                }
                $scope.customer.experienceVRList.push(
                    {
                        id: 0,
                        customerId: 0,
                        namespace: "wgroup",
                        group: "experienceVR",
                        isActive: false,
                        value: null,
                    }
                );
            });
        };

        $scope.onRemoveExperienceVR = function (index) {
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
                            var $data = $scope.customer.experienceVRList[index];
                            $scope.customer.experienceVRList.splice(index, 1);

                            if ($data.id != 0) {
                                var req = {};
                                req.id = $data.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-parameter/delete',
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

        $scope.covidBolivarRegisterCandEdit  = function() {
            if($scope.customer.covidBolivarRegisterCandEdit) {
                return true;
            }
            return false;
        }

        $scope.valideSingle = function(item) {
            var currents = $filter('filter')($scope.customer.experienceVRList, { value: item});
            if(currents.length) {
                return true;
            }
            return false;
        }

    }]);
