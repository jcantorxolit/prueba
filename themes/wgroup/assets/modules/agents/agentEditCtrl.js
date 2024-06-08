'use strict';
/**
 * controller for Customers
 */
app.controller('agentEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter) {

        var log = $log;

        $scope.flowConfig = {target: '/api/agent/upload', singleFile: true};
        $scope.loading = true;
        $scope.isView = $state.is("app.asesores.view");
        $scope.isCreate = $state.is("app.asesores.create");
        $scope.currentYear = {
            id: "0",
            item: "-- Seleccionar --",
            value: new Date().getFullYear()
        };

        $scope.agent = {
            id: $scope.isCreate ? 0 : $stateParams.agentId,
            logo: "",
            signature: "",
            signatureText: "",
            contacts: [],
            occupations: [],
            skills: [],
            legalType: null,
            gender: null,
            documentType: null,
            type: null,
            isActive: false
        };

        $scope.isSelected= true;

        // Preparamos los parametros por grupo
        $scope.tiposdoc = $rootScope.parameters("tipodoc");
        $scope.genders = $rootScope.parameters("gender");
        $scope.types = $rootScope.parameters("agent_type");
        $scope.extrainfo = $rootScope.parameters("extrainfo");
        $scope.agent_occupations = $rootScope.parameters("agent_occupation");
        $scope.agent_skills = $rootScope.parameters("agent_skill");
        $scope.legalTypes = $rootScope.parameters("agent_legal_type");
        $scope.agentRoleList = $rootScope.parameters("agent_role_list");

        /*$scope.tiposCliente = $rootScope.parameters("tipocliente");
         $scope.estados = $rootScope.parameters("estado");
         $scope.arls = $rootScope.parameters("arl");
         $scope.unities = $rootScope.parameters("bunit");
         $scope.rolescontact = $filter('orderBy')($rootScope.parameters("rolescontact"), 'id', false);
         $scope.countries = $rootScope.countries();
         $scope.agents = $rootScope.agents();
         $scope.temporaryAgencies = $rootScope.temporaryAgencies();
         $scope.groups = $rootScope.groups();
         $scope.states = [];
         $scope.towns = [];
         $scope.months = $rootScope.parameters("month");
         $scope.years = [];*/

        $scope.uploader = new Flow();
        $scope.uploaderSignature = new Flow();

        if ($scope.agent.logo == '') {
            $scope.noImage = true;
        }

        if ($scope.agent.signature == '') {
            $scope.noSignatureImage = true;
        }

        if ($scope.agent.id) {
            // se debe cargar primero la información actual del cliente..
            log.info("editando cliente con código: " + $scope.agent.id);
            var req = {
                id: $scope.agent.id
            };
            $http({
                method: 'GET',
                url: 'api/agent',
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
                        SweetAlert.swal("Información no disponible", "Cliente no encontrado", "error");
                        $timeout(function () {
                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                    }
                })
                .then(function (response) {
                    console.log(response);

                    $timeout(function () {
                        $scope.agent = response.data.result;

                        if ($scope.agent.contacts == null || $scope.agent.contacts.length == 0) {
                            $scope.agent.contacts = [
                                {
                                    id: 0,
                                    value: "",
                                    type: null
                                }
                            ];
                        }

                        if ($scope.agent.occupations == null || $scope.agent.occupations.length == 0) {
                            $scope.agent.occupations = [
                                {
                                    id: 0,
                                    description: "",
                                    type: null,
                                    license: ""
                                }
                            ];
                        }

                        if ($scope.agent.skills == null || $scope.agent.skills.length == 0) {
                            $scope.agent.skills = [
                                {
                                    id: 0,
                                    skill: null
                                }
                            ];
                        }


                        if ($scope.agent.logo != null && $scope.agent.logo.path != null) {
                            $scope.noImage = false;
                        } else {
                            $scope.noImage = true;
                        }

                        if ($scope.agent.signature != null && $scope.agent.signature.path != null) {
                            $scope.noSignatureImage = false;
                        } else {
                            $scope.noSignatureImage = true;
                        }
                    });
                }).finally(function () {

                });


        } else {
            //Se creara nuevo cliente
            log.info("creacion de nuevo asesor ");
            $scope.loading = false;
        }

        $scope.removeImage = function () {
            $scope.noImage = true;
        };

        $scope.removeSignatureImage = function () {
            $scope.noSignatureImage = true;
        };

        $scope.master = $scope.agent;
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
                    log.info($scope.agent);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    save();
                }

            },
            reset: function (form) {

                $scope.agent = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.agent);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/agent/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                if ($scope.uploader.flow !== undefined && $scope.uploader.flow !== null) {
                    $scope.uploader.flow.opts.query.id = response.data.result.id;
                    $scope.uploader.flow.resume();
                }

                if ($scope.uploaderSignature.flow !== undefined && $scope.uploaderSignature.flow !== null) {
                    $scope.uploaderSignature.flow.opts.query.id = response.data.result.id;
                    $scope.uploaderSignature.flow.resume();
                }

                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    if ($scope.isCreate) {
                        $state.go("app.asesores.edit", {"agentId":response.data.result.id});
                    } else {
                        $scope.agent = response.data.result;
                    }
                });
            }).catch(function (e) {
                $log.error(e);
                var $mmesage = e.data && e.data.message ? e.data.message : "Error guardando el registro. Por favor verifique los datos ingresados!";
                SweetAlert.swal("Error de guardado", $mmesage, "error");
            }).finally(function () {

            });

        };

        $scope.cancelEdition = function()
        {
            if ($scope.isView) {
                $state.go('app.asesores.list');
            } else {
                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Perderá todos los cambios realizados en esta vista.",
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
                                $state.go('app.asesores.list');
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        }

        $scope.onAddContact = function (){
            $scope.agent.contacts.push(
                {
                    id: 0,
                    value: "",
                    type: null
                }
            );

        };

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
                            $scope.agent.contacts.splice(index, 1);
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });


        };

        $scope.onAddOccupation = function (){
            $scope.agent.occupations.push(
                {
                    id: 0,
                    description: "",
                    type: null,
                    license: ""
                }
            );
        };

        $scope.removeOccupation = function (index) {

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
                            $scope.agent.occupations.splice(index, 1);
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });


        };

        $scope.onAddSkill = function () {
            $scope.agent.skills.push(
                {
                    id: 0,
                    skill: null
                }
            );
        };

        $scope.removeSkill = function (index) {

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
                            $scope.agent.skills.splice(index, 1);
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        };
    }
]);



