'use strict';
/**
 * controller for Customers
 */
app.controller('customerConfigActivityListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$uibModal', '$http', 'SweetAlert',
    '$document', '$aside', '$location', '$translate',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $uibModal, $http, SweetAlert, $document, $aside, $location, $translate) {

        var log = $log;
        var request = {};

        $scope.loading = true;
        $scope.customerId = $stateParams.customerId;

        $scope.isView = $state.is("app.clientes.view");
        $scope.job = {};
        $scope.activity = {};

        $scope.status = $rootScope.parameters("config_workplace_status");
        $scope.types = $rootScope.parameters("wg_structure_type");
        $scope.trackingList = $rootScope.parameters("hazard_tracking");
        $scope.workplaces = [];
        $scope.macros = [];
        $scope.processes = [];
        $scope.request = {};

        $scope.onLoadRecordActivity = function () {
            if ($scope.activity.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.activity.id);
                var req = {
                    id: $scope.activity.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/config-sgsst/activity/get',
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
                        console.log(response);

                        $timeout(function () {
                            $scope.activity = response.data.result;
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            $document.scrollTop(40, 2000);
                        });

                    });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        }

        var setDefaultActivity = function () {
            $scope.activity = {
                id: 0,
                customerId: $scope.customerId,
                status: null,
                isCritical: false
            };
        }

        setDefaultActivity();

        $scope.master = $scope.job;
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
                    log.info($scope.job);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    save();
                }

            },
            reset: function (form) {
                $scope.clear();
            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.activity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/activity/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                console.log(response)

                $timeout(function () {
                    $scope.activity = response.data.result;

                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");

                    $scope.clearActivity();
                    $scope.reloadData();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };



        $scope.onAddProcess = function (id) {
            var activity = {id: id};

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_config_activity_danger.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/sgsst/activities/customer_profile_config_sgsst_activity_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideConfigActivityProcessCtrl',
                scope: $scope,
                resolve: {
                    activity: function () {
                        return activity;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        };


        $scope.onAddDocumentTypes = function (id) {
            var activity = {id: id};

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_config_job_activity_document.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/sgsst/activities/customer_profile_config_sgsst_job_activity_document_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideConfigActivityDocumentCtrl',
                scope: $scope,
                resolve: {
                    activity: function () {
                        return activity;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        };

        // default view
        // $rootScope.tracking_section = "list";

        // Datatable configuration

        $scope.request.operation = "diagnostic";
        $scope.request.customerId = $scope.customerId;

        $scope.dtInstanceConfigJobActivity = {};
        $scope.dtOptionsConfigJobActivity = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/customer/config-sgsst/activity',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true).withOption('processing', true)
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

        $scope.dtColumnsConfigJobActivity = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 180).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var configHazardTemplate = '<a class="btn btn-info btn-xs hazardRow lnk" href="#"  uib-tooltip="Configurar peligro" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-cog"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    var configProcessTemplate = '<a class="btn btn-success btn-xs setupRow lnk" href="#"  uib-tooltip="Configurar Procesos" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-file-text-o"></i></a> ';

                    var documentTemplate = '<a class="btn btn-purple btn-xs documentRow lnk" href="#"  uib-tooltip="Configurar Tipo Documento" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-file-archive-o"></i></a> ';

                    //DB->20181019: Remove actions
                    //actions += configProcessTemplate;
                    actions += configHazardTemplate;

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_delete")) {
                        actions += deleteTemplate;
                    }

                    if (data.isCritical) {
                        actions += documentTemplate;
                    }

                    return !$scope.isView ? actions : null;
                }),

            DTColumnBuilder.newColumn('name').withTitle($translate.instant('grid.matrix.ACTIVITY')),
            DTColumnBuilder.newColumn('isCritical').withTitle("Crítica").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';

                    if (data) {
                        label = 'label label-info';
                        text = 'Si';
                    } else {
                        label = 'label label-warning';
                        text = 'No';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';


                    return status;
                }),
            DTColumnBuilder.newColumn('status.item').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch (data) {
                        case "Activo":
                            label = 'label label-success';
                            break;

                        case "Inactivo":
                            label = 'label label-danger';
                            break;

                        case "Retirado":
                            label = 'label label-warning';
                            break;
                    }

                    var status = '<span class="' + label + '">' + data + '</span>';


                    return status;
                }),
        ];

        $scope.clearActivity = function (index) {
            setDefaultActivity();
            $scope.reloadData();
        };

        $scope.setupConfigJobActivity = function (id) {
            $scope.onAddProcess(id);
        };

        $scope.setupConfigActivityDocument = function (id) {
            $scope.onAddDocumentTypes(id);
        };

        var loadRow = function () {

            $("#dtConfigJobActivity a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editConfigJobActivity(id);
            });

            $("#dtConfigJobActivity a.hazardRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onAddHazard( {id: id} );

            });

            $("#dtConfigJobActivity a.setupRow").on("click", function () {
                var id = $(this).data("id");
                $scope.setupConfigJobActivity(id);

            });

            $("#dtConfigJobActivity a.documentRow").on("click", function () {
                var id = $(this).data("id");
                $scope.setupConfigActivityDocument(id);

            });

            $("#dtConfigJobActivity a.delRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminará el registro seleccionada.",
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
                                url: 'api/customer/config-sgsst/activity/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (data) {
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

        $scope.reloadData = function () {
            $scope.dtInstanceConfigJobActivity.reloadData();
        };


        $scope.editConfigJobActivity = function (id) {
            $scope.activity.id = id;
            $scope.onLoadRecordActivity();
        };


        $scope.onAddHazard = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/sgsst/activities/customer_profile_config_sgsst_job_activity_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerConfigActivityHazardCtrl',
                scope: $scope,
                resolve: {
                    activity: function () {
                        return dataItem;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        };

    }

]);

app.controller('ModalInstanceSideCustomerConfigActivityHazardCtrl', function ($uibModalInstance, $rootScope, $location, $uibModal, $scope, activity, $log, $timeout, SweetAlert, $http, toaster,
    $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document, ListService) {

    var attachmentUploadedId = 0;
    var $formInstance = null;

    getList();

    function getList() {

        var entities = [
            { name: 'customer_config_activity_hazard_reason', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.reasonList = response.data.data.customer_config_activity_hazard_reason;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.controlMethods = $rootScope.parameters("config_control_method");
    $scope.typesMeasure = $rootScope.parameters("config_type_measure");

    $scope.entity = activity;

    var init = function() {
        $scope.hazard = {
            id: 0,
            jobActivityId: $scope.entity.id,
            type: null,
            classification: null,
            description: null,
            health: null,
            exposure: 0,
            observation: "",
            controlMethodSourceText: "",
            controlMethodMediumText: "",
            controlMethodPersonText: "",
            controlMethodAdministrativeText: "",
            measureND: null,
            measureNE: null,
            measureNC: null,
            levelP: null,
            levelIP: null,
            levelR: null,
            riskValue: null,
            riskText: null,
            exposed: 0,
            contractors: 0,
            visitors: 0,
            interventions: [],
            reason: null,
            reasonObservation: null
        };

        if ($formInstance !== null) {
            $formInstance.$setPristine(true);
        }
    }

    init();

    $scope.types = [];

    $scope.onLoadRecordHazard = function () {
        if ($scope.hazard.id != 0) {

            // se debe cargar primero la información actual del cliente..
            // log.info("editando cliente con código: " + $scope.danger.id);
            var req = {
                id: $scope.hazard.id
            };
            $http({
                method: 'GET',
                url: 'api/customer/config-sgsst/job-activity-hazard/get',
                params: req
            })
                .catch(function (e, code) {

                })
                .then(function (response) {
                    $timeout(function () {
                        $scope.hazard = response.data.result;
                        $scope.reloadDataReason();
                    });

                }).catch(function () {

                    $timeout(function () {
                        $document.scrollTop(40, 2000);
                    });

                });
        } else {

        }
    }

    $scope.onLoadRecordActivity = function () {
        if ($scope.entity.id != 0) {
            var req = {
                id: $scope.entity.id
            };
            $http({
                method: 'GET',
                url: 'api/customer/config-sgsst/activity/get',
                params: req
            })
                .catch(function (e, code) {
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.entity = response.data.result;
                    });

                }).finally(function () {
                });
        } else {
            $scope.loading = false;
        }
    };

    $scope.onLoadRecordHazard();
    $scope.onLoadRecordActivity();

    var loadList = function () {

        var req = {};
        req.operation = "diagnostic";
        req.customerId = $scope.customerId;

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/wizard/listClassification',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            console.log(response)
            $timeout(function () {
                $scope.classifications = response.data.data;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var loadListLevel = function () {

        var req = {};
        req.operation = "diagnostic";
        req.customerId = $scope.customerId;

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/wizard/listLevel',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            console.log(response);
            $timeout(function () {
                $scope.measuresND = response.data.data.ND;
                $scope.measuresNE = response.data.data.NE;
                $scope.measuresNC = response.data.data.NC;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    loadList();
    loadListLevel();

    $scope.$watch("hazard.classification", function () {
        //console.log('new result',result);
        if ($scope.hazard.classification != null) {
            var req = {};
            req.operation = "diagnostic";
            req.classificationId = $scope.hazard.classification.id;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/wizard/listType',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.types = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });
        }
    });

    $scope.$watch("hazard.type", function () {
        //console.log('new result',result);
        if ($scope.hazard.classification != null) {
            var req = {};
            req.operation = "diagnostic";
            req.typeId = $scope.hazard.type.id;

            $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/wizard/listDescription',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.descriptions = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });

            $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/wizard/listEffect',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.healthEffects = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });
        }
    });

    var calculateRisk = function () {

        $scope.hazard.riskValue = null;
        $scope.hazard.riskText = null;

        if ($scope.hazard.measureND != null && $scope.hazard.measureNE != null) {
            $scope.hazard.levelP = parseFloat($scope.hazard.measureND.value) * parseFloat($scope.hazard.measureNE.value);

            if ($scope.hazard.levelP > 20) {
                $scope.hazard.levelIP = "Muy Alto";
            } else if ($scope.hazard.levelP >= 10 && $scope.hazard.levelP <= 20) {
                $scope.hazard.levelIP = "Alto";
            } else if ($scope.hazard.levelP >= 6 && $scope.hazard.levelP <= 8) {
                $scope.hazard.levelIP = "Medio";
            } else if ($scope.hazard.levelP >= 1 && $scope.hazard.levelP <= 4) {
                $scope.hazard.levelIP = "Bajo";
            }

            if ($scope.hazard.measureNC != null) {
                $scope.hazard.levelR = parseFloat($scope.hazard.levelP) * parseFloat($scope.hazard.measureNC.value);

                if ($scope.hazard.levelR >= 600 && $scope.hazard.levelR <= 4000) {
                    $scope.hazard.riskValue = "No Aceptable";
                    $scope.hazard.riskText = "Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente";
                } else if ($scope.hazard.levelR >= 150 && $scope.hazard.levelR <= 500) {
                    $scope.hazard.riskValue = "No Aceptable o Aceptable con control especifico";
                    $scope.hazard.riskText = "Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360";
                } else if ($scope.hazard.levelR >= 40 && $scope.hazard.levelR <= 120) {
                    $scope.hazard.riskValue = "Mejorable";
                    $scope.hazard.riskText = "Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad";
                } else if ($scope.hazard.levelR >= 10 && $scope.hazard.levelR <= 39) {
                    $scope.hazard.riskValue = "Aceptable";
                    $scope.hazard.riskText = "Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable";
                }
            }
        }
    };

    $scope.$watch("hazard.measureND", function () {
        calculateRisk();
    });

    $scope.$watch("hazard.measureNE", function () {
        calculateRisk();
    });

    $scope.$watch("hazard.measureNC", function () {
        calculateRisk();
    });

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {
        init();
    }


    $scope.form = {

        submit: function (form) {
            $formInstance = form;

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
        var data = JSON.stringify($scope.hazard);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/job-activity-hazard/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (data) {

            $timeout(function () {
                toaster.pop("success", "Registro", "La información ha sido guardada satisfactoriamente");
                $scope.onClear();
                $scope.reloadData();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };


    var buildDTColumns = function() {
        var $columns = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.document != null ? data.document.path : "";
                var actions = "";
                var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" data-url="' + url + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-folder-open-o"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-ban"></i></a> ';


                var AprovedTemplate = '<a class="btn btn-info btn-xs aprovedRow lnk" href="#" uib-tooltip="Revisado Aprobado" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-check"></i></a> ';

                var deniedTemplate = '<a class="btn btn-danger btn-xs deniedRow lnk" href="#" uib-tooltip="Revisado Denegado" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash"></i></a> ';

                if ($rootScope.can("seguimiento_view")) {
                    //actions += viewTemplate;
                }

                if ($rootScope.can("seguimiento_edit")) {
                    actions += editTemplate;
                }

                if ($rootScope.can("seguimiento_delete")) {
                    actions += deleteTemplate;
                }

                actions += AprovedTemplate;
                actions += deniedTemplate;

                return actions;
            })
        ];

        $columns.push(buildDTColumn('classification.name', 'Clasificación', '', 200));
        $columns.push(buildDTColumn('type.name', 'Tipo Peligro', '', 200));
        $columns.push(buildDTColumn('description.name', 'Descripción Peligro', '', 200));
        $columns.push(buildDTColumn('health.name', 'Efectos a la Salud', '', 200));
        $columns.push(buildDTColumn('measureND.name', 'ND', '', 200));
        $columns.push(buildDTColumn('measureNE.name', 'NE', '', 200));
        $columns.push(buildDTColumn('measureNC.name', 'NC', '', 200));
        $columns.push(buildDTColumn(null, 'Verificado', '', 200).renderWith(function (data, type, full, meta) {
                var label = '';
                var text = data.status != null ? data.status : '';
                switch (data.status) {
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

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            })
        );

        return $columns;
    }

    var buildDTColumn = function(field, title, defaultContent, width) {
        return DTColumnBuilder.newColumn(field)
            .withTitle(title)
            .withOption('defaultContent', defaultContent)
            .withOption('width', width);
    };


    $scope.dtInstanceConfigJobActivityHazard = {};
    $scope.dtOptionsConfigJobActivityHazard = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.operation = "document";
                d.jobActivityId = $scope.entity.id;
                return d;
            },
            url: 'api/customer/config-sgsst/job-activity-hazard',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true).withOption('processing', true)
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

    $scope.dtColumnsConfigJobActivityHazard = buildDTColumns();

    var loadRow = function () {

        $("#dtConfigJobActivityHazard a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.hazard.id = id;
            $scope.onLoadRecordHazard();
        });

        $("#dtConfigJobActivityHazard a.aprovedRow").on("click", function () {
            var id = $(this).data("id");
            onApprove(id);
        });

        $("#dtConfigJobActivityHazard a.deniedRow").on("click", function () {
            var id = $(this).data("id");
            onDenied(id);
        });

        $("#dtConfigJobActivityHazard a.delRow").on("click", function () {
            var id = $(this).data("id");

            // Aqui se debe hacer la redireccion al formulario de edicion del customer
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado.",
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
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/customer/config-sgsst/job-activity-hazard/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (data) {
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

    $scope.reloadData = function () {
        $scope.dtInstanceConfigJobActivityHazard.reloadData();
    };

    $scope.onAddIntervention = function () {

        $timeout(function () {
            if ($scope.hazard.interventions == null) {
                $scope.hazard.interventions = [];
            }
            $scope.hazard.interventions.push(
                {
                    id: 0,
                    hazardId: $scope.hazard.id,
                    type: null,
                    description: ''
                }
            );
        });
    };

    $scope.onRemoveIntervention = function (index) {
        SweetAlert.swal({
                title: "Está seguro?",
                text: "Eliminará el registro seleccionado.",
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
                        var date = $scope.hazard.interventions[index];

                        if (date.id != 0) {
                            var req = {};
                            req.id = date.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/config-sgsst/job-activity-hazard/intervention/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (data) {
                                toaster.pop("success", "Eliminación", "Registro eliminado satisfactoriamente");
                                $scope.hazard.interventions.splice(index, 1);
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                            });
                        } else {
                            $scope.hazard.interventions.splice(index, 1);
                        }
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    }

    var onApprove = function (id) {
        var req = {};

        var tracking = {
            id: id,
            status: "Aprobado"
        }

        var data = JSON.stringify(tracking);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/job-activity-hazard/update',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (data) {

            $timeout(function () {
                toaster.pop("success", "Registro", "La información ha sido guardada satisfactoriamente");
                $scope.reloadData();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var onDenied = function (id) {

        var modalInstance = $uibModal.open({
            //templateUrl: 'app_modal_config_job_activity_danger_tracking.htm',
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/sgsst/activities/customer_profile_config_sgsst_job_activity_tracking_modal.htm",
            placement: 'right',
            size: 'lg',
            windowTopClass: 'top-modal',
            backdrop: true,
            controller: 'ModalInstanceSideConfigActivityDangerTrackingCtrl',
            scope: $scope,
            resolve: {
                hazard: function () {
                    return {id: id};
                },
                action: function () {
                    return "Denegado";
                }
            }
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        });
    };



    //---------------------------------------------------------------REASONS
    var buildDTReasonColumns = function() {
        var $columns = [];

        $columns.push(buildDTColumn('createdAt', 'Fecha', '', 200));
        $columns.push(buildDTColumn('name', 'Usuario', '', 200));
        $columns.push(buildDTColumn('reason', 'Motivo', '', 200));
        $columns.push(buildDTColumn('reasonObservation', 'Observación', '', 200));

        return $columns;
    }

    $scope.dtOptionsConfigJobActivityHazardReason = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.jobActivityHazardId = $scope.hazard.id;
                return JSON.stringify(d);
            },
            url: 'api/customer-config-activity-hazard-historical-reason',
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
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {

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

    $scope.dtColumnsConfigJobActivityHazardReason = buildDTReasonColumns();

    $scope.dtInstanceConfigJobActivityHazardReasonCallback = function (instance) {
        $scope.dtInstanceConfigJobActivityHazardReason = instance;
    };

    $scope.reloadDataReason = function () {
        $scope.dtInstanceConfigJobActivityHazardReason.reloadData();
    };

});

app.controller('ModalInstanceSideConfigActivityProcessCtrl', function ($uibModal, $rootScope, $location, $scope, $uibModalInstance, activity, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document) {

    var attachmentUploadedId = 0;


    $scope.workplaces = [];
    $scope.macros = [];
    $scope.processes = [];

    $scope.activity = activity;

    $scope.process = {
        id: 0,
        activityId: $scope.activity.id,
        workplace: null,
        macro: null,
        process: null,
        isRoutine: false,
    };

    $scope.types = [];

    var loadList = function () {

        var req = {};
        req.operation = "diagnostic";
        req.customerId = $scope.customerId;

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

    var loadMacro = function () {
        if ($scope.process.workplace != null) {
            var req = {};
            req.operation = "diagnostic";
            req.customerId = $scope.customerId;
            req.workPlaceId = $scope.process.workplace.id;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/macro/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                console.log(response);
                $timeout(function () {
                    $scope.macros = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });
        } else {
            $scope.macros = [];
        }
    }

    var loadProcess = function () {
        if ($scope.process.macro != null) {
            var req = {};
            req.operation = "diagnostic";
            req.customerId = $scope.customerId;
            req.workPlaceId = $scope.process.workplace.id;
            req.macroProcessid = $scope.process.macro.id;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/process/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.processes = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });
        } else {
            $scope.processes = [];
        }
    }

    $scope.$watch("process.workplace", function () {
        //console.log('new result',result);
        loadMacro();
    });

    $scope.$watch("process.macro", function () {
        //console.log('new result',result);
        loadProcess();
    });

    loadList();

    $scope.onLoadRecordActivity = function () {
        if ($scope.activity.id != 0) {
            var req = {
                id: $scope.activity.id
            };
            $http({
                method: 'GET',
                url: 'api/customer/config-sgsst/activity/get',
                params: req
            })
                .catch(function (e, code) {
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.activity = response.data.result;
                    });

                }).finally(function () {
                });
        } else {
            $scope.loading = false;
        }
    };

    $scope.onLoadRecordActivity();

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {
        $scope.process = {
            id: 0,
            activityId: $scope.activity.id,
            workplace: null,
            macro: null,
            process: null,
            isRoutine: false,
        };
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
        var data = JSON.stringify($scope.process);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/activity-process/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                toaster.pop("success", "Registro", "La información ha sido guardada satisfactoriamente");
                $scope.onClear();
                $scope.reloadData();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var request = {};
    request.operation = "document";
    request.activityId = $scope.activity.id;

    $scope.dtInstanceConfigActivityProcess = {};
    $scope.dtOptionsConfigActivityProcess = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/customer/config-sgsst/activity-process',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true).withOption('processing', true)
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

    $scope.dtColumnsConfigActivityProcess = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-edit"></i></a> ';

                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if ($rootScope.can("clientes_delete")) {
                }
                //actions += editTemplate;
                actions += deleteTemplate;

                return actions;
            }),
        DTColumnBuilder.newColumn('workplaceText').withTitle($scope.matrixColumnLabel1).withOption('width', 200),
        DTColumnBuilder.newColumn('macroText').withTitle($scope.matrixColumnLabel2).withOption('width', 200),
        DTColumnBuilder.newColumn('processText').withTitle($scope.matrixColumnLabel3).withOption('width', 200),
        DTColumnBuilder.newColumn('isRoutine').withTitle("Rutinaria").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = '';

                if (data) {
                    label = 'label label-info';
                    text = 'Si';
                } else {
                    label = 'label label-warning';
                    text = 'No';
                }

                var status = '<span class="' + label + '">' + text + '</span>';


                return status;
            }),
    ];

    var loadRow = function () {

        $("#dtConfigActivityProcess a.editRow").on("click", function () {
            var id = $(this).data("id");
            //$scope.editConfigProcess(id);
        });

        $("#dtConfigActivityProcess a.delRow").on("click", function () {
            var id = $(this).data("id");

            // Aqui se debe hacer la redireccion al formulario de edicion del customer
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Se eliminará el registro seleccionado.",
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
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/customer/config-sgsst/activity-process/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (data) {
                                toaster.pop("success", "Eliminación", "Registro eliminado satisfactoriamente");
                        }).catch(function (e) {
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        }).finally(function () {

                            $scope.reloadData();
                        });

                    }
                });
        });

    };

    $scope.reloadData = function () {
        $scope.dtInstanceConfigActivityProcess.reloadData();
    };

});

app.controller('ModalInstanceSideConfigActivityDocumentCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, $uibModal, activity, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document, ListService) {

    $scope.controlMethods = $rootScope.parameters("config_control_method");
    $scope.typesMeasure = $rootScope.parameters("config_type_measure");

    $scope.activity = activity;

    $scope.document = {
        id: 0,
        jobActivityId: $scope.activity.id,
        type: null,
    };

    $scope.types = [];

    getList();

    function getList() {

        var entities = [
            {name: 'customer_employee_document_type', value: $stateParams.customerId}
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.requirements =response.data.data.customerEmployeeDocumentType;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.onLoadRecordDocument = function () {
        if ($scope.document.id != 0) {

            // se debe cargar primero la información actual del cliente..
            // log.info("editando cliente con código: " + $scope.danger.id);
            var req = {
                id: $scope.hazard.id
            };
            $http({
                method: 'GET',
                url: 'api/customer/config-sgsst/job-activity-document/get',
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
                        $scope.hazard = response.data.result;
                    });

                }).finally(function () {

                    $timeout(function () {
                        $document.scrollTop(40, 2000);
                    });

                });
        } else {

        }
    }

    $scope.onLoadRecordDocument();

    $scope.onLoadRecordActivity = function () {
        if ($scope.activity.id != 0) {
            var req = {
                id: $scope.activity.id
            };
            $http({
                method: 'GET',
                url: 'api/customer/config-sgsst/activity/get',
                params: req
            })
                .catch(function (e, code) {
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.activity = response.data.result;
                    });

                }).finally(function () {
                });
        } else {
            $scope.loading = false;
        }
    };

    $scope.onLoadRecordActivity();

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {
        $scope.document = {
            id: 0,
            jobActivityId: $scope.activity.id,
            type: null,
        };
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
        var data = JSON.stringify($scope.document);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/job-activity-document/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (data) {

            $timeout(function () {
                toaster.pop("success", "Registro", "La información ha sido guardada satisfactoriamente");
                $scope.onClear();
                $scope.reloadData();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "El tipo de documento ya existe!", "error");
        }).finally(function () {

        });

    };

    $scope.dtInstanceConfigJobActivityDocument = {};
    $scope.dtOptionsConfigJobActivityDocument = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.jobActivityId = $scope.activity.id;

                return JSON.stringify(d);
            },
            url: 'api/customer-config-job-activity-document',
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
        .withOption('serverSide', true).withOption('processing', true)
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

    $scope.dtColumnsConfigJobActivityDocument = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-ban"></i></a> ';


                if ($rootScope.can("seguimiento_view")) {
                    //actions += viewTemplate;
                }

                if ($rootScope.can("seguimiento_edit")) {
                    //actions += editTemplate;
                }

                if ($rootScope.can("seguimiento_delete")) {
                }
                actions += deleteTemplate;

                return actions;
            }),
        DTColumnBuilder.newColumn('type')
            .withTitle("Tipo Documento")
            .withOption('defaultContent', 200),

        DTColumnBuilder.newColumn('createdAt')
            .withTitle("Fecha")
            .withOption('width', 200),

        DTColumnBuilder.newColumn('createdBy')
            .withTitle("Usuario")
            .withOption('defaultContent', 200)
    ];

    var loadRow = function () {

        $("#dtConfigJobActivityDocument a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.hazard.id = id;
            $scope.onLoadRecordDocument();
        });

        $("#dtConfigJobActivityDocument a.delRow").on("click", function () {
            var id = $(this).data("id");

            // Aqui se debe hacer la redireccion al formulario de edicion del customer
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado.",
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
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/customer/config-sgsst/job-activity-document/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (data) {
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

    $scope.reloadData = function () {
        $scope.dtInstanceConfigJobActivityDocument.reloadData();
    };

});


app.controller('ModalInstanceSideConfigActivityDangerTrackingCtrl', function ($scope, $uibModalInstance, $uibModal, hazard, action, $log, $timeout, SweetAlert, $http, toaster) {

    $scope.tracking = {
        id: hazard.id,
        status: action,
        description: "",
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.master = $scope.tracking;
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
                toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");

                return;

            } else {
                save();
            }

        },
        reset: function (form) {
            $scope.clear();
        }
    };

    var save = function () {
        var req = {};

        var data = JSON.stringify($scope.tracking);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/job-activity-hazard/update',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (data) {

            $timeout(function () {
                toaster.pop("success", "Registro", "La información ha sido guardada satisfactoriamente");
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };
});
