'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerHealthDamageQualificationLostEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', '$document', 'toaster', 'FileUploader',
    function ($scope, $stateParams, $log, $compile, $state,
              SweetAlert, $rootScope, $http, $timeout, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $uibModal, flowFactory,
              cfpLoadingBar, $filter, $aside, $document, toaster, FileUploader) {

        var log = $log;


        var attachmentUploadedId = 0;
        var request = {};
        var currentId = $scope.$parent.currentId;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        log.info("loading..customerHealthDamageQualificationSourceEditCtrl con el id: ", currentId);

        $scope.arl = $rootScope.parameters("arl");
        $scope.documentType =  $rootScope.parameters("customer_document_type");
        $scope.tiposdoc = $rootScope.parameters("tipodoc");

        $scope.diagnosticList = $rootScope.parameters("work_health_damage_diagnostic");
        $scope.lateralities = $rootScope.parameters("work_health_damage_laterality");
        $scope.entityPerformsDiagnostic  = $rootScope.parameters("work_health_damage_entity_perform_diagnostic");
        $scope.codeCIE10  = $rootScope.parameters("work_health_damage_code_cie_10");
        $scope.applicants  = $rootScope.parameters("work_health_damage_applicant");
        $scope.directors  = $rootScope.parameters("work_health_damage_apt");
        $scope.supports  = $rootScope.parameters("work_health_damage_diagnostic_support");
        $scope.documents  = $rootScope.parameters("work_health_damage_diagnostic_document");

        $scope.qualifyingEntities  = $rootScope.parameters("work_health_damage_entity_qualify");
        $scope.qualifiedOrigins  = $rootScope.parameters("work_health_damage_entity_qualify_origin");
        $scope.controversyStatus  = $rootScope.parameters("work_health_damage_controversy_status");

        $scope.classifications = $rootScope.parameters("work_health_damage_ql_document_type");
        $scope.documentStatusList = $rootScope.parameters("work_health_damage_document_status");

        $scope.canShow = false;
        $scope.isView = $scope.$parent.modeDsp == "view";
        $scope.minDateCurrent = new Date();
        $scope.customerId = $stateParams.customerId;

        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy HH:mm"
        };

        $scope.employees = [];
        $scope.diagnostics = [];

        $scope.onLoadRecord = function () {
            if ($scope.ql.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.ql.id);
                var req = {
                    id: $scope.ql.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/ql',
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
                            $scope.ql = response.data.result;
                            $scope.canShow = true;

                            if ($scope.ql.opportunity != null && $scope.ql.opportunity != '') {
                                $scope.opportunity = $scope.ql.opportunity;

                                $scope.opportunity.dateOf = new Date($scope.opportunity.dateOf.date);
                                $scope.opportunity.notificationDate = new Date($scope.opportunity.notificationDate.date);
                                $scope.opportunity.structuringDate = new Date($scope.opportunity.structuringDate.date);
                                $scope.opportunity.nonconformityDate = new Date($scope.opportunity.nonconformityDate.date);

                                $scope.opportunityAttachment.entityId = $scope.opportunity.id;

                                $scope.reloadDataHealthDamageQlFirstOpportunityDocument();
                            }

                            if ($scope.ql.regional != null && $scope.ql.regional != '') {
                                $scope.regional = $scope.ql.regional;

                                $scope.regional.structuringDate     = new Date($scope.regional.structuringDate.date);
                                $scope.regional.dateOf              = new Date($scope.regional.dateOf.date);
                                $scope.regional.notificationDate    = new Date($scope.regional.notificationDate.date);
                                $scope.regional.nonconformityDate   = new Date($scope.regional.nonconformityDate.date);

                                $scope.regionalAttachment.entityId = $scope.regional.id;

                                $scope.reloadDataHealthDamageQlRegionalBoardDocument();
                            }

                            if ($scope.ql.national != null && $scope.ql.national != '') {
                                $scope.national = $scope.ql.national;

                                $scope.national.structuringDate     = new Date($scope.national.structuringDate.date);
                                $scope.national.dateOf              = new Date($scope.national.dateOf.date);
                                $scope.national.notificationDate    = new Date($scope.national.notificationDate.date);

                                $scope.nationalAttachment.entityId = $scope.national.id;

                                $scope.reloadDataHealthDamageQlNationalBoardDocument();
                            }

                            if ($scope.ql.justiceFirst != null && $scope.ql.justiceFirst != '') {
                                $scope.justiceFirst = $scope.ql.justiceFirst;

                                $scope.justiceFirst.dateOf = new Date($scope.justiceFirst.dateOf.date);
                                $scope.justiceFirst.structuringDate = new Date($scope.justiceFirst.structuringDate.date);

                                $scope.justiceFirstAttachment.entityId = $scope.justiceFirst.id;

                                $scope.reloadDataHealthDamageQlJusticeBoardFirstDocument();
                            }

                            if ($scope.ql.justiceSecond != null && $scope.ql.justiceSecond != '') {
                                $scope.justiceSecond = $scope.ql.justiceSecond;

                                $scope.justiceSecond.dateOf = new Date($scope.justiceSecond.dateOf.date);
                                $scope.justiceSecond.structuringDate = new Date($scope.justiceSecond.structuringDate.date);

                                $scope.justiceSecondAttachment.entityId = $scope.justiceSecond.id;

                                $scope.reloadDataHealthDamageQlJusticeBoardSecondDocument();
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


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        }

        $scope.ql = {
            id: currentId,
            employee: null,
            arl: null,
            stepQualificationRegionalBoard: false,
            stepQualificationNationalBoard: false,
            stepQualificationLaborJustice: false,
            isActive: true,
            opportunity: null,
            regional: null,
            national: null,
            justiceFirst: null,
            justiceSecond: null,
            justiceThird: null,
            stepSecondInstance: false,
        };

        var init = function () {

            $scope.opportunity = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                qualifyingEntity: null,
                dateOf: null,
                notificationDate: null,
                origin: null,
                percentageRating: 0,
                structuringDate: null,
                nonconformityDate: null,
                controversyStatus: null,
                diagnostics: []
            };

            $scope.opportunityAttachment = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                entityId: $scope.opportunity.id,
                entityCode: 'opportunity',
                entityName: 'Primera Oportunidad',
                type: null,
                name: '',
                description: '',
                version: '1',
                status: {
                    item: 'Activo',
                    value: 'Activo'
                },
                startDate: null,
                endDate: null
            };

            $scope.regional = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                controversyStatus: null,
                structuringDate: null,
                dateOf: null,
                notificationDate: null,
                nonconformityDate: null,
                origin: null,
                percentageRating: 0,
                diagnostics: []
            };

            $scope.regionalAttachment = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                entityId: $scope.regional.id,
                entityCode: 'regional',
                entityName: 'Primera Instancia',
                type: null,
                name: '',
                description: '',
                version: '1',
                status: {
                    item: 'Activo',
                    value: 'Activo'
                },
                startDate: null,
                endDate: null
            };

            $scope.national = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                controversyStatus: null,
                structuringDate: null,
                dateOf: null,
                notificationDate: null,
                origin: null,
                percentageRating: 0,
                diagnostics: []
            };

            $scope.nationalAttachment = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                entityId: $scope.national.id,
                entityCode: 'national',
                entityName: 'Segunda Instancia',
                type: null,
                name: '',
                description: '',
                version: '1',
                status: {
                    item: 'Activo',
                    value: 'Activo'
                },
                startDate: null,
                endDate: null
            };

            $scope.justiceFirst = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                sentenceType: 'first',
                dateOf: null,
                structuringDate: null,
                judgment: '',
                origin: null,
                percentageRating: 0,
                diagnostics: []
            };

            $scope.justiceFirstAttachment = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                entityId: $scope.justiceFirst.id,
                entityCode: 'justiceFirst',
                entityName: 'Justicia Laboral Ordinaria / Primera Sentencia',
                type: null,
                name: '',
                description: '',
                version: '1',
                status: {
                    item: 'Activo',
                    value: 'Activo'
                },
                startDate: null,
                endDate: null
            };

            $scope.justiceSecond = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                sentenceType: 'second',
                dateOf: null,
                structuringDate: null,
                judgment: '',
                origin: null,
                percentageRating: 0,
                diagnostics: []
            };

            $scope.justiceSecondAttachment = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                entityId: $scope.justiceSecond.id,
                entityCode: 'justiceSecond',
                entityName: 'Justicia Laboral Ordinaria / Segunda Sentencia',
                type: null,
                name: '',
                description: '',
                version: '1',
                status: {
                    item: 'Activo',
                    value: 'Activo'
                },
                startDate: null,
                endDate: null
            };
        };

        init();

        $scope.onLoadRecord();

        $scope.master = $scope.ql;

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
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                    //your code for submit
                    log.info($scope.customer);
                    save();
                }

            },
            reset: function (form) {

                $scope.ql = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};

            var data = JSON.stringify($scope.ql);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/ql/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.ql = response.data.result;
                    $scope.canShow = true;


                    $scope.opportunity.customerHealthDamageQualificationLostId = $scope.ql.id;
                    $scope.regional.customerHealthDamageQualificationLostId  = $scope.ql.id;
                    $scope.national.customerHealthDamageQualificationLostId  = $scope.ql.id;
                    $scope.justiceFirst.customerHealthDamageQualificationLostId  = $scope.ql.id;
                    $scope.justiceSecond.customerHealthDamageQualificationLostId  = $scope.ql.id;

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


        //----------------------------------------------------------------OPPORTUNITY TAB

        $scope.saveHealthDamageQlFirstOpportunity = function(){
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.opportunity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/ql/opportunity/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                    $scope.opportunity = response.data.result;

                    $scope.opportunity.dateOf = new Date($scope.opportunity.dateOf.date);
                    $scope.opportunity.notificationDate = new Date($scope.opportunity.notificationDate.date);
                    $scope.opportunity.structuringDate = new Date($scope.opportunity.structuringDate.date);
                    $scope.opportunity.nonconformityDate = new Date($scope.opportunity.nonconformityDate.date);

                    $scope.clearHealthDamageQlFirstOpportunityDocument();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }

        //----------------------------------------------------------------DIAGNOSTICS
        $scope.onAddOpportunityDiagnostic = function () {

            $timeout(function () {
                if ($scope.opportunity.diagnostics == null) {
                    $scope.opportunity.diagnostics = [];
                }
                $scope.opportunity.diagnostics.push(
                    {
                        id: 0,
                        customerHealthDamageQlFirstOpportunityId: 0,
                        codeCIE10: null,
                        description: "",
                        observation: "",
                    }
                );
            });
        };

        $scope.onRemoveOpportunityDiagnostic = function (index) {
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
                            var date = $scope.opportunity.diagnostics[index];

                            $scope.opportunity.diagnostics.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/health-damage/ql/opportunity-diagnostic/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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


        //------------------------------------------------------------------------FIRST OPPORTUNITY DOCUMENT
        $scope.dtInstanceHealthDamageQlOpportunityDocument = {};
        $scope.dtOptionsHealthDamageQlOpportunityDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationLostId = $scope.ql.id;
                    d.entityCode = "opportunity";
                    d.entityId = $scope.opportunity.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-ql-document',
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
                loadRowQlFirstOpportunityDocument();
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

        $scope.dtColumnsHealthDamageQlOpportunityDocument = [
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
            DTColumnBuilder.newColumn('item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 150)
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

        var loadRowQlFirstOpportunityDocument = function () {

            $("#dtHealthDamageQlOpportunityDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQlFirstOpportunityDocument(id);
            });

            $("#dtHealthDamageQlOpportunityDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/ql/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQlOpportunityDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/ql/document/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQlFirstOpportunityDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQlOpportunityDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQlOpportunityDocument = dtInstance;
            $scope.reloadDataHealthDamageQlFirstOpportunityDocument();
        };

        $scope.reloadDataHealthDamageQlFirstOpportunityDocument = function () {
            if ($scope.dtInstanceHealthDamageQlOpportunityDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQlOpportunityDocument.reloadData();
            }
        };

        $scope.editHealthDamageQlFirstOpportunityDocument = function (id) {
            if (id) {
                var req = {id: id};
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/ql/document',
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
                            $scope.opportunityAttachment = response.data.result;

                            if ($scope.opportunityAttachment.startDate != null) {
                                $scope.opportunityAttachment.startDate = new Date($scope.opportunityAttachment.startDate.date);
                            }

                            if ($scope.opportunityAttachment.endDate != null) {
                                $scope.opportunityAttachment.endDate = new Date($scope.opportunityAttachment.endDate.date);
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

        $scope.saveHealthDamageQlFirstOpportunityDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.opportunityAttachment);
            if ($scope.opportunityAttachment.type != null && $scope.opportunityAttachment.name != "" && $scope.opportunityAttachment.status != null &&  $scope.opportunityAttachment.description != "" ) {
                req.data = Base64.encode(data);
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/ql/document/save',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderOpportunity.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderOpportunity.uploadAll();
                        } else {
                            $scope.clearHealthDamageQlFirstOpportunityDocument();
                            $scope.reloadDataHealthDamageQlFirstOpportunityDocument();
                            $scope.reloadDataHealthDamageQlDocument();
                        }
                    });
                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {
                });
            }else {
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            }
        }

        $scope.clearHealthDamageQlFirstOpportunityDocument = function () {
            $scope.opportunityAttachment = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                entityId: $scope.opportunity.id,
                entityCode: 'opportunity',
                entityName: 'Primera Oportunidad',
                type: null,
                name: '',
                description: '',
                version: '1',
                status: {
                    item: 'Activo',
                    value: 'Activo'
                },
                startDate: null,
                endDate: null
            };
        };


        //----------------------------------------------------------------REGIONAL BOARD TAB

        $scope.saveHealthDamageQlRegionalBoard = function(){
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.regional);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/ql/regional/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                    $scope.regional = response.data.result;

                    $scope.regional.structuringDate     = new Date($scope.regional.structuringDate.date);
                    $scope.regional.dateOf              = new Date($scope.regional.dateOf.date);
                    $scope.regional.notificationDate    = new Date($scope.regional.notificationDate.date);
                    $scope.regional.nonconformityDate   = new Date($scope.regional.nonconformityDate.date);

                    $scope.clearHealthDamageQlRegionalBoardDocument();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }

        //----------------------------------------------------------------DIAGNOSTICS
        $scope.onAddRegionalDiagnostic = function () {

            $timeout(function () {
                if ($scope.regional.diagnostics == null) {
                    $scope.regional.diagnostics = [];
                }
                $scope.regional.diagnostics.push(
                    {
                        id: 0,
                        customerHealthDamageQlRegionalBoardId: 0,
                        codeCIE10: null,
                        description: "",
                        observation: "",
                    }
                );
            });
        };

        $scope.onRemoveRegionalDiagnostic = function (index) {
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
                            var date = $scope.regional.diagnostics[index];

                            $scope.regional.diagnostics.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/health-damage/qs/regional-diagnostic/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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


        //------------------------------------------------------------------------REGIONAL BOARD DOCUMENT
        $scope.dtInstanceHealthDamageQlRegionalDocument = {};
        $scope.dtOptionsHealthDamageQlRegionalDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationLostId  = $scope.ql.id;
                    d.entityCode = "regional";
                    d.entityId = $scope.regional.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-ql-document',
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
                loadRowQlRegionalDocument();
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

        $scope.dtColumnsHealthDamageQlRegionalDocument = [
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
            DTColumnBuilder.newColumn('item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 150)
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

        var loadRowQlRegionalDocument = function () {

            $("#dtHealthDamageQlRegionalDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQlRegionalBoardDocument(id);
            });

            $("#dtHealthDamageQlRegionalDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/ql/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQlRegionalDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/ql/document/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQlRegionalBoardDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQlRegionalDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQlRegionalDocument = dtInstance;
            $scope.reloadDataHealthDamageQlRegionalBoardDocument();
        };

        $scope.reloadDataHealthDamageQlRegionalBoardDocument = function () {
            if ($scope.dtInstanceHealthDamageQlRegionalDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQlRegionalDocument.reloadData();
            }
        };

        $scope.editHealthDamageQlRegionalBoardDocument = function (id) {
            if (id) {
                var req = {id: id};
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/ql/document',
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
                            $scope.regionalAttachment = response.data.result;

                            if ($scope.regionalAttachment.startDate != null) {
                                $scope.regionalAttachment.startDate = new Date($scope.regionalAttachment.startDate.date);
                            }

                            if ($scope.regionalAttachment.endDate != null) {
                                $scope.regionalAttachment.endDate = new Date($scope.regionalAttachment.endDate.date);
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

        $scope.saveHealthDamageQlRegionalBoardDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.regionalAttachment);
            if ($scope.regionalAttachment.type != null && $scope.regionalAttachment.name != "" && $scope.regionalAttachment.status != null && $scope.regionalAttachment    .description != "" ) {
                req.data = Base64.encode(data);
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/ql/document/save',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderRegional.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderRegional.uploadAll();
                        } else {
                            $scope.clearHealthDamageQlRegionalBoardDocument();
                            $scope.reloadDataHealthDamageQlRegionalBoardDocument();
                            $scope.reloadDataHealthDamageQlDocument();
                        }
                    });
                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {
                });
            }else {
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            }
        }

        $scope.clearHealthDamageQlRegionalBoardDocument = function () {
            $scope.regionalAttachment = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                entityId: $scope.regional.id,
                entityCode: 'regional',
                entityName: 'Primera Instancia',
                type: null,
                name: '',
                description: '',
                version: '1',
                status: {
                    item: 'Activo',
                    value: 'Activo'
                },
                startDate: null,
                endDate: null
            };
        };

        //----------------------------------------------------------------NATIONAL BOARD TAB

        $scope.saveHealthDamageQlNationalBoard = function(){
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.national);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/ql/national/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                    $scope.national = response.data.result;

                    $scope.national.structuringDate     = new Date($scope.national.structuringDate.date);
                    $scope.national.dateOf              = new Date($scope.national.dateOf.date);
                    $scope.national.notificationDate    = new Date($scope.national.notificationDate.date);
                    //$scope.national.nonconformityDate   = new Date($scope.national.nonconformityDate.date);

                    $scope.clearHealthDamageQlNationalBoardDocument();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }

        //----------------------------------------------------------------DIAGNOSTICS
        $scope.onAddNationalDiagnostic = function () {

            $timeout(function () {
                if ($scope.national.diagnostics == null) {
                    $scope.national.diagnostics = [];
                }
                $scope.national.diagnostics.push(
                    {
                        id: 0,
                        customerHealthDamageQlNationalBoardId: 0,
                        codeCIE10: null,
                        description: "",
                        observation: "",
                    }
                );
            });
        };

        $scope.onRemoveNationalDiagnostic = function (index) {
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
                            var date = $scope.national.diagnostics[index];

                            $scope.national.diagnostics.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/health-damage/ql/national-diagnostic/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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


        //------------------------------------------------------------------------NATIONAL BOARD DOCUMENT
        $scope.dtInstanceHealthDamageQlNationalDocument = {};
        $scope.dtOptionsHealthDamageQlNationalDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationLostId = $scope.ql.id;
                    d.entityCode = "national";
                    d.entityId = $scope.national.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-ql-document',
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
                loadRowQlNationalDocument();
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

        $scope.dtColumnsHealthDamageQlNationalDocument = [
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
            DTColumnBuilder.newColumn('item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 150)
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

        var loadRowQlNationalDocument = function () {

            $("#dtHealthDamageQlNationalDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQlNationalBoardDocument(id);
            });

            $("#dtHealthDamageQlNationalDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/ql/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQlNationalDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/ql/document/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQlNationalBoardDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQlNationalDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQlNationalDocument = dtInstance;
            $scope.reloadDataHealthDamageQlNationalBoardDocument();
        };

        $scope.reloadDataHealthDamageQlNationalBoardDocument = function () {
            if ($scope.dtInstanceHealthDamageQlNationalDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQlNationalDocument.reloadData();
            }
        };

        $scope.editHealthDamageQlNationalBoardDocument = function (id) {
            if (id) {
                var req = {id: id};
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/ql/document',
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
                            $scope.nationalAttachment = response.data.result;

                            if ($scope.nationalAttachment.startDate != null) {
                                $scope.nationalAttachment.startDate = new Date($scope.nationalAttachment.startDate.date);
                            }

                            if ($scope.nationalAttachment.endDate != null) {
                                $scope.nationalAttachment.endDate = new Date($scope.nationalAttachment.endDate.date);
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

        $scope.saveHealthDamageQlNationalBoardDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.nationalAttachment);
            if ($scope.nationalAttachment.type != null && $scope.nationalAttachment.name != "" && $scope.nationalAttachment.status != null && $scope.nationalAttachment.description != "" ) {
                req.data = Base64.encode(data);
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/ql/document/save',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderNational.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderNational.uploadAll();
                        } else {
                            $scope.clearHealthDamageQlNationalBoardDocument();
                            $scope.reloadDataHealthDamageQlNationalBoardDocument();
                            $scope.reloadDataHealthDamageQlDocument();
                        }
                    });
                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {
                });
            }else {
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            }
        }

        $scope.clearHealthDamageQlNationalBoardDocument = function () {
            $scope.nationalAttachment = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                entityId: $scope.national.id,
                entityCode: 'national',
                entityName: 'Segunda Instancia',
                type: null,
                name: '',
                description: '',
                version: '1',
                status: {
                    item: 'Activo',
                    value: 'Activo'
                },
                startDate: null,
                endDate: null
            };
        };


        //----------------------------------------------------------------JUSTICE BOARD FIRST TAB

        $scope.saveHealthDamageQlJusticeBoardFirst = function(){
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.justiceFirst);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/ql/justice/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                    $scope.justiceFirst = response.data.result;

                    $scope.justiceFirst.structuringDate     = new Date($scope.justiceFirst.structuringDate.date);
                    $scope.justiceFirst.dateOf              = new Date($scope.justiceFirst.dateOf.date);
                    //$scope.justiceFirst.notificationDate    = new Date($scope.justiceFirst.notificationDate.date);
                    //$scope.justiceFirst.nonconformityDate   = new Date($scope.justiceFirst.nonconformityDate.date);

                    $scope.clearHealthDamageQlJusticeBoardFirstDocument();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }

        //----------------------------------------------------------------DIAGNOSTICS
        $scope.onAddJusticeFirstDiagnostic = function () {

            $timeout(function () {
                if ($scope.justiceFirst.diagnostics == null) {
                    $scope.justiceFirst.diagnostics = [];
                }
                $scope.justiceFirst.diagnostics.push(
                    {
                        id: 0,
                        customerHealthDamageQlJusticeBoardId: 0,
                        codeCIE10: null,
                        description: "",
                        observation: "",
                    }
                );
            });
        };

        $scope.onRemoveJusticeFirstDiagnostic = function (index) {
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
                            var date = $scope.justiceFirst.diagnostics[index];

                            $scope.justiceFirst.diagnostics.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/health-damage/ql/justice-diagnostic/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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


        //------------------------------------------------------------------------JUSTICE BOARD FIRST DOCUMENT
        $scope.dtInstanceHealthDamageQlJusticeFirstDocument = {};
        $scope.dtOptionsHealthDamageQlJusticeFirstDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationLostId = $scope.ql.id;
                    d.entityCode = "justiceFirst";
                    d.entityId = $scope.justiceFirst.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-ql-document',
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
                loadRowQlJusticeFirstDocument();
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

        $scope.dtColumnsHealthDamageQlJusticeFirstDocument = [
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
            DTColumnBuilder.newColumn('item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 150)
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

        var loadRowQlJusticeFirstDocument = function () {

            $("#dtHealthDamageQlJusticeFirstDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQlJusticeBoardFirstDocument(id);
            });

            $("#dtHealthDamageQlJusticeFirstDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/ql/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQlJusticeFirstDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/ql/document/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQlJusticeBoardFirstDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQlJusticeFirstDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQlJusticeFirstDocument = dtInstance;
            $scope.reloadDataHealthDamageQlJusticeBoardFirstDocument();
        };

        $scope.reloadDataHealthDamageQlJusticeBoardFirstDocument = function () {
            if ($scope.dtInstanceHealthDamageQlJusticeFirstDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQlJusticeFirstDocument.reloadData();
            }
        };

        $scope.editHealthDamageQlJusticeBoardFirstDocument = function (id) {
            if (id) {
                var req = {id: id};
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/ql/document',
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
                            $scope.justiceFirstAttachment = response.data.result;

                            if ($scope.justiceFirstAttachment.startDate != null) {
                                $scope.justiceFirstAttachment.startDate = new Date($scope.justiceFirstAttachment.startDate.date);
                            }

                            if ($scope.justiceFirstAttachment.endDate != null) {
                                $scope.justiceFirstAttachment.endDate = new Date($scope.justiceFirstAttachment.endDate.date);
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

        $scope.saveHealthDamageQlJusticeBoardFirstDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.justiceFirstAttachment);
            if ($scope.justiceFirstAttachment.type != null && $scope.justiceFirstAttachment.name != "" && $scope.justiceFirstAttachment.status != null && $scope.justiceFirstAttachment.description != "" ) {
                req.data = Base64.encode(data);
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/ql/document/save',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderJusticeFirst.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderJusticeFirst.uploadAll();
                        } else {
                            $scope.clearHealthDamageQlJusticeBoardFirstDocument();
                            $scope.reloadDataHealthDamageQlJusticeBoardFirstDocument();
                            $scope.reloadDataHealthDamageQlDocument();
                        }
                    });
                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {
                });
            }else {
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            }
        }

        $scope.clearHealthDamageQlJusticeBoardFirstDocument = function () {
            $scope.justiceFirstAttachment = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                entityId: $scope.justiceFirst.id,
                entityCode: 'justiceFirst',
                entityName: 'Justicia Laboral Ordinaria / Primera Sentencia',
                type: null,
                name: '',
                description: '',
                version: '1',
                status: {
                    item: 'Activo',
                    value: 'Activo'
                },
                startDate: null,
                endDate: null
            };
        };


        //----------------------------------------------------------------JUSTICE BOARD SECOND TAB

        $scope.saveHealthDamageQlJusticeBoardSecond = function(){
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.justiceSecond);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/ql/justice/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');

                    $scope.justiceSecond = response.data.result;

                    $scope.clearHealthDamageQlJusticeBoardSecondDocument();

                    $scope.justiceSecond.structuringDate     = new Date($scope.justiceSecond.structuringDate.date);
                    $scope.justiceSecond.dateOf              = new Date($scope.justiceSecond.dateOf.date);
                    //$scope.justiceSecond.notificationDate    = new Date($scope.justiceSecond.notificationDate.date);
                    //$scope.justiceSecond.nonconformityDate   = new Date($scope.justiceSecond.nonconformityDate.date);


                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }


        //----------------------------------------------------------------DIAGNOSTICS
        $scope.onAddJusticeSecondDiagnostic = function () {

            $timeout(function () {
                if ($scope.justiceSecond.diagnostics == null) {
                    $scope.justiceSecond.diagnostics = [];
                }
                $scope.justiceSecond.diagnostics.push(
                    {
                        id: 0,
                        customerHealthDamageQlJusticeBoardId: 0,
                        codeCIE10: null,
                        description: "",
                        observation: "",
                    }
                );
            });
        };

        $scope.onRemoveJusticeSecondDiagnostic = function (index) {
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
                            var date = $scope.justiceSecond.diagnostics[index];

                            $scope.justiceSecond.diagnostics.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/health-damage/ql/justice-diagnostic/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

        //------------------------------------------------------------------------JUSTICE BOARD SECOND DOCUMENT
        $scope.dtInstanceHealthDamageQlJusticeSecondDocument = {};
        $scope.dtOptionsHealthDamageQlJusticeSecondDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationLostId = $scope.ql.id;
                    d.entityCode = "justiceSecond";
                    d.entityId = $scope.justiceSecond.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-ql-document',
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
                loadRowQlJusticeSecondDocument();
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

        $scope.dtColumnsHealthDamageQlJusticeSecondDocument = [
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
            DTColumnBuilder.newColumn('item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 150)
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

        var loadRowQlJusticeSecondDocument = function () {

            $("#dtHealthDamageQlJusticeSecondDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQlJusticeBoardSecondDocument(id);
            });

            $("#dtHealthDamageQlJusticeSecondDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/ql/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQlJusticeSecondDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/ql/document/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQlJusticeBoardSecondDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQlJusticeSecondDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQlJusticeSecondDocument = dtInstance;
            $scope.reloadDataHealthDamageQlJusticeBoardSecondDocument();
        };

        $scope.reloadDataHealthDamageQlJusticeBoardSecondDocument = function () {
            if ($scope.dtInstanceHealthDamageQlJusticeSecondDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQlJusticeSecondDocument.reloadData();
            }
        };

        $scope.editHealthDamageQlJusticeBoardSecondDocument = function (id) {
            if (id) {
                var req = {id: id};
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/ql/document',
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
                            $scope.justiceSecondAttachment = response.data.result;

                            if ($scope.justiceSecondAttachment.startDate != null) {
                                $scope.justiceSecondAttachment.startDate = new Date($scope.justiceSecondAttachment.startDate.date);
                            }

                            if ($scope.justiceSecondAttachment.endDate != null) {
                                $scope.justiceSecondAttachment.endDate = new Date($scope.justiceSecondAttachment.endDate.date);
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

        $scope.saveHealthDamageQlJusticeBoardSecondDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.justiceSecondAttachment);
            if ($scope.justiceSecondAttachment.type != null && $scope.justiceSecondAttachment.name != "" && $scope.justiceSecondAttachment.status != null && $scope.justiceSecondAttachment.description != "" ) {
                req.data = Base64.encode(data);
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/ql/document/save',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderJusticeSecond.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderJusticeSecond.uploadAll();
                        } else {
                            $scope.clearHealthDamageQlJusticeBoardSecondDocument();
                            $scope.reloadDataHealthDamageQlJusticeBoardSecondDocument();
                            $scope.reloadDataHealthDamageQlDocument();
                        }
                    });
                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {
                });
            }else {
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            }
        }

        $scope.clearHealthDamageQlJusticeBoardSecondDocument = function () {
            $scope.justiceSecondAttachment = {
                id: 0,
                customerHealthDamageQualificationLostId: $scope.ql.id,
                entityId: $scope.justiceSecond.id,
                entityCode: 'justiceSecond',
                entityName: 'Justicia Laboral Ordinaria / Segunda Sentencia',
                type: null,
                name: '',
                description: '',
                version: '1',
                status: {
                    item: 'Activo',
                    value: 'Activo'
                },
                startDate: null,
                endDate: null
            };
        };

        //------------------------------------------------------------------------DOCUMENTS
        $scope.dtInstanceHealthDamageQlDocument = {};
        $scope.dtOptionsHealthDamageQlDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customer_health_damage_ql_id = $scope.ql.id;
                },
                url: 'api/customer/health-damage/ql/document-all',
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
                loadRowQlDocument();
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

        $scope.dtColumnsHealthDamageQlDocument = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var url = data.document != null ? data.document.path : "";

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    var downloadTemplate = '<a target="_self" class="btn btn-info btn-xs downloadRow lnk" href="#" uib-tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                        '   <i class="fa fa-download"></i></a> ';

                    if (url != '') {
                        actions += downloadTemplate;
                    }

                    actions += deleteTemplate;

                    return $scope.isView ? downloadTemplate : actions;
                }),
            DTColumnBuilder.newColumn('createdAt').withTitle("Fecha registro").withOption('width', 120).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('entityName').withTitle("Módulo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type.item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status.item').withTitle("Estado").withOption('width', 150)
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

        var loadRowQlDocument = function () {

            $("#dtHealthDamageQlDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/ql/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQlDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/ql/document/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQlDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQlDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQlDocument = dtInstance;
            $scope.reloadDataHealthDamageQlDocument();
        };

        $scope.reloadDataHealthDamageQlDocument = function () {
            if ($scope.dtInstanceHealthDamageQlDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQlDocument.reloadData();
            }
        };

        $scope.onDownload = function () {
            jQuery("#download")[0].src = "api/customer/health-damage/ql/document/download-all?id=" + $scope.ql.id;
        };

        //----------------------------------------------------------------UPLOADER OPPORTUNITY
        var uploaderOpportunity = $scope.uploaderOpportunity = new FileUploader({
            url: 'api/customer/health-damage/ql/document/upload',
            formData: [],
            removeAfterUpload: true
        });

        uploaderOpportunity.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS
        uploaderOpportunity.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = {id: attachmentUploadedId};
            item.formData.push(formData);
        };
        uploaderOpportunity.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQlFirstOpportunityDocument();
            $scope.reloadDataHealthDamageQlFirstOpportunityDocument();
            $scope.reloadDataHealthDamageQlDocument();
        };

        //----------------------------------------------------------------UPLOADER REGIONAL
        var uploaderRegional = $scope.uploaderRegional = new FileUploader({
            url: 'api/customer/health-damage/ql/document/upload',
            formData: [],
            removeAfterUpload: true
        });

        uploaderRegional.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS
        uploaderRegional.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = {id: attachmentUploadedId};
            item.formData.push(formData);
        };
        uploaderRegional.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQlRegionalBoardDocument();
            $scope.reloadDataHealthDamageQlRegionalBoardDocument();
            $scope.reloadDataHealthDamageQlDocument();
        };

        //----------------------------------------------------------------UPLOADER NATIONAL
        var uploaderNational = $scope.uploaderNational = new FileUploader({
            url: 'api/customer/health-damage/ql/document/upload',
            formData: [],
            removeAfterUpload: true
        });

        uploaderNational.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS
        uploaderNational.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = {id: attachmentUploadedId};
            item.formData.push(formData);
        };
        uploaderNational.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQlNationalBoardDocument();
            $scope.reloadDataHealthDamageQlNationalBoardDocument();
            $scope.reloadDataHealthDamageQlDocument();
        };

        //----------------------------------------------------------------UPLOADER JUSTICE FIRST
        var uploaderJusticeFirst = $scope.uploaderJusticeFirst = new FileUploader({
            url: 'api/customer/health-damage/ql/document/upload',
            formData: [],
            removeAfterUpload: true
        });

        uploaderJusticeFirst.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS
        uploaderJusticeFirst.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = {id: attachmentUploadedId};
            item.formData.push(formData);
        };
        uploaderJusticeFirst.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQlJusticeBoardFirstDocument();
            $scope.reloadDataHealthDamageQlJusticeBoardFirstDocument();
            $scope.reloadDataHealthDamageQlDocument();
        };

        //----------------------------------------------------------------UPLOADER JUSTICE SECOND
        var uploaderJusticeSecond = $scope.uploaderJusticeSecond = new FileUploader({
            url: 'api/customer/health-damage/ql/document/upload',
            formData: [],
            removeAfterUpload: true
        });

        uploaderJusticeSecond.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS
        uploaderJusticeSecond.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = {id: attachmentUploadedId};
            item.formData.push(formData);
        };
        uploaderJusticeSecond.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQlJusticeBoardSecondDocument();
            $scope.reloadDataHealthDamageQlJusticeBoardSecondDocument();
            $scope.reloadDataHealthDamageQlDocument();
        };


        //----------------------------------------------------------------EMPLOYEE

        $scope.onAddEmployee = function() {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification-lost/customer_absenteeism_disability_employee_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideHealthQualificationSourceEmployeeCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function () {
                //loadEmployees();
            });
        };

        $scope.onAddDisabilityEmployeeList = function() {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification-lost/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideHealthQualificationSourceEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (employee) {
                //loadEmployees();
                var result = $filter('filter')($scope.employees, {id: employee.id});

                if (result.length == 0) {
                    $scope.employees.push(employee);
                }

                $scope.ql.employee = employee;
            });
        };

        //----------------------------------------------------------------ENABLE BUTTON OPTIONS

        $scope.saveStepQualificationRegionalBoard = function() {
            toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
            $scope.ql.stepQualificationRegionalBoard = true;
            $scope.stepQualificationRegionalBoardActive = true;
            save();
        }

        $scope.saveStepQualificationNationalBoard = function() {
            toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
            $scope.ql.stepQualificationNationalBoard = true;
            $scope.stepQualificationNationalBoardActive = true;
            save();
        }

        $scope.saveStepQualificationLaborJustice = function() {
            toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
            $scope.ql.stepQualificationLaborJustice = true;
            $scope.stepQualificationLaborJusticeActive = true;
            save();
        }

        $scope.saveStepSecondInstance = function () {
            toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente se habilitara la pestaña Instancia Segunda');
            $scope.ql.stepSecondInstance = true;
            $scope.stepSecondInstanceActive = true;
            save();
        }



        //----------------------------------------------------------------DIAGNOSTICS

        $scope.onAddDisabilityDiagnosticOpportunity = function(index) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_diagnostic.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification/customer_absenteeism_disability_diagnostic_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                resolve: {
                    employee: function () {
                        return $scope.ql.employee;
                    }
                },
                controller: 'ModalInstanceSideHealthDamageQlDiagnosticListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (diagnostic) {
                //loadEmployees();

                var result = $filter('filter')($scope.diagnostics, {id: diagnostic.id});

                if (result.length == 0) {
                    $scope.diagnostics.push(diagnostic);
                }

                $scope.opportunity.diagnostics[index].codeCIE10 = diagnostic;
            });
        };


        $scope.onAddDisabilityDiagnosticRegional = function(index) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_diagnostic.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification/customer_absenteeism_disability_diagnostic_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                resolve: {
                    employee: function () {
                        return $scope.ql.employee;
                    }
                },
                controller: 'ModalInstanceSideHealthDamageQlDiagnosticListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (diagnostic) {
                //loadEmployees();

                var result = $filter('filter')($scope.diagnostics, {id: diagnostic.id});

                if (result.length == 0) {
                    $scope.diagnostics.push(diagnostic);
                }

                $scope.regional.diagnostics[index].codeCIE10 = diagnostic;
            });
        };


        $scope.onAddDisabilityDiagnosticNational = function(index) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_diagnostic.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification/customer_absenteeism_disability_diagnostic_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                resolve: {
                    employee: function () {
                        return $scope.ql.employee;
                    }
                },
                controller: 'ModalInstanceSideHealthDamageQlDiagnosticListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (diagnostic) {
                //loadEmployees();

                var result = $filter('filter')($scope.diagnostics, {id: diagnostic.id});

                if (result.length == 0) {
                    $scope.diagnostics.push(diagnostic);
                }

                $scope.national.diagnostics[index].codeCIE10 = diagnostic;
            });
        };


        $scope.onAddDisabilityDiagnosticJusticeFirst = function(index) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_diagnostic.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification/customer_absenteeism_disability_diagnostic_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                resolve: {
                    employee: function () {
                        return $scope.ql.employee;
                    }
                },
                controller: 'ModalInstanceSideHealthDamageQlDiagnosticListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (diagnostic) {
                //loadEmployees();

                var result = $filter('filter')($scope.diagnostics, {id: diagnostic.id});

                if (result.length == 0) {
                    $scope.diagnostics.push(diagnostic);
                }

                $scope.justiceFirst.diagnostics[index].codeCIE10 = diagnostic;
            });
        };


        $scope.onAddDisabilityDiagnosticJusticeSecond = function(index) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_diagnostic.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification/customer_absenteeism_disability_diagnostic_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                resolve: {
                    employee: function () {
                        return $scope.ql.employee;
                    }
                },
                controller: 'ModalInstanceSideHealthDamageQlDiagnosticListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (diagnostic) {
                //loadEmployees();

                var result = $filter('filter')($scope.diagnostics, {id: diagnostic.id});

                if (result.length == 0) {
                    $scope.diagnostics.push(diagnostic);
                }

                $scope.justiceSecond.diagnostics[index].codeCIE10 = diagnostic;
            });
        };




    }]);

app.controller('ModalInstanceSideHealthQualificationSourceEmployeeCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.contractTypes = $rootScope.parameters("employee_contract_type");
    $scope.documentTypes = $rootScope.parameters("employee_document_type");

    var initialize = function() {
        $scope.employee = {
            id: 0,
            customerId:$stateParams.customerId,
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

    var loadWorkPlace = function()
    {

        var req = {};
        req.operation = "restriction";
        req.customerId = $stateParams.customerId;;


        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/workplace/listProcess',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

    var loadJobs = function()
    {
        if ($scope.employee.workPlace != null) {
            var req = {};
            req.operation = "restriction";
            req.customerId = $stateParams.customerId;;
            req.workPlaceId = $scope.employee.workPlace.id;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/job/listByWorkPlace',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function(){
                $scope.attachment = response.data.result;
                toaster.pop('success', 'Operación Exitosa', 'Registro eliminado');
                $scope.onCloseModal();
            });
        }).catch(function(e){
            $log.error(e);
            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
        }).finally(function(){

        });

    };

});

app.controller('ModalInstanceSideHealthQualificationSourceEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function ()
    {
        if ($scope.employee.id != 0) {
            var req = {
                id: $scope.employee.id,
            };
            $http({
                method: 'GET',
                url: 'api/customer-employee',
                params: req
            })
                .catch(function(e, code){
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () { $state.go(messagered); }, 3000);
                    } else if (code == 404)
                    {
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function(){
                        $scope.employee = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function(){
                        $scope.onCloseModal();
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }

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

                return $scope.isView ? '' : actions;
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

                var status = '<span class="' + label +'">' + text + '</span>';

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

                var status = '<span class="' + label +'">' + text + '</span>';

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

    $scope.editDisabilityEmployee = function(id){
        $scope.employee.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});

app.controller('ModalInstanceSideHealthDamageQlDiagnosticListCtrl', function ($rootScope, $stateParams, $scope, employee, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var request = {};

    $scope.diagnostic = {
        id: 0,
        code:"",
        description:"",
        isActive: true
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.diagnostic);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function ()
    {
        if ($scope.diagnostic.id != 0) {
            var req = {
                id: $scope.diagnostic.id,
            };
            $http({
                method: 'GET',
                url: 'api/disability-diagnostic',
                params: req
            })
                .catch(function(e, code){
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () { $state.go(messagered); }, 3000);
                    } else if (code == 404)
                    {
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function(){
                        $scope.diagnostic = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function(){
                        $scope.onCloseModal();
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }

    $scope.dtInstanceDisabilityDiagnosticList = {};
    $scope.dtOptionsDisabilityDiagnosticList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerEmployeeId = employee.id;
                return JSON.stringify(d);
            },
            url: 'api/disability-diagnostic-source-employee',
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

    $scope.dtColumnsDisabilityDiagnosticList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar diagnóstico"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return $scope.isView ? '' : actions;
            }),

        DTColumnBuilder.newColumn('code').withTitle("Código").withOption('width', 200),
        DTColumnBuilder.newColumn('description').withTitle("Diagnóstico"),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';

                if (data.isActive != null || data.isActive != undefined) {
                    if (data.isActive) {
                        label = 'label label-success';
                    } else {
                        label = 'label label-danger';
                    }
                }

                var status = '<span class="' + label +'">' + data.status + '</span>';

                return status;
        })
    ];

    var loadRow = function () {
        $("#dtDisabilityDiagnosticList a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editDisabilityDiagnostic(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityDiagnostic.reloadData();
    };

    $scope.viewDisabilityDiagnostic = function (id) {
        $scope.diagnostic.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityDiagnostic = function(id){
        $scope.diagnostic.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});
