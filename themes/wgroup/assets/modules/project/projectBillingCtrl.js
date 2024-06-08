'use strict';
/**
 * controller for User Profile Example
 */
app.controller('projectBillingCtrl', ['$scope', 'flowFactory', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$filter', '$uibModal',
    function ($scope, flowFactory, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $filter, $uibModal) {

        $scope.currentProjectAgentId = 0;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";


        $scope.currentCustomerId = 0;
        $scope.currentAgentId = 0;
        $scope.currentMonth = 0;
        $scope.currentYear = 0;
        $scope.currentARL = 0;
        $scope.currentOS = '';

        $scope.filter = {};
        $scope.filter.selectedCustomer = null;
        $scope.filter.selectedAgent = null;
        $scope.filter.selectedMonth = null;
        $scope.filter.selectedYear = null;
        $scope.filter.selectedARL = null;
        $scope.filter.selectedOS = null;

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
            status:"activo"
        }

        // Preparamos los parametros por grupos
        $scope.types = $rootScope.parameters("project_type");
        $scope.taskTypes = [];
        $scope.arls = $rootScope.parameters("arl");
        $scope.skills = $rootScope.parameters("agent_skill");
        $scope.agents = [];
        $scope.agentsFilter = $rootScope.agents();
        $scope.customers = [];
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
            transport: {
                read: {
                    url: serviceRoot + "/GanttTasks",
                    dataType: "jsonp"
                },
                update: {
                    url: serviceRoot + "/GanttTasks/Update",
                    dataType: "jsonp"
                },
                destroy: {
                    url: serviceRoot + "/GanttTasks/Destroy",
                    dataType: "jsonp"
                },
                create: {
                    url: serviceRoot + "/GanttTasks/Create",
                    dataType: "jsonp"
                },
                parameterMap: function(options, operation) {
                    if (operation !== "read") {
                        return { models: kendo.stringify(options.models || [options]) };
                    }
                }
            },
            schema: {
                model: {
                    id: "id",
                    fields: {
                        id: { from: "ID", type: "number" },
                        orderId: { from: "OrderID", type: "number", validation: { required: true } },
                        parentId: { from: "ParentID", type: "number", defaultValue: null, validation: { required: true } },
                        start: { from: "Start", type: "date" },
                        end: { from: "End", type: "date" },
                        title: { from: "Title", defaultValue: "", type: "string" },
                        percentComplete: { from: "PercentComplete", type: "number" },
                        summary: { from: "Summary", type: "boolean" },
                        expanded: { from: "Expanded", type: "boolean", defaultValue: true }
                    }
                }
            }
        });

        var dependenciesDataSource = new kendo.data.GanttDependencyDataSource({
            transport: {
                read: {
                    url: serviceRoot + "/GanttDependencies",
                    dataType: "jsonp"
                },
                update: {
                    url: serviceRoot + "/GanttDependencies/Update",
                    dataType: "jsonp"
                },
                destroy: {
                    url: serviceRoot + "/GanttDependencies/Destroy",
                    dataType: "jsonp"
                },
                create: {
                    url: serviceRoot + "/GanttDependencies/Create",
                    dataType: "jsonp"
                },
                parameterMap: function(options, operation) {
                    if (operation !== "read" && options.models) {
                        return { models: kendo.stringify(options.models) };
                    }
                }
            },
            schema: {
                model: {
                    id: "id",
                    fields: {
                        predecessorId: { from: "PredecessorID", type: "number" },
                        successorId: { from: "SuccessorID", type: "number" },
                        type: { from: "Type", type: "number" }
                    }
                }
            }
        });

        $scope.ganttOptions = {
            dataSource: tasksDataSource,
            dependencies: dependenciesDataSource,
            views: [
                "day",
                { type: "week", selected: true },
                "month"
            ],
            columns: [
                { field: "id", title: "ID", width: 60 },
                { field: "title", title: "Title", editable: true },
                { field: "start", title: "Start Time", format: "{0:MM/dd/yyyy}", width: 100 },
                { field: "end", title: "End Time", format: "{0:MM/dd/yyyy}", width: 100 },
                { field: "duration", title: "Duration", width: 100 }
            ],
            height: 700,

            showWorkHours: false,
            showWorkDays: false
        };



        $scope.projectTasks = [];

        $scope.events = [];

        $scope.summaryProject =
        {
            assignedHours: 0,
            scheduledHours: 0,
            runningHours: 0,
            total: 0,
            data: [
                {
                    value: 0,
                    color: '#46BFBD',
                    highlight: '#5AD3D1',
                    label: 'Asignadas'
                },
                {
                    value: 0,
                    color: '#FDB45C',
                    highlight: '#FFC870',
                    label: 'Ejecutadas'
                }
            ]
        };

        $scope.projects = [];

        $scope.recentTasks = [];

        // Chart.js Options
        $scope.options = {

            // Sets the chart to be responsive
            responsive: false,

            //Boolean - Whether we should show a stroke on each segment
            segmentShowStroke: true,

            //String - The colour of each segment stroke
            segmentStrokeColor: '#fff',

            //Number - The width of each segment stroke
            segmentStrokeWidth: 2,

            //Number - The percentage of the chart that we cut out of the middle
            percentageInnerCutout: 50, // This is 0 for Pie charts

            //Number - Amount of animation steps
            animationSteps: 100,

            //String - Animation easing effect
            animationEasing: 'easeOutBounce',

            //Boolean - Whether we animate the rotation of the Doughnut
            animateRotate: true,

            //Boolean - Whether we animate scaling the Doughnut from the centre
            animateScale: false,

            //String - A legend template
            legendTemplate: '<ul class="tc-chart-js-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>'

        };

        $scope.addProject = function () {

            $scope.project = {
                id: 0,
                name: "",
                deliveryDate: new Date(),
                estimatedHours: 0,
                isRecurrent: false,
                serviceOrder: "",
                isBilled: false,
                invoiceNumber:""
            };

            $scope.project.type = {
                id: "0",
                item: "-- Seleccionar --",
                value: "-S-"
            };
            $scope.project.customer = {
                id: "0",
                item: "-- Seleccionar --",
                value: "-S-"
            };
            $scope.project.status = {
                id: "0",
                item: "-- Seleccionar --",
                value: "-S-"
            };

            $scope.project.defaultSkill = {
                id: "0",
                item: "-- Seleccionar --",
                value: "-S-"
            };

            $scope.project.agents = []

            $scope.project.projectTask = {
                id: 0,
                projectAgentId: 0,
                type: null,
                task: "",
                observation: "",
                startDateTime: new Date(),
                endDateTime: new Date(),
                status:"activo"
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
                        $scope.project.deliveryDate =  new Date($scope.project.deliveryDate.date);
                        $scope.project.customer =  $scope.project.customer[0];
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
            //$scope.project = {};
            $scope.currentProjectAgentId = project.projectAgentId;

            loadProjectTask();

            $scope.project.projectTask = {
                id: 0,
                projectAgentId: project.projectAgentId,
                customerName: project.customerName,
                name: project.name,
                description: project.description,
                assignedHours: project.assignedHours,
                type: null,
                task: "",
                observation: "",
                startDateTime: new Date(),
                endDateTime: new Date(),
                status: "activo"
            }

            $scope.taskEdited($scope.project);
        };

        $scope.taskEdited = function (event) {
            showModalTask('Edited', event);
        };

        $scope.projectEdited = function (project) {
            showModal('Edited', project);
        };

        function showModal(action, project) {
            var modalInstance = $aside.open({
                templateUrl: 'app_project.html',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalProjectInstanceSideCtrl',
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
                loadProjects();
                loadRecentTask();

            });
        }

        $scope.viewProjectTask = function (project)
        {
            var modalInstance = $aside.open({
                templateUrl: 'projectTaskView.html',
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalProjectTaskViewInstanceSideCtrl',
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
                loadProjects();
                loadRecentTask();

            });
        }

        function showModalTask(action, project) {
            var modalInstance = $aside.open({
                templateUrl: 'app_admin_projectTask.html',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideTaskCtrl',
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
                loadProjects();
                loadRecentTask();

            });
        }

        var loadProjects = function () {

            var req = {};
            $scope.customer = {};
            var data = JSON.stringify($scope.customer);
            req.data = Base64.encode(data);
            req.customer_id = $scope.currentCustomerId;
            req.agent_id = $scope.currentAgentId;
            req.month = $scope.currentMonth;
            req.year = $scope.currentYear;
            req.arl = $scope.currentARL;
            req.os = $scope.currentOS;
            req.isBilled = 0;

            return $http({
                method: 'POST',
                url: 'api/project/summaryBilling',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {


                $timeout(function () {

                    $scope.projects = response.data.data;

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        var loadList = function () {

            var req = {};
            $scope.customer = {};
            var data = JSON.stringify($scope.customer);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/project/fillList',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {


                $timeout(function () {

                    $scope.years = response.data.data.years;

                    if ($scope.years.length > 0) {
                        var years = $filter('filter')($scope.years, {id: response.data.data.currentYear});

                        if (years.length > 0) {
                            $scope.filter.selectedYear = years[0];
                        } else {
                            $scope.filter.selectedYear = $scope.years[$scope.years.length - 1];
                        }
                    } else {
                        $scope.filter.selectedYear = {
                            id: response.data.data.currentYear,
                            item: response.data.data.currentYear,
                            value: response.data.data.currentYear
                        }
                    }

                    var months = $filter('filter')($scope.months, {id: response.data.data.currentMonth});

                    if (months.length > 0) {
                        $scope.filter.selectedMonth = months[0];
                    }

                    $scope.currentMonth = response.data.data.currentMonth;
                    $scope.currentYear = response.data.data.currentYear;

                    loadProjects();

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        loadList();

        $scope.changeCustomer = function (item, model) {
            $timeout(function () {
                $scope.currentCustomerId = item.id;
                loadProjects();
            });
        };

        $scope.clearCustomer = function()
        {
            $timeout(function () {
                $scope.currentCustomerId = 0;
                $scope.filter.selectedCustomer = null;
                loadProjects();
            });
        }

        $scope.changeAgent = function (item, model) {
            $timeout(function () {
                $scope.currentAgentId = item.id;
                loadProjects();
            });
        };

        $scope.clearAgent= function()
        {
            $timeout(function () {
                $scope.currentAgentId = 0;
                $scope.filter.selectedAgent = null;
                loadProjects();
            });
        }

        $scope.changeMonth = function (item, model) {
            $timeout(function () {
                $scope.currentMonth = item.id;
                loadProjects();
            });
        };

        $scope.clearMonth= function()
        {
            $timeout(function () {
                $scope.currentMonth = 0;
                $scope.filter.selectedMonth = null;
                loadProjects();
            });
        }

        $scope.changeYear = function (item, model) {
            $timeout(function () {
                $scope.currentYear = item.id;
                loadProjects();
            });
        };

        $scope.clearYear= function()
        {
            $timeout(function () {
                $scope.currentYear = 0;
                $scope.filter.selectedYear = null;
                loadProjects();
            });
        }

        $scope.changeARL = function (item, model) {
            $timeout(function () {
                $scope.currentARL = item.id;
                loadProjects();
            });
        };

        $scope.clearARL = function()
        {
            $timeout(function () {
                $scope.currentARL = 0;
                $scope.filter.selectedARL = null;
                loadProjects();
            });
        }

        $scope.searchOS = function () {
            $timeout(function () {
                $scope.currentOS = $scope.filter.selectedOS;
                loadProjects();
            });
        };

        $scope.clearOS = function()
        {
            $timeout(function () {
                $scope.currentOS = '';
                $scope.filter.selectedOS = '';
                loadProjects();
            });
        }

        $scope.sendStatus = function () {

            var project = {
                id: 0,
                status: "sendStatus",
                tracking: {
                    action: "",
                    description: ""
                }
            }

            var modalInstance = $uibModal.open({
                templateUrl: 'projectStatus.html',
                controller: 'ModalInstanceProjectTrackingCtrl',
                windowTopClass: 'top-modal',
                resolve: {
                    project: function () {
                        return project;
                    },
                    action: function () {
                        return "Enviar Estado Proyectos";
                    },
                    filters: function() {
                        return $scope.filter;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {

            }, function () {

            });
        };

        var loadRecentTask = function () {

            var req = {};
            $scope.customer = {};
            var data = JSON.stringify($scope.customer);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/project/report',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {


                $timeout(function () {

                    if (response.data.data.length > 0)
                        $scope.recentTasks = response.data.data;

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

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

                    //if (response.data.data.length > 0)
                        $scope.agents = response.data.data;

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
                    $scope.taskTypes = response.data.data.taskType;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

            req.data = data;
            return $http({
                method: 'POST',
                url: 'api/project/customer',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {


                $timeout(function () {

                    $scope.customers = response.data.data;

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        loadProjects();
        loadCustomer();

        $scope.refreshAll = function () {
            loadProjects();
        };

        $scope.changeSkill = function (item, model) {
            //$("#ddlState input.ui-select-search").val("");
            //$("#ddlTown input.ui-select-search").val("");
            $scope.project.agents = [];

            $scope.loadAgent(item.value);
        };


        var loadTask = function () {
            var req = {};
            var data = JSON.stringify($scope.customer);
            req.data = data;
            return $http({
                method: 'POST',
                url: 'api/project/task',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {


                $timeout(function () {

                    $scope.events = response.data.data;
                    $scope.events.forEach(function (entry) {
                        entry.startsAt =  new Date(entry.startsAt.date);
                        entry.endsAt =  new Date(entry.endsAt.date);
                    });
                    //$scope.tracking.event_date =  new Date($scope.tracking.eventDateTimeTsp.date);
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        loadTask();

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
                    $scope.projectTasks = response.data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        }

        $scope.onProjectAdmin = function(){
            $state.go("app.projects.list");
        };

        $scope.onSaveBilling = function () {
            var req = {};

            var projectData = {
                isValid: true,
                projects: []
            }

            var result = true;

            angular.forEach($scope.projects, function(project) {
                if (project.isBilled && project.invoiceNumber == '') {
                    result = false;
                } else if (project.isBilled && project.invoiceNumber != '') {
                    projectData.projects.push(project);
                }
            });


            if (result) {

                if (projectData.projects.length > 0) {
                    var data = JSON.stringify(projectData);

                    req.data = Base64.encode(data);

                    return $http({
                        method: 'POST',
                        url: 'api/project/updateBilling',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param(req)
                    }).then(function (response) {

                        $timeout(function () {
                            toaster.pop('success', 'Operación Exitosa', 'Facturas ingresadas');
                            $scope.refreshAll();
                        });
                    }).catch(function (e) {
                        $log.error(e);
                        toaster.pop("error", "Error", "Ha ocurrido un error ingresando las facturas");
                    }).finally(function () {

                    });
                } else {
                    toaster.pop("error", "Error", "No hay proyectos marcados para facturar");
                }
            } else {
                toaster.pop("error", "Error", "El número de factura es requerido para los proyectos marcados como facturado");
            }

        }

    }]);