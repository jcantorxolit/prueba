'use strict';
/**
 * controller for Customers
 */
app.controller('positivaFgnIndicatorCoverageAxisCtrl',
    function($scope, DTOptionsBuilder, DTColumnBuilder, $compile, $localStorage, $state, $rootScope, $http, SweetAlert, $aside, PositivaFGNIndicatorFilterService) {

        $scope.entity = {
            userId: $rootScope.$id,
            selectedYear: null,
            periodChart: null
        };

        var initialize = function() {
            $scope.entity = {
                period: null,
                sectional: null,
                axis: null
            };
        };

        initialize();

        var storeDatatable = 'positivaFgnIndicatorsCoverageAxis-' + window.currentUser.id;
        $scope.dtInstanceIndicatorsCoverageAxisCallback = function(instance) {
            $scope.dtInstanceIndicatorsCoverageAxis = instance;
        };
        $scope.dtOptionsIndicatorsCoverageAxis = DTOptionsBuilder.newOptions()
            .withOption('searching', false)
            .withOption('ordering', false)
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.customFilter = getFilters();
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-indicator/indicators/reports/activities-consolidated-compliance',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function(settings, data) {
                $localStorage[storeDatatable] = data;
            })
            .withOption('stateLoadCallback', function() {
                return $localStorage[storeDatatable];
            })
            .withOption('order', [])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function(row) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsIndicatorsCoverageAxis = [
            DTColumnBuilder.newColumn('activity').withTitle("Actividad").withOption('width', 200),
            DTColumnBuilder.newColumn('axis').withTitle("Eje").withOption('width', 200),
            DTColumnBuilder.newColumn('meta_coverage').withTitle("Meta").withOption('width', 200),
            DTColumnBuilder.newColumn('countPopulation').withTitle("N° Participantes").withOption('width', 200),
            DTColumnBuilder.newColumn('percentCoverage').withTitle("Indicador Cobertura").withOption('width', 120)
            .renderWith(function(data) {
                var type;
                if (data < 75) {
                    type = "danger";
                } else if (data >= 75 && data < 90) {
                    type = "warning";
                } else if (data >= 90 && data <= 100) {
                    type = "success";
                } else {
                    type = "info";
                }
                return '<uib-progressbar class="border-progress-' + type + '" value="' + data + '" max="100" type="' + type + '" title="' + data + '">' +
                        '<span style="color: black !important;">' + data + '%</span>' +
                    '</uib-progressbar>';
            }),
        ];

        $scope.reloadData = function() {
            $scope.dtInstanceIndicatorsCoverageAxis.reloadData();
        };

        $scope.onExportExcel = function() {
            var data = {
                filters: getFilters()
            }

            jQuery("#downloadDocument")[0].src = "api/positiva-fgn-fgn-indicator/indicators/reports/activities-failed-coverage-axis-export?data=" + Base64.encode(JSON.stringify(data));
        }

        $scope.onConsolidate = function() {
            $http({
                method: 'POST',
                url: 'api/positiva-fgn-fgn-indicator/consolidated',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            }).then(function (response) {
            }).catch(function () {
                SweetAlert.swal("Error", "Ocurrió un problema cargar la información.", "error");
            });
        }

        function getFilters() {
            return {
                regionals: PositivaFGNIndicatorFilterService.filteredRegionals(),
                sectionals: PositivaFGNIndicatorFilterService.filteredSectionals(),
                periods: PositivaFGNIndicatorFilterService.getPeriods(),
                groups: PositivaFGNIndicatorFilterService.filteredGroups(),
                axis: PositivaFGNIndicatorFilterService.filteredAxis(),
                typeIndicator: 'coverage',
                goalAnnual: '1',
                provideCoverage: true
            }
        }

        //Se define la función que da apertura al Modal de los filtros
        $scope.onFilter = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/indicators/reports/filters/reports_filter_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'md',
                backdrop: true,
                controller: 'positivaFgnIndicatorsFiltersCtrlModalInstanceSide',
                scope: $scope,
                resolve: {
                    dataSource: {
                        filters: {
                            division: true,
                            periods: true,
                            groups: true,
                            axis: true
                        }
                    }
                }
            });

            modalInstance.result.then(function() {
                $scope.reloadData();
            });
        }

        //Botón que regresa a la página principal del listado de reportes o indicadores
        $scope.onBack = function() {
            $state.go("app.positiva-fgn.fgn-indicators");
        }

    });
