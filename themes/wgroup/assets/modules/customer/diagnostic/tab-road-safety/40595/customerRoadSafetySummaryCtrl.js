'use strict';
/**
 * controller for Customers
 */
app.controller('customerRoadSafetySummary40595Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ChartService',
    '$uibModal', '$document',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, ChartService, $uibModal, $document) {

        var log = $log;

        $scope.canShowBody = false;
        $scope.isView = $scope.$parent.editMode == 'view';
        $scope.currentId = 0;
        $scope.misionallyty = null;
        $scope.misionallitySize = null;

        $scope.chart = {
            bar: { options: null },
            doughnut: { options: null },
            programs: { data: null },
            progress: {
                data: null,
                total: 0
            }
        };

        function getCharts() {
            if ($scope.currentId > 0) {
                var $criteria = {
                    customerId: $stateParams.customerId,
                    customerRoadSafetyId: $scope.currentId
                };

                var entities = [
                    { name: 'chart_bar_options', criteria: null },
                    { name: 'chart_doughnut_options', criteria: null },
                    { name: 'customer_road_safety_40595', criteria: $criteria }
                ];

                ChartService.getDataChart(entities)
                    .then(function (response) {
                        $scope.chart.bar.options = response.data.data.chartBarOptions;
                        $scope.chart.doughnut.options = response.data.data.chartDoughnutOptions;
                        $scope.chart.programs.data = response.data.data.customerRoadSafetyCycle;
                        $scope.chart.progress.data = response.data.data.customerRoadSafetyProgress;
                        $scope.chart.progress.total = response.data.data.customerRoadSafetyAverage;

                        //$scope.currentId = response.data.data.customerRoadSafetyId;
                        //$scope.$parent.currentId = response.data.data.customerRoadSafetyId;
                    }, function (error) {
                        $scope.status = 'Unable to load customer data: ' + error.message;
                    });
            }
        }

        var parseMisionallitySize = function(size) {
            var sizes = size.split(' ');
            return sizes.length > 0 ? sizes[0] : null;
        }

        var settingRoadSafety = function() {
            $scope.currentId = $scope.entity.id;
            $scope.misionallyty = $scope.entity.misionallity == 'M1' ? 'Misionalidad 1' : 'Misionalidad 2';
            $scope.misionallitySize = parseMisionallitySize($scope.entity.size.item);
            $scope.canShowBody = true;
            getCharts();
            $scope.reloadData();
        }

        var initialize = function () {
            $scope.entity = {
                id: 0,
                customerId: $stateParams.customerId,
                misionallity: null,
                companySize: null
            };

            $http({
                method: 'GET',
                url: 'api/customer-road-safety-40595/find',
                params: { id: $scope.entity.customerId }
            }).catch(function (e, code) {

            }).then(function (response) {
                $timeout(function () {
                    $scope.entity = response.data.result;
                    if ($scope.entity) {
                        settingRoadSafety();
                    } else {
                        $scope.onOpenConfigModal();
                    }
                });
            }).finally(function () {
                $timeout(function () {
                    $scope.loading = false;
                }, 400);

                $timeout(function () {
                    $document.scrollTop(40, 2000);
                });
            });
        }

        initialize();



        $scope.dtOptionsRoadSafetySummary40595 = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    d.customerRoadSafetyId = $scope.currentId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-road-safety-40595-summary',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                },
                complete: function (data) {
                    getCharts();
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

        $scope.dtColumnsRoadSafetySummary40595 = [
            DTColumnBuilder.newColumn('name')
                .withTitle("Ciclo")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('description')
                .withTitle("Parámetro - Definición")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('items')
                .withTitle("Variables")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('checked')
                .withTitle("Evaluados")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('advance')
                .withTitle("Avance Ciclo (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('total')
                .withTitle("Valoración Variables (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn(null)
                .withTitle("Estado")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Sin Iniciar';

                    if (parseInt(data.checked) == parseInt(data.items)) {
                        text = 'Completado';
                        label = 'label label-info';
                    }
                    else if (parseInt(data.checked) > 0) {
                        text = 'Iniciado';
                        label = 'label label-success';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
                .notSortable()
        ];


        $scope.dtInstanceRoadSafetySummary40595Callback = function (instance) {
            $scope.dtInstanceRoadSafetySummary40595 = instance;
        };

        $scope.reloadData = function () {
            if ($scope.dtInstanceRoadSafetySummary40595) {
                $scope.dtInstanceRoadSafetySummary40595.reloadData();
            }
        };

        $scope.onContinue = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", $scope.currentId);
            }
        };

        $scope.onImport = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("import", "import", $scope.currentId);
            }
        };

        $scope.onAttachment = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("attachment", "attachment", $scope.currentId);
            }
        };

        $scope.onViewReport = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("report", "report", $scope.currentId);
            }
        };

        $scope.onViewMonthlyReport = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("monthlyReport", "monthlyReport", $scope.currentId);
            }
        };

        $scope.onExportPdf = function () {
            $timeout(function () {
                kendo.drawing.drawDOM($(".minimun-standard-40595-export-pdf"))
                    .then(function (group) {
                        // Render the result as a PDF file
                        return kendo.drawing.exportPDF(group, {
                            paperSize: "auto",
                            margin: { left: "1cm", top: "1cm", right: "1cm", bottom: "1cm" }
                        });
                    })
                    .done(function (data) {
                        // Save the PDF file
                        kendo.saveAs({
                            dataURI: data,
                            fileName: "PESV_40595_" + $scope.entity.misionallity + "_" + $scope.entity.size.item  + ".pdf",
                            proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                        });
                    });
            }, 200);
        }

        $scope.onExportExcel = function () {
            var data = JSON.stringify({
                customerId: $stateParams.customerId,
                customerRoadSafetyId: $scope.currentId,
            });

            angular.element("#download")[0].src = "api/customer-road-safety-40595-summary/export-excel?data=" + Base64.encode(data);
        }

        $scope.onReportExportPdf = function () {
            var data = JSON.stringify({
                customerId: $stateParams.customerId,
                customerRoadSafetyId: $scope.currentId,
            });

            angular.element("#download")[0].src = "api/customer-road-safety-40595/export-pdf?data=" + Base64.encode(data);
        }

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", 0);
            }
        }

        $scope.onOpenConfigModal = function () {
            var modalInstance = $uibModal.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/diagnostic/tab-road-safety/40595/customer_safety_road_config_modal.htm",
                placement: 'right',
                size: 'lg',
                //backdrop: true,
                backdrop: 'static',
                controller: 'ModalInstanceSideCustomerRoadSafetyConfig40595Ctrl',
                scope: $scope,
                keyboard: false, // ESC key close enable/disable
                resolve: {
                    entity: function () {
                        return $scope.entity;
                    },
                    isView: function() {
                        return $scope.isView;
                    }
                }
            });

            modalInstance.result.then(function (entity) {
                if (entity.isSaved) {
                    $scope.entity = entity;
                    settingRoadSafety()
                }
            }, function () {

            });
        }
    }]
);

app.controller('ModalInstanceSideCustomerRoadSafetyConfig40595Ctrl', function ($stateParams, $rootScope, $scope, entity, isView, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, $compile, ListService) {

    var isCustomer = $rootScope.isCustomer();
    var isAgent = $rootScope.isAgent();
    var $currentSize = null;

    $scope.isView = isCustomer || isAgent ? false : isView;

    $scope.originalCompanySizeList = [];
    $scope.companySizeList = [];

    var initialize = function () {
        $scope.entity = entity ? entity : {
            id: 0,
            customerId: $stateParams.customerId,
            misionallity: null,
            companySize: null
        };

        $currentSize = $scope.entity.size;
    }

    initialize();

    getList();

    function getList() {
        var entities = [
            {
                name: 'wg_customer_road_safety_company_size',
                criteria: {
                    customerId:  $stateParams.customerId
                }
            }
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.originalCompanySizeList = response.data.data.wg_customer_road_safety_company_size;
                $scope.onChangeMisionallity(false);
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.onContinue = function () {
        $scope.entity.size = $scope.entity.isSaved ? $scope.entity.size : $currentSize;
        if (!$scope.entity.isSaved) {
            toaster.pop('warning', 'Advertencia', 'No se guardó la configuración. No se generaron cambios');
            $scope.entity.size =  $currentSize;
        }
        $uibModalInstance.close($scope.entity);
    };

    $scope.onClear = function () {
        initialize();
    }

    $scope.onChangeMisionallity = function(resetSize) {
        if ($scope.entity && $scope.entity.misionallity) {
            $scope.companySizeList = $scope.originalCompanySizeList.filter(function(item) {
                return item.code == $scope.entity.misionallity
            });
            if (resetSize) {
                $scope.entity.size = null;
            }
        }
    }

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

                $timeout(function () {
                    toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                }, 500);

                return;

            } else {
                $scope.onSave();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.onSave = function () {

        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-road-safety-40595/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.entity = response.data.result;
                $scope.entity.isSaved = true;
                toaster.pop('success', 'Operación Exitosa', 'Configuración exitosa.');
            });
        }).catch(function (e) {
            toaster.pop('Error', 'Error inesperado', e);
        }).finally(function () {

        });

    };

});
