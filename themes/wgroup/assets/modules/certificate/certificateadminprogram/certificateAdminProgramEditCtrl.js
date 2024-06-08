'use strict';
/**
 * controller for Customers
 */
app.controller('certificateAdminProgramEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter) {

        var log = $log;

        $scope.loading = true;
        $scope.isView = $scope.$parent.formMode == "view";
        $scope.isCreate = $scope.$parent.formMode == "create";

        $scope.currencies = $rootScope.parameters("certificate_program_currency");
        $scope.categories = $rootScope.parameters("certificate_program_category");
        $scope.specialities = $rootScope.parameters("agent_skill");
        $scope.validities = $rootScope.parameters("certificate_program_validity_type");
        $scope.specialityCategories = $rootScope.parameters("certificate_program_speciality_category");
        $scope.requirements = $rootScope.parameters("certificate_program_requirement");

        $scope.program = {
            id: $scope.isCreate ? 0 : $scope.$parent.currentId,
            code: "",
            name: "",
            amount: 0,
            currency: null,
            category: null,
            speciality: null,
            capacity: 0,
            hourDuration: 0,
            validityNumber: 0,
            validityType: null,
            authorizationResolution: "",
            authorizingEntity: "",
            captionHeader: "",
            captionFooter: "",
            description: "",
            isActive: true,
            specialities: [],
            requirements: [],
            isMandatory: false
        };


        if ($scope.program.id != 0) {
            // se debe cargar primero la información actual del cliente..
            log.info("editando cliente con código: " + $scope.program.id);
            var req = {
                id: $scope.program.id
            };
            $http({
                method: 'GET',
                url: 'api/certificate-program',
                params: req
            })
                .catch(function (e, code) {
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.certificate.admin';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Encuesta no encontrada", "error");
                        $timeout(function () {
                            //$state.go('app.poll.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.program = response.data.result;

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

        $scope.master = $scope.program;
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
                    log.info($scope.poll);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información de la encuesta...", "success");
                    //your code for submit
                    log.info($scope.program);
                    save();
                }

            },
            reset: function (form) {

                $scope.program = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.program);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/certificate-program/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    if($scope.$parent != null){
                        $scope.$parent.navToSection("list", "list");
                    }
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.onCancel = function () {
            if ($scope.isView) {
                if($scope.$parent != null){
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
                                if($scope.$parent != null){
                                    $scope.$parent.navToSection("list", "list");
                                }
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        var afterInit = function()
        {
            var req = {};

            req.program_id = $stateParams.id ? $stateParams.id : 0;

            $http({
                method: 'POST',
                url: 'api/collection-data',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.collections = response.data.data;
                        $scope.collectionsReport = $filter('filter')($scope.collections, {type: "quote"});
                        //$scope.collectionsChart = $filter('filter')($scope.collections, {type: "chart"});
                    });

                }).finally(function () {

                });
        }

        //afterInit();

        $scope.onAddSpeciality = function () {

            if ($scope.program.specialityCategory == null) {

                SweetAlert.swal("Error", "Debe seleccionar la especialidad. Por favor verifique! !", "error");

            } else {

                var result = $filter('filter')($scope.program.specialities, {categoryId: $scope.program.specialityCategory.id});

                if (result.length == 0)
                {
                    var speciality = {
                        id: 0,
                        certificateProgramId: $scope.isCreate ? 0 : $scope.$parent.currentId,
                        categoryId: $scope.program.specialityCategory.id,
                        category: $scope.program.specialityCategory,
                    }
                    $scope.program.specialities.push(speciality);
                }
            }

        };

        $scope.onRemoveSpeciality = function(index)
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
                            var speciality = $scope.program.specialities[index];

                            $scope.program.specialities.splice(index, 1);

                            if (speciality.id != 0) {
                                var req = {};
                                req.id = speciality.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/certificate-program-speciality/delete',
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


        $scope.onAddRequirement = function () {

            if ($scope.program.requirement == null) {

                SweetAlert.swal("Error", "Debe seleccionar el requerimiento. Por favor verifique! !", "error");

            } else {

                var result = $filter('filter')($scope.program.requirements, {requirementId: $scope.program.requirement.id});

                if (result.length == 0)
                {
                    var requirement = {
                        id: 0,
                        certificateProgramId: $scope.isCreate ? 0 : $scope.$parent.currentId,
                        requirementId: $scope.program.requirement.value,
                        requirement: $scope.program.requirement,
                        isMandatory: $scope.program.isMandatory == undefined ? false : $scope.program.isMandatory,
                    }
                    $scope.program.requirements.push(requirement);
                }
            }

        };

        $scope.onRemoveRequirement = function(index)
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
                            var requirement = $scope.program.requirements[index];

                            $scope.program.requirements.splice(index, 1);

                            if (requirement.id != 0) {
                                var req = {};
                                req.id = requirement.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/certificate-program-requirement/delete',
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

    }]);



