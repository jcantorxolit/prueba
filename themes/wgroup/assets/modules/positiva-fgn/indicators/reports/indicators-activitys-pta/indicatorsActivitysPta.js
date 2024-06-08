'use strict';
/**
 * controller for Customers
 */
app.controller('positivaFgnIndicatorsActivitysPTACtrl',
    function($scope, DTOptionsBuilder, DTColumnBuilder, $compile, $state, $rootScope, $http, SweetAlert, $aside, PositivaFGNIndicatorFilterService) {

        var initialize = function() {
            $scope.entity = {
                userId: $rootScope.$id,
                period: null,
                sectional: null,
                axis: null
            };
        };

        initialize();
        loadAxisIndicators();

        $scope.periodList = [];
        $scope.axisList = [];

        $scope.options = {
            readOnly: true,
            displayPrevious: true,
            barCap: 25,
            trackWidth: 20,
            barWidth: 20,
            trackColor: 'rgba(92,184,92,.1)',
            barColor: '#5BC01E',
            textColor: '#000'
        };


        $scope.dtInstanceActivitysPTACallback = function(instance) {
            $scope.dtInstanceActivitysPTACallback = instance;
        };

        $scope.dtInstanceActivitysPTACallback = {};
        $scope.dtOptionsActivitysPTA = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('searching', false)
            .withOption('ordering', false)
            .withOption('ajax', {
                data: function(d) {
                    d.customFilter = getFilters();
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-indicator/indicators/reports/activities-pta-compliance',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('fnDrawCallback', function() {
                loadRowCoverage();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsActivitysPTA = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var view = '<a class="btn btn-success btn-xs viewIndicator lnk" href="#" uib-tooltip="Continuar" data-id="' + data.id + '">' +
                                '<i class="fa fa-play-circle"></i>' +
                            '</a>';
                actions += view;
                return actions;
            }),
            DTColumnBuilder.newColumn('axis').withTitle("Eje").withOption('width', 200),
            DTColumnBuilder.newColumn('goalCompliance').withTitle("Meta Cump.").withOption('width', 200),
            DTColumnBuilder.newColumn('countActivities').withTitle("N° Actividades").withOption('width', 200),
            DTColumnBuilder.newColumn('percentCompliance').withTitle("Indicador Cumplimiento").withOption('width', 120).notSortable()
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
            DTColumnBuilder.newColumn('goalCoverage').withTitle("Meta Cob.").withOption('width', 200),
            DTColumnBuilder.newColumn('countPopulation').withTitle("N° Población").withOption('width', 200),
            DTColumnBuilder.newColumn('percentCoverage').withTitle("Indicador Cobertura").withOption('width', 120).notSortable()
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

        var loadRowCoverage = function() {
            $("#dtActivityPTA a.viewIndicator").on("click", function() {
                var id = $(this).data("id");
                $scope.showDetail(id)
            });
        };

        $scope.reloadDataActivitysPTA = function() {
            $scope.dtInstanceActivitysPTACallback.reloadData();
        };

        $scope.onExportExcel = function() {
            var data = {
                indicator: getCurrentTab(),
                filters: getFilters()
            }

            jQuery("#downloadDocument")[0].src = "api/positiva-fgn-fgn-indicator/indicators/reports/activities-pta-compliance-export?data=" + Base64.encode(JSON.stringify(data));
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


        function loadAxisIndicators(filters) {
            if (!filters) {
                filters = {};
            }

            var req = {
                data: Base64.encode(JSON.stringify(filters))
            };
            $http({
                method: 'POST',
                url: 'api/positiva-fgn-fgn-indicator/indicators/reports/activities-pta-compliance-axis',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $scope.axisList = response.data.result;
            }).catch(function(e) {
                SweetAlert.swal("Error", "Ocurrió un problema cargar la información.", "error");
            });
        }


        function getFilters() {
            return {
                regionals: PositivaFGNIndicatorFilterService.filteredRegionals(),
                sectionals: PositivaFGNIndicatorFilterService.filteredSectionals(),
                periods: PositivaFGNIndicatorFilterService.getPeriods(),
                groups: PositivaFGNIndicatorFilterService.filteredGroups(),
                axis: PositivaFGNIndicatorFilterService.filteredAxis()
            }
        }

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

            modalInstance.result.then(function(response) {
                var filters = getFilters();
                loadAxisIndicators(filters);

                $scope.reloadDataActivitysPTA();
            });
        }

        $scope.onBack = function() {
            $state.go("app.positiva-fgn.fgn-indicators");
        }

        $scope.showDetail = function(axis) {
            var currentTab = getCurrentTab();

            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/indicators/reports/indicators-activitys-pta/details_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'positivaFgnIndicatorsActivityPTADetailsCtrlModalInstanceSide',
                scope: $scope,
                resolve: {
                    dataSource: function() {
                        return {
                            axis: axis,
                            filters: getFilters(),
                            indicator: currentTab
                        };
                    }
                }
            });

            modalInstance.result.then();
        }

        function getCurrentTab() {
            var currentTab = angular.element("li.uib-tab.nav-item.ng-scope.ng-isolate-scope.active").attr("index");
            if (currentTab === 1) {
                return "compliance";
            } else {
                return "coverage";
            }
        }

    });
