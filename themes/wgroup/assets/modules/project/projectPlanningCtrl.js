'use strict';
/**
 * controller for User Profile Example
 */
app.controller('projectPlanningCtrl', ['$scope', 'flowFactory', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$filter',
    function ($scope, flowFactory, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $filter) {


        var log = $log;

        $scope.canShowGrantt = false;

        $scope.currentProjectAgentId = 0;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.canCreateTask = false;
        $scope.canCreateProject = false;
        $scope.currentTask = null;

        $scope.currentCustomerId = 0;
        $scope.currentAgentId = 0;
        $scope.currentMonth = 0;
        $scope.currentYear = 0;

        $scope.filter = {
            selectedType: {
                id: "1",
                item: "Grupo Económico",
                value: "GE"
            },
            selectedMonth: null,
            selectedYear: null,
            selectedAgent: null,
            selectedCustomer: null,
        }

        $scope.project = {
            id: 0,
            name: "",
            deliveryDate: new Date(),
            estimatedHours: 0,
            isRecurrent: false,
            serviceOrder: "",
            isBilled: false,
            invoiceNumber: "",
        };

        $scope.project.type = null;
        $scope.project.customer = null;
        $scope.project.status = null;
        $scope.project.defaultSkill = null;

        $scope.project.agents = []

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

        // Preparamos los parametros por grupos
        $scope.projectTypes = $rootScope.parameters("project_type");
        $scope.taskTypes = [];
        $scope.projectTaskResponsible = [];
        $scope.arls = $rootScope.parameters("arl");
        $scope.skills = $rootScope.parameters("agent_skill");

        $scope.agents = [];
        $scope.agentsFilter = $rootScope.agents();
        $scope.filterCustomers = [];
        $scope.customers = [];
        $scope.economicGroups = [];
        $scope.customersList = [];

        $scope.types = [
            {
                id: "1",
                item: "Grupo Económico",
                value: "GE"
            },
            {
                id: "2",
                item: "Cliente",
                value: "CU"
            }
        ];

        $scope.months = [
            {
                id: "1",
                item: "Enero",
                value: "1"
            },
            {
                id: "2",
                item: "Febrero",
                value: "2"
            },
            {
                id: "3",
                item: "Marzo",
                value: "3"
            },
            {
                id: "4",
                item: "Abril",
                value: "4"
            },
            {
                id: "5",
                item: "Mayo",
                value: "5"
            },
            {
                id: "6",
                item: "Junio",
                value: "6"
            },
            {
                id: "7",
                item: "Julio",
                value: "7"
            },
            {
                id: "8",
                item: "Agosto",
                value: "8"
            },
            {
                id: "9",
                item: "Septiembre",
                value: "9"
            },
            {
                id: "10",
                item: "Octubre",
                value: "10"
            },
            {
                id: "11",
                item: "Noviembre",
                value: "11"
            },
            {
                id: "12",
                item: "Diciembre",
                value: "12"
            }
        ]

        $scope.years = [
            {
                id: "2015",
                item: "2015",
                value: "2015"
            },
            {
                id: "2016",
                item: "2016",
                value: "2016"
            },
            {
                id: "2017",
                item: "2017",
                value: "2017"
            },
            {
                id: "2018",
                item: "2018",
                value: "2018"
            }
        ];

        var serviceRoot = "//demos.telerik.com/kendo-ui/service";
        var tasksDataSource = new kendo.data.GanttDataSource({
            batch: false,
            type: "odata",
            transport: {
                read: {
                    url: function () {
                        var data = {
                            customerId: $scope.currentCustomerId,
                            currentAgentId: $scope.currentAgentId,
                            month: $scope.currentMonth,
                            year: $scope.currentYear,
                            type: $scope.filter.selectedType.value,
                        };

                        return "api/project/gantt?data=" + Base64.encode(JSON.stringify(data));
                    },
                    dataType: "json",
                    type: "GET"
                },
                /*read: {
                 url: serviceRoot + "/GanttTasks",
                 dataType: "jsonp"
                 },*/
                parameterMap: function (options, operation) {
                    if (operation !== "read") {
                        return {models: kendo.stringify(options.models || [options])};
                    }
                }
            },
            schema: {
                /*model: {
                 id: "id",
                 fields: {
                 id: { from: "ID", type: "number" },
                 orderId: { from: "OrderID", type: "number", validation: { required: true } },
                 parentId: { from: "ParentID", type: "number", defaultValue: null, validation: { required: true } },
                 start: { from: "Start", type: "date" },
                 end: { from: "End", type: "date" },
                 title: { from: "Title", defaultValue: "", type: "string" },
                 originalId: { from: "originalId", defaultValue: "0", type: "number" },
                 summary: { from: "Summary", type: "boolean" },
                 expanded: { from: "Expanded", type: "boolean", defaultValue: true }
                 }
                 },*/
                model: {
                    id: "id",
                    fields: {
                        id: {from: "id", type: "string"},
                        parentId: {from: "parentId", type: "string", defaultValue: null, validation: {required: true}},
                        start: {from: "startDate", type: "date"},
                        end: {from: "endDateTime", type: "date"},
                        title: {from: "businessName", defaultValue: "", type: "string"},
                        originalId: {from: "originalId", defaultValue: "0", type: "number"},
                        assignedHours: {from: "assignedHours", defaultValue: "0", type: "number"},
                        scheduledHours: {from: "scheduledHours", defaultValue: "0", type: "number"},
                        runningHours: {from: "runningHours", defaultValue: "0", type: "number"},
                        percentComplete: {from: "percentage", defaultValue: "0", type: "number"},
                        amount: {from: "amount", defaultValue: "0", type: "number"},
                        classification: {from: "classification", defaultValue: "", type: "string"},
                        summary: {from: "summary", type: "boolean"},
                        expanded: {from: "expanded", type: "boolean", defaultValue: true}
                    }
                },
                data: function (result) {
                    return result.data || result;
                },
                total: function (result) {
                    return result.recordsTotal || result.data.length || 0;
                }
            }
        });

        $timeout(function () {
            $scope.canShowGrantt = true;

            $scope.ganttOptions = {
                dataSource: tasksDataSource,
                resources: {
                    field: "resources",
                    dataColorField: "Color",
                    dataTextField: "Name",
                    dataSource: {
                        type: "odata",
                        transport: {
                            read: {
                                url: function () {
                                    var data = {
                                        customerId: $scope.currentCustomerId,
                                        currentAgentId: $scope.currentAgentId,
                                        month: $scope.currentMonth,
                                        year: $scope.currentYear,
                                        type: $scope.filter.selectedType.value,
                                    };

                                    return "api/project/gantt-resource?data=" + Base64.encode(JSON.stringify(data));
                                },
                                dataType: "json",
                                type: "GET"
                            },
                            parameterMap: function (options, operation) {
                                if (operation !== "read") {
                                    return {models: kendo.stringify(options.models || [options])};
                                }
                            }
                        },
                        schema: {
                            model: {
                                id: "id",
                                fields: {
                                    id: {from: "ID", type: "number"}
                                }
                            },
                            data: function (result) {
                                return result.data || result;
                            },
                            total: function (result) {
                                return result.recordsTotal || result.data.length || 0;
                            }
                        }
                    }
                },
                assignments: {
                    dataTaskIdField: "TaskID",
                    dataResourceIdField: "ResourceID",
                    dataValueField: "Units",
                    dataSource: {
                        type: "odata",
                        transport: {
                            read: {
                                url: function () {
                                    var data = {
                                        customerId: $scope.currentCustomerId,
                                        currentAgentId: $scope.currentAgentId,
                                        month: $scope.currentMonth,
                                        year: $scope.currentYear,
                                        type: $scope.filter.selectedType.value,
                                    };

                                    return "api/project/gantt-resource-assignment?data=" + Base64.encode(JSON.stringify(data));
                                },
                                dataType: "json",
                                type: "GET"
                            },
                            parameterMap: function (options, operation) {
                                if (operation !== "read") {
                                    return {models: kendo.stringify(options.models || [options])};
                                }
                            }
                        },
                        schema: {
                            model: {
                                id: "ID",
                                fields: {
                                    ID: {type: "number"},
                                    ResourceID: {type: "number"},
                                    Units: {type: "number"},
                                    TaskID: {type: "string"}
                                }
                            },
                            data: function (result) {
                                return result.data || result;
                            },
                            total: function (result) {
                                return result.recordsTotal || result.data.length || 0;
                            }
                        }
                    }
                },
                views: [
                    "day",
                    {type: "week", selected: true},
                    "month"
                ],
                messages: {
                    views: {
                        day: "Día",
                        week: "Semana",
                        month: "Mes",
                        Year: "Año"
                    }
                },
                toolbar: [
                    {name: "pdf", text: "Exportar a PDF"}
                ],
                pdf: {
                    fileName: "Proyecto Planeacion",
                    proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                },
                change: function (e) {

                    var selection = this.select();
                    var task;

                    if (selection) {
                        task = this.dataItem(selection);

                        $timeout(function () {
                            $scope.canCreateTask = false;
                            $scope.canCreateProject = false;

                            if (task != null && typeof(task.classification) !== 'undefined') {

                                $scope.currentTask = task;

                                if (task.classification == "CLIENTE GRUPO" || task.classification == "CLIENTE") {
                                    $scope.canCreateProject = $scope.isAdmin;
                                } else if (task.classification == "PROYECTO") {
                                    $scope.canCreateTask = true;
                                }
                            }

                        }, 300);
                    }

                },
                columns: [

                    {field: "title", title: "Titulo", editable: true, width: 350},
                    {field: "classification", title: "Tipo", width: 100},
                    {field: "start", title: "F Inicial", format: "{0:dd/MM/yyyy}", width: 80},
                    {field: "end", title: "F Final", format: "{0:dd/MM/yyyy}", width: 80},
                    {field: "assignedHours", title: "H. Asignadas", width: 80},
                    {field: "scheduledHours", title: "H. Programadas", width: 80},
                    {field: "runningHours", title: "H. Ejecutadas", width: 80},
                    {field: "amount", title: "Valor Tarea", width: 100, format: "{0:c}"},
                    {field: "resources", title: "Asesores", width: 200}
                ],
                height: 700,

                showWorkHours: false,
                showWorkDays: false,
                editable: false
            };

        }, 200);


        $scope.projectTasks = [];


        $scope.addProject = function () {

            $scope.project = {
                id: 0,
                name: "",
                deliveryDate: new Date(),
                estimatedHours: 0,
                isRecurrent: false,
                serviceOrder: "",
                isBilled: false,
                invoiceNumber: ""
            };

            var result = $filter('filter')($scope.customersList, {id: $scope.currentTask.originalId.toString()}, true);

            $scope.project.customer = result.length > 0 ? result[0] : null;

            $scope.project.type = null;

            $scope.project.status = null;

            $scope.project.defaultSkill = null;

            $scope.project.agents = []

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

            $scope.agents = [];

            $scope.projectEdited($scope.project);
        };

        $scope.editProject = function (project) {

            var req = {
                id: project.id
            };
            $http({
                method: 'GET',
                url: 'api/project',
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
                        $scope.project.deliveryDate = new Date($scope.project.deliveryDate.date);
                        $scope.project.customer = $scope.project.customer[0];
                        $scope.loadAgent($scope.project.defaultSkill.value);
                        $scope.projectEdited($scope.project);
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);

                    $timeout(function () {

                    });
                });


        };

        $scope.addTask = function (project) {

            if ($scope.isAdmin || $scope.isAgent) {

                var req = {
                    id: project.originalId
                };

                $http({
                    method: 'GET',
                    url: 'api/project',
                    params: req
                })
                    .catch(function (e, code) {
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.project = response.data.result;
                            $scope.project.deliveryDate = new Date($scope.project.deliveryDate.date);
                            $scope.project.customer = $scope.project.customer[0];
                            $scope.projectTaskResponsible = $scope.project.agents;

                            //loadProjectTask();

                            $scope.project.projectTask = {
                                id: 0,
                                projectAgentId: 0,
                                customerName: $scope.project.customerId.businessName,
                                name: $scope.project.name,
                                description: $scope.project.description,
                                assignedHours: $scope.project.estimatedHours,
                                type: null,
                                task: "",
                                observation: "",
                                startDateTime: new Date(),
                                endDateTime: new Date(),
                                status: "activo"
                            }

                            $scope.taskEdited($scope.project);
                        });

                    }).finally(function () {

                    });

            } else {
                //$scope.currentProjectAgentId = project.projectAgentId;
            }
            //$scope.project = {};


        };

        $scope.taskEdited = function (event) {
            showModalTask('Edited', event);
        };

        $scope.projectEdited = function (project) {
            showModal('Edited', project);
        };

        function showModal(action, project) {
            var modalInstance = $aside.open({
                templateUrl: 'app_planning_project.html',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'PlanningProjectInstanceModalSideCtrl',
                scope: $scope,
                resolve: {
                    project: function () {
                        return project;
                    },
                    isview: function () {
                        return $scope.isview;
                    }
                }
            });
            modalInstance.result.then(function (selectedEvent, action) {

                $scope.currentProjectAgentId = 0;
                $scope.refreshGantt();
            });
        }


        function showModalTask(action, project) {
            var modalInstance = $aside.open({
                templateUrl: 'app_planning_project_task.html',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'PlanningProjectTaskInstanceModalSideCtrl',
                scope: $scope,
                resolve: {
                    project: function () {
                        return project;
                    },
                    isview: function () {
                        return $scope.isview;
                    }
                }
            });
            modalInstance.result.then(function (selectedEvent, action) {

                $scope.currentProjectAgentId = 0;
                $scope.refreshGantt();
            });
        }

        $scope.changeCustomer = function (item, model) {
            $timeout(function () {
                $scope.currentCustomerId = item.id;
                $scope.refreshGantt();
            });
        };

        $scope.clearCustomer = function () {
            $timeout(function () {
                $scope.currentCustomerId = 0;
                $scope.filter.selectedCustomer = null;
                $scope.refreshGantt();
            });
        }

        $scope.changeAgent = function (item, model) {
            $timeout(function () {
                $scope.currentAgentId = item.id;
                $scope.refreshGantt();
            });
        };

        $scope.clearAgent = function () {
            $timeout(function () {
                $scope.currentAgentId = 0;
                $scope.filter.selectedAgent = null;
                $scope.refreshGantt();
            });
        }

        $scope.changeMonth = function (item, model) {
            $timeout(function () {
                $scope.currentMonth = item.id;
                $scope.refreshGantt();
            });
        };

        $scope.clearMonth = function () {
            $timeout(function () {
                $scope.currentMonth = 0;
                $scope.filter.selectedMonth = null;
                $scope.refreshGantt();
            });
        }

        $scope.changeYear = function (item, model) {
            $timeout(function () {
                $scope.currentYear = item.id;
                $scope.refreshGantt();
            });
        };

        $scope.clearYear = function () {
            $timeout(function () {
                $scope.currentYear = 0;

                $scope.filter.selectedYear = null;

                $scope.refreshGantt();
            });
        }

        $scope.changeType = function (item, model) {
            $timeout(function () {
                $scope.filterCustomers = $scope.filter.selectedType.value == "GE" ? $scope.economicGroups : $scope.customers;
                $scope.clearCustomer();
                $scope.refreshGantt();
            });
        };

        $scope.refreshGantt = function () {
            var gantt = $("div [kendo-gantt]").data("kendoGantt");

            if (gantt) {
                gantt.dataSource.read();
                gantt.resources.dataSource.read();
                gantt.assignments.dataSource.read();
            }
        }

        $scope.loadAgent = function (skill) {
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
                    $scope.agents = data.data;

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        var loadCustomer = function () {
            var req = {};
            var data = JSON.stringify($scope.customer);
            req.data = data;
            $http({
                method: 'POST',
                url: 'api/customer/economic-group/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.customers = response.data.data.customer;
                    $scope.economicGroups = response.data.data.economicGroup;
                    $scope.taskTypes = response.data.data.taskType;
                    $scope.filterCustomers = $scope.filter.selectedType.value == "GE" ? $scope.economicGroups : $scope.customers;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

            req.data = data;
            $http({
                method: 'POST',
                url: 'api/project/customer',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                console.log(response);
                $timeout(function () {

                    $scope.customersList = response.data.data;

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        loadCustomer();

        $scope.changeSkill = function (item, model) {
            //$("#ddlState input.ui-select-search").val("");
            //$("#ddlTown input.ui-select-search").val("");
            $scope.project.agents = [];
            $scope.loadAgent(item.value);
        };

        var loadProjectTask = function () {
            var req = {
                project_agent_id: $scope.currentProjectAgentId
            };

            var data = JSON.stringify($scope.customer);
            req.data = data;
            return $http({
                method: 'POST',
                url: 'api/project/tasks',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.projectTasks = data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        }

    }]);

app.controller('PlanningProjectInstanceModalSideCtrl', function ($scope, $uibModalInstance, project, $log, $timeout, SweetAlert, isview, $http, $filter, toaster) {

    $scope.project = project;
    $scope.isview = isview;

    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy hh:mm tt"
        //value: $scope.project.deliveryDate.date
    };
    //$scope.project.type = null;
    //$scope.project.customer = null;

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
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Procediendo con el guardado...');
                }, 500);

                $scope.onSaveProject();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.onSaveProject = function () {
        var req = {};


        if ($scope.project.deliveryDate != null) {

            $scope.project.event_date = $scope.project.deliveryDate.toISOString();

            var data = JSON.stringify($scope.project);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/project/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                    //$scope.projectTask = response.data.result;
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

    $scope.removeAgent = function (index) {

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
                        $scope.mainContact.info.splice(index, 1);
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });


    };

    $scope.addAgent = function (index) {

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

                        var result = $filter('filter')($scope.project.agents, {agentId: $scope.agents[index].id});

                        if (result.length == 0) {
                            $scope.project.agents.push(
                                {
                                    id: 0,
                                    agentId: $scope.agents[index].id,
                                    projectId: 0,
                                    scheduledHours: $scope.agents[index].scheduledHours,
                                    notAssignedHours: $scope.agents[index].notAssignedHours,
                                    name: $scope.agents[index].name
                                }
                            );
                        } else {
                            result[0].scheduledHours = parseInt(result[0].scheduledHours) + parseInt($scope.agents[index].scheduledHours);
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

                        var req = {};
                        req.id = result.id;
                        $http({
                            method: 'POST',
                            url: 'api/project/agent/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    };


});

app.controller('PlanningProjectTaskInstanceModalSideCtrl', function ($scope, $uibModalInstance, project, $log, $uibModal, $timeout, SweetAlert, isview, $http, DTOptionsBuilder, DTColumnBuilder, $compile, toaster) {

    $scope.project = project;
    $scope.projectTask = project.projectTask;
    $scope.projectTask.tracking = {
        action: "",
        description: "",
    };

    $scope.isview = isview;
    $scope.reschedule = false;

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

                if ($scope.projectTask.startDateTime == null || $scope.projectTask.endDateTime == null) {

                    $timeout(function () {
                        toaster.pop("error", "Error", "Las fechas son requeridas. Por favor diligencia los datos del formulario y vuelva a intentarlo");
                    }, 500);

                    return;
                }


                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Procediendo con el guardado...');
                }, 500);

                save();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.cancelTask = function (id) {

        var task = {
            id: id,
            status: "cancelador",
            tracking: {
                action: "",
                description: ""
            }
        }

        var modalInstance = $uibModal.open({
            templateUrl: 'projectTracking.html',
            controller: 'ModalInstanceTrackingCtrl',
            windowTopClass: 'top-modal',
            resolve: {
                task: function () {
                    return task;
                },
                action: function () {
                    return "Cancelar";
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {
            $log.info('Modal dismissed at: ' + new Date());
            $scope.reloadData();

        });
    };

    $scope.completeTask = function (id) {
        var task = {
            id: id,
            status: "inactivo",
            tracking: {
                action: "",
                description: ""
            }
        }
        //task.status = "inactivo";
        update(task);
    };

    $scope.reloadTask = function (id) {

        $scope.reschedule = true;

        var task = {
            id: id,
            status: "inactivo",
            tracking: {
                action: "Reprogramar",
                description: ""
            }
        }

        loadProjectTaskModel(task.id)
    };

    $scope.$watch("projectTask.endDateTime - projectTask.startDateTime", function () {
        //console.log('new result',result);
        var end = new moment($scope.projectTask.endDateTime);
        var start = new moment($scope.projectTask.startDateTime);
        $scope.projectTask.duration = moment.duration(end.diff(start)).hours();
    });

    var save = function () {
        var req = {};

        $scope.projectTask.startDateTime = $scope.projectTask.startDateTime.toISOString();
        $scope.projectTask.endDateTime = $scope.projectTask.endDateTime.toISOString();

        var data = JSON.stringify($scope.projectTask);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/project/task/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $scope.projectTask = response.data.result;
                $scope.onClose();
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var update = function (task) {
        var req = {};

        $scope.projectTask.startDateTime = $scope.projectTask.startDateTime.toISOString();
        $scope.projectTask.endDateTime = $scope.projectTask.endDateTime.toISOString();

        var data = JSON.stringify(task);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/project/task/update',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $scope.projectTask = response.data.result;
                $scope.onClose();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var loadProjectTaskModel = function (idProjectTask) {
        // se debe cargar primero la información actual del cliente..
        var req = {
            id: idProjectTask
        };

        $http({
            method: 'GET',
            url: 'api/project/task',
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
                    SweetAlert.swal("Información no disponible", "Seguimiento no encontrado", "error");
                    $timeout(function () {
                        $state.go('app.clientes.list');
                    });
                } else {
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del seguimiento", "error");
                }
            })
            .then(function (response) {

                $timeout(function () {
                    $scope.projectTask = response.data.result;
                    $scope.projectTask.startDateTime = new Date($scope.projectTask.startDateTime.date);
                    $scope.projectTask.endDateTime = new Date($scope.projectTask.endDateTime.date);

                    if ($scope.reschedule) {
                        $scope.projectTask.tracking = {
                            action: "Reprogramar",
                            description: "",
                        }
                    }
                });

            }).finally(function () {
                $timeout(function () {
                    $scope.loading = false;
                    $scope.isview = true;
                }, 400);
            });


    };

    $scope.request = {};
    $scope.request.operation = "tracking";
    $scope.request.project_agent_id = $scope.currentProjectAgentId

    $scope.changeResponsible = function (item, model) {
        $timeout(function () {
            $scope.currentProjectAgentId = item.id;
            $scope.projectTask.projectAgentId = $scope.currentProjectAgentId;
            $scope.request.project_agent_id = $scope.currentProjectAgentId
            $scope.reloadData();
        });
    };

    $scope.dtInstanceTask = {};
    $scope.dtOptionsTask = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: $scope.request,
            url: 'api/project/tasks',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return true;
        })
        .withOption('fnDrawCallback', function () {

        })
        /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })

        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {

            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsTask = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs" href="#" uib-tooltip="Completar tarea" ng-click="completeTask(' + data.id + ')" >' +
                    '   <i class="fa fa-check-circle-o"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs" href="#" uib-tooltip="Reprogramar tarea" ng-click="reloadTask(' + data.id + ')" >' +
                    '   <i class="fa fa-clock-o"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs" href="#" uib-tooltip="Cancelar tarea" ng-click="cancelTask(' + data.id + ')">' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if (data.status == "activo") {
                    actions += viewTemplate;
                    actions += editTemplate;
                    actions += deleteTemplate;
                }
                return actions;
            }),
        DTColumnBuilder.newColumn('task').withTitle("Tarea").withOption('width', 100),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 100),
        DTColumnBuilder.newColumn('startDateTime').withTitle("Fecha Inicio").withOption('width', 100),
        DTColumnBuilder.newColumn('endDateTime').withTitle("Fecha Fin").withOption('width', 100),
        DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 80)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = '';
                switch (data) {
                    case "activo":
                        label = 'label label-success';
                        text = 'Programada'
                        break;

                    case "cancelador":
                        label = 'label label-danger';
                        text = 'Cancelada'
                        break;

                    case "inactivo":
                        label = 'label label-warning';
                        text = 'Completada'
                        break;
                }

                var status = '<span class="' + label + '">' + text + '</span>';


                return status;
            })
    ];

    $scope.reloadData = function () {
        $scope.dtInstanceTask.reloadData();
    };

});
