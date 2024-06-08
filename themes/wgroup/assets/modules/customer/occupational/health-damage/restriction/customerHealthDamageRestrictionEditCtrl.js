'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerHealthDamageRestrictionEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', '$document', 'FileUploader', '$localStorage', 'toaster',
    function ($scope, $stateParams, $log, $compile, $state,
        SweetAlert, $rootScope, $http, $timeout, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $uibModal, flowFactory,
        cfpLoadingBar, $filter, $aside, $document, FileUploader, $localStorage, toaster) {

        var log = $log;

        var attachmentUploadedId = 0;
        var request = {};
        var currentId = $scope.$parent.currentId;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        log.info("loading..customerHealthDamageRestrictionEditCtrl con el id de tracking: ", currentId);
        $scope.canShow = false;
        // parametros para seguimientos
        $scope.restrictions = $rootScope.parameters("work_health_damage_restriction");
        $scope.whoPerceived = $rootScope.parameters("work_health_damage_who_perceived");
        $scope.observationTypeList = $rootScope.parameters("work_health_damage_restriction_observation_type");
        $scope.accessLevelList = $rootScope.parameters("work_health_damage_restriction_observation_access");

        $scope.arl = $rootScope.parameters("arl");
        $scope.documentType = $rootScope.parameters("customer_document_type");
        $scope.tiposdoc = $rootScope.parameters("tipodoc");

        $scope.documentStatusList = $rootScope.parameters("work_health_damage_document_status");
        $scope.classifications = $rootScope.parameters("work_health_damage_restriction_document_type");


        $scope.isView = $scope.$parent.modeDsp == "view";
        console.log($scope.isView);
        $scope.minDateCurrent = new Date();
        $scope.customerId = $stateParams.customerId;

        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy HH:mm"
        };

        $scope.employees = [];

        $scope.onLoadRecord = function () {
            if ($scope.restriction.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.restriction.id);
                var req = {
                    id: $scope.restriction.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/restriction',
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
                            $scope.restriction = response.data.result;
                            $scope.canShow = true;
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

        $scope.restriction = {
            id: currentId,
            employee: null,
            arl: null,
        };

        var init = function () {
            $scope.detail = {
                id: 0,
                customerHealthDamageRestrictionId: $scope.restriction.id,
                dateOfIssue: null,
                timeInMonths: 0,
                expirationDate: null,
                isPermanentManagement: false,
                restriction: null,
                description: '',
                whoPerceived: null,
                observation: '',
            };
        };

        var initializeObservation = function () {
            $scope.observation = {
                id: 0,
                customerHealthDamageRestrictionId: $scope.restriction.id,
                dateOf: new Date(),
                type: null,
                accessLevel: null,
                description: ''
            };
        };

        var initializeAttachment = function () {
            $scope.attachment = {
                id: 0,
                customerHealthDamageRestrictionId: $scope.restriction.id,
                type: null,
                name: "",
                description: "",
                status: null,
                version: 1,
                startDate: null,
                endDate: null
            };
        }


        init();
        initializeObservation();
        initializeAttachment();

        $scope.onLoadRecord();

        $scope.master = $scope.restriction;

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
                    log.info($scope.customer);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                    //your code for submit
                    log.info($scope.customer);
                    save();
                }

            },
            reset: function (form) {

                $scope.restriction = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};

            var data = JSON.stringify($scope.restriction);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/restriction/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {

                    $scope.restriction = response.data.result;
                    $scope.canShow = true;
                    request.customer_health_damage_id = $scope.restriction.id;
                    init();
                    initializeObservation();
                    initializeAttachment();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        };

        $scope.cancelEdition = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        //------------------------------------------------------------------------HealthDamageRestrictionDetail
        request.customer_health_damage_id = currentId;

        $scope.dtInstanceHealthDamageRestrictionDetail = {};
        $scope.dtOptionsHealthDamageRestrictionDetail = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageRestrictionId = currentId;;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-restriction-detail',
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

        $scope.dtColumnsHealthDamageRestrictionDetail = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    actions += editTemplate;
                    actions += deleteTemplate;

                    return $scope.isView ? '' : actions;
                }),
            DTColumnBuilder.newColumn('dateOfIssue').withTitle("Fecha Emision").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('expirationDate').withTitle("Fecha Vencimiento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('item').withTitle("Restricción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('isPermanentManagement').withTitle("Manejo Permanente").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';

                    if (data || data == '1') {
                        text = 'Si';
                        label = 'label label-success';
                    } else {
                        text = 'No';
                        label = 'label label-danger';
                    }

                    return '<span class="' + label + '">' + text + '</span>';
                })
        ];

        var loadRow = function () {

            $("#dtHealthDamageRestrictionDetail a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageRestrictionDetail(id);
            });

            $("#dtHealthDamageRestrictionDetail a.delRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

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
                                url: 'api/customer/health-damage/restriction-detail/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageRestrictionDetail();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageRestrictionDetailCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageRestrictionDetail = dtInstance;
        };

        $scope.reloadDataHealthDamageRestrictionDetail = function () {
            $scope.dtInstanceHealthDamageRestrictionDetail.reloadData();
        };

        $scope.editHealthDamageRestrictionDetail = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/restriction-detail',
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
                            $scope.detail = response.data.result;

                            if ($scope.detail.dateOfIssue != null) {
                                $scope.detail.dateOfIssue = new Date($scope.detail.dateOfIssue.date);
                            }

                            if ($scope.detail.expirationDate != null) {
                                $scope.detail.expirationDate = new Date($scope.detail.expirationDate.date);
                            }
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            //$document.scrollTop(40, 2000);
                        });

                    });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        };

        $scope.saveHealthDamageRestrictionDetail = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();

            var data = JSON.stringify($scope.detail);
            if ($scope.detail.whoPerceived != null && $scope.detail.timeInMonths != 0 && $scope.detail.dateOfIssue != null && $scope.detail.restriction != null) {
                req.data = Base64.encode(data);
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/restriction-detail/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        $scope.clearHealthDamageRestrictionDetail()
                        $scope.reloadDataHealthDamageRestrictionDetail()
                    });
                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {
                });
            } else {
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");

            }
        }

        $scope.clearHealthDamageRestrictionDetail = function () {
            init();
        };


        //------------------------------------------------------------------------HealthDamageRestrictionObservation
        request.customer_health_damage_id = currentId;

        $scope.dtInstanceHealthDamageRestrictionObservation = {};
        $scope.dtOptionsHealthDamageRestrictionObservation = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageRestrictionId = currentId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-restriction-observation',
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
                loadRowObservation();
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

        $scope.dtColumnsHealthDamageRestrictionObservation = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    actions += editTemplate;
                    actions += deleteTemplate;

                    return $scope.isView ? '' : actions;
                }),
            DTColumnBuilder.newColumn('dateOf').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type').withTitle("Restricción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('accessLevel').withTitle("Nivel acceso").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';

                    if (data == 'Pública') {
                        label = 'label label-success';
                    } else {
                        label = 'label label-danger';
                    }

                    return '<span class="' + label + '">' + data + '</span>';
                }),
            DTColumnBuilder.newColumn('description').withTitle("Observación").withOption('defaultContent', ''),
        ];

        var loadRowObservation = function () {

            $("#dtHealthDamageRestrictionObservation a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageRestrictionObservation(id);
            });

            $("#dtHealthDamageRestrictionObservation a.delRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

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
                                url: 'api/customer/health-damage/restriction-observation/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageRestrictionObservation();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageRestrictionObservationCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageRestrictionObservation = dtInstance;
        };

        $scope.reloadDataHealthDamageRestrictionObservation = function () {
            $scope.dtInstanceHealthDamageRestrictionObservation.reloadData();
        };

        $scope.editHealthDamageRestrictionObservation = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/restriction-observation',
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
                            $scope.observation = response.data.result;

                            if ($scope.observation.dateOf != null) {
                                $scope.observation.dateOf = new Date($scope.observation.dateOf.date);
                            }
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            //$document.scrollTop(40, 2000);
                        });

                    });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        };

        $scope.saveHealthDamageRestrictionObservation = function () {
            var req = {};
            var data = JSON.stringify($scope.observation);
            if ($scope.observation.type != null && $scope.observation.accessLevel != null) {
                req.data = Base64.encode(data);
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/restriction-observation/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        $scope.clearHealthDamageRestrictionObservation()
                        $scope.reloadDataHealthDamageRestrictionObservation()
                    });
                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {
                });
            } else {
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            }
        }

        $scope.clearHealthDamageRestrictionObservation = function () {
            initializeObservation();
        };


        //------------------------------------------------------------------------HealthDamageRestrictionDocument
        request.customer_health_damage_id = currentId;

        $scope.dtInstanceHealthDamageRestrictionDocument = {};
        $scope.dtOptionsHealthDamageRestrictionDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageRestrictionId = currentId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-restriction-document',
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
                loadRowAttachment();
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

        $scope.dtColumnsHealthDamageRestrictionDocument = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var url = data.document != null ? data.document.path : "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    var downloadTemplate = '<a target="_self" class="btn btn-info btn-xs downloadRow lnk" href="#" uib-tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                        '   <i class="fa fa-download"></i></a> ';

                    actions += editTemplate;
                    if (url != '') {
                        actions += downloadTemplate;
                    }

                    actions += deleteTemplate;

                    return $scope.isView ? downloadTemplate : actions;
                }),
            DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';

                    if (data == 'Activo') {
                        text = data;
                        label = 'label label-success';
                    } else {
                        text = data;
                        label = 'label label-danger';
                    }

                    return '<span class="' + label + '">' + text + '</span>';
                })
        ];

        var loadRowAttachment = function () {

            $("#dtHealthDamageRestrictionDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageRestrictionDocument(id);
            });

            $("#dtHealthDamageRestrictionDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/restriction-document/download?id=" + id;
                }
            });

            $("#dtHealthDamageRestrictionDocument a.delRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

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
                                url: 'api/customer/health-damage/restriction-document/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageRestrictionDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageRestrictionDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageRestrictionDocument = dtInstance;
        };

        $scope.reloadDataHealthDamageRestrictionDocument = function () {
            $scope.dtInstanceHealthDamageRestrictionDocument.reloadData();
        };

        $scope.editHealthDamageRestrictionDocument = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/restriction-document',
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
                            $scope.attachment = response.data.result;

                            if ($scope.attachment.startDate != null) {
                                $scope.attachment.startDate = new Date($scope.attachment.startDate.date);
                            }

                            if ($scope.attachment.endDate != null) {
                                $scope.attachment.endDate = new Date($scope.attachment.endDate.date);
                            }
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            //$document.scrollTop(40, 2000);
                        });

                    });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        };

        $scope.saveHealthDamageRestrictionDocument = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();

            var data = JSON.stringify($scope.attachment);
            if ($scope.attachment.type != null && $scope.attachment.name != "" && $scope.attachment.status != null && $scope.attachment.description != "") {
                req.data = Base64.encode(data);
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/restriction-document/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        if ($scope.uploader.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploader.uploadAll();
                        } else {
                            $scope.clearHealthDamageRestrictionDocument()
                            $scope.reloadDataHealthDamageRestrictionDocument()
                        }
                    });
                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {
                });
            } else {
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            }
        }

        $scope.clearHealthDamageRestrictionDocument = function () {
            initializeAttachment();
        };


        //----------------------------------------------------------------UPLOADER
        var uploader = $scope.uploader = new FileUploader({
            url: 'api/customer/health-damage/restriction-document/upload',
            formData: [],
            removeAfterUpload: true
        });

        uploader.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS
        uploader.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = { id: attachmentUploadedId };
            item.formData.push(formData);
        };
        uploader.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageRestrictionDocument()
            $scope.reloadDataHealthDamageRestrictionDocument()
        };


        //----------------------------------------------------------------EMPLOYEE

        $scope.onAddEmployee = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/restriction/customer_absenteeism_disability_employee_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideHealthDamageRestrictionEmployeeCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function () {
                //loadEmployees();
            });
        };

        $scope.onAddDisabilityEmployeeList = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/restriction/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideHealthDamageRestrictionEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (employee) {
                //loadEmployees();
                var result = $filter('filter')($scope.employees, { id: employee.id });

                if (result.length == 0) {
                    $scope.employees.push(employee);
                }

                $scope.restriction.employee = employee;
            });
        };


        //----------------------------------------------------------------WATCHERS

        var calculateDate = function () {
            log.info("calculateDate");
            if ($scope.detail.dateOfIssue != null && $scope.detail.timeInMonths != null) {
                var result = new Date($scope.detail.dateOfIssue);
                result.setMonth(result.getMonth() + parseInt($scope.detail.timeInMonths));
                $scope.detail.expirationDate = result;
            }
        }

        $scope.$watch("detail.dateOfIssue", function () {
            calculateDate();
        });

        $scope.$watch("detail.timeInMonths", function () {
            calculateDate();
        });

    }]);

app.controller('ModalInstanceSideHealthDamageRestrictionEmployeeCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.contractTypes = $rootScope.parameters("employee_contract_type");
    $scope.documentTypes = $rootScope.parameters("employee_document_type");

    var initialize = function () {
        $scope.employee = {
            id: 0,
            customerId: $stateParams.customerId,
            isActive: true,
            contractType: null,
            job: null,
            workPlace: null,
            salary: 0,
            entity: {
                id: 0,
                documentType: null,
                documentNumber: "",
                firstName: "",
                lastName: "",
                isActive: true
            }
        };
    };

    initialize();

    var loadWorkPlace = function () {

        var req = {};
        req.operation = "restriction";
        req.customerId = $stateParams.customerId;
        ;


        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/workplace/listProcess',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.workPlaces = response.data.data;
            });
        }).catch(function (e) {

        }).finally(function () {

        });

    };

    loadWorkPlace();

    var loadJobs = function () {
        if ($scope.employee.workPlace != null) {
            var req = {};
            req.operation = "restriction";
            req.customerId = $stateParams.customerId;
            ;
            req.workPlaceId = $scope.employee.workPlace.id;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/job/listByWorkPlace',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.jobs = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });
        } else {
            $scope.jobs = [];
        }
    };

    $scope.$watch("employee.workPlace", function () {
        //console.log('new result',result);
        loadJobs();
    });

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancelEmployee = function () {
        $uibModalInstance.dismiss('cancel');
    };

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
                $scope.onSaveEmployee();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.onSaveEmployee = function () {

        var req = {};
        var data = JSON.stringify($scope.employee);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-employee/quickSave',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                $scope.attachment = response.data.result;
                toaster.pop('success', 'Operación Exitosa', 'Registro eliminado');
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            $log.error(e);
            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
        }).finally(function () {

        });

    };

});

app.controller('ModalInstanceSideHealthDamageRestrictionEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.employee.id != 0) {
            var req = {
                id: $scope.employee.id,
            };
            $http({
                method: 'GET',
                url: 'api/customer-employee',
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
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.employee = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.onCloseModal();
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }

    $scope.dtInstanceDisabilityEmployeeList = {};
    $scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerId = $stateParams.customerId;
                return JSON.stringify(d);
            },
            url: 'api/customer-employee-modal-basic',
            contentType: 'application/json',
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

    $scope.dtColumnsDisabilityEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar empleado" tooltip-placement="right"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200),
        DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200),
        DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
        DTColumnBuilder.newColumn('workPlace').withTitle("Centro de Trabajo").withOption('width', 200),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200),
        DTColumnBuilder.newColumn('neighborhood').withTitle("Centro de Costos").withOption('width', 200),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isActiveCode != null || data.isActiveCode != undefined) {
                    if (data.isActiveCode == 'Activo') {
                        label = 'label label-success';
                        text = 'Activo';
                    } else {
                        label = 'label label-danger';
                        text = 'Inactivo';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            }),
        DTColumnBuilder.newColumn(null).withTitle("Autorización").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isAuthorized != null || data.isAuthorized != undefined) {
                    if (data.isAuthorized == 'Autorizado') {
                        label = 'label label-success';
                        text = 'Autorizado';
                    } else if (data.isAuthorized == 'No Autorizado') {
                        label = 'label label-danger';
                        text = 'No Autorizado';
                    } else {
                        label = 'label label-info';
                        text = 'N/A';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            })
    ];

    var loadRow = function () {
        $("#dtDisabilityEmployeeList a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editDisabilityEmployee(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityEmployeeList.reloadData();
    };

    $scope.viewDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});
