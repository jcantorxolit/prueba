'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticRiskMatrixHistoricalCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 
    '$translate', 'ListService', '$aside', 'moment',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $translate, ListService, $aside, moment) {


        var log = $log;

        var pager = {
            refresh: true,
            index: 0
        };

        $scope.isView = $scope.customer.matrixType != 'G';

        $scope.risks = $rootScope.parameters("config_acceptance_risks");

        $scope.filter = {
            selectedWorkPlace: null,
            selectedRisk: null
        }

        var loadList = function () {

            var req = {};
            req.operation = "diagnostic";
            req.customerId = $stateParams.customerId;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/workplace/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.workplaces = response.data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        loadList();

        $timeout(function () {    

            var kendoGridColumns = function()
            {
                var $columns = [];
    
                $columns.push(buildKendoGridColumn('workPlace', $translate.instant('grid.matrix.WORK-PLACE'), '250px'));
                $columns.push(buildKendoGridColumn('macroProcess', $translate.instant('grid.matrix.MACROPROCESS'), '200px'));
                $columns.push(buildKendoGridColumn('process', $translate.instant('grid.matrix.PROCESS'), '200px'));            
                $columns.push(buildKendoGridColumn('job', 'Cargo', '200px'));
                $columns.push(buildKendoGridColumn('activity', $translate.instant('grid.matrix.ACTIVITY'), '200px'));
                $columns.push(buildKendoGridColumn('isRoutine', 'Rutinaria', '200px'));
                $columns.push(buildKendoGridColumn('classification', 'Clasificación', '200px'));
                $columns.push(buildKendoGridColumn('type', 'Tipo Peligro', '200px'));
                $columns.push(buildKendoGridColumn('description', 'Descripción Peligro', '200px'));
                $columns.push(buildKendoGridColumn('effect', 'Efectos a la Salud', '200px'));
                $columns.push(buildKendoGridColumn('timeExposure', 'T. Expuesto', '200px'));
                $columns.push(buildKendoGridColumn('controlMethodSourceText', $translate.instant('grid.matrix.CONTROL-METHOD-SOURCE'), '200px'));
                $columns.push(buildKendoGridColumn('controlMethodMediumText', $translate.instant('grid.matrix.CONTROL-METHOD-MEDIUM'), '200px'));
                $columns.push(buildKendoGridColumn('controlMethodPersonText', $translate.instant('grid.matrix.CONTROL-METHOD-PERSON'), '200px'));
                if ($rootScope.app.instance == 'isa') {
                    $columns.push(buildKendoGridColumn('controlMethodAdministrativeText', 'M. Control Señalización / Control Administrativo', '200px'));
                }
                $columns.push(buildKendoGridColumn('measureND', 'N. Deficiencia', '200px'));
                $columns.push(buildKendoGridColumn('measureNE', 'N. Exposición', '200px'));
                $columns.push(buildKendoGridColumn('measureNC', 'N. Consecuencia', '200px'));
                $columns.push(buildKendoGridColumn('levelP', 'N. Probabilidad', '200px'));
                $columns.push(buildKendoGridColumn('levelIP', 'Interp N. Probabilidad', '200px'));
                $columns.push(buildKendoGridColumn('levelR', 'Nivel Riesgo', '200px'));
                $columns.push(buildKendoGridColumn(null, 'Interp Riesgo', '200px', false, levelIRTemplate));    
                $columns.push(buildKendoGridColumn(null, 'Valoracion riesgo', '200px', false, riskValueTemplate));               
                $columns.push(buildKendoGridColumn('exposed', 'Trabajadores Vinculados o en Misión', '200px'));
                $columns.push(buildKendoGridColumn('contractors', 'Trabajadores Contratistas', '200px'));
                $columns.push(buildKendoGridColumn('visitors', 'Visitantes', '200px'));    
                $columns.push(buildKendoGridColumn('status', 'Verificado', '200px', false, statusTemplate));
                $columns.push(buildKendoGridColumn('reason', 'Motivo', '200px'));

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

            var levelIRTemplate = function(dataItem) {
                return buildColumnTemplateRisk(dataItem.levelIR, dataItem.levelIR);
            }

            var riskValueTemplate = function(dataItem) {
                return buildColumnTemplateRisk(dataItem.riskValue, dataItem.levelIR);
            }

            var statusTemplate = function(dataItem) {
                var label = '';
                var text = dataItem.status != null ? dataItem.status : '';
                switch (dataItem.status) {
                    case "Denegado":
                        label = 'label label-danger';
                        break;

                    case "Pendiente":
                        label = 'label label-warning';
                        break;

                    case "Aprobado":
                        label = 'label label-success';
                        break;
                }

                return '<span class="' + label + '">' + text + '</span>';
            };
            
            var createdAtTemplate = function(dataItem) {
                var $createdAt = new moment(dataItem.createdAt.date);
                return $createdAt.format('DD-MM-YYYY HH:mm');
            } 
    
            var buildColumnTemplateRisk = function(text, levelIR) {
                var label = '';
                var text = text != null ? text : '';
                switch (levelIR) {
                    case "I":
                        label = 'label label-danger';
                        break;
    
                    case "II":
                        label = 'label label-warning';
                        break;
    
                    case "III":
                        label = 'label label-info';
                        break;
    
                    case "IV":
                        label = 'label label-success';
                        break;
                }
            
                return '<span class="' + label + '">' + text + '</span>';;
            }  

            $scope.mainGridOptions = {
                dataSource: {
                    type: "odata",
                    transport: {
                        read: {
                            url: "api/customer-config-activity-hazard",
                            dataType: "json",
                            type: "POST",
                            data: function() {

                                var param = {
                                    customerId: $stateParams.customerId             
                                };

                                if ($scope.filter.selectedWorkPlace !== null) {
                                    param.workPlaceId = $scope.filter.selectedWorkPlace.id
                                }

                                if ($scope.filter.selectedRisk !== null) {
                                    param.levelIR = $scope.filter.selectedRisk.value
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
                },
                columns: kendoGridColumns()
            };

            $scope.detailGridOptions = function(dataItem) {
                return {
                    dataSource: {
                        type: "odata",
                        transport: {
                            read: {
                                url: "api/customer-config-activity-hazard-historical",
                                dataType: "json",
                                type: "POST",
                                data: function() {

                                    var param = {                                                                                
                                        jobActivityHazardId: dataItem.id,                                        
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
                                fields: {}
                            },
                            data: function(result) {
                                return result.data || result;
                            },
                            total: function(result) {
                                return result.recordsTotal || result.data.length || 0;
                            }
                        },
                        serverPaging: true,
                        serverSorting: true,
                        serverFiltering: false,
                        pageSize: 10,
                        filter: { field: "jobActivityHazardId", operator: "eq", value: dataItem.id }
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
                                { text: " ", template: "<a class='btn btn-info btn btn-xs' ng-click='onView(dataItem)' uib-tooltip='Ver Cambios' tooltip-placement='right'><i class='fa fa-eye'></i></a> " },                        
                            ], 
                            width: "50px"
                        },                                      
                        buildKendoGridColumn('type', 'Acción', '100px'),
                        buildKendoGridColumn('source', 'Origen', '200px'),
                        buildKendoGridColumn('name', 'Realizado Por', null),
                        buildKendoGridColumn('reason', 'Motivo de Actualización', '300px'),
                        buildKendoGridColumn('reasonObservation', 'Observación del Motivo', null),                        
                        buildKendoGridColumn('createdAt', 'Fecha', '150px', false, createdAtTemplate)                             
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
            $scope.grid.dataSource.read();
        }

        $scope.onSelectRisk = function()
        {            
            $scope.grid.dataSource.read();
        }

        $scope.onClearWorkPlace = function()
        {
            $scope.filter.selectedWorkPlace = null;
            $scope.grid.dataSource.read();
        }

        $scope.onClearRisk = function()
        {
            $scope.filter.selectedRisk = null;
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
            jQuery("#downloadDocument")[0].src = "api/customer-config-activity-hazard-historical/export?id=" + $stateParams.customerId;
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