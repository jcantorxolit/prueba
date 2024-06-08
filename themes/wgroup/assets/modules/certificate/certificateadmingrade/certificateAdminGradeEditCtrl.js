'use strict';
/**
 * controller for Customers
 */
app.controller('certificateAdminGradeEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter) {

        var log = $log;

        $scope.loading = true;
        $scope.isView = $scope.$parent.formMode == "view";
        $scope.isCreate = $scope.$parent.formMode == "create";

        $scope.programs = [];
        $scope.status = $rootScope.parameters("certificate_grade_status");
        $scope.locations = $rootScope.parameters("certificate_grade_location");
        $scope.workCenters = $rootScope.parameters("certificate_grade_work_center");
        $scope.channels = $rootScope.parameters("certificate_grade_channel");
        $scope.validities = $rootScope.parameters("certificate_program_validity_type");
        $scope.specialities = $rootScope.parameters("agent_skill");
        $scope.agents = [];
        $scope.totalHourDuration = 0;

        $scope.grade = {
            id: $scope.isCreate ? 0 : $scope.$parent.currentId,
            program: null,
            code: "",
            name: "",
            location: null,
            description: "",
            status: null,
            calendar: [],
            agents: []
        };


        if ($scope.grade.id > 0) {
            // se debe cargar primero la información actual del cliente..
            log.info("editando cliente con código: " + $scope.grade.id);
            var req = {
                id: $scope.grade.id
            };
            $http({
                method: 'GET',
                url: 'api/certificate-grade',
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
                        SweetAlert.swal("Información no disponible", "Encuesta no encontrada", "error");
                        $timeout(function () {
                            //$state.go('app.grade.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.grade = response.data.result;
                        $scope.grade.defaultSpeciality = $scope.grade.program.speciality;

                        $scope.loadAgents($scope.grade.defaultSpeciality.value);
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

        $scope.master = $scope.grade;
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
                    log.info($scope.grade);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información de la encuesta...", "success");
                    //your code for submit
                    log.info($scope.grade);
                    save();
                }

            },
            reset: function (form) {

                $scope.grade = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.grade);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/certificate-grade/save',
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
            if ($scope.isview) {
                //$state.go('app.grade.list');
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

            req.operation = "program-list";

            $http({
                method: 'POST',
                url: 'api/certificate-program',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.programs = response.data.data;
                    });

                }).finally(function () {

                });
        }

        afterInit();


        $scope.onAddCalendarDate = function () {

            if ($scope.grade.startDateTime == null || $scope.grade.hourDuration == null || $scope.grade.hourDuration == 0) {

                SweetAlert.swal("Error", "Debe ingresar la información requerida. Por favor verifique! !", "error");

            } else {

                if ($scope.grade.hourDuration > 24)
                {
                    toaster.pop('error', 'Error', 'La duración supera las horas disponibles por fecha.');
                    return;
                }

                if (($scope.totalHourDuration + parseInt($scope.grade.hourDuration)) > parseInt($scope.grade.program.hourDuration)) {
                    toaster.pop('error', 'Error', 'La duración total supera las horas asignadas al program.');
                    return;
                }

                var result = $filter('filter')($scope.grade.calendar, {startDate: $scope.grade.startDateTime.id});

                if (result.length == 0)
                {
                    var date = {
                        id: 0,
                        certificateGradeId: $scope.isCreate ? 0 : $scope.$parent.currentId,
                        startDateFormat: $filter('date')($scope.grade.startDateTime, "dd/MM/yyyy"),
                        startDate: $scope.grade.startDateTime,
                        hourDuration: $scope.grade.hourDuration,
                    }
                    $scope.grade.calendar.push(date);
                }
            }

        };

        $scope.$watch("grade.calendar", function () {

            $log.info($scope.grade.calendar.length);

            $scope.totalHourDuration = 0;

            if ($scope.grade.calendar.length > 0) {
                angular.forEach($scope.grade.calendar, function(detail) {

                    $scope.totalHourDuration += parseInt(detail.hourDuration);
                });
            }
        }, true);

        $scope.onRemoveCalendarDate = function(index)
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
                            var date = $scope.grade.calendar[index];

                            $scope.grade.calendar.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/certificate-grade-calendar/delete',
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

        $scope.changeSkill = function (item, model) {
            //$("#ddlState input.ui-select-search").val("");
            //$("#ddlTown input.ui-select-search").val("");
            $scope.grade.agents = [];

            $scope.loadAgents(item.value);
        };

        $scope.changeProgram = function (item, model) {
            $scope.grade.defaultSpeciality = item.speciality;

            $scope.loadAgents(item.speciality.value);
        };

        $scope.loadAgents = function (skill) {
            var req = {};
            req.skill = skill;
            var data = JSON.stringify($scope.customer);
            req.data = data;
            return $http({
                method: 'POST',
                url: 'api/project/agent',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {


                $timeout(function () {

                    //if (data.data.length > 0)
                    $scope.agents = response.data.data;

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        $scope.onAddAgent = function (index) {

            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Confirmas adicionar este recurso al proyecto?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, adicionar!",
                    cancelButtonText: "No, cancelar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        $timeout(function () {
                            // eliminamos el registro en la posicion seleccionada
                            //$scope.mainContact.info.splice(index, 1);

                            var result = $filter('filter')($scope.grade.agents, {agentId: $scope.agents[index].id});

                            if (result.length == 0) {
                                $scope.grade.agents.push(
                                    {
                                        id: 0,
                                        agentId: $scope.agents[index].id,
                                        certificateGradeId: $scope.isCreate ? 0 : $scope.$parent.currentId,
                                        estimatedHours: $scope.agents[index].estimatedHours,
                                        notAssignedHours: $scope.agents[index].notAssignedHours,
                                        name: $scope.agents[index].name
                                    }
                                );
                            } else {
                                //result[0].scheduledHours =  parseInt(result[0].scheduledHours) + parseInt($scope.agents[index].scheduledHours)  ;
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        };

        $scope.onRemoveAgent = function(index)
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
                            var agent = $scope.grade.agents[index];

                            $scope.grade.agents.splice(index, 1);

                            if (agent.id != 0) {
                                var req = {};
                                req.id = agent.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/certificate-grade-agent/delete',
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



