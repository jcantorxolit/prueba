'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticRiskMatrixCharacterizationCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 
    '$translate', 'ListService', '$aside', 'moment', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $translate, ListService, $aside, moment, ChartService) {


        var log = $log;

        var pager = {
            refresh: true,
            index: 0
        };
        
        $scope.filter = {
            selectedWorkPlace: null,            
            selectedProcess: null,            
            selectedClassification: null,            
        }

        getList();

        function getList() {
            var entities = [
                {
                    name: 'customer_config_acitivty_hazard_workplace_list', 
                    criteria: { 
                        customerId:  $stateParams.customerId 
                    }
                },                   
            ];

            ListService.getDataList(entities)
                .then(function (response) {                
                    $scope.workplaceList = response.data.data.customerConfigAcitivtyHazardWorkplaceList;
                    $scope.statusList = response.data.data.config_workplace_status;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        loadOnDemandProcess();

        function loadOnDemandProcess() {            
            var entities = [
                {
                    name: 'customer_config_acitivty_hazard_process_list', 
                    criteria: { 
                        customerId:  $stateParams.customerId,
                        workplace: $scope.filter.selectedWorkPlace ? $scope.filter.selectedWorkPlace : null
                    }
                },
                {
                    name: 'customer_config_acitivty_hazard_classification_list', 
                    criteria: { 
                        customerId:  $stateParams.customerId,
                        workplace: $scope.filter.selectedWorkPlace ? $scope.filter.selectedWorkPlace : null,
                        process: $scope.filter.selectedProcess ? $scope.filter.selectedProcess : null,
                    }
                },
            ];

            ListService.getDataList(entities)
                .then(function (response) {                
                    $scope.processList = response.data.data.customerConfigAcitivtyHazardProcessList;
                    $scope.classificationList = response.data.data.customerConfigAcitivtyHazardClassificationList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.chart = {
            bar: { options: null },
            pie: { options: null },
            classification: { data: null},
            acceptability: { 
                total: { data: null },
                classification: { data: null },
                type: { data: null }
            },
            intervention: { data: null },
            improvementPlan: { data: null },
        };       

        getCharts();

        function getCharts() {
            var $criteria = {
                customerId: $stateParams.customerId,                
                workplace: $scope.filter.selectedWorkPlace ? $scope.filter.selectedWorkPlace : null,
                process: $scope.filter.selectedProcess ? $scope.filter.selectedProcess : null,
                classification: $scope.filter.selectedClassification ? $scope.filter.selectedClassification : null
            };

            var entities = [           
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_pie_options', criteria: null}, 
                { name: 'customer_config_activity_hazard_characterization', criteria: $criteria }
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.pie.options = angular.copy(response.data.data.chartPieOptions); 
                    $scope.chart.pie.options.tooltips = {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                log.info(data);
                                log.info(tooltipItem);

                                var label = data.labels[tooltipItem.index] || '';
            
                                if (label) {
                                    //label += ': ';
                                }

                                //label += data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index] + ' %';
                                
                                return label;
                            }
                        }
                    };

                    $scope.chart.bar.options.legend.position = 'bottom';
                    $scope.chart.pie.options.legend.position = 'bottom';
                    $scope.chart.bar.options.maintainAspectRatio = false;
                    $scope.chart.bar.options.responsive = false;
                    $scope.chart.pie.options.maintainAspectRatio = false;
                    $scope.chart.pie.options.responsive = false;

                    $scope.chart.classification.data = response.data.data.matrixCharacterizationClassification;
                    $scope.chart.acceptability.total.data = response.data.data.matrixCharacterizationAcceptability;
                    $scope.chart.acceptability.classification.data = response.data.data.matrixCharacterizationAcceptabilityClassification;
                    $scope.chart.acceptability.type.data = response.data.data.matrixCharacterizationAcceptabilityType;
                    $scope.chart.intervention.data = response.data.data.matrixCharacterizationIntervention;
                    $scope.chart.improvementPlan.data = response.data.data.matrixCharacterizationImprovement;                                 
                    
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $timeout(function () {    

            var kendoGridColumns = function()
            {
                var $columns = [];

                $columns.push(buildKendoGridColumn('classification', 'Clasificación', null));
                $columns.push(buildKendoGridColumn('total', 'Total', '100px', null));
                $columns.push(buildKendoGridColumn('acceptable', 'Aceptable', '200px', "success-header"));
                $columns.push(buildKendoGridColumn('improvable', 'Mejorable', '200px', "info-header"));
                $columns.push(buildKendoGridColumn('noAcceptableControl', 'No Aceptable o Aceptable con control especifico', '200px', "warning-header"));
                $columns.push(buildKendoGridColumn('noAcceptable', 'No Aceptable', '200px', "danger-header"));
                
                return $columns;
            };

            var buildKendoGridColumn = function(field, title, width, headerClass, filterable, templateCallback)
            {
                return {
                    field: field,
                    title: title,
                    width: width,
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
                            url: "api/customer-config-activity-hazard-characterization",
                            dataType: "json",
                            type: "POST",
                            data: function() {

                                var param = {
                                    customerId: $stateParams.customerId             
                                };

                                if ($scope.filter.selectedWorkPlace !== null) {
                                    param.workplaceId = $scope.filter.selectedWorkPlace.id
                                }

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
                        log.info('page.index', pager.index);
                    }
                },
                filterable: false,
                dataBinding: function(e) {
                    $log.info("dataBinding");
                },
                dataBound: function (e) {
                    $log.info("dataBound");

                    //this.expandRow(this.tbody.find("tr.k-master-row"));

                    $scope.grid.tbody.find("tr").each(function () {

                        var model = $scope.grid.dataItem(this);

                        if (model !== undefined && (model.id == null || model.id == "")) {
                            $(this).find(".btn-success").remove();
                        } else if (model !== undefined && (model.id != null && model.id != "")) {
                            $(this).find(".btn-dark-azure").remove();
                        }
                    });                    
                },
                columns: kendoGridColumns()
            };

            $scope.detailGridOptions = function(dataItem) {
                return {
                    dataSource: {
                        type: "odata",
                        transport: {
                            read: {
                                url: "api/customer-config-activity-hazard-characterization-detail",
                                dataType: "json",
                                type: "POST",
                                data: function() {

                                    var param = {
                                        customerId: $stateParams.customerId,
                                        classificationId: dataItem.id,                                        
                                    };

                                    if ($scope.filter.selectedWorkPlace !== null) {
                                        param.workplaceId = $scope.filter.selectedWorkPlace.id
                                    }

                                    return param;
                                }
                            },
                            parameterMap: function (data, operation) {
                                return JSON.stringify(data);
                            }
                        },
                        requestEnd: function(e) {

                        },                        
                        schema: {
                            model:{
                                id: "id",
                                fields: {}
                            },
                            data: function(result) {
                                return result.data || result;
                            },
                            total: function(result) {
                                return result.recordsTotal || result.data.length || 0;
                            }
                        },
                        serverPaging: false,
                        serverSorting: true,
                        serverFiltering: false,                        
                        filter: { field: "classificationId", operator: "eq", value: dataItem.id }
                    },
                    noRecords: true,                    
                    scrollable: false,
                    sortable: true,
                    pageable: false,  
                    dataBound: function (e) {
                        $log.info("dataBound child");                       
                        this.wrapper.css({ 
                            width: this.wrapper.closest("div.k-parent")[0].offsetWidth - 90 + 'px' 
                        })                        
                    },                                      
                    columns: [
                        {
                            field: "type",
                            title: "Tipo de Peligro",                            
                            headerAttributes: {
                                style: "display: none"
                            }
                        },                                              
                        buildKendoGridColumn('total', '', '100px'),
                        buildKendoGridColumn('acceptable', '', '200px'),
                        buildKendoGridColumn('improvable', '', '200px'),
                        buildKendoGridColumn('noAcceptableControl', '', '200px'),
                        buildKendoGridColumn('noAcceptable', '', '200px'),
                    ]
                };
            };

        });

        $scope.$on("kendoWidgetCreated", function(event, widget) {
            if ($scope.grid === undefined || $scope.grid === null) {
                $scope.grid = widget;
            }
        });

        $scope.onSelectWorkPlace = function()
        {    
            $scope.filter.selectedProcess = null;        
            $scope.filter.selectedClassification = null;        
            loadOnDemand();            
        }

        $scope.onClearWorkPlace = function()
        {
            $scope.filter.selectedWorkPlace = null;
            loadOnDemand();
        }

        $scope.onSelectProcess = function()
        {            
            loadOnDemand();            
        }

        $scope.onClearProcess = function()
        {
            $scope.filter.selectedProcess = null;
            loadOnDemand();
        }

        $scope.onSelectClassification = function()
        {            
            loadOnDemand();            
        }

        $scope.onClearClassification = function()
        {
            $scope.filter.selectedClassification = null;
            loadOnDemand();
        }

        var loadOnDemand = function() {
            loadOnDemandProcess();
            getCharts();
            $scope.grid.dataSource.read();
        }

        $scope.onView = function (dataItem) {
            
            var modalInstance = $aside.open({                
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/diagnostic/tab-matrix/customer_diagnostic_risk_matrix_historical_modal.htm",
                placement: 'right',
                size: 'lg',                
                backdrop: true,
                controller: 'ModalInstanceSideMatrixJobActivityHazardHistoricalCtrl',
                scope: $scope,
                resolve: {
                    entity: function () {
                        return dataItem;
                    }
                }
            });
            modalInstance.result.then(function () {

            }, function() {

            });
        };

        $scope.onExportExcel = function () {
            jQuery("#downloadDocument")[0].src = "api/customer-config-activity-hazard-characterization/export?id=" + $stateParams.customerId;
        }

        $scope.onExportPdf = function()
        {            
            kendo.drawing.drawDOM($(".characterization-export-pdf"))
                .then(function(group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: { left: "1cm", top: "1cm", right: "1cm", bottom: "1cm" }
                    });
                })
                .done(function(data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Caracterización.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }
    }
]);

app.controller('ModalInstanceSideMatrixJobActivityHazardHistoricalCtrl', function ($rootScope, $scope, $location, $uibModalInstance, entity, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document, $stateParams) {

    $log.info('Historical Open');

    $scope.entity = entity;

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

});