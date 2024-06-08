'use strict';
/**
 * controller for Customers
 */
app.controller('positivaFgnIndicatorsActivitySectionaCtrl',
    function($scope, $state, $rootScope, $http, SweetAlert, ListService, ModuleListService, ngNotify) {

        $scope.exportUrl = '';

        var initialize = function() {
            $scope.entity = {
                userId: $rootScope.$id,
                indicator: 'compliance'
            };
        };

        initialize();

        $scope.tipIndicatorList = [
            { name: "Cumplimiento", value: "compliance" },
            { name: "Cobertura", value: "coverage" }
        ]

        $scope.monthList = [
            { item: "Enero", value: "01" },
            { item: "Febrero", value: "02" },
            { item: "Marzo", value: "03" },
            { item: "Abril", value: "04" },
            { item: "Mayo", value: "05" },
            { item: "Junio", value: "06" },
            { item: "Julio", value: "07" },
            { item: "Agosto", value: "08" },
            { item: "Septiembre", value: "09" },
            { item: "Octubre", value: "10" },
            { item: "Noviembre", value: "11" },
            { item: "Diciembre", value: "12" },
        ];

        getList();

        function getList() {
            var entities = [
                { name: 'positiva_fgn_activity_group', value: null },
                { name: 'export_url', value: null }
            ];

            ListService.getDataList(entities)
                .then(function(response) {
                    $scope.axis = $rootScope.parameters("positiva_fgn_activity_axis");
                    $scope.groupList = response.data.data.groupList;
                    $scope.exportUrl = response.data.data.exportUrl.item;
                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getParams() {
            var entities = [
                { name: 'positiva_fgn_period_all', value: null },
            ];

            ModuleListService.getDataList("/positiva-fgn-fgn-management/config", entities)
                .then(function(response) {
                    $scope.yearList = response.data.result.positivaFgnPeriod;
                }, function(error) {
                    $scope.status = 'Unable to load activity data: ' + error.message;
                });
        }

        getParams();

        $scope.allAxis = function() {
            $scope.axis.map(function(item) {
                item.isActive = true;
            })
        }

        $scope.clearAxis = function() {
            $scope.axis.map(function(item) {
                item.isActive = false;
            })
        }

        $scope.allGroups = function() {
            $scope.groupList.map(function(item) {
                item.isActive = true;
            })
        }

        $scope.clearGroups = function() {
            $scope.groupList.map(function(item) {
                item.isActive = false;
            })
        }

        $scope.clearMonths = function() {
            $scope.monthList.map(function(item) {
                item.isActive = false;
            });
        }

        $scope.onAllMonths = function() {
            $scope.monthList.map(function(item) {
                item.isActive = true;
            })
        }

        // ***************  Filtered  *************
        var dataFactory = {}

        dataFactory.filteredTipIndicator = function() {
            var filtered = [];
            $scope.tipIndicatorList.map(function(item) {
                if (item.isActive) {
                    filtered.push(item.value);
                }
            });

            return filtered;
        }

        dataFactory.filteredAxis = function() {
            var filtered = [];
            $scope.axis.map(function(item) {
                if (item.isActive) {
                    filtered.push(item.value);
                }
            });

            return filtered;
        }

        dataFactory.getPeriods = function() {
            var year = $scope.yearList.find(function(item) {
                return item.isActive === item.period;
            })

            var months = $scope.monthList.filter(function(item) {
                return item.isActive === true;
            });

            if (!year || !months) {
                return [];
            }

            var periods = [];
            months.forEach(function(month) {
                periods.push(year.period.toString() + month.value);
            });

            return periods;
        }

        dataFactory.filteredGroups = function() {
            var filtered = [];
            $scope.groupList.map(function(item) {
                if (item.isActive) {
                    filtered.push(item.value);
                }
            });

            return filtered;
        }

        $scope.existsFilterByYear = function () {
            return $scope.yearList.some(function (year) {
                return year.isActive === year.period;
            });
        };

        $scope.onCheckMonth = function (month) {
            if (!$scope.existsFilterByYear()) {
                month.isActive = false;
            }
        };

        // Export to excel
        function getFilters() {
            return {
                tipIndicator: $scope.entity.indicator,
                axis: dataFactory.filteredAxis(),
                periods: dataFactory.getPeriods(),
                groups: dataFactory.filteredGroups(),
                userId: $scope.entity.userId
            }
        }

        $scope.onExportExcel = function() {
            ngNotify.set('<div class="row"><div class="col-sm-5"><div class="loader-spinner pull-right"></div> </div> <div class="col-sm-6 text-left">El reporte se está generando. Por favor espere!</div> </div>', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var param = {
                userId: $rootScope.currentUser().id,
                customFilter: getFilters()
            };
            var request = { data: Base64.encode(JSON.stringify(param)) };

            return $http({
                method: 'POST',
                url: $scope.exportUrl + "api/v1/positiva-fgn/indicator-activity-sectional-export",
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(request)
            }).then(function(response) {
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

            }).catch(function(response) {

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
        }

        //Consolidado
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

        //Botón que regresa a la página principal del listado de reportes o indicadores
        $scope.onBack = function() {
            $state.go("app.positiva-fgn.fgn-indicators");
        }
    });