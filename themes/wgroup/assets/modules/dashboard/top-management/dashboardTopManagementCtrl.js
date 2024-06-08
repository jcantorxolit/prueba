'use strict';
/**
 * controller for Dashboard Top Management
 */
app.controller('dashboardTopManagementCtrl', function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
    $rootScope, $timeout, $http, SweetAlert, ChartService, $filter, ListService, ngNotify, $aside) {

    $scope.typeList = $rootScope.parameters("project_type");
    $scope.performanceLevelList = $rootScope.parameters("project_performance_level");

    $scope.showIndicators = false;
    $scope.customerList = [];
    $scope.administratorList = [];
    $scope.periodList = [];
    $scope.santisfactionAnswerTypes = null;

    $scope.filter = {
        performanceLevel: null
    };

    $scope.init = function () {
        $scope.filters = {
            startDate: null,
            endDate: null,
            type: null,
            concept: null,
            classification: null,
            customer: null,
            administrator: null,
            period: null
        };

        $scope.hideGrids();
    };

    $scope.hideGrids = function () {
        $scope.isOpenGridHistoricalSales = true;
        $scope.isOpenGridTotalSales = true;
        $scope.isOpenGridSalesByType = true;
        $scope.isOpenGridSalesByConcept = true;
        $scope.isOpenGridSalesByClassification = true;
        $scope.isOpenGridExperiencesByMonths = true;
        $scope.isOpenGridSatisfactionByExperience = true;
        $scope.isOpenGridRegisteredVsParticipants = true;
        $scope.isOpenGridPerformanceByConsultant = true;
        $scope.isOpenGridProgrammedVsExecutedSales = true;
    }

    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
    };

    $scope.chart = {
        line: { options: null },
        lineFormatMoney: { options: null },

        bar: { options: null },
        barFormatMoney: { options: null },
        barWithScalesMoney: { options: null },
        barWithScales: { options: null },

        pie: { options: null },
        data: {
            dashboardTopManagementCostHistorical: null,
            dashboardTopManagementCostTotalCurrentYear: null,

            dashboardTopManagementCostTypesByStates: null,
            dashboardTopManagementCostTypesByStates2: null,

            dashboardTopManagementCostByMonths: null,
            dashboardTopManagementCostByType: null,
            dashboardTopManagementCostByConcept: null,
            dashboardTopManagementCostByClassification: null,

            dashboardTopManagementExperiencesByMoths: null,
            dashboardTopManagementSatisfactionByExperience: null,
            registeredVsParticipantsByMonths: null,
            registeredVsParticipantsAllClientsAndPeriods: null,

            dashboardTopManagementPerformanceByConcultant: null
        }
    };

    $scope.init();
    getList();
    getCharts();


    $scope.dtInstanceTopManagementPerformanceLevel = {};
    $scope.dtOptionsTopManagementPerformanceLevel = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d.startDate = $scope.filters.startDate;
                d.endDate = $scope.filters.endDate;
                d.levelCompliance = $scope.filter.performanceLevel == null ? null : $scope.filter.performanceLevel.item;
                if ($scope.filters.customer)
                    d.customerId = $scope.filters.customer.id;

                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/performance-level',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('order', [
            [0, 'desc']
        ])
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.filters.startDate != null && $scope.filters.endDate != null;
        })
        .withOption('fnDrawCallback', function () {
            loadRow();
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsTopManagementPerformanceLevel = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
            .renderWith(function (data) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs exportRow lnk" href="#" uib-tooltip="Exportar" ' +
                    'data-id="' + data.id + '"  >' +
                    '   <i class="fa fa-download"></i></a> ';
                actions += editTemplate;
                return actions;
            }),

        DTColumnBuilder.newColumn('consultant').withTitle("Asesor"),
        DTColumnBuilder.newColumn('availability').withTitle("Disponibilidad"),
        DTColumnBuilder.newColumn('assigned').withTitle("Asignado"),
        DTColumnBuilder.newColumn('executed').withTitle("Ejecutado"),
        DTColumnBuilder.newColumn('percentCompliance').withTitle("% Cumplimiento"),
        DTColumnBuilder.newColumn('levelCompliance').withTitle("Nivel de Cumplimiento")
            .renderWith(function (data) {
                var type = '';
                switch (data) {
                    case "Muy Alto":
                        type = 'bg-level-high-green';
                        break;
                    case "Alto":
                        type = 'bg-level-green';
                        break;
                    case "Medio":
                        type = 'bg-level-medium';
                        break;
                    case "Bajo":
                    default:
                        type = 'bg-level-low';
                }

                return '<span class="label ' + type + '">' + data + '</span>';
            })
    ];





    $scope.dtInstanceSalesHistoricalGridCallback = function (instance) {
        $scope.dtInstanceSalesHistoricalGrid = instance;
    };

    $scope.dtOptionsSalesHistoricalGrid = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                if ($scope.filter.period != null) {
                    d.period = $scope.filter.period.vlaue;
                }
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/sales-historical',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('order', [
            [0, 'desc']
        ])
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.isOpenGridHistoricalSales == true;
        })
        .withOption('paginate', false)
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsSalesHistoricalGrid = [
        DTColumnBuilder.newColumn('year').withTitle("AÑO"),
        DTColumnBuilder.newColumn('sst').withTitle("SST"),
        DTColumnBuilder.newColumn('vr').withTitle("REALIDAD VIRTUAL"),
        DTColumnBuilder.newColumn('sylogi').withTitle("SYLOGI"),
        DTColumnBuilder.newColumn('total').withTitle("TOTAL VENTAS")
    ];


    var loadRow = function () {
        $("#dtTopManagementPerformanceLevel a.exportRow").on("click", function () {
            var id = $(this).data("id");

            var data = {
                userId: $rootScope.currentUser().id,
                id: id,
                startDate: $scope.filters.startDate,
                endDate: $scope.filters.endDate,
            };

            if ($scope.filters.customer)
                data.customerId = $scope.filters.customer.id;

            var req = {
                data: Base64.encode(JSON.stringify(data))
            };

            return $http({
                method: 'POST',
                url: $scope.exportUrl + 'api/v1/customer-vr-satisfaction-export',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                var $url = $scope.exportUrl + 'storage/' + response.data.filename;
                var $link = '<a class="btn btn-wide btn-default" href="' + $url + '" target="_self"><i class="glyphicon glyphicon-download"></i> Descargar el reporte</a>';

                if (response.data.isQueue) {
                    $url = 'app/user/messages';
                    $link = response.data.message + ' <a  class="btn btn-wide btn-default" href="' + $url + '" translate="Ver mensajes"> Ver mensajes </a>';
                }

                ngNotify.set($link, {
                    position: 'bottom',
                    sticky: true,
                    type: response.data.isQueue ? 'info' : 'success',
                    button: true,
                    html: true
                });

            }).catch(function (e) {
                $log.error(e);

                if (response.data != null && response.data.message !== undefined) {
                    ngNotify.set(response.data.message, {
                        position: 'bottom',
                        sticky: true,
                        type: 'error',
                        button: true,
                        html: true
                    });
                } else {
                    ngNotify.set("Lo sentimos, ha ocurrido un error en la generación del reporte", {
                        position: 'bottom',
                        sticky: true,
                        type: 'error',
                        button: true,
                        html: true
                    });
                }
            });

        });
    };


    $scope.reloadData = function () {
        $scope.dtInstanceTopManagementPerformanceLevel.reloadData();
    };



    // ************************************************  START GRID'S ********************************************

    $scope.dtInstanceTopManagementTotalSalesCallback = function (instance) {
        $scope.dtInstanceTopManagementTotalSales = instance;
    };
    $scope.dtOptionsTopManagementTotalSales = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d = appyFilter(d);
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/total-sales',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.showIndicators == true && $scope.isOpenGridTotalSales == false;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });

    $scope.dtColumnsTopManagementTotalSales = [
        DTColumnBuilder.newColumn('period').withTitle("Periodo"),
        DTColumnBuilder.newColumn('programmed').withTitle("Programado"),
        DTColumnBuilder.newColumn('executed').withTitle("Ejecutado"),
        DTColumnBuilder.newColumn('balance').withTitle("Balance")
    ];




    $scope.dtInstanceTopManagementSalesByTypeCallback = function (instance) {
        $scope.dtInstanceTopManagementSalesByType = instance;
    };
    $scope.dtOptionsTopManagementSalesByType = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d = appyFilter(d);
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/sales-by-type',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.showIndicators == true && $scope.isOpenGridSalesByType == false;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsTopManagementSalesByType = [
        DTColumnBuilder.newColumn('period').withTitle("Periodo"),
        DTColumnBuilder.newColumn('group').withTitle("Tipo"),
        DTColumnBuilder.newColumn('sales').withTitle("Total Ventas"),
    ];



    $scope.dtInstanceTopManagementSalesByConceptCallback = function (instance) {
        $scope.dtInstanceTopManagementSalesByConcept = instance;
    };
    $scope.dtOptionsTopManagementSalesByConcept = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d = appyFilter(d);
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/sales-by-concept',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.showIndicators == true && $scope.isOpenGridSalesByConcept == false;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsTopManagementSalesByConcept = [
        DTColumnBuilder.newColumn('period').withTitle("Periodo"),
        DTColumnBuilder.newColumn('group').withTitle("Concepto"),
        DTColumnBuilder.newColumn('sales').withTitle("Total Ventas"),
    ];


    $scope.dtInstanceTopManagementSalesByClassificationCallback = function (instance) {
        $scope.dtInstanceTopManagementSalesByClassification = instance;
    };
    $scope.dtOptionsTopManagementSalesByClassification = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d = appyFilter(d);
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/sales-by-classification',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.showIndicators == true && $scope.isOpenGridSalesByClassification == false;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });

    $scope.dtColumnsTopManagementSalesByClassification = [
        DTColumnBuilder.newColumn('period').withTitle("Periodo"),
        DTColumnBuilder.newColumn('group').withTitle("Clasificación"),
        DTColumnBuilder.newColumn('sales').withTitle("Total Ventas"),
    ];



    $scope.dtInstanceTopManagementExperienesByMonthsCallback = function (instance) {
        $scope.dtInstanceTopManagementExperienesByMonths = instance;
    };
    $scope.dtOptionsTopManagementExperienesByMonths = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d = appyFilter(d);
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/experiencies-by-months',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.showIndicators == true && $scope.isOpenGridExperiencesByMonths == false;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });

    $scope.dtColumnsTopManagementExperienesByMonths = [
        DTColumnBuilder.newColumn('period').withTitle("Periodo"),
        DTColumnBuilder.newColumn('experience').withTitle("Experiencia"),
        DTColumnBuilder.newColumn('sales').withTitle("Total Ventas"),
    ];


    $scope.dtInstanceTopManagementAmountBySatisfactionLevelCallback = function (instance) {
        $scope.dtInstanceTopManagementAmountBySatisfactionLevel = instance;
    };

    $scope.dtOptionsTopManagementAmountBySatisfactionLevel = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d = appyFilter(d);
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/amount-by-satistaction-level',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.showIndicators == true && $scope.isOpenGridSatisfactionByExperience == false;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });

    $scope.dtColumnsTopManagementAmountBySatisfactionLevel = [
        DTColumnBuilder.newColumn('period').withTitle("Periodo"),
        DTColumnBuilder.newColumn('experience').withTitle("Experiencia"),
        DTColumnBuilder.newColumn('excelente').withTitle("Excelente"),
        DTColumnBuilder.newColumn('bueno').withTitle("Bueno"),
        DTColumnBuilder.newColumn('regular').withTitle("Regular"),
        DTColumnBuilder.newColumn('malo').withTitle("Malo"),
        //DTColumnBuilder.newColumn('muy_malo').withTitle("Muy Malo"),
    ];

    $scope.dtInstanceTopManagementRegisteredVsParticipantsCallback = function (instance) {
        $scope.dtInstanceTopManagementRegisteredVsParticipants = instance;
    };

    $scope.dtOptionsTopManagementRegisteredVsParticipants = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d = appyFilter(d);
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/registered-vs-participants',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.showIndicators == true && $scope.isOpenGridRegisteredVsParticipants == false;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });

    $scope.dtColumnsTopManagementRegisteredVsParticipants = [
        DTColumnBuilder.newColumn('period').withTitle("Periodo"),
        DTColumnBuilder.newColumn('amountParticipants').withTitle("Participantes"),
        DTColumnBuilder.newColumn('amountSurveyed').withTitle("Encuestados"),
    ];



    $scope.dtInstanceTopManagementPerformanceByConsultantCallback = function (instance) {
        $scope.dtInstanceTopManagementPerformanceByConsultant = instance;
    };
    $scope.dtOptionsTopManagementPerformanceByConsultant = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d = appyFilter(d);
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/performance-by-consultant',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.showIndicators == true && $scope.isOpenGridPerformanceByConsultant == false;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });

    $scope.dtColumnsTopManagementPerformanceByConsultant = [
        DTColumnBuilder.newColumn('consultant').withTitle("Asesor"),
        DTColumnBuilder.newColumn('assigned').withTitle("Asignado"),
        DTColumnBuilder.newColumn('executed').withTitle("Ejecutado"),
        DTColumnBuilder.newColumn('balance').withTitle("Balance")
    ];



    $scope.dtInstanceTopManagementProgrammedVsExecutedSalesCallback = function (instance) {
        $scope.dtInstanceTopManagementProgrammedVsExecutedSales = instance;
    };
    $scope.dtOptionsTopManagementProgrammedVsExecutedSales = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d = appyFilter(d);
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/programmed-vs-executed-sales',
            contentType: "application/json",
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return $scope.isOpenGridProgrammedVsExecutedSales == false;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });

    $scope.dtColumnsTopManagementProgrammedVsExecutedSales = [
        DTColumnBuilder.newColumn('year').withTitle("Año"),
        DTColumnBuilder.newColumn('vrProgrammed').withTitle("RV. Programado"),
        DTColumnBuilder.newColumn('vrExecute').withTitle("RV. Ejecutado"),
        DTColumnBuilder.newColumn('vrBalance').withTitle("RV. Balance"),
        DTColumnBuilder.newColumn('sstProgrammed').withTitle("SST Programado"),
        DTColumnBuilder.newColumn('sstExecute').withTitle("SST Ejecutado"),
        DTColumnBuilder.newColumn('sstBalance').withTitle("SST Balance"),
        DTColumnBuilder.newColumn('sylProgrammed').withTitle("SYLOGI Programado"),
        DTColumnBuilder.newColumn('sylExecute').withTitle("SYLOGI Ejecutado"),
        DTColumnBuilder.newColumn('sylBalance').withTitle("SYLOGI Balance")
    ];





    // ************************************************   END GRID'S ********************************************





    $scope.onFilter = function () {
        if ($scope.filters.startDate == null || $scope.filters.endDate == null) {
            SweetAlert.swal("Campos incorretos", "Seleccione las fechas de inicio y fin para continuar", "warning");
            return;
        }

        $scope.hideGrids();
        $scope.showIndicators = true;
        getSummaryCharts();
        $scope.reloadData();
    };


    $scope.onCancel = function () {
        $scope.showIndicators = false;
        $scope.init();
    };


    $scope.onConsolidate = function () {
        return $http({
            method: 'POST',
            url: 'api/dashboard/top-management/consolidate',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        }).then(function () {
            $scope.refreshAll();
            SweetAlert.swal("Proceso Exitoso", ".", "success");
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error al consolidar", e.data.message, "error");
        });
    };


    $scope.onChangeType = function () {
        var relationshipConcepts = $filter('filter')($rootScope.parameters("project_concepts"), { code: $scope.filters.type.value });
        var adminConcept = $filter('filter')($rootScope.parameters("project_concepts"), { value: 'PCOSGA' });

        $scope.conceptList = relationshipConcepts.concat(adminConcept);
        $scope.classificationList = [];
    };


    $scope.onChangeConcept = function () {
        $scope.classificationList = $filter('filter')($rootScope.parameters("project_classifications"), { code: $scope.filters.concept.value });
    };


    $scope.onChangePerformanceLevelFilter = function () {
        $scope.reloadData();
    };


    $scope.onSearchCustomer = function () {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
            placement: 'right',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideDashboardTopManagementSearchCustomerCtrl',
            scope: $scope,
            windowTopClass: 'top-modal',
        });

        modalInstance.result.then(function (customer) {
            // if it doesn't exist, add it
            var result = $filter('filter')($scope.customerList, { id: customer.id });
            if (result.length == 0) {
                $scope.customerList.push(customer);
            }

            $scope.filters.customer = customer;

            $scope.filters.administrator = null;
            $scope.administratorList = [];
        });
    };

    $scope.onClearCustomer = function () {
        $scope.filters.customer = null;
    };


    $scope.onSearchAdministrator = function () {
        if (!$scope.filters.customer) {
            SweetAlert.swal("Cliente requerido", "Debes indicar el cliente.", "warning");
            return;
        }

        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
            placement: 'right',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideDashboardTopManagementSearchAdministratorCtrl',
            scope: $scope,
            windowTopClass: 'top-modal',
            resolve: {
                customerId: $scope.filters.customer
            }
        });
        modalInstance.result.then(function (administrator) {
            var result = $filter('filter')($scope.administratorList, { id: administrator.id });

            if (result.length == 0) {
                $scope.administratorList.push(administrator);
            }

            $scope.filters.administrator = administrator;
        });
    };


    $scope.onClearAdministrator = function () {
        $scope.filters.administrator = null;
    };




    $scope.onOpenGridHisotricalSales = function () {
        $scope.isOpenGridHistoricalSales = !$scope.isOpenGridHistoricalSales;
        $scope.dtInstanceSalesHistoricalGrid.reloadData();
    };

    $scope.onOpenGridProgrammedVsExecutedSales = function () {
        $scope.isOpenGridProgrammedVsExecutedSales = !$scope.isOpenGridProgrammedVsExecutedSales;
        console.log('asdfasdf')
        $scope.dtInstanceTopManagementProgrammedVsExecutedSales.reloadData();
    };

    $scope.onOpenGridTotalSales = function () {
        $scope.isOpenGridTotalSales = !$scope.isOpenGridTotalSales;
        $scope.dtInstanceTopManagementTotalSales.reloadData();
    };

    $scope.onOpenGridSalesByType = function () {
        $scope.isOpenGridSalesByType = !$scope.isOpenGridSalesByType;
        $scope.dtInstanceTopManagementSalesByType.reloadData();
    };

    $scope.onOpenGridSalesByConcept = function () {
        $scope.isOpenGridSalesByConcept = !$scope.isOpenGridSalesByConcept;
        $scope.dtInstanceTopManagementSalesByConcept.reloadData();
    };

    $scope.onOpenGridSalesByClassification = function () {
        $scope.isOpenGridSalesByClassification = !$scope.isOpenGridSalesByClassification;
        $scope.dtInstanceTopManagementSalesByClassification.reloadData();
    };

    $scope.onOpenGridExperiencesByMonths = function () {
        $scope.isOpenGridExperiencesByMonths = !$scope.isOpenGridExperiencesByMonths;
        $scope.dtInstanceTopManagementExperienesByMonths.reloadData();
    };

    $scope.onOpenGridSatisfactionByExperience = function () {
        $scope.isOpenGridSatisfactionByExperience = !$scope.isOpenGridSatisfactionByExperience;
        if ($scope.santisfactionAnswerTypes) {
            var veryGood = $scope.santisfactionAnswerTypes.veryGood ? $scope.santisfactionAnswerTypes.veryGood.answer : 'Excelente';
            var good = $scope.santisfactionAnswerTypes.good ? $scope.santisfactionAnswerTypes.good.answer : 'Bueno';
            var regular = $scope.santisfactionAnswerTypes.regular ? $scope.santisfactionAnswerTypes.regular.answer : 'Regular';
            var bad = $scope.santisfactionAnswerTypes.bad ? $scope.santisfactionAnswerTypes.bad.answer : 'Malo';
            var veryBad = $scope.santisfactionAnswerTypes.veryBad ? $scope.santisfactionAnswerTypes.veryBad.answer : 'Muy Malo';
            $scope.dtInstanceTopManagementAmountBySatisfactionLevel.DataTable.column(2).header().innerHTML = veryGood;
            $scope.dtInstanceTopManagementAmountBySatisfactionLevel.DataTable.column(3).header().innerHTML = good;
            $scope.dtInstanceTopManagementAmountBySatisfactionLevel.DataTable.column(4).header().innerHTML = regular;
            $scope.dtInstanceTopManagementAmountBySatisfactionLevel.DataTable.column(5).header().innerHTML = bad;
            //$scope.dtInstanceTopManagementAmountBySatisfactionLevel.DataTable.column(6).header().innerHTML = veryBad;
        }
        $scope.dtInstanceTopManagementAmountBySatisfactionLevel.reloadData();
    };

    $scope.onOpenGridRegisteredVsParticipants = function () {
        $scope.isOpenGridRegisteredVsParticipants = !$scope.isOpenGridRegisteredVsParticipants;
        $scope.dtInstanceTopManagementRegisteredVsParticipants.reloadData();
    };

    $scope.onOpenGridPerformanceByConsultant = function () {
        $scope.isOpenGridPerformanceByConsultant = !$scope.isOpenGridPerformanceByConsultant;
        $scope.dtInstanceTopManagementPerformanceByConsultant.reloadData();
    };



    $scope.clearFilterPerformanceLevel = function () {
        $scope.filter.performanceLevel = null;
        $scope.reloadData();
    };


    $scope.refreshAll = function () {
        $scope.hideGrids();
        getCharts();

        if ($scope.showIndicators) {
            getSummaryCharts();
            $scope.reloadData();
        }
    };

    $scope.onChangePeriod = function () {
        getCharts();
    }


    function getList() {
        var entities = [
            { name: 'export_url', value: null },
            { name: 'dashboard_top_management_periods', value: null },
            { name: 'customer_vr_employee_satisfactions_answers_types', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.exportUrl = response.data.data.exportUrl.item;
                $scope.periodList = response.data.data.dashboardTopManagementPeriods;
                $scope.santisfactionAnswerTypes = response.data.data.customerVrEmployeeSatisfactionsAnswersTypes;
                if ($scope.periodList.length > 0) {
                    $scope.filter.period = $scope.periodList[0];
                    getCharts();
                }
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    function getCharts() {
        var $criteria = {
            period: $scope.filter.period ? $scope.filter.period.value : -1
        }
        var entities = [
            { name: 'chart_line_options', criteria: null },
            { name: 'chart_bar_options', criteria: null },
            { name: 'chart_bar_with_scales_options', criteria: null },
            { name: 'chart_pie_options', criteria: null },
            { name: 'dashboard_top_management_cost_historical', criteria: $criteria },
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                // Graphics Bar Settings
                $scope.chart.line.options = angular.copy(response.data.data.chartLineOptions);
                $scope.chart.line.options.legend.position = 'bottom';
                $scope.chart.line.options.scaleShowValues = true;
                $scope.chart.line.options.scales = {
                    xAxes: [{
                        ticks: {
                            autoSkip: false
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }

                $scope.chart.lineFormatMoney.options = angular.copy(response.data.data.chartLineOptions);
                $scope.chart.lineFormatMoney.options.legend.position = 'bottom';
                $scope.chart.lineFormatMoney.options.scales.yAxes = [{
                    ticks: {
                        callback: function (value) {
                            return "$ " + $filter('number')(value, 0);
                        }
                    }
                }];

                $scope.chart.lineFormatMoney.options.tooltips = {
                    callbacks: {
                        label: function (tooltipItem, data) {
                            var formattedValue = $filter('number')(tooltipItem.yLabel, 2);
                            var serie = data.datasets[tooltipItem.datasetIndex].label;
                            return serie + ":  $ " + formattedValue;
                        }
                    }
                };


                $scope.chart.bar.options = angular.copy(response.data.data.chartBarOptions);
                $scope.chart.bar.options.legend.position = 'bottom';

                $scope.chart.barFormatMoney.options = angular.copy(response.data.data.chartBarOptions);
                $scope.chart.barFormatMoney.options.legend.position = 'bottom';

                $scope.chart.barFormatMoney.options.scales.yAxes = [{
                    ticks: {
                        autoSkip: false,
                        min: 0,
                        callback: function (value) {
                            return "$ " + $filter('number')(value, 0);
                        }
                    }
                }];

                $scope.chart.barFormatMoney.options.tooltips = {
                    callbacks: {
                        label: function (tooltipItem, data) {
                            var formattedValue = $filter('number')(tooltipItem.yLabel, 2);
                            var serie = data.datasets[tooltipItem.datasetIndex].label;
                            return serie + ":  $ " + formattedValue;
                        }
                    }
                };


                $scope.chart.barWithScales.options = angular.copy(response.data.data.chartBarOptionsWithScales);


                $scope.chart.barWithScalesMoney.options = angular.copy(response.data.data.chartBarOptionsWithScales);
                $scope.chart.barWithScalesMoney.options.legend.display = false;
                $scope.chart.barWithScalesMoney.options.scales.yAxes = [{
                    ticks: {
                        callback: function (value) {
                            return "$ " + $filter('number')(value, 0);
                        }
                    }
                }]
                $scope.chart.barWithScalesMoney.options.tooltips = {
                    callbacks: {
                        title: function (tooltipItem, data) {
                            var datasetIndex = tooltipItem[0].datasetIndex;
                            return data.datasets[datasetIndex].stack;
                        },
                        label: function (tooltipItem, data) {

                            var formattedValue = $filter('number')(tooltipItem.yLabel, 2);
                            var serie = data.datasets[tooltipItem.datasetIndex].label;

                            if (serie == "Ejecutado") {

                                // sum all value from same stack
                                var stack = data.datasets[tooltipItem.datasetIndex].stack;

                                var programmed = 0;
                                $filter('filter')(data.datasets, { stack: stack }).forEach(function (row) {
                                    row.data.forEach(function (x) {
                                        programmed += parseFloat(x);
                                    })
                                });

                                var formattedProgrammed = $filter('number')(programmed, 2);
                                return serie + ":  $ " + formattedValue + " Programado: " + formattedProgrammed;

                            } else {
                                return serie + ":  $ " + formattedValue;
                            }
                        }
                    }
                };


                $scope.chart.pie.options = response.data.data.chartPieOptions;
                $scope.chart.pie.options.legend.position = 'bottom';


                $scope.chart.data.dashboardTopManagementCostHistorical = response.data.data.dashboardTopManagementCostHistorical;
                $scope.chart.data.dashboardTopManagementCostTotalCurrentYear = response.data.data.dashboardTopManagementCostTotalCurrentYear;
                $scope.chart.data.dashboardTopManagementCostTypesByStates = response.data.data.dashboardTopManagementCostTypesByStates;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function getSummaryCharts() {
        var entities = [
            { name: 'dashboard_top_management_cost_summary', criteria: $scope.filters },
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.data.dashboardTopManagementCostByType = response.data.data.dashboardTopManagementCostByType;
                $scope.chart.data.dashboardTopManagementCostByConcept = response.data.data.dashboardTopManagementCostByConcept;
                $scope.chart.data.dashboardTopManagementCostByClassification = response.data.data.dashboardTopManagementCostByClassification;
                $scope.chart.data.dashboardTopManagementCostByMonths = response.data.data.dashboardTopManagementCostByMonths;

                $scope.chart.data.dashboardTopManagementExperiencesByMoths = response.data.data.dashboardTopManagementExperiencesByMoths;
                $scope.chart.data.dashboardTopManagementSatisfactionByExperience = response.data.data.dashboardTopManagementSatisfactionByExperience;
                $scope.chart.data.registeredVsParticipantsAllClientsAndPeriods = response.data.data.registeredVsParticipantsAllClientsAndPeriods;
                $scope.chart.data.registeredVsParticipantsByMonths = response.data.data.registeredVsParticipantsByMonths;

                $scope.chart.data.dashboardTopManagementPerformanceByConcultant = response.data.data.dashboardTopManagementPerformanceByConcultant;



            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function appyFilter(d) {
        d.startDate = $scope.filters.startDate;
        d.endDate = $scope.filters.endDate;
        d.type = $scope.filters.type ? $scope.filters.type.value : null;
        d.concept = $scope.filters.concept ? $scope.filters.concept.value : null;
        d.classification = $scope.filters.classification ? $scope.filters.classification.value : null;
        d.customer = $scope.filters.customer ? $scope.filters.customer.id : null;
        d.administrator = $scope.filters.administrator ? $scope.filters.administrator.id : null;
        return d;
    }


});
