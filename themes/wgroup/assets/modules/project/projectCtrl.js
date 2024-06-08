'use strict';
/**
 * controller for User Profile Example
 */
app.controller('projectCtrl', ['$scope', 'flowFactory', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$filter', '$uibModal',
    'ChartService', 'ListService', 'ngNotify', 
    function ($scope, flowFactory, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $filter, $uibModal, ChartService, ListService, ngNotify) {

        $scope.currentProjectAgentId = 0;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.chart = {
            bar: { options: null },
            doughnut: { options: null },
            programs: { data: null },
            summary: {
                data: null,
                total: 0
            }
        };

        $scope.filter = {
            selectedCustomer: null,
            selectedAgent: null,
            selectedMonth: null,
            selectedYear: null,
            selectedARL: null,
            selectedType: null,
            selectedOS: null,
            administrator: null
        };

        var pager = {
            refresh: true,
            index: 0
        };

        getList();

        function getList() {

            var entities = [
                { name: 'month', value: null },
                { name: 'customer_project_year', value: null },
                { name: 'current_year', value: null },
                { name: 'current_month', value: null },
                { name: 'project_type', value: null },
                { name: 'project_concepts', value: null },
                { name: 'project_classifications', value: null },
                { name: 'arl', value: null },
                { name: 'agent', value: null },
                { name: 'agent_skill', value: null },
                { name: 'customer_project_task_type', value: null },
                { name: 'project_invoice_status', value: null },
            ];


            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.months = response.data.data.month;
                    $scope.years = response.data.data.yearList;
                    $scope.taskTypes = response.data.data.projectTaskTypeList;

                    $scope.arls = response.data.data.arl;
                    $scope.skills = response.data.data.agent_skill;
                    $scope.types = response.data.data.project_type;

                    $scope.conceptList = response.data.data.project_concepts;
                    $scope.classificationList = response.data.data.project_classifications;
                    $scope.invoiceStatusList = response.data.data.project_invoice_status;

                    $scope.agentsFilter = $rootScope.agents();

                    if ($scope.years.length > 0) {
                        var years = $filter('filter')($scope.years, { id: response.data.data.currentYear }, true);

                        if (years.length > 0) {
                            $scope.filter.selectedYear = years[0];
                        } else {
                            $scope.filter.selectedYear = $scope.years[0];
                        }
                    } else {
                        $scope.filter.selectedYear = {
                            id: response.data.data.currentYear,
                            item: response.data.data.currentYear,
                            value: response.data.data.currentYear
                        }
                    }

                    var months = $filter('filter')($scope.months, { value: response.data.data.currentMonth.toString() }, true);

                    if (months.length > 0) {
                        $scope.filter.selectedMonth = months[0];
                    }

                    $scope.currentMonth = response.data.data.currentMonth;
                    $scope.currentYear = response.data.data.currentYear;
                    getAdministrators();

                    getProjectAll();

                    if (!$scope.canShowTable) {
                        initialiceGrid();
                    } else {
                        kendoGridReload();
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        function getAdministrators() {
            $scope.filter.administrator = null;

            var entities = [{
                name: 'customer_project_administrators', criteria: {
                    type: $rootScope.currentUser().wg_type,
                    year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : null,
                    month: $scope.filter.selectedMonth ? $scope.filter.selectedMonth.value : null,
                    customerId: $scope.filter.selectedCustomer ? $scope.filter.selectedCustomer.id : null
                }
            }];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.filter.administrator = null;
                    $scope.administratorList = response.data.data.projectAdministratorList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        var getProjectAll = function () {
            var $criteria = {
                type: $rootScope.currentUser().wg_type,
                customerId: $scope.filter.selectedCustomer ? $scope.filter.selectedCustomer.id : null,
                agentId: $scope.filter.selectedAgent ? $scope.filter.selectedAgent.id : null,
                month: $scope.filter.selectedMonth ? $scope.filter.selectedMonth.value : null,
                year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : null,
                odes: $scope.filter.selectedOS ? $scope.filter.selectedOS : null,
                projectType: $scope.filter.selectedType ? $scope.filter.selectedType.value : null,
                arl: $scope.filter.selectedARL ? $scope.filter.selectedARL.value : null,
                administrator: $scope.filter.administrator ? $scope.filter.administrator.value : null,
                isBilled: null,
            }

            var entities = [
                { name: 'customer_project_summary', value: null, criteria: $criteria },
                { name: 'customer_project_agent_task_timeline', value: null, criteria: $criteria }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.projectSummary = response.data.data.projectSummary;
                    //$scope.projects = response.data.data.projectList;
                    $scope.recentTasks = response.data.data.projectAgentTaskTimeLine;
                    $scope.chart.summary.data = response.data.data.chartProjectSummary;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getCharts();

        function getCharts() {
            var entities = [
                { name: 'chart_doughnut_options', criteria: null },
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.doughnut.options = response.data.data.chartDoughnutOptions;

                    $scope.chart.doughnut.options.legend.position = 'top';
                    $scope.chart.doughnut.options.maintainAspectRatio = false;
                    $scope.chart.doughnut.options.responsive = false;

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.taskTypes = [];
        $scope.arls = [];
        $scope.agents = [];
        $scope.filterAgents = [];
        $scope.customerList = [];
        $scope.projectTasks = [];
        $scope.projects = [];
        $scope.recentTasks = [];

        $scope.canShowTable = false;


        var initialiceGrid = function () {
            $scope.canShowTable = true;

            $timeout(function () {

                var kendoGridColumns = function () {
                    var $columns = [
                        {
                            command: [
                                { text: " ", template: "<a class='btn btn-success btn btn-xs' ng-click='onEditProject(dataItem)' uib-tooltip='Editar' tooltip-placement='right'><i class='fa fa-edit'></i></a> " },
                                { text: " ", template: "<a class='btn btn-warning btn btn-xs' ng-click='viewProjectTask(dataItem)' uib-tooltip='Ver Tareas' tooltip-placement='right'><i class='fa fa-list'></i></a> " },
                                { text: " ", template: "<a class='btn btn-dark-azure btn btn-xs' ng-click='onAddTask(dataItem)' uib-tooltip='Nueva Tarea' tooltip-placement='right'><i class='fa fa-plus-circle'></i></a> " },
                                { text: " ", template: "<a class='btn btn-light-red btn btn-xs' ng-click='onAddComment(dataItem)' uib-tooltip='Comentarios' tooltip-placement='right'><i class='fa fa-comments'></i></a> " },
                                { text: " ", template: "<a class='btn btn-info btn btn-xs' ng-click='onAddAttachment(dataItem)' uib-tooltip='Anexos' tooltip-placement='right'><i class='fa fa-paperclip'></i></a> " },
                                { text: " ", template: "<a class='btn btn-dark-green btn btn-xs' ng-click='onDownloadAttachment(dataItem)' uib-tooltip='Descargar Anexos' tooltip-placement='right'><i class='fa fa-download'></i></a> " },
                                { text: " ", template: "<a class='btn btn-dark-red btn btn-xs' ng-click='onAddReasonCancel(dataItem)' uib-tooltip='Cancelar' tooltip-placement='right'><i class='fa fa-ban'></i></a> " },
                                { text: " ", template: "<a class='btn btn-dark-orange btn btn-xs' ng-click='onAddReasonReSchedule(dataItem)' uib-tooltip='Reprogramar' tooltip-placement='right'><i class='fa fa-calendar-check-o'></i></a> " }
                            ],
                            locked: false,
                            width: "180px"
                        }
                    ];

                    $columns.push(buildKendoGridColumn('customerName', 'Empresa', "300px"));
                    $columns.push(buildKendoGridColumn('name', 'Nombre', '300px'));
                    $columns.push(buildKendoGridColumn('description', 'Descripción', '200px'));
                    $columns.push(buildKendoGridColumn('type', 'Tipo', '200px'));
                    $columns.push(buildKendoGridColumn('serviceOrder', 'Odes/Consec.', '200px'));
                    $columns.push(buildKendoGridColumn('agentName', 'Asesor', '200px'));
                    $columns.push(buildKendoGridColumn('administrator', 'Administrador', '200px'));
                    $columns.push(buildKendoGridColumn('assignedHours', 'H. Asignadas', '200px', "danger-header"));
                    $columns.push(buildKendoGridColumn('scheduledHours', 'H. Programadas', '200px', "info-header"));
                    $columns.push(buildKendoGridColumn('runningHours', 'H. Ejecutadas', '200px', "success-header"));
                    $columns.push(buildKendoGridColumn('statusText', 'Estado', '200px', false, function (dataItem) {
                        return dataItem.status;
                    }));

                    return $columns;
                };

                var buildKendoGridColumn = function (field, title, width, headerClass, filterable, templateCallback) {
                    return {
                        field: field,
                        title: title,
                        width: width,
                        minScreenWidth: 100,
                        headerAttributes: {
                            class: "text-bold " + headerClass ? headerClass : '',
                            style: title == '' ? "display: none" : ''
                        },
                        filterable: filterable !== undefined ? filterable : false,
                        template: (typeof templateCallback == 'function') ? templateCallback : null
                    };
                }

                $scope.mainGridOptions = {
                    dataSource: {
                        type: "odata",
                        transport: {
                            read: {
                                url: "api/customer-project/activities",
                                dataType: "json",
                                type: "POST",
                                data: function () {

                                    var param = {
                                        type: $rootScope.currentUser().wg_type,
                                        customerId: $scope.filter.selectedCustomer ? $scope.filter.selectedCustomer.id : null,
                                        agentId: $scope.filter.selectedAgent ? $scope.filter.selectedAgent.id : null,
                                        month: $scope.filter.selectedMonth ? $scope.filter.selectedMonth.value : null,
                                        year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : null,
                                        odes: $scope.filter.selectedOS ? $scope.filter.selectedOS : null,
                                        projectType: $scope.filter.selectedType ? $scope.filter.selectedType.value : null,
                                        arl: $scope.filter.selectedARL ? $scope.filter.selectedARL.value : null,
                                        administrator: $scope.filter.administrator ? $scope.filter.administrator.value : null,
                                        isBilled: null,
                                    };

                                    return param;
                                }
                            },
                            parameterMap: function (data, operation) {
                                return JSON.stringify(data);
                            }
                        },
                        schema: {
                            model: {
                                id: "id",
                                fields: {}
                            },
                            data: function (result) {
                                return result.data || result;
                            },
                            total: function (result) {
                                return result.recordsTotal || result.data.length || 0;
                            }
                        },
                        pageSize: 10,
                        serverPaging: true,
                        serverFiltering: true,
                        serverSorting: true
                    },
                    noRecords: true,
                    scrollable: true,
                    sortable: {
                        mode: "multiple"
                    },
                    pageable: {
                        change: function (e) {
                            pager.index = e.index;
                        }
                    },
                    filterable: false,
                    dataBinding: function (e) {
                        $log.info("dataBinding");
                    },
                    dataBound: function (e) {
                        $scope.grid.tbody.find("tr").each(function () {

                            var model = $scope.grid.dataItem(this);

                            var canComment = $rootScope.can("projects_show_comments_card");
                            var canAttachment = $rootScope.can("projects_show_attachments_card");
                            var canDownload = $rootScope.can("projects_download_attachments");

                            if (!canComment) {
                                $(this).find(".btn-light-red").remove();
                            }

                            if (!canAttachment) {
                                $(this).find(".btn-info").remove();
                            }

                            if (!canDownload || model.countAttachment == 0) {
                                $(this).find(".btn-dark-green").remove();
                            }

                            if (model.statusText != "En progreso") {
                                $(this).find(".btn-dark-red").remove();
                                $(this).find(".btn-dark-orange").remove();
                            }

                            if (!$scope.isAgent) {
                                $(this).find(".btn-dark-azure").remove();
                            }

                            if (!$scope.isAdmin) {
                                $(this).find(".btn-success").remove();
                                $(this).find(".btn-warning").remove();
                                $(this).find(".btn-dark-red").remove();
                                $(this).find(".btn-dark-orange").remove();
                            }

                        });
                    },
                    columns: kendoGridColumns()
                };
            });
        }

        var kendoGridReload = function () {
            if ($scope.grid !== undefined) {
                $scope.grid.dataSource.read();
            }
        }

        $scope.$on("kendoWidgetCreated", function (event, widget) {
            if ($scope.grid === undefined || $scope.grid === null) {
                $scope.grid = widget;
            }
        });


        $scope.onAddProject = function () {
            onOpenProjectModal({ id: 0 });
        };

        $scope.onEditProject = function (project) {
            onOpenProjectModal(project);
        };

        $scope.onAddTask = function (project) {
            openTaskModal(project);
        };

        $scope.onAddReasonCancel = function (project) {
            addReason(project, "C");
        }

        $scope.onAddReasonReSchedule = function (project) {
            addReason(project, "R");
        }

        $scope.onDownloadAttachment = function (project) {
            ngNotify.set('El archivo se está generando.', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var entity = {
                customerId: project.customerId,
                customerProjectId: project.id
            };

            var req = {};
            var data = JSON.stringify(entity);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: 'api/customer-project-document/export',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                var $url = response.data.path + response.data.filename;
                var $link = '<div class="row"></div> <div class="col-sm-12 text-center">Por favor espere y verifique su correo y la bandeja de mensajes!</div> </div>';
                ngNotify.set($link, {
                    position: 'bottom',
                    sticky: true,
                    type: 'info',
                    button: true,
                    html: true
                });

            }).catch(function (response) {
                ngNotify.set(response.data.message, {
                    position: 'bottom',
                    sticky: true,
                    type: 'error',
                    button: true,
                    html: true
                });
            }).finally(function () {

            });
        }        

        var addReason = function(project, type) {
            var modalInstance = $uibModal.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/project/project_historial_modal.htm',
                controller: 'ModalInstanceHistorialCtrl',
                windowTopClass: 'top-modal',
                resolve: {
                    type: function () {
                        return type;
                    }
                }
            });

            modalInstance.result.then(function (entity) {

                if (entity) {
                    var payload = {
                        id: 0,
                        customerProjectId: project.id,
                        type: type,
                        reason: entity.reason,
                        deliveryDate: entity.deliveryDate
                    };

                    var req = {};
                    var data = JSON.stringify(payload);
                    req.data = Base64.encode(data);

                    return $http({
                        method: 'POST',
                        url: 'api/customer-project-historial/save',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        data: $.param(req)
                    }).then(function (response) {
                        $timeout(function () {
                            toaster.pop('success', 'Operación Exitosa', 'La información ha sido guardada satisfactoriamente.');
                            kendoGridReload();
                        });
                    }).catch(function (e) {
                        toaster.pop('Error', 'Error inesperado', e);
                    }).finally(function () {

                    });
                }

            }, function () {

            });
        }


        function onOpenProjectModal(project) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/project/project_edit_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalProjectInstanceSideCtrl',
                scope: $scope,
                resolve: {
                    project: function () {
                        return project;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function (selectedEvent, action) {

                $scope.currentProjectAgentId = 0;
                getProjectAll();
                kendoGridReload();

            }, function () {

            });
        }

        $scope.viewProjectTask = function (project) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/project/project_task_view_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalProjectTaskViewInstanceSideCtrl',
                scope: $scope,
                resolve: {
                    project: function () {
                        return project;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function (selectedEvent, action) {

                $scope.currentProjectAgentId = 0;
                getProjectAll();
                kendoGridReload();

            }, function () {

            });
        }

        function openTaskModal(project) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/project/project_task_edit_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideTaskCtrl',
                scope: $scope,
                resolve: {
                    project: function () {
                        return project;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });

            modalInstance.result.then(function (selectedEvent, action) {
                $scope.currentProjectAgentId = 0;
                getProjectAll();
                kendoGridReload();
            }, function () {

            });
        }

        $scope.changeCustomer = function () {
            $timeout(function () {
                getProjectAll();
                kendoGridReload();
            });
        };

        $scope.clearCustomer = function () {
            $timeout(function () {
                $scope.filter.selectedCustomer = null;
                getProjectAll();
                kendoGridReload();
            });
        }

        $scope.changeAgent = function () {
            $timeout(function () {
                getProjectAll();
                kendoGridReload();
            });
        };

        $scope.clearAgent = function () {
            $timeout(function () {
                $scope.filter.selectedAgent = null;
                getProjectAll();
                kendoGridReload();
            });
        }

        $scope.changeMonth = function () {
            $timeout(function () {
                getAdministrators();
                getProjectAll();
                kendoGridReload();
            });
        };

        $scope.clearMonth = function () {
            $timeout(function () {
                $scope.filter.selectedMonth = null;
                getProjectAll();
                kendoGridReload();
            });
        }

        $scope.changeYear = function () {
            $timeout(function () {
                getAdministrators();
                getProjectAll();
                kendoGridReload();
            });
        };

        $scope.clearYear = function () {
            $timeout(function () {
                $scope.filter.selectedYear = null;
                getProjectAll();
                kendoGridReload();
            });
        }

        $scope.changeARL = function () {
            $timeout(function () {
                getProjectAll();
            });
        };

        $scope.clearARL = function () {
            $timeout(function () {
                $scope.filter.selectedARL = null;
                getProjectAll();
                kendoGridReload();
            });
        }

        $scope.changeType = function () {
            $timeout(function () {
                getProjectAll();
                kendoGridReload();
            });
        };

        $scope.clearType = function () {
            $timeout(function () {
                $scope.filter.selectedType = null;
                getProjectAll();
                kendoGridReload();
            });
        }

        $scope.searchOS = function () {
            $timeout(function () {
                getProjectAll();
                kendoGridReload();
            });
        };

        $scope.clearOS = function () {
            $timeout(function () {
                $scope.filter.selectedOS = null;
                getProjectAll();
                kendoGridReload();
            });
        }

        $scope.clearAdministrator = function () {
            $timeout(function () {
                $scope.filter.administrator = null;
                getProjectAll();
                kendoGridReload();
            });
        }

        $scope.sendStatus = function () {
            var modalInstance = $uibModal.open({
                templateUrl: 'projectStatus.html',
                controller: 'ModalInstanceProjectTrackingCtrl',
                windowTopClass: 'top-modal',
                resolve: {
                    project: function () {
                        return {
                            id: 0,
                            status: "sendStatus",
                            tracking: {
                                action: "",
                                description: ""
                            }
                        };
                    },
                    action: function () {
                        return "Enviar Estado Proyectos";
                    },
                    filters: function () {
                        return $scope.filter;
                    }
                }
            });

            modalInstance.result.then(function () {

            }, function () {

            });
        };

        $scope.refreshAll = function () {
            getProjectAll();
            kendoGridReload();
        };

        $scope.onBilling = function () {
            $state.go("app.projects.billing");
        };

        $scope.onSearchCustomer = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideProjectSearchCustomerCtrl',
                scope: $scope,
                windowTopClass: 'top-modal',
                resolve: {
                }
            });
            modalInstance.result.then(function (customer) {
                var result = $filter('filter')($scope.customerList, { id: customer.id });

                if (result.length == 0) {
                    $scope.customerList.push(customer);
                }

                $scope.filter.selectedCustomer = customer;
                $scope.changeCustomer(customer);
                getAdministrators();
            }, function () {

            });
        };


        $scope.onAddAttachment = function (project) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/common/modals/customer_document_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalProjectAttachmentInstanceSideCtrl',
                scope: $scope,
                resolve: {
                    item: function () {
                        return project;
                    },
                    customer: function () {
                        return $scope.currentCustomer ? $scope.currentCustomer : $scope.filter.selectedCustomer;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });

            modalInstance.result.then(function () { }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        }


        $scope.onAddComment = function (project) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_sgsst_comment.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_questions_comment_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalProjecCommentInstanceSideCtrl',
                scope: $scope,
                resolve: {
                    item: function () {
                        return project;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        }

    }
]);

app.controller('ModalProjectInstanceSideCtrl', function ($rootScope, $scope, $uibModalInstance, project, $log, $timeout, SweetAlert, isView, $http, $filter, toaster, $aside) {

    $scope.project = project;
    $scope.isView = isView;
    $scope.items = [];

    $scope.isAgent = $rootScope.currentUser().wg_type == "agent";

    $scope.activeConceptList = [];

    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
        //value: $scope.project.deliveryDate.date
    };

    var init = function () {
        $scope.project = {
            id: project.id,
            name: "",
            deliveryDate: new Date(),
            estimatedHours: 0,
            isRecurrent: false,
            serviceOrder: "",
            isBilled: false,
            invoiceNumber: "",
            item: null,
            type: null,
            customer: null,
            status: null,
            defaultSkill: null,
            associatedCosts: [],
            costTotal: 0,
            invoiceStatus: null,
            createdBy: project.id ? null : $rootScope.currentUser().name
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
            status: "activo"
        }
    }

    init();

    var onLoadRecord = function () {
        if ($scope.project.id) {
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
                        if ($scope.project.deliveryDate) {
                            $scope.project.deliveryDate = new Date($scope.project.deliveryDate.date);
                        }
                        $scope.project.customer = $scope.project.customer[0];
                        loadAgent($scope.project.defaultSkill.value);
                        $scope.calculateGeneralTotal();

                        loadActiveConceptList($scope.project.type);
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        }
    }

    onLoadRecord();

    $scope.ok = function () {
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
                url: 'api/project/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'La información ha sido guardada satisfactoriamente');
                    }, 500);

                    $scope.projectTask = response.data.result;
                    $scope.refreshAll();
                    $scope.ok();
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

                        var result = $filter('filter')($scope.project.agents, { agentId: agent.id });

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
                                url: 'api/project/agent/delete',
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

    $scope.changeProjectType = function (item, model) {
        if (item != null) {
            $scope.project.item = null;
            loadItems(item.value);
        }

        $scope.refreshConcepts(item);
    };

    $scope.changeSkill = function (item, model) {
        $scope.project.agents = [];
        loadAgent(item.value);
    };

    var loadItems = function (classification) {
        var req = {
            classification: classification
        };

        return $http({
            method: 'POST',
            url: 'api/budget/classification',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.items = response.data.data;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    }

    var loadAgent = function (skill) {
        var req = {};
        req.skill = skill;
        var data = JSON.stringify($rootScope.currentUser());
        req.data = data;
        return $http({
            method: 'POST',
            url: 'api/project/agent',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.agentList = response.data.data;
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    }

    $scope.onSearchCustomer = function () {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
            placement: 'right',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideProjectSearchCustomerCtrl',
            scope: $scope,
            windowTopClass: 'top-modal',
            resolve: {
            }
        });
        modalInstance.result.then(function (customer) {
            var result = $filter('filter')($scope.customerList, { id: customer.id });

            if (result.length == 0) {
                $scope.customerList.push(customer);
            }

            $scope.project.customer = customer;
        }, function () {

        });
    };



    $scope.onAddCost = function () {
        if (!$scope.project.type) {
            return;
        }

        $scope.project.associatedCosts.push({
            id: 0,
            concept: null,
            classification: null,
            amount: 0,
            price: 0,
            totalValue: 0,
            status: "PROGRAMADA",
            classificationActiveList: []
        });
    };


    $scope.onRemoveCost = function (index, id) {
        if (id === 0) {
            $scope.project.associatedCosts.splice(index, 1);
            $scope.calculateGeneralTotal();
            return;
        }

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
                if (!isConfirm) {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                    return;
                }

                $timeout(function () {
                    var req = { id: id };
                    $http({
                        method: 'POST',
                        url: 'api/project/cost/delete',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        data: $.param(req)
                    }).then(function (response) {
                        $scope.project.associatedCosts.splice(index, 1);
                        $scope.calculateGeneralTotal();
                        swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                    }).catch(function (e) {
                        $log.error(e);
                        SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                    });
                });

            });
    };


    $scope.refreshConcepts = function (type) {
        loadActiveConceptList(type);
        $scope.project.associatedCosts.forEach(function (cost) {
            cost.concept = null;
            cost.classification = null;
            cost.classificationActiveList = [];
        });
    };

    $scope.onChangeConcept = function (concept, index) {
        $scope.project.associatedCosts[index].classification = null;

        var classificationActiveList = $filter('filter')($scope.classificationList, { code: concept.value });
        $scope.project.associatedCosts[index].classificationActiveList = classificationActiveList;
    };

    $scope.onCalculateTotalValue = function (cost) {
        cost.totalValue = cost.amount * cost.price;
        $scope.calculateGeneralTotal();
    };

    $scope.calculateGeneralTotal = function () {
        $scope.project.costTotal = 0;
        $scope.project.associatedCosts.forEach(function (cost) {
            $scope.project.costTotal += Number.parseInt(cost.totalValue);
        })
    };


    function loadActiveConceptList(type) {
        var relationshipConcepts = $filter('filter')($scope.conceptList, { code: type.value });
        var adminConcept = $filter('filter')($scope.conceptList, { value: 'PCOSGA' });
        $scope.activeConceptList = relationshipConcepts.concat(adminConcept);

        refreshClassifications();
    }

    function refreshClassifications() {
        $scope.project.associatedCosts.forEach(function (cost) {
            var classificationActiveList = $filter('filter')($scope.classificationList, { code: cost.concept.value });
            cost.classificationActiveList = classificationActiveList;
        })
    }

});

app.controller('ModalInstanceSideTaskCtrl', function ($rootScope, $scope, $uibModalInstance, project, $log, $uibModal, $timeout, SweetAlert, isView, $http, DTOptionsBuilder, DTColumnBuilder, $compile, toaster) {

    $scope.project = project;

    $scope.limitDeliveryDate = $rootScope.parameters("projects_time_to_allow_create_task");

    $scope.allowToSave = false;

    var init = function () {
        $scope.projectTask = {
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
            status: "activo",
            tracking: {
                action: "",
                description: "",
            }
        }

        $scope.validateSave();
    };

    $scope.validateSave = function () {
        var delivaryDateProject = new Date($scope.project.deliveryDate);
        var nextMonth = delivaryDateProject.getMonth() + 1;
        var day = $scope.limitDeliveryDate[0] ? $scope.limitDeliveryDate[0].item : 1;

        var dateAllowSave = new Date(delivaryDateProject.getFullYear(), nextMonth, day);

        if (new Date() < dateAllowSave) {
            $scope.allowToSave = true;
        }
    };

    init();

    $scope.isView = isView;
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
                var $startDate = $scope.projectTask.startDateTime;
                var $endDateTime = $scope.projectTask.endDateTime;
                if (!($startDate instanceof Date && !isNaN($startDate.valueOf())) || !($endDateTime instanceof Date && !isNaN($endDateTime.valueOf()))) {
                    $timeout(function () {
                        toaster.pop("error", "Error", "Las fechas son requeridas. Por favor diligencia los datos del formulario y vuelva a intentarlo");
                    }, 500);
                    return;
                }

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
            templateUrl: $rootScope.app.views.urlRoot + 'modules/project/project_task_tracking_modal.htm',
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
            $scope.reloadData();
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


    var save = function () {
        var req = {};
        var $startDate = $scope.projectTask.startDateTime;
        var $endDateTime = $scope.projectTask.endDateTime;

        $scope.projectTask.startDateTime = $scope.projectTask.startDateTime.toISOString();
        $scope.projectTask.endDateTime = $scope.projectTask.endDateTime.toISOString();

        var data = JSON.stringify($scope.projectTask);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/project/task/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $uibModalInstance.close(1);
            });
        }).catch(function (e) {
            $scope.projectTask.startDateTime = $startDate;
            $scope.projectTask.endDateTime = $endDateTime;
            var $message = e && e.data && e.data.message ? e.data.message : "Error guardando el registro. Por favor verifique los datos ingresados!";
            SweetAlert.swal("Error de guardado", $message, "error");
        }).finally(function () {

        });

    };

    var update = function (task) {
        var req = {};

        //$scope.projectTask.startDateTime = new Date($scope.projectTask.startDateTime);
        //$scope.projectTask.endDateTime = new Date($scope.projectTask.endDateTime);

        var data = JSON.stringify(task);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/project/task/update',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $scope.projectTask = response.data.result;
                $scope.reloadData();
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
                    $scope.isView = false;
                }, 400);
            });


    };


    var request = {};
    request.operation = "tracking";
    request.project_agent_id = project.projectAgentId

    $scope.dtInstanceTask = {};
    $scope.dtOptionsTask = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
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
        DTColumnBuilder.newColumn('duration').withTitle("Duración").withOption('width', 70),
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

app.controller('ModalInstanceTrackingCtrl', function ($scope, $uibModalInstance, task, action, $log, $timeout, SweetAlert, $http) {

    $scope.task = task;

    $scope.task.tracking = {
        action: action,
        description: ""
    }

    $scope.ok = function () {
        $uibModalInstance.close(1);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.saveTracking = function () {
        save();
    };

    var save = function () {
        var req = {};

        var data = JSON.stringify($scope.task);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/project/task/update',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $uibModalInstance.close(1);
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };
});

app.controller('ModalInstanceProjectTrackingCtrl', function ($scope, $uibModalInstance, project, action, filters, $log, $timeout, SweetAlert, $http) {

    $scope.report = project;

    $scope.report.tracking = {
        action: action,
        description: ""
    }

    $scope.onClose = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.saveTracking = function () {
        save();
    };

    var save = function () {
        var req = {};

        $scope.report.customerId = filters.selectedCustomer != null ? filters.selectedCustomer.value : 0;
        $scope.report.agentId = filters.selectedAgent != null && filters.selectedAgent != '' ? filters.selectedAgent.id : 0;
        $scope.report.month = filters.selectedMonth != null ? filters.selectedMonth.value : 0;
        $scope.report.year = filters.selectedYear != null ? filters.selectedYear.value : 0;
        $scope.report.arl = filters.selectedARL != null ? filters.selectedARL.value : 0;
        $scope.report.type = filters.selectedType != null ? filters.selectedType.value : 0;
        $scope.report.os = filters.selectedOS != "" ? filters.selectedOS : "";

        var data = JSON.stringify($scope.report);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/project/send-status',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $uibModalInstance.close(1);
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };
});

app.controller('ModalProjectTaskViewInstanceSideCtrl', function ($scope, $uibModalInstance, project, $log, $uibModal, $timeout, SweetAlert, isView, $http, DTOptionsBuilder, DTColumnBuilder, $compile) {

    $scope.project = project;

    $scope.ok = function () {
        $uibModalInstance.close(1);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var request = {};
    request.operation = "tracking";
    request.project_id = $scope.project.id;

    $scope.dtInstanceTask = {};
    $scope.dtOptionsTask = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/project/tasks/all',
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


    $scope.dtColumnsTask = [
        DTColumnBuilder.newColumn('task').withTitle("Tarea"),
        DTColumnBuilder.newColumn('shortObservation').withTitle("Descriptión"),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200),
        DTColumnBuilder.newColumn('startDateTime').withTitle("Fecha Inicio").withOption('width', 150),
        DTColumnBuilder.newColumn('endDateTime').withTitle("Fecha Fin").withOption('width', 150),
        DTColumnBuilder.newColumn('duration').withTitle("Duración").withOption('width', 150),
        DTColumnBuilder.newColumn('agent').withTitle("Asesor").withOption('width', 400),
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

app.controller('ModalInstanceSideProjectSearchCustomerCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'CLIENTES DISPONIBLES';

    var isAgent = $rootScope.isAgent();

    var url = isAgent ? 'api/customer-agent' : 'api/customer';

    $scope.entity = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        var customer = {
            id: $scope.entity.id,
            item: $scope.entity.businessName,
            value: $scope.entity.id,
            arl: $scope.entity.arl ? $scope.entity.arl.item : null,
        }
        $uibModalInstance.close(customer);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var onLoadRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id,
            };
            $http({
                method: 'GET',
                url: 'api/customer',
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
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.entity = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.onCloseModal();
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }

    $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.operation = "customer";
                return JSON.stringify(d);
            },
            url: url,
            contentType: 'application/json',
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
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {
            //log.info("fnDrawCallback");
            loadRow();
            //Pace.stop();

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

    $scope.dtColumnsCommonDataTableList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar causa"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('documentType').withTitle("Tipo de Documento").withOption('width', 200),
        DTColumnBuilder.newColumn('documentNumber').withTitle("Nro Documento").withOption('width', 200),
        DTColumnBuilder.newColumn('businessName').withTitle("Razón Social").withOption('width', 200),
        DTColumnBuilder.newColumn('type').withTitle("Tipo de Cliente").withOption('width', 200),
        DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {

                if (data == null || data == undefined)
                    return "";

                return data;
            }),
        DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                switch (data) {
                    case "Activo":
                        label = 'label label-success';
                        break;

                    case "Inactivo":
                        label = 'label label-danger';
                        break;

                    case "Retirado":
                        label = 'label label-warning';
                        break;
                }

                var status = '<span class="' + label + '">' + data + '</span>';


                return status;
            }),
    ];

    var loadRow = function () {
        angular.element("#dtCommonDataTableList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            onLoadRecord(id);
        });
    };

    $scope.dtInstanceCommonDataTableListCallback = function (instance) {
        $scope.dtInstanceCommonDataTableList = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceCommonDataTableList.reloadData();
    };

});

app.controller('ModalInstanceHistorialCtrl', function ($scope, $uibModalInstance, type, $log, $timeout, SweetAlert, $http) {

    $scope.title = type == "C" ? "Cancelar" : "Reprogramar";
    $scope.isDateVisible  = type == "R";
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy",
        min: new Date()
    };

    $scope.entity = {
        reason: null,
        deliveryDate: null,
    };

    $scope.ok = function () {
        $uibModalInstance.close(1);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.saveTracking = function () {

        if ($scope.entity.reason == null || $scope.entity.reason.trim() == '') {
            SweetAlert.swal("Error", "El motivo es requerido", "error");
            return;
        }

        if (type == "R" && $scope.entity.deliveryDate == null) {
            SweetAlert.swal("Error", "La feha de entrega es requerida", "error");
            return;
        }

        $uibModalInstance.close($scope.entity);
    };
});