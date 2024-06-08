'use strict';
/**
 * controller for Customers
 */
app.controller('customerManagementndicatorsCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document', '$filter',
    'ListService', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter, ListService, ChartService) {


        $scope.isLoaded = false;

        $scope.filter = {
            canCompare: false,
            year: null,            
            program: null,
            workplace: null,
            compareWorkplaceList: []
        };
        
        $scope.compareWorkplaceList = [];

        $scope.chart = {
            line: { options: null },
            bar: { options: null },
            average: { data: null },
            valoration: { data: null },
            improvementPlan: { data: null },
            absenteeismMedicalCause: { data: null },
            occupationalDiseaseFatalityRate: { data: null },
            occupationalDiseasePrevalence: { data: null },
            occupationalDiseaseIncidence: { data: null }
        };

        getList();

        function getList() {
            var entities = [
                {name: 'current_year'},     
                { 
                    name: 'customer_management_indicator_filters', 
                    criteria: { 
                        customerId: $stateParams.customerId 
                    }
                },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.years = response.data.data.customer_management_indicator_years;
                    $scope.programList = response.data.data.customer_management_indicator_program_list;
                    $scope.workplaceOriginalList = response.data.data.customer_management_indicator_workplace_id;
                    var $currentYear = response.data.data.currentYear;

                    if ($scope.years.length == 1) {
                        $scope.filter.year = $scope.years[0];
                    } else {
                        var $result = $filter('filter')($scope.years, {value: $currentYear});
                        $scope.filter.year = $result.length ? $result[0] : null;
                    }

                    $scope.isLoaded = true;                    
                    //getCharts();                    
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }       
        
        function getCharts() {
            var $criteria = {
                customerId: $stateParams.customerId,
                year: $scope.filter.year ? $scope.filter.year.value : 0,
                programId: $scope.filter.program ? $scope.filter.program.id : 0,                
            };

            if ($scope.filter.workplace) {
                $criteria.workplaceList = $scope.filter.compareWorkplaceList.map(function(item) {
                    return (item && item.name ? item.name.id : 0);
                });
                $criteria.workplaceList.push($scope.filter.workplace.id);
            }

            var entities = [
                { name: 'chart_line_options', criteria: null },
                { name: 'chart_bar_options', criteria: null },
                { name: 'customer_management_indicator', criteria: $criteria }
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {
                   
                    $scope.chart.bar.options = angular.copy(response.data.data.chartLineOptions);
                   
                    $scope.chart.bar.options.legend.position = 'bottom';
                    $scope.chart.bar.options.maintainAspectRatio = false;
                    $scope.chart.bar.options.responsive = false;
                                       

                    $scope.chart.average.data = response.data.data.customerManagementAverageProgram;
                    $scope.chart.improvementPlan.data = response.data.data.customerManagemenImprovementPlanStatus;
                    $scope.chart.valoration.data = response.data.data.customerManagemenValoration;
                    
                }, function (error) {

                });
        }     
        
        var fillCompareWorkplaceist = function() {
            $scope.moduleCompareWokplaceList = $scope.workplaceList.filter(function($item) {
                return $item.id != $scope.filter.workplace.id;
            });
        }

        //-------------------------------------------------------FILTERS
        $scope.onAddCompare = function() {
            $scope.filter.compareWorkplaceList.push({});
        }

        $scope.onRemoveCompare = function(index) {
            $scope.filter.compareWorkplaceList.splice(index, 1);
            $scope.reloadData();
            $scope.reloadResponsibleData();
            getCharts();
        }

        $scope.onRemoveWorkplace = function() {
            $scope.filter.workplace = null;            
            $scope.filter.compareWorkplaceList = [];

            if ($scope.filter.year && $scope.filter.program) {
                $scope.reloadData();
                $scope.reloadResponsibleData();
                getCharts();
            }
        }

        $scope.onSelectYear = function() {
            if ($scope.filter.program) {
                $scope.reloadData();
                $scope.reloadResponsibleData();
                getCharts();
            }
        }

        $scope.onSelectProgram = function() {
            $scope.workplaceList = $scope.workplaceOriginalList.filter(function(item) {
                return item.programId == ($scope.filter.program ? $scope.filter.program.id : 0)
            });

            $scope.onRemoveWorkplace();            
        }

        $scope.onSelectWorkplace = function() {
            fillCompareWorkplaceist();
            $scope.compareWorkplaceList = [];
            if ($scope.filter.year && $scope.filter.program) {
                $scope.reloadData();
                $scope.reloadResponsibleData();
                getCharts();
            } 
        }

        $scope.onSelectCompareWorkplace = function($index) {
            // var $entity = $scope.filter.compareWorkplaceList[$index];

            // var workplaceInList = $entity ? $scope.filter.compareWorkplaceList.filter(function(item) {
            //     return item.id == $entity.id;
            // }) : [];

            // if (workplaceInList.length > 0) {
            //     $scope.filter.compareWorkplaceList[$index] = null;                
            // } else {
    
            // }

            $scope.reloadData();
            $scope.reloadResponsibleData();
            getCharts();
        }

        
		$scope.dtOptionsManagementIndicatorSummary = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {                    
                    d.customerId = $stateParams.customerId;        
                    d.programId = $scope.filter.program ? $scope.filter.program.id : 0;
                    d.year = $scope.filter.year ? $scope.filter.year.value : 0;

                    if ($scope.filter.workplace) {
                        d.workplaceId = $scope.filter.compareWorkplaceList.map(function(item) {
                            return (item && item.name ? item.name.id : 0);
                        });
                        d.workplaceId.push($scope.filter.workplace.id);
                    }

                    return JSON.stringify(d);
                },
                url: 'api/customer-management-summary-indicator',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {

                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                
            })
            .withDOM('tr')
            .withOption('paging', false)
 
            .withOption('createdRow', function (row, data, dataIndex) {
                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);
            });        

        $scope.dtColumnsManagementIndicatorSummary = [
            DTColumnBuilder.newColumn('name')
                .withTitle("Programa")
                .withOption('width', 400),
            DTColumnBuilder.newColumn('abbreviation')
                .withTitle("Código")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('workplace')
                .withTitle("Centro de Trabajo")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('questions')
                .withTitle("Preguntas")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('answers')
                .withTitle("Respuestas")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('advance')
                .withTitle("Avance (%)")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('average')
                .withTitle("Promedio Total (%)")
                .withOption('width', 200),
            DTColumnBuilder.newColumn(null)
                .withTitle("Estado")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Sin Iniciar';

                    if (parseInt(data.answers) == parseInt(data.questions))
                    {
                        text = 'Completado';
                        label = 'label label-info';
                    }
                    else if (parseInt(data.answers) > 0) {
                        text = 'Iniciado';
                        label = 'label label-success';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
                .notSortable()
        ];



        $scope.dtInstanceManagementIndicatorSummaryCallback = function (instance) {
            $scope.dtInstanceManagementIndicatorSummary = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceManagementIndicatorSummary.reloadData();
        };


        //----------------------------------------------------------------------------

        $scope.dtOptionsManagementResponsibleSummary = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {                    
                    d.customerId = $stateParams.customerId;     
                    //d.programId = $scope.filter.program ? $scope.filter.program.id : 0;
                    d.year = $scope.filter.year ? $scope.filter.year.value : 0;

                    if ($scope.filter.workplace) {
                        d.workplaceId = $scope.filter.compareWorkplaceList.map(function(item) {
                            return (item && item.name ? item.name.id : 0);
                        });
                        d.workplaceId.push($scope.filter.workplace.id);
                    }
                    
                    return JSON.stringify(d);
                },
                url: 'api/customer-management-summary-responsible',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {

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
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {                
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsManagementResponsibleSummary = [
            DTColumnBuilder.newColumn('workplace')
            .withTitle("Centro de Trabajo"),
            
            DTColumnBuilder.newColumn('responsible')
                .withTitle("Nombre del Responsable")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('qty')
                .withTitle("Cant. Planes Mejoramiento")
                .withOption('width', 200)      
        ];



        $scope.dtInstanceManagementResponsibleSummaryCallback = function (instance) {
            $scope.dtInstanceManagementResponsibleSummary = instance;
        };

        $scope.reloadResponsibleData = function () {
            $scope.dtInstanceManagementResponsibleSummary.reloadData();
        };




        //--------------------------------------------------------EXPORT PDF AND EXCEL

        $scope.onExportPdf = function (name) {    
            var $el = null;
            var $filename = 'Gráfica.pdf';

            switch (name) {
                case 'AV':
                    $el = '.export-pdf-average';
                    $filename = 'PROMEDIO_TOTAL_PROGRAMA_EMPRESARIAL.pdf';
                break;

                case 'VA':
                    $el = '.export-pdf-valoration';
                    $filename = 'VALORACION_PROGRAMA_EMPRESARIAL.pdf';
                break;     
                
                case 'IP':
                    $el = '.export-pdf-improvement-plan';
                    $filename = 'PLANES_MEJORAMIENTO.pdf';
                break;                               
            }
            
            kendo.drawing.drawDOM($($el))
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
                        fileName: $filename                        
                    });
                });
        }
    }
]);