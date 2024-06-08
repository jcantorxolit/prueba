'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticExpressMatrixDashboardSummaryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 
    '$document', '$location', '$translate', '$aside', 'ListService', 'ExpressDashboardService', 'ChartService', 'ExpressMatrixService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $location, $translate, $aside, ListService, 
              ExpressDashboardService, ChartService, ExpressMatrixService) {

        $scope.isView = $scope.customer.matrixType != 'E'; 

        $scope.isWorkplaceDisabled = false;   

        $scope.calendarWidget = null;
        $scope.calendarDate = null;

        $scope.filter = {
            selectedWorkPlace: null,
            selectedHazard: null,
            selectedYear: null,
        }    

        $scope.chart = {            
            doughnut: { options: null },
            intervention: { data: null},
        }; 

        $scope.calendarOptions = {            
            weekNumber: true,
            month: {          
                content: '# if ($.inArray(+data.date, data.dates) != -1) { #' +
                '<div class="intervention-date">#= data.value #</div>' +
                '# } else { #' +
                '#= data.value #' +
                '# } #',
                weekNumber: '<a class="italic">#= data.weekNumber #</a>'
            },
            footer: false
        }

        $scope.$on("kendoWidgetCreated", function(event, widget) {
            if ($scope.calendarWidget == null) {
                $scope.calendarWidget = widget;            
            }
        });


        getList();

        function getList() {
            var entities = [
                {
                    name: 'customer_express_matrix_workplace_with_qa', 
                    criteria: { 
                        customerId:  $stateParams.customerId 
                    }
                }
            ];

            ListService.getDataList(entities)
                .then(function (response) {                
                    $scope.workplaceList = response.data.data.customerExpressMatrixWorkplaceList;                    
                    $scope.workplaceList.push({
                        id: 0,
                        name: 'Matriz General'
                    });

                    if ($scope.filter.selectedWorkPlace == null) {
                        $scope.filter.selectedWorkPlace = $scope.workplaceList[$scope.workplaceList.length - 1];
                    }

                    getInterventionList();
                    getCharts();

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }                 

        function getInterventionList() {

            var $criteria = {                
                customerId: $stateParams.customerId,                
                isHistorical: false,
                isClosed: false,
            };

            if ($scope.filter.selectedWorkPlace && $scope.filter.selectedWorkPlace.id != 0) {
                $criteria.workplaceId = $scope.filter.selectedWorkPlace.id;
            }         
            
            if ($scope.filter.selectedYear != null) {
                $criteria.year = $scope.filter.selectedYear.value;
            }

            var entities = [             
                {  name: 'customer_express_matrix_question_intervention_list',  criteria: $criteria }, 
                {  name: 'customer_express_matrix_hazard_general_stats',  criteria: $criteria }, 
            ];

            ListService.getDataList(entities)
                .then(function (response) {                
                    $scope.interventionList = response.data.data.customerExpressMatrixQuestionInterventionList;                                                     
                    $scope.interventionListFilter = response.data.data.customerExpressMatrixQuestionInterventionList;                                                     
                    $scope.generalStats = response.data.data.customerExpressMatrixHazardGeneralStats;                                                     
                    $scope.yearList = response.data.data.customerExpressMatrixQuestionInterventionYearList;                                                     

                    if ($scope.interventionList !== undefined && $scope.interventionList !== null) {
                        angular.forEach($scope.interventionList, function (model, key) {
                            if (model.executionDate != null) {
                                model.executionDate = new Date(model.executionDate.date);
                            }        
                        });
                    }

                    if ($scope.filter.selectedYear == null) {
                        $scope.filter.selectedYear = $scope.yearList.length > 0 ? $scope.yearList[0] : null;
                    }

                    onInitializeCalendar();

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }               

        function getCharts() {
            var $criteria = {                
                customerId: $stateParams.customerId,                
                isHistorical: false
            };

            if ($scope.filter.selectedWorkPlace && $scope.filter.selectedWorkPlace.id != 0) {
                $criteria.workplaceId = $scope.filter.selectedWorkPlace.id;
            }   

            if ($scope.filter.selectedYear != null) {
                $criteria.year = $scope.filter.selectedYear.value;
            } 

            var entities = [                           
                {name: 'chart_doughnut_options', criteria: null}, 
                { name: 'customer_express_matrix_hazard_intervention_stats', criteria: $criteria }
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {                    
                    $scope.chart.doughnut.options = angular.copy(response.data.data.chartDoughnutOptions);                     
                    $scope.chart.doughnut.options.legend.position = 'bottom';
                    $scope.chart.doughnut.options.maintainAspectRatio = false;
                    $scope.chart.doughnut.options.responsive = false;
                    $scope.chart.doughnut.options.cutoutPercentage = 70; 

                    $scope.chart.intervention.data = response.data.data.customerExpressMatrixHazardInterventionStats;
                    
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var onInitializeCalendar = function() { 
            if ($scope.interventionList !== undefined && $scope.interventionList !== null) {         
                $scope.calendarOptions.dates = $scope.interventionList.filter(function(item) {
                    return item.executionDate != null;
                }).map(function(item) {
                    return +item.executionDate;
                });

                $scope.calendarWidget.setOptions($scope.calendarOptions);
            }
        }  
        
        $scope.onChangeCalendar = function() {
            if ($scope.calendarDate != null) {
                $scope.interventionListFilter = $scope.interventionList.filter(function(item) {
                    return item.executionDate.getTime() == $scope.calendarDate.getTime();
                });
            }
        }

        $scope.onViewDetail = function() {
            if ($scope.$parent != null) {
                ExpressDashboardService.setIsBack(true);
                $scope.$parent.navToSection("hazard", "list", 0);
            }
        }

        $scope.onCreate = function() {        
            ExpressMatrixService.setIsBackInNavigation(true);
            ExpressMatrixService.setShouldCreateNewWorkplace(true);
            ExpressMatrixService.setWorkplaceId(null);
            $rootScope.$emit('wizardGoTo', { newValue: 0 });       
        }

        $scope.onSelectWorkPlace = function() {
            $scope.reloadData();
            $scope.calendarDate = null;
            getInterventionList();
            getCharts();
        }

        $scope.onSelectYear = function() {           
            getInterventionList();
            getCharts();
        }

        $scope.onSelectIntervention = function(intervention)
        {
            var modalInstance = $aside.open({                
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/diagnostic/tab-express-matrix/dashboard/intervention/customer_diagnostic_express_matrix_dashboard_intervention_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: 'static',
                controller: 'ModalInstanceSideCustomerDiagnosticExpressMatrixInterventionDetailCtrl',
                scope: $scope,
                resolve: {
                    entity: function () {
                        return intervention;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();                
                getInterventionList();
                getCharts();
            }, function () {
                getInterventionList();
                getCharts();              
            });
        }


        $scope.dtOptionsCustomerConfigQuestionExpressInterventionResponsible = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    
                    d.customerId = $stateParams.customerId;
                    if ($scope.filter.selectedWorkPlace && $scope.filter.selectedWorkPlace.id != 0) {
                        d.workplaceId = $scope.filter.selectedWorkPlace.id
                    }
                    return JSON.stringify(d);
                },
                url: 'api/customer-config-question-express-intervention-responsible',
                contentType: "application/json",
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

        $scope.dtColumnsCustomerConfigQuestionExpressInterventionResponsible = [
            DTColumnBuilder.newColumn('responsibleName').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('responsibleEmail').withTitle("E-mail").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('qty').withTitle("Tareas Asignadas").withOption('width', 200).withOption('defaultContent', ''),
        ];        

        $scope.dtInstanceCustomerConfigQuestionExpressInterventionResponsibleCallback = function (instance) {
            $scope.dtInstanceCustomerConfigQuestionExpressInterventionResponsible = instance;
        };
		
        $scope.reloadData = function () {
			if ($scope.dtInstanceCustomerConfigQuestionExpressInterventionResponsible != null) {				
				$scope.dtInstanceCustomerConfigQuestionExpressInterventionResponsible.reloadData();
			}
        };



        $scope.onExportPdf = function () {            
            kendo.drawing.drawDOM($(".express-matrix-dashboard-export-pdf"))
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
                        fileName: "TABLERO_GENERAL_PELIGROS.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onExportExcel = function () {
            var data = {
                customerId: $stateParams.customerId,
                isHistorical:  0
            };

            if ($scope.filter.selectedWorkPlace && $scope.filter.selectedWorkPlace.id != 0) {
                data.workplaceId = $scope.filter.selectedWorkPlace.id;
            }
 
            angular.element("#downloadGeneralDashboardExcel")[0].src = "api/customer-config-question-express-intervention/export-excel-general?data=" + Base64.encode(JSON.stringify(data));
        }

    }
]);
