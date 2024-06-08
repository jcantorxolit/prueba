app.controller('ModalInternalProjectInstanceSideCtrl',
    function ($rootScope, $scope, $uibModalInstance, project, customer, $log, $timeout, SweetAlert, isView, $http, $filter, toaster, ListService) {
    //
    $scope.types = $rootScope.parameters("project_type");
    //$scope.project = project;
    $scope.isView = isView;

    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy hh:mm tt"
        //value: $scope.project.deliveryDate.date
    };

    var init = function() {
        $scope.project = {
            id: project.id,
            customer: customer,
            name: null,
            deliveryDate: new Date(),
            estimatedHours: 0,
            isRecurrent: false,
            serviceOrder: "",
            isBilled: false,
            invoiceNumber: "",
            type: null,
            status: null,
            defaultSkill: null,
            agents: [],
        }

        $scope.project.projectTask = {
            id: 0,
            projectAgentId: 0,
            type: null,
            task: "",
            observation: "",
            startDateTime: new Date(),
            endDateTime: new Date(),
            status: "activo"
        }
    }

    init();


    var onLoadRecord = function() {
        if ($scope.project.id) {
            var req = {
                id: project.id
            };
            $http({
                method: 'GET',
                url: 'api/internal-project',
                params: req
            })
                .catch(function (e, code) {
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Aporte no encontrado", "error");
                        $timeout(function () {
                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del aporte", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.project = response.data.result;
                        if ($scope.project.deliveryDate) {
                            $scope.project.deliveryDate = new Date($scope.project.deliveryDate.date);
                        }
                        $scope.project.customer = $scope.project.customer[0];
                        loadAgent($scope.project.defaultSkill.id);
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        }
    }

    onLoadRecord();


    $scope.onClose = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

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

                $timeout(function () {
                    toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                }, 500);

                return;

            } else {
                if ($scope.project.agents.length == 0) {
                    toaster.pop("error", "Error", "Debe adicionar al menos un recurso");
                    return;
                }

                onSaveProject();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    var onSaveProject = function () {
        var req = {};
        if ($scope.project.deliveryDate != null) {

            $scope.project.event_date = $scope.project.deliveryDate.toISOString();

            var data = JSON.stringify($scope.project);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/internal-project/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'La información ha sido guardada satisfactoriamente');
                    }, 500);
                    $scope.projectTask = response.data.result;
                    $scope.onClose();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        } else {
            SweetAlert.swal("Error de guardado", "Error guardando el cliente por favor verifique la fecha ingresada!", "error");
        }

    }

    var loadList = function () {
        var req = {};
        req.customerId = customer.id;
        return $http({
            method: 'POST',
            url: 'api/internal-project/listCustomer',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.agentsFilter = response.data.data.users;
                $scope.types = response.data.data.projectTypes;
                $scope.skills = response.data.data.userSkills;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    }

    loadList();

    var loadAgent = function (skill) {
        var req = {};
        req.skill = skill;
        var data = JSON.stringify($scope.customer);
        req.data = data;
        req.customerId = customer.id;
        return $http({
            method: 'POST',
            url: 'api/internal-project/agent',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.agentList = response.data.data;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    }

    $scope.addAgent = function (agent) {
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

                        var result = $filter('filter')($scope.project.agents, {agentId: agent.id});

                        if (result.length == 0) {
                            $scope.project.agents.push(
                                {
                                    id: 0,
                                    agentId: agent.id,
                                    projectId: 0,
                                    scheduledHours: agent.scheduledHours,
                                    notAssignedHours: agent.notAssignedHours,
                                    name: agent.name
                                }
                            );
                        } else {
                            result[0].scheduledHours = parseInt(result[0].scheduledHours) + parseInt(agent.scheduledHours);
                        }
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    };

    $scope.removeAgentProject = function (index) {

        SweetAlert.swal({
            title: "Está seguro?",
            text: "Eliminará el registro seleccionado",
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
                        var result = $scope.project.agents[index];

                        if (result.id) {
                            var req = {};
                            req.id = result.id;
                            $http({
                                method: 'POST',
                                url: 'api/internal-project/agent/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {
                                $scope.project.agents.splice(index, 1);
                                //$scope.reloadData();
                            });
                        } else {
                            $scope.project.agents.splice(index, 1);
                        }
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    };

    $scope.changeSkill = function (item, model) {
        $scope.project.agents = [];
        loadAgent(item.id);
    };

});
