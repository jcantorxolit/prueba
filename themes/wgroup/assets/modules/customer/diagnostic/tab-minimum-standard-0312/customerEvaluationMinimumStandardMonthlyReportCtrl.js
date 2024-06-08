'use strict';
/**
 * controller for Customers
 */
app.controller('customerEvaluationMinimumStandardMonthlyReport0312Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ChartService', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ChartService, ListService) {


        var log = $log;
        
        $scope.currentId =  $scope.$parent.currentId;
        $scope.canShowDatatable =  false;
        
        $scope.chart = {
            bar: { options: null },
            line: { options: null },
            status: { data: null },
            average: { data: null },
            total: { data: null },
            advance: { data: null },
        };       

        function getCharts() {
            var $criteria = {
                customerId: $stateParams.customerId,
                customerEvaluationMinimumStandardId: $scope.currentId,
                year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0
            };

            var entities = [           
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_line_options', criteria: null}, 
                { name: 'customer_evaluation_minimum_standard_monthly_0312', criteria: $criteria }
            ];
    
            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.line.options = response.data.data.chartLineOptions; 

                    $scope.chart.bar.options.legend.position = 'bottom';
                    $scope.chart.line.options.legend.position = 'bottom';

                    $scope.chart.status.data = response.data.data.customerEvaluationMinimumStandardMonthlyStatus;
                    $scope.chart.average.data = response.data.data.customerEvaluationMinimumStandardMonthlyAverage;
                    $scope.chart.total.data = response.data.data.customerEvaluationMinimumStandardMonthlyTotal;
                    $scope.chart.advance.data = response.data.data.customerEvaluationMinimumStandardMonthlyAdvance;                   
                    
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();

        function getList() {

            var $criteria = {
                customerId: $stateParams.customerId,
                customerEvaluationMinimumStandardId: $scope.currentId
            };

            var entities = [
                {name: 'customer_evaluation_minimum_stardard_year_0312', value: null, criteria: $criteria},
                {name: 'minimum_stardard_rate_0312', value: $stateParams.customerId},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.years = response.data.data.customerEvaluationMinimumStandardYear;
                    
                    if ($scope.years.length > 0) {
                        $scope.filter.selectedYear = $scope.years[0];
                        getCharts();
                    }

                    $scope.canShowDatatable = true;
                
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.audit = {};
        $scope.audit.operation = "info";
        $scope.audit.year = 0;
        $scope.audit.standardId = $scope.currentId;

        $scope.filter = {
            selectedYear: null
        };

        var buildKendoGridColumn = function(field, title, width, withHeader, templateCallback)
        {
            var $column = {
                field: field,
                title: title,
                width: width,
                attributes: {
                    style: "text-align: center",
                },
                headerAttributes: {
                    class: "text-bold",
                },                
                template: (typeof templateCallback == 'function') ? templateCallback : null
            };

            if (withHeader !== undefined && !withHeader) {
                $column.headerAttributes = {
                    style: "display: none"
                }
            }
            
            return $column;
        }

        $timeout(function () {    
            $scope.mainGridOptions = {
                dataSource: {
                    type: "odata",
                    transport: {
                        read: {
                            url: "api/customer-evaluation-minimum-standard-tracking-0312-summary-cycle",
                            dataType: "json",
                            type: "POST",
                            data: function() {

                                var param = {
                                    customerEvaluationMinimumStandardId: $scope.currentId,                                    
                                    year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0                                    
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
                            fields: {
                                description: {editable: false, nullable: true},                              
                            }
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
                sortable: false,
                pageable: false,
                filterable: false,
                dataBound: function (e) {
                    //this.expandRow(this.tbody.find("tr.k-master-row"));                    
                    var grid = this;

                    this.tbody.find(".k-hierarchy-cell").each(function () {
                        var currentDataItem = grid.dataItem($(this).closest("tr"));

                        if (currentDataItem.abbreviation == 'PUNTAJE') {
                            $(this).find('a.k-icon').remove()
                        }
                    })                                        
                },
                columns: [
                    {
                        field: "abbreviation",
                        title: "Código",
                        attributes: {
                            class: "text-orange text-large",
                        }                       
                    },  {
                        field: "name",
                        title: "Ciclo",
                        attributes: {
                            class: "text-orange text-large",
                        }                       
                    }, 
                    buildKendoGridColumn("JAN", "ENE", "70px", true, function(dataItem) {
                        return dataItem.JAN != null ? dataItem.JAN + ' %' : '';
                    }),
                    buildKendoGridColumn("FEB", "FEB", "70px", true, function(dataItem) {
                        return dataItem.FEB != null ? dataItem.FEB + ' %' : '';
                    }),
                    buildKendoGridColumn("MAR", "MAR", "70px", true, function(dataItem) {
                        return dataItem.MAR != null ? dataItem.MAR + ' %' : '';
                    }),
                    buildKendoGridColumn("APR", "ABR", "70px", true, function(dataItem) {
                        return dataItem.APR != null ? dataItem.APR + ' %' : '';
                    }),
                    buildKendoGridColumn("MAY", "MAY", "70px", true, function(dataItem) {
                        return dataItem.MAY != null ? dataItem.MAY + ' %' : '';
                    }),
                    buildKendoGridColumn("JUN", "JUN", "70px", true, function(dataItem) {
                        return dataItem.JUN != null ? dataItem.JUN + ' %' : '';
                    }),
                    buildKendoGridColumn("JUL", "JUL", "70px", true, function(dataItem) {
                        return dataItem.JUL != null ? dataItem.JUL + ' %' : '';
                    }),
                    buildKendoGridColumn("AUG", "AGO", "70px", true, function(dataItem) {
                        return dataItem.AUG != null ? dataItem.AUG + ' %' : '';
                    }),
                    buildKendoGridColumn("SEP", "SEP", "70px", true, function(dataItem) {
                        return dataItem.SEP != null ? dataItem.SEP + ' %' : '';
                    }),
                    buildKendoGridColumn("OCT", "OCT", "70px", true, function(dataItem) {
                        return dataItem.OCT != null ? dataItem.OCT + ' %' : '';
                    }),
                    buildKendoGridColumn("NOV", "NOV", "70px", true, function(dataItem) {
                        return dataItem.NOV != null ? dataItem.NOV + ' %' : '';
                    }),
                    buildKendoGridColumn("DEC", "DIC", "70px", true, function(dataItem) {
                        return dataItem.DEC != null ? dataItem.DEC + ' %' : '';
                    }),
                ]
            };

            $scope.detailGridOptions = function(dataItem) {
                return {
                    dataSource: {
                        type: "odata",
                        transport: {
                            read: {
                                url: "api/customer-evaluation-minimum-standard-tracking-0312-summary-cycle-detail",
                                dataType: "json",
                                type: "POST",
                                data: function() {

                                    var param = {                                                                            
                                        customerEvaluationMinimumStandardId: $scope.currentId,                                    
                                        year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0,                                    
                                        cycle: dataItem.cycle,    
                                    };

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
                                fields: {
                                    indicator: { editable: false, nullable: true },
                                    /*article: { editable: false, nullable: true },                                    
                                    rate: { editable: false, defaultValue: { id: 0, text: null, code: null} },*/
                                }
                            },
                            data: function(result) {
                                return result.data || result;
                            },
                            total: function(result) {
                                return result.recordsTotal || result.data.length || 0;
                            }
                        },
                        serverPaging: false,
                        serverSorting: false,
                        serverFiltering: false,                        
                        //filter: { field: "minimumStandardId", operator: "eq", value: dataItem.id }
                    },
                    //editable: 'incell',
                    /*edit: function(e) {
                        editedRow.model = e.model;
                    },*/
                    scrollable: false,
                    sortable: false,
                    pageable: false,                    
                    columns: [
                        {
                            field: "indicator",
                            title: "Indicador",
                            //width: '402px',
                            headerAttributes: {
                                style: "display: none"
                            }
                        },
                        buildKendoGridColumn("JAN", "ENE", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.JAN);
                        }),
                        buildKendoGridColumn("FEB", "FEB", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.FEB);
                        }),
                        buildKendoGridColumn("MAR", "MAR", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.MAR);
                        }),
                        buildKendoGridColumn("APR", "ABR", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.APR);
                        }),
                        buildKendoGridColumn("MAY", "MAY", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.MAY);
                        }),
                        buildKendoGridColumn("JUN", "JUN", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.JUN);
                        }),
                        buildKendoGridColumn("JUL", "JUL", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.JUL);
                        }),
                        buildKendoGridColumn("AUG", "AGO", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.AUG);
                        }),
                        buildKendoGridColumn("SEP", "SEP", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.SEP);
                        }),
                        buildKendoGridColumn("OCT", "OCT", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.OCT);
                        }),
                        buildKendoGridColumn("NOV", "NOV", "70px", false, function(dataItem) {
                            return convertToInt(dataItem.NOV);
                        }),
                        buildKendoGridColumn("DEC", "DIC", "60px", false, function(dataItem) {
                            return convertToInt(dataItem.DEC);
                        }),
                    ]
                };
            };

        });

        $scope.$on("kendoWidgetCreated", function(event, widget) {
            if ($scope.grid === undefined || $scope.grid === null) {
                $scope.grid = widget;
            }
        });   
        
        $scope.dtOptionsMinimumStandardSummaryProgram0312 = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerEvaluationMinimumStandardId = $scope.currentId;
                    d.year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0;
                    return JSON.stringify(d);
                },
                url: 'api/customer-evaluation-minimum-standard-tracking-0312-summary-cycle',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {

                }
            })
            .withDataProp('data')            
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsMinimumStandardSummaryProgram0312 = [
            DTColumnBuilder.newColumn('abbreviation')
                .withTitle("Código")
                .withOption('width', 100),
            DTColumnBuilder.newColumn('name')
                .withTitle("Ciclo")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('JAN')
                .withTitle("ENE")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('FEB')
                .withTitle("FEB")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('MAR')
                .withTitle("MAR")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('APR')
                .withTitle("ABR")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('MAY')
                .withTitle("MAY")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('JUN')
                .withTitle("JUN")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('JUL')
                .withTitle("JUL")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('AUG')
                .withTitle("AGO")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('SEP')
                .withTitle("SEP")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('OCT')
                .withTitle("OCT")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('NOV')
                .withTitle("NOV")
                .withOption('width', 200),
            DTColumnBuilder.newColumn('DEC')
                .withTitle("DIC")
                .withOption('width', 200),
        ];


        /////////////////////////////////////////////////////////////////
        //Indicators
        ////////////////////////////////////////////////////////////////        
        $scope.dtOptionsMinimumStandardSummaryIndicator0312 = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerEvaluationMinimumStandardId = $scope.currentId;
                    d.year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0;
                    return JSON.stringify(d);
                },
                url: 'api/customer-evaluation-minimum-standard-tracking-0312-summary-indicator',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {

                }
            })
            .withDataProp('data')            
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {

            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsMinimumStandardSummaryIndicator0312 = [
            DTColumnBuilder.newColumn('indicator')
                .withTitle("Indicador")
                .withOption('width', 400),

            DTColumnBuilder.newColumn(null)
                .withTitle("ENE")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.JAN) : data.JAN;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("FEB")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.FEB) : data.FEB;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("MAR")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.MAR) : data.MAR;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("ABR")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.APR) : data.APR;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("MAY")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.MAY) : data.MAY;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("JUN")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.JUN) : data.JUN;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("JUL")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.JUL) : data.JUL;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("AGO")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.AUG) : data.AUG;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("SEP")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.SEP) : data.SEP;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("OCT")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.OCT) : data.OCT;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("NOV")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.NOV) : data.NOV;
                }),
            DTColumnBuilder.newColumn(null)
                .withTitle("DIC")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    return data.indicator != 'Promedio Total %' ? convertToInt(data.DEC) : data.DEC;
                }),
        ];

        var convertToInt = function (value) {
            return value != null ? parseInt(value) : "";
        }

        $scope.dtInstanceMinimumStandardSummaryProgram0312Callback = function (instance) {
            $scope.dtInstanceMinimumStandardSummaryProgram0312 = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceMinimumStandardSummaryProgram0312.reloadData();
        };


        $scope.dtInstanceMinimumStandardSummaryIndicator0312Callback = function (instance) {
            $scope.dtInstanceMinimumStandardSummaryIndicator0312 = instance;
        };

        $scope.reloadIndicatorData = function () {
            $scope.dtInstanceMinimumStandardSummaryIndicator0312.reloadData();
        };

        $scope.onContinue = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", $scope.currentId);
            }
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.currentId);
            }
        };

        $scope.onSelectYear = function () {
            $timeout(function () {
                $scope.reloadData();
                $scope.reloadIndicatorData();
                getCharts();
            });
        };

        $scope.onClearYear = function () {
            $timeout(function () {
                $scope.audit.operation = "info";
                $scope.audit.standardId = $scope.currentId;
                $scope.audit.year = 0;
                $scope.filter.selectedYear = null;
                $scope.reloadData();
                $scope.reloadIndicatorData();
                getCharts();
            });
        }

        $scope.onSummaryByProgramExportExcel = function () {
            var data = JSON.stringify({
                customerId: $stateParams.customerId,
                customerEvaluationMinimumStandardId: $scope.currentId,
                year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0
            });
            angular.element("#downloadDocument")[0].src = "api/customer-evaluation-minimum-standard-tracking-0312-summary-cycle/export-excel?data=" + Base64.encode(data);
        }

        $scope.onSummaryByProgramExportPdf = function () {            
            kendo.drawing.drawDOM($(".export-pdf-program-em-0312"))
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
                        fileName: "EM Reporte Mensual Ciclos.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryByIndicatorExportExcel = function () {
            var data = JSON.stringify({
                customerId: $stateParams.customerId,
                customerEvaluationMinimumStandardId: $scope.currentId,
                year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0
            });

            angular.element("#downloadDocument")[0].src = "api/customer-evaluation-minimum-standard-tracking-0312-summary-indicator/export-excel?data=" + Base64.encode(data);
        }

        $scope.onSummaryByIndicatorExportPdf = function () {            
            kendo.drawing.drawDOM($(".export-pdf-indicator-em-0312"))
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
                        fileName: "EM 0312 Reporte Mensual Indicadores.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

    }
]);