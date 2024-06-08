'use strict';
/**
 * controller for User Profile Example
 */
app.controller('internalProjectCtrl', ['$scope', 'flowFactory', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$filter', '$uibModal',
    'ListService', 'ChartService',
    function ($scope, flowFactory, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $filter, $uibModal, ListService, ChartService) {

        $scope.currentProjectAgentId = 0;

        $scope.isCustomerAgent = $rootScope.isCustomerUser();
        $scope.isCustomerAdmin = $rootScope.isCustomerAdmin();
        $scope.isAdmin = $rootScope.isAdmin();
        $scope.isAgent = $rootScope.isAgent();

        $scope.customerList = [];

        $scope.currentCustomerId = $rootScope.isCustomer() ? $rootScope.currentUser().company : 0;

        $scope.filter = {
            selectedCustomer: null,
            selectedAgent: '',
            selectedMonth: null,
            selectedYear: null,
            selectedOS: null,
            selectedType: null
        };

        $scope.typeList = $rootScope.parameters("project_type");

        $scope.chart = {
            bar: { options: null },
            doughnut: { options: null },
            programs: { data: null },
            summary: { 
                data: null, 
                total: 0
            }
        };        

        getList();

        function getList() {
            var entities = [
                {name: 'customer_internal_project_year', value: null},
                {name: 'project_type', value: null},
                {name: 'month_options', value: null},
                {name: 'current_year', value: null},
                {name: 'current_month', value: null},
                {name: 'current_customer', value: $scope.currentCustomerId},
            ];
            
            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.years = response.data.data.yearList;
                    $scope.months = response.data.data.monthOptions;
                    $scope.currentCustomer = response.data.data.currentCustomer;
                    $scope.typeList = response.data.data.project_type;

                    if ($scope.years.length > 0) {
                        //var years = $filter('filter')($scope.years, { value: response.data.data.currentYear });
                        $scope.filter.selectedYear = $scope.years[0];
                    } else {
                        $scope.filter.selectedYear = {
                            id: response.data.data.currentYear,
                            item: response.data.data.currentYear,
                            value: response.data.data.currentYear
                        }
                    }

                    var months = $filter('filter')($scope.months, { value: response.data.data.currentMonth });

                    if (months.length > 0) {
                        $scope.filter.selectedMonth = months[0];
                    }                    
                //       loadProjects();
                //     loadSummaryProject();

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getCharts();

        function getCharts() {
            var entities = [                            
                {name: 'chart_doughnut_options', criteria: null},                                
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

        $scope.onAddProject = function () {
            onOpenProjectModal( {id: 0} );
        };

        $scope.onEditProject = function (project) {
            onOpenProjectModal(project, $scope.isView);
        };

        $scope.onViewProject = function (project) {
            onOpenProjectModal(project, true);
        };

        function onOpenProjectModal(project, isView) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/internalproject/project_edit_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInternalProjectInstanceSideCtrl',
                scope: $scope,
                resolve: {
                    customer: function () {
                        return $scope.currentCustomer ? $scope.currentCustomer : $scope.filter.selectedCustomer;
                    },                    
                    project: function () {
                        return project;
                    },
                    isView: function () {
                        return isView;
                    }
                }
            });
            modalInstance.result.then(function (selectedEvent, action) {

                $scope.currentProjectAgentId = 0;
                loadSummaryProject();
                loadProjects();
                loadRecentTask();

            }, function() {

            });
        }

        $scope.onViewProjectTask = function (project) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/internalproject/project_task_view_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInternalProjectTaskViewInstanceSideCtrl',
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
                loadSummaryProject();
                loadProjects();
                loadRecentTask();

            }, function() {

            });
        }

        $scope.onAddProjectTask = function(project) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/internalproject/project_task_edit_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideInternalProjectTaskCtrl',
                scope: $scope,
                resolve: {
                    customer: function () {
                        return $scope.currentCustomer ? $scope.currentCustomer : $scope.filter.selectedCustomer;
                    },
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
                loadSummaryProject();
                loadProjects();
                loadRecentTask();

            }, function() {

            });
        }

        var loadProjects = function () {

            $scope.customer = {};

            var customerId = $scope.currentCustomerId;

            if ($scope.isAdmin || $scope.isAgent) {
                customerId = $scope.currentCustomerId != 0 ? $scope.currentCustomerId : -1;
            }

            var req = {                
                data: Base64.encode(JSON.stringify($scope.customer)),
                customer_id: customerId,
                agent_id: $scope.filter.selectedAgent != null ? $scope.filter.selectedAgent.id : 0,
                month: $scope.filter.selectedMonth != null ? $scope.filter.selectedMonth.value : 0,
                year: $scope.filter.selectedYear != null ? $scope.filter.selectedYear.value : 0,
                os: $scope.filter.selectedOS != "" ? $scope.filter.selectedOS : "",
                type: $scope.filter.selectedType != null ? $scope.filter.selectedType.id : '',
            };

            return $http({
                method: 'POST',
                url: 'api/internal-project/summary',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.projects = response.data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de Aplicación", "Error cargando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        var loadSummaryProject = function () {

            var req = {};
            $scope.customer = {};
            var data = JSON.stringify($scope.customer);
            req.data = Base64.encode(data);

            if ($scope.isAdmin || $scope.isAgent) {
                req.customer_id = $scope.currentCustomerId != 0 ? $scope.currentCustomerId : -1;
            } else {
                req.customer_id = $scope.currentCustomerId;
            }
            req.agent_id = $scope.filter.selectedAgent != null ? $scope.filter.selectedAgent.id : 0;
            req.month = $scope.filter.selectedMonth != null ? $scope.filter.selectedMonth.value : 0;
            req.year = $scope.filter.selectedYear != null ? $scope.filter.selectedYear.value : 0;
            req.os = $scope.filter.selectedOS != "" ? $scope.filter.selectedOS : "";
            req.type = $scope.filter.selectedType != null ? $scope.filter.selectedType.id : '';

            return $http({
                method: 'POST',
                url: 'api/internal-project/setting',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {


                $timeout(function () {

                    if (response.data.data.length > 0) {
                        $scope.summaryProject = response.data.data[0];
                    } else {
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
                    }

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        $scope.onSelectAgent = function (item, model) {
            $timeout(function () {                
                loadProjects();
                loadSummaryProject();
            });
        };

        $scope.onClearAgent = function () {
            $timeout(function () {                
                $scope.filter.selectedAgent = null;
                loadProjects();
                loadSummaryProject();
            });
        }

        $scope.onSelectMonth = function (item, model) {
            $timeout(function () {                
                loadProjects();
                loadSummaryProject();
            });
        };

        $scope.onClearMonth = function () {
            $timeout(function () {                
                $scope.filter.selectedMonth = null;
                loadProjects();
                loadSummaryProject();
            });
        }

        $scope.onSelectYear = function () {
            $timeout(function () {                
                loadProjects();
                loadSummaryProject();
            });
        };

        $scope.onClearYear = function () {
            $timeout(function () {                
                $scope.filter.selectedYear = null;
                loadProjects();
                loadSummaryProject();
            });
        }

        $scope.onSearchOS = function () {
            $timeout(function () {                
                loadProjects();
                loadSummaryProject();
            });
        };

        $scope.onClearOS = function () {
            $timeout(function () {                
                $scope.filter.selectedOS = '';
                loadProjects();
                loadSummaryProject();
            });
        }

        $scope.onSelectType = function (item, model) {
            $timeout(function () {                
                loadProjects();
                loadSummaryProject();
            });
        };

        $scope.onClearType = function () {
            $timeout(function () {                
                $scope.filter.selectedType = null;
                loadProjects();
                loadSummaryProject();
            });
        }

        $scope.onSendStatus = function () {

            var project = {
                id: 0,
                status: "sendStatus",
                tracking: {
                    action: "",
                    description: ""
                }
            }

            var modalInstance = $uibModal.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/internalproject/project_status_modal.htm",
                controller: 'ModalInstanceInternalProjectTrackingCtrl',
                windowTopClass: 'top-modal',
                resolve: {
                    project: function () {
                        return project;
                    },
                    action: function () {
                        return "Enviar Estado Proyectos";
                    },
                    filters: function () {
                        return $scope.filter;
                    },
                    customerId: function () {
                        return $scope.currentCustomerId;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {

            }, function () {

            });
        };

        $scope.onAddAttachment = function(project) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/common/modals/customer_document_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInternalProjectAttachmentInstanceSideCtrl',
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
            modalInstance.result.then(function () {
                //loadData();
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());                   
            });            
        }

        $scope.onAddComment = function(project) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_sgsst_comment.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_questions_comment_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInternalProjecCommentInstanceSideCtrl',
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

        //----------------------------------------------------------------------------EXPORT
        $scope.onExportPdf = function () {            
            kendo.drawing.drawDOM($(".internal-project-export-pdf"))
                .then(function (group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: {left: "1cm", top: "1cm", right: "1cm", bottom: "1cm"}
                    });
                })
                .done(function (data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "PLANES_DE_TRABAJO_INTERNOS.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }        

        var loadRecentTask = function () {

            var req = {};
            $scope.customer = {};
            var data = JSON.stringify($scope.customer);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/internal-project/report',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

        var loadList = function () {
            var req = {};
            req.customerId = $scope.currentCustomerId;
            return $http({
                method: 'POST',
                url: 'api/internal-project/listCustomer',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.agentsFilter = response.data.data.users;
                    $scope.typeList = response.data.data.projectTypes;     
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        }

        var loadTask = function () {
            var req = {};
            var data = JSON.stringify($scope.customer);
            req.data = data;
            return $http({
                method: 'POST',
                url: 'api/internal-project/task',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {


                $timeout(function () {

                    $scope.events = response.data.data;
                    $scope.events.forEach(function (entry) {
                        entry.starts_at = new Date(entry.starts_at.date);
                        entry.ends_at = new Date(entry.ends_at.date);
                    });
                    //$scope.tracking.event_date =  new Date($scope.tracking.eventDateTimeTsp.date);
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }        

        loadSummaryProject();
        loadProjects();
        loadRecentTask();
        loadList();       
        loadTask(); 

        $scope.refresAll = function () {
            loadSummaryProject();
            loadProjects();
            loadRecentTask();
        };

        $scope.onSelectCustomer = function() {
            $scope.currentCustomerId = $scope.filter.selectedCustomer.value;
            loadList();
            loadProjects();
            loadSummaryProject();            
        }

        $scope.onClearCustomer = function() {
            $scope.filter.selectedCustomer = null;
            $scope.currentCustomerId = 0;
            loadList();
            loadProjects();
            loadSummaryProject();
        }

        $scope.onSearchCustomer = function () {            
            var modalInstance = $aside.open({                
                templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideProjectInternalSearchCustomerCtrl',
                scope: $scope,
                windowTopClass: 'top-modal',
                resolve: {                    
                }
            });
            modalInstance.result.then(function (customer) {
                var result = $filter('filter')($scope.customerList, {id: customer.id});

                if (result.length == 0) {
                    $scope.customerList.push(customer);
                }

                $scope.filter.selectedCustomer = customer;
                $scope.onSelectCustomer();
            }, function() {
                
            });
        };

    }
]);

