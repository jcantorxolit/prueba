'use strict';
/**
 * controller for Customers
 */
app.controller('customerArlCtrl', ['$scope', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'ChartService', "ListService",
    function ($scope, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, ChartService, ListService) {

        var log = $log;

        $scope.currentUser = $rootScope.currentUser();
        $scope.isAgent = $scope.currentUser.wg_type == "agent" ? true : false;
        $scope.isAdmin = $scope.currentUser.wg_type == "system" ? true : false;
        $scope.isCustomerAdmin = $scope.currentUser.wg_type == "customerAdmin" ? true : false;
        $scope.isCustomerUser = $scope.currentUser.wg_type == "customerUser" ? true : false;

        $scope.filterYears = [];
        $scope.filterYears = [];
        $scope.services = [];
        $scope.filteredYear = null;
        $scope.filteredServiceYear = null;

        //$scope.canEdit = $scope.isCustomerAdmin || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
        $scope.canEdit = (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
        $rootScope.canEditRoot = $scope.canEdit;


        $scope.isView = $state.is("app.clientes.view");
        $scope.isCreate = $state.is("app.clientes.create");

        $scope.chart = {
            line: { options: null },
            doughnut: { options: null },
            data: {
                contributationsVsExecutions: null,
                contributationsVsExecutionsByMonth: null
            }
        };


        var init = function () {
            $scope.contribution = {
                id: 0,
                customerId: $scope.customer.id,
                input: "",
                year: new Date().getFullYear(),
                month: null,
                percentReinvestmentARL: 0,
                percentReinvestmentWG: 0,
                reinvestmentARL: 0,
                reinvestmentWG: 0,
                total: 0
            };
        }

        init();
        getLists();

        // Preparamos los parametros por grupos
        $scope.months = $rootScope.parameters("month");

        $scope.onLoadRecord = function () {
            if ($scope.contribution.id != 0) {
                var req = {
                    id: $scope.contribution.id
                };
                $http({
                    method: 'GET',
                    url: 'api/contribution',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Información no disponible", "Aporte no encontrado", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del aporte", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.contribution = response.data.result;
                            //loadReports();
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            $document.scrollTop(0, 2000);
                        });
                    });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        }


        $scope.master = $scope.contribution;
        $scope.form = {
            submit: function (form) {
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
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información del cliente...", "success");
                    save();
                }
            },
            reset: function (form) {
                $scope.customer = angular.copy($scope.master);
                form.$setPristine(true);
            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.contribution);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/contribution/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Validación exitosa", "Guardando información del aporte...", "success");
                    $scope.contribution = response.data.result;
                    getLists();
                    $scope.reloadData();
                    //loadReports();
                    $scope.clearContribution();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            });
        };

        $scope.cancelEdition = function (index) {
            if ($scope.isView) {
                $state.go('app.clientes.list');
            } else {
                SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Perderá todos los cambios realizados en este formulario.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, cancelar!",
                    cancelButtonText: "No, continuar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                    function (isConfirm) {
                        if (isConfirm) {
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };


        $scope.dtInstanceContribution = {};
        $scope.dtOptionsContribution = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customer_id = $scope.customer.id;
                    d.year = $scope.filteredYear.year;

                    return d;
                },
                url: 'api/contribution',
                type: 'POST',
                beforeSend: function () { },
                complete: function () { }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return $scope.filteredYear != null;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsContribution = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("clientes_edit") && $scope.canEdit) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_delete") && $scope.canEdit) {
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('year').withTitle("Año"),
            DTColumnBuilder.newColumn('month').withTitle("Mes"),
            DTColumnBuilder.newColumn('input').withTitle("Aporte").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
            DTColumnBuilder.newColumn('percent_reinvestment_arl').withTitle("% Comisión ARL"),
            DTColumnBuilder.newColumn('reinvestmentARL').withTitle("Comisión ARL").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
            DTColumnBuilder.newColumn('percent_reinvestment_wg').withTitle("% Reinversión WG"),
            DTColumnBuilder.newColumn('reinvestmentWG').withTitle("Reinversión WG").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
            DTColumnBuilder.newColumn('sales').withTitle("Ventas").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
            DTColumnBuilder.newColumn('balance').withTitle("Balance").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
        ];

        var loadRow = function () {

            angular.element("#dtCustomerContribution a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onEdit(id);
            });

            angular.element("#dtCustomerContribution a.delRow").on("click", function () {
                var id = angular.element(this).data("id");
                SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, eliminar!",
                    cancelButtonText: "No, continuar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                    function (isConfirm) {
                        if (isConfirm) {
                            var req = {};
                            req.id = id;
                            $http({
                                method: 'POST',
                                url: 'api/contribution/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {
                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });
        };


        $scope.dtInstanceBalance = {};
        $scope.dtOptionsBalance = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-contributions/get-general-balanace',
                type: 'POST'
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return $scope.filteredYear != null;
            })
            .withOption('fnDrawCallback', function () {
                loadRowDetails();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsBalance = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 100).notSortable()
                .renderWith(function (data) {
                    return '<a class="btn btn-primary btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-year="' + data.year + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';
                }),
            DTColumnBuilder.newColumn('year').withTitle("Año"),
            DTColumnBuilder.newColumn('previousBalance').withTitle("SALDO ANTERIOR").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
            DTColumnBuilder.newColumn('contributions').withTitle("APORTE ACUMULADO").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
            DTColumnBuilder.newColumn('commissions').withTitle("COMISIÓN ACUMULADA").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
            DTColumnBuilder.newColumn('reinvesments').withTitle("REINVERSIÓN ACUMULADA").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
            DTColumnBuilder.newColumn('sales').withTitle("VENTAS ACUMULADAS").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
            DTColumnBuilder.newColumn('balance').withTitle("BALANCE TOTAL").renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
        ];


        $scope.onEdit = function (id) {
            $scope.contribution.id = id;
            $scope.isView = false;
            $scope.onLoadRecord();
        };

        $scope.onChangeFilteredYear = function (model) {
            if (model) {
                $scope.filteredYear = model;
            }

            $scope.onRefresh();
        };

        $scope.onChangeFilteredServiceYear = function (model) {
            if (model) {
                $scope.filteredServiceYear = model;
            }

            $scope.reloadServiceData();
        };

        $scope.onRefresh = function () {
            getCharts();
            $scope.reloadData();
        };

        $scope.onChangeInput = function () {
            $scope.calculateReinvestmentARL();
            $scope.calculateReinvestmentWG();
        }

        $scope.onChangeReinvestmentARL = function () {
            $scope.calculateReinvestmentARL();
        };

        $scope.onChangeReinvestmentWG = function () {
            $scope.calculateReinvestmentWG();
        };

        $scope.calculateReinvestmentARL = function () {
            $scope.contribution.reinvestmentARL = ($scope.contribution.input * $scope.contribution.percentReinvestmentARL) / 100;
            $scope.calculateReinvestmentWG();
        };

        $scope.calculateReinvestmentWG = function () {
            $scope.contribution.reinvestmentWG = ($scope.contribution.reinvestmentARL * $scope.contribution.percentReinvestmentWG) / 100;
        };

        $scope.dtInstanceContributionCallback = function (instance) {
            $scope.dtInstanceContribution = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceContribution.reloadData();
            $scope.dtInstanceBalance.reloadData();
        };

        $scope.clearContribution = function () {
            init();
        };


        //-------------------------------------------------------CHART



        var loadReports = function () {

            var req = {};
            req.customer_id = $stateParams.customerId;
            req.year = $scope.currentYear.value;

            $http({
                method: 'GET',
                url: 'api/customer/report',
                params: req
            }).then(function (response) {

                $timeout(function () {
                    $scope.options.responsive = true;
                    $scope.data_rpt = response.data.result.report_contribution;
                    $scope.years = response.data.result.report_years;

                    $.each($scope.data_rpt.datasets, function (k, v) {
                        // rgb.replace(/[^\d,]/g, '').split(',');
                        var cl = 'rgb(' + v.fillColor.r + ',' + v.fillColor.g + ',' + v.fillColor.b + ')';
                        v.fillColor = cl;
                        v.highlightFill = cl;
                        v.highlightStroke = cl;
                    });

                    if ($scope.years.length == 0) {
                        $scope.years = [
                            {
                                id: "0",
                                item: "-- Seleccionar --",
                                value: "-S-"
                            }];
                    }
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });

        };


        var loadRowDetails = function () {
            angular.element("#dtCustomerBalance a.viewRow").on("click", function () {
                var year = angular.element(this).data("year");
                onOpenDetails(year);
            });
        };

        $scope.changeReportYear = function (item, model) {
            $scope.currentYear = item;
            $timeout(function () {
                loadReports();
            }, 3000);
        };


        function getLists() {
            var entities = [
                { name: 'customer_arl_years', criteria: { customerId: $stateParams.customerId } },
                { name: 'customer_arl_service_years', criteria: { customerId: $stateParams.customerId } },
                { name: 'customer_arl_service', criteria: { customerId: $stateParams.customerId } }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.filterYears = response.data.data.customerArlYears;
                    $scope.filterServiceYears = response.data.data.customerArlServiceYears;
                    $scope.services = response.data.data.customer_arl_service;

                    if ($scope.filterYears.length) {
                        $scope.filteredYear = $scope.filterYears[0];
                        $scope.onChangeFilteredYear();
                    }

                    if ($scope.filterServiceYears && $scope.filterServiceYears.length) {
                        $scope.filteredServiceYear = $scope.filterServiceYears[0];
                        $scope.onChangeFilteredServiceYear();
                    }
                }, function (error) {
                    $scope.status = "Unable to load customer data: " + error.message;
                });
        }


        function getCharts() {
            var entities = [
                { name: 'chart_line_options', criteria: null },
                { name: 'chart_doughnut_options', criteria: null },
                { name: 'customer_projects_arl_contributions', criteria: { customerId: $stateParams.customerId, year: $scope.filteredYear.year } },
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.doughnut.options = response.data.data.chartDoughnutOptions;
                    $scope.chart.doughnut.options.legend.position = 'top';
                    $scope.chart.doughnut.options.maintainAspectRatio = false;
                    $scope.chart.doughnut.options.responsive = false;

                    $scope.chart.line.options = angular.copy(response.data.data.chartLineOptions);
                    $scope.chart.line.options.scales.yAxes = [{
                        ticks: {
                            callback: function (value) {
                                return "$ " + $filter('number')(value, 0);
                            }
                        }
                    }];

                    $scope.chart.line.options.tooltips = {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                var formattedValue = $filter('number')(tooltipItem.yLabel, 2);
                                var serie = data.datasets[tooltipItem.datasetIndex].label;
                                return serie + ":  $ " + formattedValue;
                            }
                        }
                    };

                    $scope.chart.doughnut.options.tooltips = {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
                                var formattedValue = $filter('number')(value, 2);
                                var serie = data.labels[tooltipItem.index];
                                return serie + ":  $ " + formattedValue;
                            }
                        }
                    };

                    $scope.chart.data.contributationsVsExecutions = response.data.data.customerProjectsArlContributions;
                    $scope.chart.data.contributationsVsExecutionsByMonth = response.data.data.contributionsVsExec;

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        function onOpenDetails(year) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/arl/balance_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                controller: "customerContributionBalanceModalCtrl",
                scope: $scope,
                resolve: {
                    dataSource: {
                        year: year
                    }
                }
            });

            modalInstance.result.then();
        }


        //***************************************SERVICES / COST ******************************* */
        var $formInstance = null;

        var initService = function () {
            $scope.entity = {
                id: 0,
                customerId: $scope.customer.id,
                service: null,
                cost: 0
            };

            if ($formInstance != null) {
                $formInstance.$setPristine(true);
            }
        }

        initService();

        var onLoadServiceRecord = function (id) {
            var req = {
                id: id
            };
            $http({
                method: 'GET',
                url: 'api/customer-arl-service-cost/get',
                params: req
            })
                .catch(function (e, code) {
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Aporte no encontrado", "error");
                        $timeout(function () {
                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del aporte", "error");
                    }
                })
                .then(function (response) {
                    $timeout(function () {
                        $scope.entity = response.data.result;
                        if ($scope.entity.registrationDate) {
                            $scope.entity.registrationDate = new Date($scope.entity.registrationDate.date)
                        }
                    });
                }).finally(function () {

                });
        }



        $scope.formService = {
            submit: function (form) {
                $formInstance = form;

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
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                } else {
                    if (!$scope.entity.cost || $scope.entity.cost <= 0) {
                        SweetAlert.swal("Alerta", "El costo es requerido", "error");
                        return;
                    }
                    saveService();
                }
            },
            reset: function (form) {
                $scope.customer = angular.copy($scope.master);
                form.$setPristine(true);
            }
        };

        var saveService = function () {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-arl-service-cost/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Operación exitosa", "Registro agregado satisfactoriamente", "success");
                    $scope.reloadServiceData();
                    $scope.onClearService();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            });
        };

        $scope.dtOptionsServiceCost = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $scope.customer.id;
                    if ($scope.filteredServiceYear) {
                        d.year = $scope.filteredServiceYear.value;
                    }
                    return JSON.stringify(d);
                },
                url: 'api/customer-arl-service-cost',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () { },
                complete: function () { }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadServiceRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsServiceCost = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    if ($rootScope.can("clientes_arl_service_edit") && $scope.canEdit) {
                        actions += editTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('registrationDate').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('service').withTitle("Servicio").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('cost').withTitle("Costo").withOption('width', 200).renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
        ];

        var loadServiceRow = function () {

            angular.element("#dtCustomerServiceCost a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onEditService(id);
            });

            angular.element("#dtCustomerServiceCost a.delRow").on("click", function () {
                var id = angular.element(this).data("id");
                SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, eliminar!",
                    cancelButtonText: "No, continuar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                    function (isConfirm) {
                        if (isConfirm) {
                            var req = {};
                            req.id = id;
                            $http({
                                method: 'POST',
                                url: 'api/customer-arl-service-cost/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {
                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });
        };

        $scope.onEditService = function (id) {
            $scope.isView = false;
            onLoadServiceRecord(id);
        };

        $scope.dtInstanceServiceCostCallback = function (instance) {
            $scope.dtInstanceServiceCost = instance;
        };

        $scope.reloadServiceData = function () {
            $scope.dtInstanceServiceCost.reloadData();
        };

        $scope.onClearService = function () {
            initService();
        };

        $scope.onGenerateReport = function () {
            if (!$scope.filteredYear) {
                SweetAlert.swal("Error", "Debe seleccionar el periodo.", "error");
                return;
            }

            var param = {
                customerId: $stateParams.customerId,
                selectedYear: $scope.filteredYear ? $scope.filteredYear.year : null
            };
            angular.element("#downloadDocument")[0].src = "api/customer-contributions/generate-report-pdf?data=" + Base64.encode(JSON.stringify(param));
        }
    }]);
