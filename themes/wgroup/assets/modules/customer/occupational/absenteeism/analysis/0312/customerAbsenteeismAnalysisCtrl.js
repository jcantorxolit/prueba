'use strict';
/**
 * controller for Customers
 */
app.controller('customerAbsenteeismAnalysisResolution0312Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter',
    '$aside', 'ListService', 'ChartService', 'ngNotify', 
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService, ChartService, ngNotify) {

        var log = $log;
        var pager = {
            refresh: true,
            index: 0
        };

        $scope.isLoaded = false;

        $scope.filter = {
            canCompare: false,
            selectedCause: null,
            selectedYear: null,            
            compareYearList: []
        };

        $scope.compareYearList = [];

        $scope.chart = {
            line: { options: null },
            bar: { options: null },
            frequencyAccidentality: { data: null },
            severityAccidentality: { data: null },
            mortalProportionAccidentality: { data: null },
            absenteeismMedicalCause: { data: null },
            occupationalDiseaseFatalityRate: { data: null },
            occupationalDiseasePrevalence: { data: null },
            occupationalDiseaseIncidence: { data: null }
        };

        function getCharts() {

            var $compareYearList = $scope.filter.compareYearList.filter(function (year) {
                return year != null && year.value != null && $scope.filter.canCompare;
              }).map(function(year, index, array) {
                return year.value.value;
            });

            if ($compareYearList === undefined || $compareYearList === null) {
                $compareYearList = [];
            }

            $compareYearList.push($scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0);

            var $criteria = {
                customerId: $stateParams.customerId,
                yearList:  $compareYearList
            };

            var entities = [
                { name: 'chart_line_options', criteria: null },
                { name: 'chart_bar_options', criteria: null },
                { name: 'customer_absenteeism_indicator_0312', criteria: $criteria }
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.line.options = response.data.data.chartLineOptions;
                    $scope.chart.bar.options = response.data.data.chartLBarOptions;
                    $scope.chart.frequencyAccidentality.data = response.data.data.customerAbsenteeismIndicatorFrequencyAccidentality;
                    $scope.chart.severityAccidentality.data = response.data.data.customerAbsenteeismIndicatorSeverityAccidentality;
                    $scope.chart.mortalProportionAccidentality.data = response.data.data.customerAbsenteeismIndicatorMortalProportionAccidentality;
                    $scope.chart.absenteeismMedicalCause.data = response.data.data.customerAbsenteeismIndicatorAbsenteeismMedicalCause;
                    $scope.chart.occupationalDiseaseFatalityRate.data = response.data.data.customerAbsenteeismIndicatorOccupationalDiseaseFatalityRate;
                    $scope.chart.occupationalDiseasePrevalence.data = response.data.data.customerAbsenteeismIndicatorOccupationalDiseasePrevalence;
                    $scope.chart.occupationalDiseaseIncidence.data = response.data.data.customerAbsenteeismIndicatorOccupationalDiseaseIncidence;
                }, function (error) {

                });
        }

        getList();

        function getList() {
            var entities = [
                {name: 'current_year'},     
                { name: 'absenteeism_disability_indicator_years', value: $stateParams.customerId },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.years = response.data.data.absenteeism_disability_indicator_years;
                    var $currentYear = response.data.data.currentYear;
                    var $result = $filter('filter')($scope.years, {value: $currentYear});

                    $scope.filter.selectedYear = $result.length ? $result[0] : null;

                    $scope.isLoaded = true;

                    fillCompareYearList();
                    getCharts();                    
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var fillCompareYearList = function() {
            $scope.compareYearList = $scope.years.filter(function($year) {
                return $year.value != $scope.filter.selectedYear.value;
            });
        }

        $scope.reloadData = function() {
            $scope.grid.dataSource.read();
            $scope.onSelectYear();
        }

        $scope.onConsolidate = function () {
            var req = {};
            req.id = $stateParams.customerId;
            $http({
                method: 'POST',
                url: 'api/customer-absenteeism-indicator/consolidate-0312',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {

                if (response.data.result.isQueue) {
                    var $message = '<div class="row"><div class="col-sm-12 text-center">Por favor espere y verifique su correo y la bandeja de mensajes!</div> </div>';
                    ngNotify.set($message, {
                        position: 'bottom',
                        sticky: true,
                        type: 'info',
                        button: true,
                        html: true
                    });
    
                } else {
                    swal("Consolidación", "La Matriz de indicadores se ha consolidado satisfactoriamente", "info");
                    $scope.reloadData();
                }

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error en la Consolidación", "Se ha presentado un error durante la Consolidación de la matriz por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        }

        $timeout(function () {    

            var kendoGridColumns = function()
            {
                var $columns = [
                    {
                        command: [
                            { text: " ", template: "<a class='btn btn-success btn btn-xs' ng-click='onEdit(dataItem)' uib-tooltip='Editar' tooltip-placement='right'><i class='fa fa-edit'></i></a> " }
                        ], 
                        width: "80px"
                    }
                ];
    
                $columns.push(buildKendoGridColumn('classification', 'Clasificación', '250px'));
                $columns.push(buildKendoGridColumn('period', 'Periodo', '100px'));
                $columns.push(buildKendoGridColumn('disabilityDays', 'Días Incapacidad', null));            
                $columns.push(buildKendoGridColumn('eventNumber', 'Eventos', null));
                $columns.push(buildKendoGridColumn('chargedDays', 'Días Cargados', null));
                $columns.push(buildKendoGridColumn('eventMortalNumber', 'Mortales', null));
                $columns.push(buildKendoGridColumn('programedDays', 'Días Programados', '200px'));
                $columns.push(buildKendoGridColumn('employeeQuantity', 'Empleados', '200px'));
    
                return $columns;
            };
    
            var buildKendoGridColumn = function(field, title, width, filterable, templateCallback)
            {
                return {
                    field: field,
                    title: title,
                    width: width,
                    headerAttributes: {
                        class: "text-bold",
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
                            url: "api/customer-absenteeism-indicator-parent",
                            dataType: "json",
                            type: "POST",
                            data: function() {

                                var param = {
                                    customerId: $stateParams.customerId             
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
                                url: "api/customer-absenteeism-indicator-detail",
                                dataType: "json",
                                type: "POST",
                                data: function() {

                                    var param = {
                                        cause: dataItem.cause,                                            
                                        periodCode: dataItem.periodCode,                                            
                                        customerId: $stateParams.customerId,                                            
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
                                classification: "classification",
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
                        pageSize: 10,                        
                        filter: [                            
                            { field: "cause", operator: "eq", value: dataItem.cause },
                            { field: "periodCode", operator: "eq", value: dataItem.periodCode }
                        ]
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
                            command: [                                
                                { text: " ", template: "<a class='btn btn-warning btn btn-xs' ng-click='onEditDetail(dataItem)' uib-tooltip='Configurar' tooltip-placement='right'><i class='fa fa-gear'></i></a> " }
                            ], 
                            width: "50px"
                        },
                        buildKendoGridColumn('workplace', 'Centro de Trabajo', null),
                        buildKendoGridColumn('disabilityDays', 'Días Incapacidad', '150px'),
                        buildKendoGridColumn('eventNumber', 'Eventos', '100px'),
                        buildKendoGridColumn('chargedDays', 'Días Cargados', '150px'),
                        buildKendoGridColumn('eventMortalNumber', 'Mortales', '100px')                                
                    ]
                };
            };

        });

        $scope.$on("kendoWidgetCreated", function(event, widget) {
            if ($scope.grid === undefined || $scope.grid === null) {
                $scope.grid = widget;
            }
        });

        $scope.onEdit = function (dataItem) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_goal.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/analysis/0312/customer_absenteeism_disability_indicator_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerAbsenteeismAnalysisResolution0312Ctrl',
                scope: $scope,
                resolve: {
                    dataItem: function () {
                        return dataItem;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.grid.dataSource.read();
            }, function() {
                $scope.grid.dataSource.read();
            });
        };

        $scope.onEditDetail = function (dataItem) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_indicator.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/analysis/0312/customer_absenteeism_disability_indicator_detail_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerAbsenteeismAnalysisResolution0312DetailCtrl',
                scope: $scope,
                resolve: {
                    dataItem: function () {
                        return dataItem;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.grid.dataSource.read();
            }, function() {
                $scope.grid.dataSource.read();
            });
        };


        //-------------------------------------------------------FILTERS
        $scope.onAddCompareYear = function() {
            $scope.filter.compareYearList.push({});
        }

        $scope.onRemoveCompareYear = function(index) {
            $scope.filter.compareYearList.splice(index, 1);
            getCharts();
        }

        $scope.onSelectYear = function () {   
            fillCompareYearList();         
            getCharts();
            $scope.dtInstanceFrequencyAccidentality.reloadData();
            $scope.dtInstanceSeverityAccidentality.reloadData();
            $scope.dtInstanceMortalProportionAccidentality.reloadData();
            $scope.dtInstanceAbsenteeismMedicalCause.reloadData();
            $scope.dtInstanceOccupationalDiseaseFatalityRate.reloadData();
            $scope.dtInstanceOccupationalDiseasePrevalence.reloadData();
            $scope.dtInstanceOccupationalDiseaseIncidence.reloadData();
        };

        $scope.onChangeCompare = function() {
            getCharts();
        }        

        //--------------------------------------------------------------FREQUENCY ACCIDENTALITY
        $scope.dtOptionsFrequencyAccidentality = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here                
                data: function(d) {                    
                    d.customerId = $stateParams.customerId;
                    d.year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0;                                        
                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-indicator-frequency-accidentality',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')            
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsFrequencyAccidentality = [
            DTColumnBuilder.newColumn('month').withTitle("Mes").notSortable(),
            DTColumnBuilder.newColumn('eventNumber').withTitle("Eventos").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('employeeQuantity').withTitle("Empleados").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('result').withTitle("Resultado").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceFrequencyAccidentalityCallback = function(instance) {
            $scope.dtInstanceFrequencyAccidentality = instance;
        }


        //--------------------------------------------------------------SEVERITY ACCIDENTALITY
        $scope.dtOptionsSeverityAccidentality = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here                
                data: function(d) {                    
                    d.customerId = $stateParams.customerId;
                    d.year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0;                                        
                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-indicator-severity-accidentality',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')            
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsSeverityAccidentality = [
            DTColumnBuilder.newColumn('month').withTitle("Mes").notSortable(),
            DTColumnBuilder.newColumn('disabilityDays').withTitle("Días Incapacidad").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('chargedDays').withTitle("Días Cargados").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('employeeQuantity').withTitle("Empleados").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('result').withTitle("Resultado").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceSeverityAccidentalityCallback = function(instance) {
            $scope.dtInstanceSeverityAccidentality = instance;
        }


        //--------------------------------------------------------------MORTAL PROPORTION ACCIDENTALITY
        $scope.dtOptionsMortalProportionAccidentality = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here                
                data: function(d) {                    
                    d.customerId = $stateParams.customerId;
                    d.year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0;                                        
                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-indicator-mortal-proportion-accidentality',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')            
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsMortalProportionAccidentality = [
            DTColumnBuilder.newColumn('year').withTitle("Año").notSortable(),
            DTColumnBuilder.newColumn('eventMortalNumber').withTitle("Mortales").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('eventNumber').withTitle("Eventos").withOption('width', 200).notSortable(),            
            DTColumnBuilder.newColumn('result').withTitle("Resultado").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceMortalProportionAccidentalityCallback = function(instance) {
            $scope.dtInstanceMortalProportionAccidentality = instance;
        }        


        //--------------------------------------------------------------ABSENTEEISM MEDICAL CAUSE
        $scope.dtOptionsAbsenteeismMedicalCause = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here                
                data: function(d) {                    
                    d.customerId = $stateParams.customerId;
                    d.year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0;                                        
                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-indicator-absenteeism-medical-cause',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')            
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsAbsenteeismMedicalCause = [
            DTColumnBuilder.newColumn('month').withTitle("Mes").notSortable(),
            DTColumnBuilder.newColumn('disabilityDays').withTitle("Días Incapacidad").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('programedDays').withTitle("Días Programados").withOption('width', 200).notSortable(),            
            DTColumnBuilder.newColumn('result').withTitle("Resultado").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceAbsenteeismMedicalCauseCallback = function(instance) {
            $scope.dtInstanceAbsenteeismMedicalCause = instance;
        }        


        //--------------------------------------------------------------ABSENTEEISM MEDICAL CAUSE
        $scope.dtOptionsOccupationalDiseaseFatalityRate = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here                
                data: function(d) {                    
                    d.customerId = $stateParams.customerId;
                    d.year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0;                                        
                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-indicator-occupational-disease-fatality-rate',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')            
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsOccupationalDiseaseFatalityRate = [
            DTColumnBuilder.newColumn('year').withTitle("Año").notSortable(),
            DTColumnBuilder.newColumn('eventMortalNumber').withTitle("Mortales").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('eventNumber').withTitle("Eventos").withOption('width', 200).notSortable(),            
            DTColumnBuilder.newColumn('result').withTitle("Resultado").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceOccupationalDiseaseFatalityRateCallback = function(instance) {
            $scope.dtInstanceOccupationalDiseaseFatalityRate = instance;
        } 


        //--------------------------------------------------------------OCCUPATIONAL DISEASE PREVALENCE
        $scope.dtOptionsOccupationalDiseasePrevalence = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here                
                data: function(d) {                    
                    d.customerId = $stateParams.customerId;                                                       
                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-indicator-occupational-disease-prevalence',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')            
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsOccupationalDiseasePrevalence = [
            DTColumnBuilder.newColumn('year').withTitle("Año").notSortable(),
            DTColumnBuilder.newColumn('diagnosticAll').withTitle("Casos").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('employeeQuantity').withTitle("Promedio de Empleados").withOption('width', 200).notSortable(),            
            DTColumnBuilder.newColumn('result').withTitle("Resultado").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceOccupationalDiseasePrevalenceCallback = function(instance) {
            $scope.dtInstanceOccupationalDiseasePrevalence = instance;
        }
        

        //--------------------------------------------------------------OCCUPATIONAL DISEASE INCIDENCE
        $scope.dtOptionsOccupationalDiseaseIncidence = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here                
                data: function(d) {                    
                    d.customerId = $stateParams.customerId;                                                            
                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-indicator-occupational-disease-incidence',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')            
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                //loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });        

        $scope.dtColumnsOccupationalDiseaseIncidence = [
            DTColumnBuilder.newColumn('year').withTitle("Año").notSortable(),
            DTColumnBuilder.newColumn('diagnosticNew').withTitle("Casos").withOption('width', 200).notSortable(),
            DTColumnBuilder.newColumn('employeeQuantity').withTitle("Promedio de Empleados").withOption('width', 200).notSortable(),            
            DTColumnBuilder.newColumn('result').withTitle("Resultado").withOption('width', 200).notSortable(),
        ];

        $scope.dtInstanceOccupationalDiseaseIncidenceCallback = function(instance) {
            $scope.dtInstanceOccupationalDiseaseIncidence = instance;
        }


        //--------------------------------------------------------EXPORT PDF AND EXCEL

        $scope.onExportPdf = function (name) {    
            var $el = null;
            var $filename = 'Gráfica.pdf';

            switch (name) {
                case 'FA':
                    $el = '.export-pdf-frequency-accidentality';
                    $filename = 'FRECUENCIA_ACCIDENTALIDAD.pdf';
                break;

                case 'SA':
                    $el = '.export-pdf-severity-accidentality';
                    $filename = 'SEVERIDAD_ACCIDENTALIDAD.pdf';
                break;     
                
                case 'PAM':
                    $el = '.export-pdf-mortal-proportion-accidentality';
                    $filename = 'PROPORCION_ACCIDENTES_MORTALES.pdf';
                break; 
                
                case 'PAM':
                    $el = '.export-pdf-mortal-proportion-accidentality';
                    $filename = 'PROPORCION_DE_ACCIDENTES_DE_TRABAJO_MORTALES.pdf';
                break;   

                case 'ACM':
                    $el = '.export-pdf-absenteeism-medical-cause';
                    $filename = 'AUSENTISMO_CAUSA_MEDICA.pdf';
                break;             
                
                case 'TL':
                    $el = '.export-pdf-occupational-disease-fatality-rate';
                    $filename = 'TASA_LETALIDAD_ENFERMEDAD_LABORAL.pdf';
                break;      
                
                case 'PEL':
                    $el = '.export-pdf-occupational-disease-prevalence';
                    $filename = 'PREVALENCIA_ENFERMEDAD_LABORAL.pdf';
                break;
                
                case 'IEL':
                    $el = '.export-pdf-occupational-disease-incidence';
                    $filename = 'INCIDENCIA_ENFERMEDAD_LABORAL.pdf';
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

        $scope.onExportExcel = function (name) {

            var $api = null;            

            switch (name) {
                case 'FA':
                    $api = 'api/customer-absenteeism-indicator/export-frequency-accidentality';                    
                    break;
                
                case 'SA':
                    $api = 'api/customer-absenteeism-indicator/export-severity-accidentality';                    
                break;

                case 'PAM':
                    $api = 'api/customer-absenteeism-indicator/export-mortal-proportion-accidentality';                    
                break;      
                
                case 'ACM':
                    $api = 'api/customer-absenteeism-indicator/export-absenteeism-medical-cause';                    
                break;    
                
                case 'TL':
                    $api = 'api/customer-absenteeism-indicator/export-occupational-disease-fatality-rate';                    
                break; 

                case 'PEL':
                    $api = 'api/customer-absenteeism-indicator/export-occupational-disease-prevalence';                    
                break; 
                
                case 'IEL':
                    $api = 'api/customer-absenteeism-indicator/export-occupational-disease-incidence';                    
                break;                 
                
                case 'MA':
                    $api = 'api/customer-absenteeism-indicator/export-parent';                    
                break;
            }

            var $year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : '';
            
            angular.element("#download")[0].src = $api + "?customerId=" + $stateParams.customerId + "&year=" + $year;
        }
    }
]);


app.controller('ModalInstanceSideCustomerAbsenteeismAnalysisResolution0312Ctrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, dataItem, $log, $timeout, $document, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {


    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var onInit = function () {
        $scope.indicator = {            
            customerId: dataItem.customerId,
            cause: dataItem.cause,
            periodCode: dataItem.periodCode,
            classification: dataItem.classification,
            period: dataItem.period,
            employeeQuantity: dataItem.employeeQuantity,
            programedDays: dataItem.programedDays,
            eventNumber: dataItem.eventNumber,
            disabilityDays: dataItem.disabilityDays,
            chargedDays: dataItem.chargedDays,
            eventMortalNumber: dataItem.eventMortalNumber
        };
    };

    onInit();

    $scope.master = $scope.indicator;
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
                return;

            } else {
                SweetAlert.swal("Validación exitosa", "Guardando información de indicador...", "success");
                //your code for submit
                //  log.info($scope.disability);
                save();
            }

        },
        reset: function (form) {
            onInit();
        }
    };

    var save = function () {
        console.log($scope.indicator);
        var req = {};
        var data = JSON.stringify($scope.indicator);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/customer-absenteeism-indicator/update',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.indicator = response.data.result;     
                SweetAlert.swal("Validación exitosa", "El registro se guardó satisfactoriamente", "success");           
                $uibModalInstance.dismiss('cancel');                
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            //$scope.dtInstanceDisabilityIndicatorList.reloadData();
        });

    };

});

app.controller('ModalInstanceSideCustomerAbsenteeismAnalysisResolution0312DetailCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, dataItem, $log, $timeout, $document, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {
  
    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.isDisableChargedDays = (dataItem.cause != 'AT' && dataItem.cause != 'AL');
    $scope.isDisableEventMortalNumber = (dataItem.cause != 'AT' && dataItem.cause != 'AL' && dataItem.cause != 'EL' && dataItem.cause != 'ELC');

    var onInit = function () {
        $scope.indicator = {  
            id: dataItem.id,          
            customerId: dataItem.customerId,
            cause: dataItem.cause,
            periodCode: dataItem.periodCode,
            classification: dataItem.classification,
            period: dataItem.period,
            workCenter: dataItem.workplace,
            employeeQuantity: dataItem.employeeQuantity,
            programedDays: dataItem.programedDays,
            eventNumber: dataItem.eventNumber,
            disabilityDays: dataItem.disabilityDays,
            chargedDays: dataItem.chargedDays,
            eventMortalNumber: dataItem.eventMortalNumber
        };
    };    

    onInit();

    $scope.master = $scope.indicator;
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
                
                return;

            } else {
                SweetAlert.swal("Validación exitosa", "Guardando información de indicador...", "success");
                //your code for submit
                //  log.info($scope.disability);
                save();
            }

        },
        reset: function (form) {
            onInit();
        }
    };

   

    var save = function () {
        console.log($scope.indicator);
        var req = {};
        var data = JSON.stringify($scope.indicator);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/customer-absenteeism-indicator/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.indicator = response.data.result;
                SweetAlert.swal("Validación exitosa", "El registro se guardó satisfactoriamente", "success");           
                $uibModalInstance.dismiss('cancel');                
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            //$scope.dtInstanceDisabilityIndicatorList.reloadData();
        });

    };

});