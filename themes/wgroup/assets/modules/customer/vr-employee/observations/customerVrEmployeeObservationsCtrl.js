'use strict';
/**
  * controller for Customers
*/
app.controller('customerVrEmployeeObservationsCtrl',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ChartService, ListService) {


        $scope.entity = { selectedYear: null };
        $scope.periodList = [];
        $scope.observationList = [];
        $scope.chart = {
            bar: { options: null },
            doughnut: { options: null },
            genre: null,
            obsTypes: null,
            genreTotal: null,
        };

        function getChart() {

            var chart = [
                {
                    name: "customer_vr_employee_observations_charts",
                    criteria: { customerId: $stateParams.customerId, selectedYear: $scope.entity.selectedYear ? $scope.entity.selectedYear.value : null }
                },
                { name: "chart_bar_options" }
            ];

            ChartService.getDataChart(chart)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.genre = response.data.data.genre;
                    $scope.chart.obsTypes = response.data.data.obsTypes;
                    $scope.chart.genreTotal = response.data.data.genreTotal;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getList() {

            var list = [
                { name: 'customer_vr_employee_historical_period_observation_list', criteria: { customerId: $stateParams.customerId } },
                { name: 'customer_vr_employee_observation', criteria: { customerId: $stateParams.customerId } }
            ];

            ListService.getDataList(list)
                .then(function (response) {
                    $scope.periodList = response.data.data.periodList;
                    $scope.observationList = response.data.data.customer_vr_employee_observation;
                    if ($scope.periodList.length) {
                        $scope.entity.selectedYear = $scope.periodList[0];
                        getChart();
                        $scope.reloadDataTypes();
                        $scope.reloadDataDetails();
                    }

                    $scope.observationList.push({
                        "item": "Otra",
                        "value": "O"
                    });

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();

        $scope.onSelectMonth = function () {
            $scope.reloadDataTypes();
            $scope.reloadDataDetails();
            getChart();
        }

        $scope.onClearFilter = function () {
            $scope.entity.selectedYear = null;
            $scope.reloadDataTypes();
            $scope.reloadDataDetails();
            getChart();
        }

        $scope.dtInstanceVrEmployeeO = {};
        $scope.dtOptionsVrEmployeeO = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    if ($scope.entity.selectedYear) {
                        d.selectedYear = $scope.entity.selectedYear.value;
                    }
                    return JSON.stringify(d);
                },
                url: 'api/customer-vr-employee-experience-answer/observations-count',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function (instance) {
                    if (!$scope.entity.selectedYear) {
                        instance.abort(instance);
                    }
                },
                complete: function (response) { }
            })
            .withDataProp('data')
            .withOption('order', [[1, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                if ($scope.entity.selectedYear) {
                    return true;
                }
                return false;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsVrEmployeeO = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 40).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var planTemplate = '<a class="btn btn-warning btn-xs planRow lnk" href="#" uib-tooltip="Agregar Plan Mejoramiento" data-experience=' + data.experience + ' data-id=' + data.id + ' data-obstype=' + data.observationType + ' >' +
                        '   <i class="fa fa-plus-square"></i></a> ';

                    actions += planTemplate;
                    return actions;
                }),
            DTColumnBuilder.newColumn('experience').withTitle("Experiencia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observationType').withTitle("Tipo de Observación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('total').withTitle("Número de Observaciones").withOption('width', 200).withOption('defaultContent', '')
        ];

        var loadRow = function () {
            $("#dtVrEmployeeO a.planRow").on("click", function () {
                var id = $(this).data("id");
                var experience = $(this).data("experience");
                var obstype = $(this).data("obstype");
                var data = {
                    id: id,
                    experience: experience,
                    observationType: obstype
                }
                $scope.onAddPlan(data);
            });
        };

        $scope.dtInstanceVrEmployeeOCallback = function (instance) {
            $scope.dtInstanceVrEmployeeO = instance;
        };

        $scope.reloadDataTypes = function () {
            $scope.dtInstanceVrEmployeeO.reloadData();
        }

        $scope.onAddPlan = function (entity) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_improvement_plan_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideVrEmployeeImprovementPlanCtrl',
                scope: $scope,
                resolve: {
                    entity: function () {
                        return entity;
                    }
                }
            });
            modalInstance.result.then(function () {
                // $scope.reloadData();
            }, function () {
                // $scope.reloadData();
            });
        }


        // ***************************      OBSERVATION DETAIL *******************

        $scope.dtOptionsVrEmployeeOD = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    if ($scope.entity.selectedYear) {
                        d.selectedYear = $scope.entity.selectedYear.value;
                    }
                    return JSON.stringify(d);
                },
                url: 'api/customer-vr-employee-experience-answer/observations-detail',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function (instance) {
                    if (!$scope.entity.selectedYear) {
                        instance.abort(instance);
                    }
                },
                complete: function (response) { }
            })
            .withDataProp('data')
            .withOption('order', [[1, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                if ($scope.entity.selectedYear) {
                    return true;
                }
                return false;
            })
            .withOption('fnDrawCallback', function () { })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsVrEmployeeOD = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 40).notSortable()
                .renderWith(function (data, type, full, meta) {
                    return "";
                }),
            DTColumnBuilder.newColumn('date').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('experience').withTitle("Experiencia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observationType').withTitle("Tipo de Observación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observation').withTitle("Observación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('firstName').withTitle("Nombres").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200).withOption('defaultContent', ''),
        ];

        $scope.dtInstanceVrEmployeeODCallback = function (instance) {
            $scope.dtInstanceVrEmployeeOD = instance;
        };

        $scope.reloadDataDetails = function () {
            $scope.dtInstanceVrEmployeeOD.reloadData();
        }

        $scope.onExportExcel = function () {

            if (!$scope.entity.selectedYear) {
                SweetAlert.swal("Alerta", "No se puede generar el excel, no hay información de periodos", "error");
                return;
            }

            var param = {
                selectedYear: $scope.entity.selectedYear,
                customerId: $stateParams.customerId
            };

            angular.element("#downloadDocument")[0].src = "api/customer-vr-employee-experience-answer/observations-export?data=" + Base64.encode(JSON.stringify(param));
        }


        //***************************************GENERAL OBSERVATIONS ******************************* */
        var $formInstance = null;

        var init = function () {
            $scope.entity = {
                id: 0,
                customerId: $scope.customer.id,
                registrationDate: null,
                observation: null
            };

            if ($formInstance != null) {
                $formInstance.$setPristine(true);
            }
        }

        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        init();

        var onLoadRecord = function (id) {
            var req = {
                id: id
            };
            $http({
                method: 'GET',
                url: 'api/customer-vr-general-observation/get',
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



        $scope.form = {
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
                    if (!$scope.entity.registrationDate) {
                        SweetAlert.swal("Alerta", "La fecha es requerida", "error");
                        return;
                    }
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

            var $observation = $scope.entity.observation.value;
            var $isCustomObservation = false;

            if ($observation == "O") {
                $observation = $scope.entity.observationText;
                $isCustomObservation = true;
                
                if ($scope.observationList.some(function(item) {
                    return item.value.trim().toLowerCase() === $observation.trim().toLowerCase();
                })) {
                    SweetAlert.swal("El formulario contiene errores!", "La observación ingresada ya existe en la lista.", "error");
                    return;
                }
            }

            var $entity = {
                id: $scope.entity.id,
                customerId: $scope.customer.id,
                registrationDate: $scope.entity.registrationDate,
                observation: $observation,
                isCustomObservation: $isCustomObservation,
            }

            var data = JSON.stringify($entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-vr-general-observation/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Operación exitosa", "Registro agregado satisfactoriamente", "success");
                    $scope.entity = response.data.result;
                    if ($scope.entity.registrationDate) {
                        $scope.entity.registrationDate = new Date($scope.entity.registrationDate.date)
                    }
                    getList();
                    $scope.reloadData();
                    $scope.onCancelObservation();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            });
        };

        $scope.dtOptionsVrGeneralObservation = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $scope.customer.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-vr-general-observation',
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
                loadGeneralObservationRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsVrGeneralObservation = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("clientes_vr_general_observation_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_vr_general_observation_delete")) {
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
                DTColumnBuilder.newColumn('registrationDate').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
                DTColumnBuilder.newColumn('observation').withTitle("Observación").withOption('defaultContent', ''),
                DTColumnBuilder.newColumn('createdByUser').withTitle("Creado Por").withOption('width', 300).withOption('defaultContent', ''),
        ];

        var loadGeneralObservationRow = function () {

            angular.element("#dtCustomerVrGeneralObservation a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onEdit(id);
            });

            angular.element("#dtCustomerVrGeneralObservation a.delRow").on("click", function () {
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
                                url: 'api/customer-vr-general-observation/delete',
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

        $scope.onEdit = function (id) {
            $scope.isView = false;
            onLoadRecord(id);
        };

        $scope.dtInstanceVrGeneralObservationCallback = function (instance) {
            $scope.dtInstanceVrGeneralObservation = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceVrGeneralObservation.reloadData();
        };

        $scope.onCancelObservation = function () {
            init();
        };

    });


app.controller('ModalInstanceSideVrEmployeeImprovementPlanCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, entity,
    $log, $timeout, SweetAlert, $filter, FileUploader, $http, DTOptionsBuilder, DTColumnBuilder, $compile) {

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.responsibleList = [];

    $scope.typesAlert = $filter('orderBy')($rootScope.parameters("tracking_alert_type"), 'id', false);
    $scope.typesTime = $rootScope.parameters("tracking_alert_timeType");
    $scope.statusAlert = $rootScope.parameters("tracking_alert_status");
    $scope.preferencesAlert = $rootScope.parameters("tracking_alert_preference");
    $scope.typeList = $rootScope.parameters("improvement_plan_type");

    var init = function () {
        $scope.improvement = {
            id: 0,
            customerId: $stateParams.customerId,
            classificationName: entity.experience,
            classificationId: entity.experience,
            entityName: 'RVE',
            entityId: entity.id,
            type: null,
            endDate: null,
            description: entity.observationType,
            observation: '',
            responsible: null,
            isRequiresAnalysis: false,
            status: {
                id: 0,
                value: 'CR',
                item: 'Creada'
            },
            trackingList: [],
            alertList: []
        };
    }

    init();

    $scope.onLoadRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id
            };
            $http({
                method: 'GET',
                url: 'api/customer/improvement-plan',
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
                        SweetAlert.swal("Información no disponible", "Centro de trabajo no encontrado", "error");

                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del centro de trabajo", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        if (response.data.result != null && response.data.result != '') {
                            $scope.improvement = response.data.result;

                            initializeDates();
                        }
                    }, 400);

                }).finally(function () {

                });
        } else {
            $scope.loading = false;
        }
    }

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {

    }

    var loadList = function () {

        var req = {
            customer_id: $stateParams.customerId
        };

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan/list-data',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.responsibleList = response.data.data.responsible;
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    loadList();

    $scope.master = $scope.improvement;

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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                return;

            } else {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                //your code for submit

                save();
            }

        },
        reset: function (form) {

            $scope.improvement = angular.copy($scope.master);
            form.$setPristine(true);

        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.improvement);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                init();
            });
        }).catch(function (e) {

            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.reloadData();
        });
    };

    var initializeDates = function () {
        if ($scope.improvement.endDate != null) {
            $scope.improvement.endDate = new Date($scope.improvement.endDate.date);
        }

        angular.forEach($scope.improvement.trackingList, function (model, key) {
            if (model.startDate != null) {
                model.startDate = new Date(model.startDate.date);
            }
        });
    }

    //----------------------------------------------------------------TRACKING
    $scope.onAddTracking = function () {

        $timeout(function () {
            if ($scope.improvement.trackingList == null) {
                $scope.improvement.trackingList = [];
            }
            $scope.improvement.trackingList.push(
                {
                    id: 0,
                    customerImprovementPlanId: 0,
                    responsible: null,
                    startDate: null,
                }
            );
        });
    };

    $scope.onRemoveTracking = function (index) {
        SweetAlert.swal({
            title: "Está seguro?",
            text: "Desea confirmar la eliminación de este registro ?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Si, eliminar!",
            cancelButtonText: "No, cancelar!",
            closeOnConfirm: true,
            closeOnCancel: true
        },
            function (isConfirm) {
                if (isConfirm) {
                    $timeout(function () {
                        // eliminamos el registro en la posicion seleccionada
                        var date = $scope.improvement.trackingList[index];

                        $scope.improvement.trackingList.splice(index, 1);

                        if (date.id != 0) {
                            var req = {};
                            req.id = date.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/improvement-plan-tracking/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                            });
                        }
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    }

    //----------------------------------------------------------------VERIFICATION MODE
    $scope.onAddAlert = function () {

        $timeout(function () {
            if ($scope.improvement.alertList == null) {
                $scope.improvement.alertList = [];
            }
            $scope.improvement.alertList.push(
                {
                    id: 0,
                    customerImprovementPlanId: 0,
                    type: null,
                    preference: null,
                    time: 0,
                    timeType: null,
                    status: null,
                }
            );
        });
    };

    $scope.onRemoveAlert = function (index) {
        SweetAlert.swal({
            title: "Está seguro?",
            text: "Desea confirmar la eliminación de este registro ?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Si, eliminar!",
            cancelButtonText: "No, cancelar!",
            closeOnConfirm: true,
            closeOnCancel: true
        },
            function (isConfirm) {
                if (isConfirm) {
                    $timeout(function () {
                        // eliminamos el registro en la posicion seleccionada
                        var date = $scope.improvement.alertList[index];

                        $scope.improvement.alertList.splice(index, 1);

                        if (date.id != 0) {
                            var req = {};
                            req.id = date.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/improvement-plan-alert/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                            });
                        }
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    }


    //----------------------------------------------------------------IMPROVEMENT PLAN LIST
    $scope.dtInstanceImprovementPlan = {};
    $scope.dtOptionsImprovementPlan = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customerId = $scope.improvement.customerId;
                d.entityId = $scope.improvement.entityId;
                d.entityName = $scope.improvement.entityName;

                return JSON.stringify(d);
            },
            url: 'api/customer-improvement-plan-entity',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
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
            //log.info("fnDrawCallback");
            loadRow();
            //Pace.stop();

        })
        /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })

        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {

            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsImprovementPlan = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if ($rootScope.can('cliente_plan_mejoramiento_edit')) {
                    actions += editTemplate;
                }

                if ($rootScope.can('cliente_plan_mejoramiento_delete')) {
                    actions += deleteTemplate;
                }

                return !$scope.isView ? actions : null;
            }),
        DTColumnBuilder.newColumn('origin').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('description').withTitle("Hallazgo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('responsibleName').withTitle("Responsable").withOption('defaultContent', ''),
        DTColumnBuilder.newColumn(null).withTitle("Fecha Cierre").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                if (typeof data.endDate == 'object' && data.endDate != null) {
                    return moment(data.endDate.date).format('DD/MM/YYYY');
                }
                return data.endDate != null ? moment(data.endDate).format('DD/MM/YYYY') : '';
            }),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200).withOption('defaultContent', '')
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-success';
                var text = data.status;

                switch (data.statusCode) {
                    case "AB":
                        label = 'label label-info'
                        break;

                    case "CO":
                        label = 'label label-success'
                        break;

                    case "CA":
                        label = 'label label-danger'
                        break;
                }

                return '<span class="' + label + '">' + text + '</span>';
            })
    ];

    var loadRow = function () {

        $("#dtImprovementPlan a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.onLoadRecord(id);
        });

        $("#dtImprovementPlan a.delRow").on("click", function () {
            var id = $(this).data("id");

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
                            url: 'api/customer/improvement-plan/delete',
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

    $scope.dtInstanceImprovementPlanCallback = function (dtInstance) {
        $scope.dtInstanceImprovementPlan = dtInstance;
    }

    $scope.reloadData = function () {
        $scope.dtInstanceImprovementPlan.reloadData();
    };

});
