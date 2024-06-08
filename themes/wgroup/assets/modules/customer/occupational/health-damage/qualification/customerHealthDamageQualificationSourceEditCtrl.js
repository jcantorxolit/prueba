'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerHealthDamageQualificationSourceEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', '$document', 'toaster', 'FileUploader',
    function ($scope, $stateParams, $log, $compile, $state,
        SweetAlert, $rootScope, $http, $timeout, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $uibModal, flowFactory, cfpLoadingBar,
        $filter, $aside, $document, toaster, FileUploader) {

        var log = $log;


        var request = {};
        var attachmentUploadedId = 0;
        var currentId = $scope.$parent.currentId;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        log.info("loading..customerHealthDamageQualificationSourceEditCtrl con el id: ", currentId);

        $scope.arl = $rootScope.parameters("arl");
        $scope.documentType = $rootScope.parameters("customer_document_type");
        $scope.tiposdoc = $rootScope.parameters("tipodoc");

        $scope.diagnosticList = $rootScope.parameters("work_health_damage_diagnostic");
        $scope.lateralities = $rootScope.parameters("work_health_damage_laterality");
        $scope.entityPerformsDiagnostic = $rootScope.parameters("work_health_damage_entity_perform_diagnostic");
        $scope.codeCIE10 = $rootScope.parameters("work_health_damage_code_cie_10");
        $scope.applicants = $rootScope.parameters("work_health_damage_applicant");
        $scope.directors = $rootScope.parameters("work_health_damage_apt");
        $scope.supports = $rootScope.parameters("work_health_damage_diagnostic_support");
        $scope.documents = $rootScope.parameters("work_health_damage_diagnostic_document");

        $scope.qualifyingEntities = $rootScope.parameters("work_health_damage_entity_qualify");
        $scope.qualifiedOrigins = $rootScope.parameters("work_health_damage_entity_qualify_origin");
        $scope.controversyStatus = $rootScope.parameters("work_health_damage_controversy_status");

        $scope.classifications = $rootScope.parameters("work_health_damage_qs_document_type");
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
            if ($scope.qs.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.qs.id);
                var req = {
                    id: $scope.qs.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs',
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
                            $scope.qs = response.data.result;
                            $scope.canShow = true;

                            if ($scope.qs.diagnostic != null && $scope.qs.diagnostic != '') {
                                $scope.diagnostic = $scope.qs.diagnostic;

                                if ($scope.diagnostic.dateOf) {
                                    $scope.diagnostic.dateOf = new Date($scope.diagnostic.dateOf.date);
                                }

                                if ($scope.diagnostic.requestDate) {
                                    $scope.diagnostic.requestDate = new Date($scope.diagnostic.requestDate.date);
                                }

                                if ($scope.diagnostic.dateSend) {
                                    $scope.diagnostic.dateSend = new Date($scope.diagnostic.dateSend.date);
                                }

                                if ($scope.diagnostic.dateReceived) {
                                    $scope.diagnostic.dateReceived = new Date($scope.diagnostic.dateReceived.date);
                                }

                                $scope.diagnosticAttachment.entityId = $scope.diagnostic.id;
                                $scope.reloadDataHealthDamageQsDiagnosticDocument();
                            }

                            if ($scope.qs.opportunity != null && $scope.qs.opportunity != '') {
                                $scope.opportunity = $scope.qs.opportunity;

                                if ($scope.opportunity.dateOf) {
                                    $scope.opportunity.dateOf = new Date($scope.opportunity.dateOf.date);
                                }

                                if ($scope.opportunity.notificationDate) {
                                    $scope.opportunity.notificationDate = new Date($scope.opportunity.notificationDate.date);
                                }

                                if ($scope.opportunity.filingDate) {
                                    $scope.opportunity.filingDate = new Date($scope.opportunity.filingDate.date);
                                }

                                $scope.opportunityDetail.customerHealthDamageQsFirstOpportunityId = $scope.opportunity.id;
                                $scope.opportunityAttachment.entityId = $scope.opportunity.id;

                                $scope.reloadDataHealthDamageQsFirstOpportunityDetail();
                                $scope.reloadDataHealthDamageQsFirstOpportunityDocument();
                            }

                            if ($scope.qs.regional != null && $scope.qs.regional != '') {
                                $scope.regional = $scope.qs.regional;

                                if ($scope.regional.dateOf) {
                                    $scope.regional.dateOf = new Date($scope.regional.dateOf.date);
                                }

                                if ($scope.regional.notificationDate) {
                                    $scope.regional.notificationDate = new Date($scope.regional.notificationDate.date);
                                }

                                if ($scope.regional.filingDate) {
                                    $scope.regional.filingDate = new Date($scope.regional.filingDate.date);
                                }

                                $scope.regionalDetail.customerHealthDamageQsRegionalBoardId = $scope.regional.id;
                                $scope.regionalAttachment.entityId = $scope.regional.id;

                                $scope.reloadDataHealthDamageQsRegionalBoardDetail();
                                $scope.reloadDataHealthDamageQsRegionalBoardDocument();
                            }

                            if ($scope.qs.national != null && $scope.qs.national != '') {
                                $scope.national = $scope.qs.national;

                                if ($scope.national.dateOf) {
                                    $scope.national.dateOf = new Date($scope.national.dateOf.date);
                                }

                                if ($scope.national.notificationDate) {
                                    $scope.national.notificationDate = new Date($scope.national.notificationDate.date);
                                }

                                $scope.nationalDetail.customerHealthDamageQsNationalBoardId = $scope.national.id;
                                $scope.nationalAttachment.entityId = $scope.national.id;

                                $scope.reloadDataHealthDamageQsNationalBoardDetail();
                                $scope.reloadDataHealthDamageQsNationalBoardDocument();
                            }

                            if ($scope.qs.justiceFirst != null && $scope.qs.justiceFirst != '') {
                                $scope.justiceFirst = $scope.qs.justiceFirst;

                                if ($scope.justiceFirst.dateOf) {
                                    $scope.justiceFirst.dateOf = new Date($scope.justiceFirst.dateOf.date);
                                }

                                $scope.justiceFirstDetail.customerHealthDamageQsJusticeBoardId = $scope.justiceFirst.id;
                                $scope.justiceFirstAttachment.entityId = $scope.justiceFirst.id;

                                $scope.reloadDataHealthDamageQsJusticeBoardFirstDetail();
                                $scope.reloadDataHealthDamageQsJusticeBoardFirstDocument();
                            }

                            if ($scope.qs.justiceSecond != null && $scope.qs.justiceSecond != '') {
                                $scope.justiceSecond = $scope.qs.justiceSecond;

                                if ($scope.justiceSecond.dateOf) {
                                    $scope.justiceSecond.dateOf = new Date($scope.justiceSecond.dateOf.date);
                                }

                                $scope.justiceSecondDetail.customerHealthDamageQsJusticeBoardId = $scope.justiceSecond.id;
                                $scope.justiceSecondAttachment.entityId = $scope.justiceSecond.id;

                                $scope.reloadDataHealthDamageQsJusticeBoardSecondDetail();
                                $scope.reloadDataHealthDamageQsJusticeBoardSecondDocument();
                            }

                            if ($scope.qs.justiceThird != null && $scope.qs.justiceThird != '') {
                                $scope.justiceThird = $scope.qs.justiceThird;

                                if ($scope.justiceThird.dateOf) {
                                    $scope.justiceThird.dateOf = new Date($scope.justiceThird.dateOf.date);
                                }

                                $scope.justiceThirdDetail.customerHealthDamageQsJusticeBoardId = $scope.justiceThird.id;
                                $scope.justiceThirdAttachment.entityId = $scope.justiceThird.id;

                                $scope.reloadDataHealthDamageQsJusticeBoardThirdDetail();
                                $scope.reloadDataHealthDamageQsJusticeBoardThirdDocument();
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

        $scope.qs = {
            id: currentId,
            employee: null,
            arl: null,
            stepQualificationFirstOpportunity: false,
            stepQualificationRegionalBoard: false,
            stepQualificationNationalBoard: false,
            stepQualificationLaborJustice: false,
            stepSecondInstance: false,
            stepThirdInstance: false,
            isActive: true,
            diagnostic: null,
            opportunity: null,
            regional: null,
            national: null,
            justiceFirst: null,
            justiceSecond: null,
            justiceThird: null,
        };

        var init = function () {
            $scope.diagnostic = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                dateOf: null,
                diagnostic: null,
                laterality: null,
                entityPerformsDiagnostic: null,
                codeCIE10: null,
                description: '',
                isRequestedSupport: true,
                requestDate: null,
                applicant: null,
                directorApt: null,
                dateSend: null,
                dateReceived: null,
                supports: [],
                documents: [],
            };

            $scope.diagnosticAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.diagnostic.id,
                entityCode: 'diagnostic',
                entityName: 'Diagnóstico',
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

            $scope.opportunity = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                dateOf: null,
                opinionNumber: 0,
                qualifyingEntity: null,
                notificationDate: null,
                description: '',
                filingDate: null,
                isRemainedFirm: true,
            };

            $scope.opportunityDetail = {
                id: 0,
                customerHealthDamageQsFirstOpportunityId: $scope.opportunity.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };

            $scope.opportunityAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.opportunity.id,
                entityCode: 'opportunity',
                entityName: 'Calificación Primera Oportunidad',
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
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                dateOf: null,
                opinionNumber: 0,
                qualifyingEntity: null,
                notificationDate: null,
                description: '',
                filingDate: null,
                isRemainedFirm: true,
            };

            $scope.regionalDetail = {
                id: 0,
                customerHealthDamageQsRegionalBoardId: $scope.regional.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };

            $scope.regionalAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.regional.id,
                entityCode: 'regional',
                entityName: 'Calificación Junta Regional',
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
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                dateOf: null,
                opinionNumber: 0,
                notificationDate: null,
                description: '',
            };

            $scope.nationalDetail = {
                id: 0,
                customerHealthDamageQsNationalBoardId: $scope.national.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };

            $scope.nationalAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.national.id,
                entityCode: 'national',
                entityName: 'Calificación Junta Nacional',
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
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                instance: 'first',
                dateOf: null,
                judgment: '',
                claimant: '',
                defendant: '',
                numberProcess: '',
            };

            $scope.justiceFirstDetail = {
                id: 0,
                customerHealthDamageQsJusticeBoardId: $scope.justiceFirst.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };

            $scope.justiceFirstAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.justiceFirst.id,
                entityCode: 'justiceFirst',
                entityName: 'Calificación Justicia Laboral / Primera Instancia',
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
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                instance: 'second',
                dateOf: null,
                judgment: '',
                claimant: '',
                defendant: '',
                numberProcess: '',
            };

            $scope.justiceSecondDetail = {
                id: 0,
                customerHealthDamageQsJusticeBoardId: $scope.justiceSecond.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };

            $scope.justiceSecondAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.justiceSecond.id,
                entityCode: 'justiceSecond',
                entityName: 'Calificación Justicia Laboral / Segunda Instancia',
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

            $scope.justiceThird = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                instance: 'third',
                dateOf: null,
                judgment: '',
                claimant: '',
                defendant: '',
                numberProcess: '',
            };

            $scope.justiceThirdDetail = {
                id: 0,
                customerHealthDamageQsJusticeBoardId: $scope.justiceThird.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };

            $scope.justiceThirdAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.justiceThird.id,
                entityCode: 'justiceThird',
                entityName: 'Calificación Justicia Laboral / Tercera Instancia',
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

        $scope.master = $scope.qs;

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

                $scope.qs = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};

            var data = JSON.stringify($scope.qs);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/qs/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {

                    $scope.qs = response.data.result;
                    $scope.canShow = true;

                    /*$scope.diagnostic.customerHealthDamageQualificationSourceId = $scope.qs.id;
                     $scope.opportunity.customerHealthDamageQualificationSourceId = $scope.qs.id;
                     $scope.regional.customerHealthDamageQualificationSourceId = $scope.qs.id;
                     $scope.national.customerHealthDamageQualificationSourceId = $scope.qs.id;
                     $scope.justiceFirst.customerHealthDamageQualificationSourceId = $scope.qs.id;
                     $scope.justiceSecond.customerHealthDamageQualificationSourceId = $scope.qs.id;
                     $scope.justiceThird.customerHealthDamageQualificationSourceId = $scope.qs.id;*/
                    init();
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


        //----------------------------------------------------------------DIAGNOSTIC TAB


        //----------------------------------------------------------------SUPPORT
        $scope.onAddDiagnosticSupport = function () {

            $timeout(function () {
                if ($scope.diagnostic.supports == null) {
                    $scope.diagnostic.supports = [];
                }
                $scope.diagnostic.supports.push(
                    {
                        id: 0,
                        customerHealthDamageQualificationSourceDiagnosticId: 0,
                        support: null,
                        description: "",
                    }
                );
            });
        };

        $scope.onRemoveDiagnosticSupport = function (index) {
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
                            var date = $scope.diagnostic.supports[index];

                            $scope.diagnostic.supports.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/health-damage/qs/diagnostic-support/delete',
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

        //----------------------------------------------------------------DOCUMENT
        $scope.onAddDiagnosticDocument = function () {

            $timeout(function () {
                if ($scope.diagnostic.documents == null) {
                    $scope.diagnostic.documents = [];
                }
                $scope.diagnostic.documents.push(
                    {
                        id: 0,
                        customerHealthDamageQualificationSourceDiagnosticId: 0,
                        document: null,
                        description: "",
                    }
                );
            });
        };

        $scope.onRemoveDiagnosticSupport = function (index) {
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
                            var date = $scope.diagnostic.documents[index];

                            $scope.diagnostic.documents.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/health-damage/qs/diagnostic-document/delete',
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


        $scope.saveHealthDamageQsDiagnostic = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.diagnostic);
            req.data = Base64.encode(data);

            if ($scope.diagnostic.laterality != null && $scope.diagnostic.entityPerformsDiagnostic != null && $scope.diagnostic.codeCIE10 != null && $scope.diagnostic.applicant != null && $scope.diagnostic.directorApt != null) {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/diagnostic/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {

                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');

                        $scope.diagnostic = response.data.result;

                        $scope.diagnostic.dateOf = new Date($scope.diagnostic.dateOf.date);
                        $scope.diagnostic.requestDate = new Date($scope.diagnostic.requestDate.date);
                        $scope.diagnostic.dateSend = new Date($scope.diagnostic.dateSend.date);
                        $scope.diagnostic.dateReceived = new Date($scope.diagnostic.dateReceived.date);

                        $scope.clearHealthDamageQsDiagnosticDocument();
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


        //------------------------------------------------------------------------DIAGNOSTIC DOCUMENT
        $scope.dtInstanceHealthDamageQsDiagnosticDocument = {};
        $scope.dtOptionsHealthDamageQsDiagnosticDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationSourceId = $scope.qs.id;
                    d.entityCode = "diagnostic";
                    d.entityId = $scope.diagnostic.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-document',
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
                loadRowQsDiagnosticDocument();
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

        $scope.dtColumnsHealthDamageQsDiagnosticDocument = [
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
            DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 200).withOption('defaultContent', ''),
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

        var loadRowQsDiagnosticDocument = function () {

            $("#dtHealthDamageQsDiagnosticDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsDiagnosticDocument(id);
            });

            $("#dtHealthDamageQsDiagnosticDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQsDiagnosticDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/document/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsDiagnosticDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsDiagnosticDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsDiagnosticDocument = dtInstance;
            $scope.reloadDataHealthDamageQsDiagnosticDocument();
        };

        $scope.reloadDataHealthDamageQsDiagnosticDocument = function () {
            if ($scope.dtInstanceHealthDamageQsDiagnosticDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsDiagnosticDocument.reloadData();
            }
        };

        $scope.editHealthDamageQsDiagnosticDocument = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/document',
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
                            $scope.diagnosticAttachment = response.data.result;

                            if ($scope.diagnosticAttachment.startDate != null) {
                                $scope.diagnosticAttachment.startDate = new Date($scope.diagnosticAttachment.startDate.date);
                            }

                            if ($scope.diagnosticAttachment.endDate != null) {
                                $scope.diagnosticAttachment.endDate = new Date($scope.diagnosticAttachment.endDate.date);
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

        $scope.saveHealthDamageQsDiagnosticDocument = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();

            var data = JSON.stringify($scope.diagnosticAttachment);
            req.data = Base64.encode(data)
            if ($scope.diagnosticAttachment.type != null && $scope.diagnosticAttachment.name != "" && $scope.diagnosticAttachment.status != null && $scope.diagnosticAttachment.description != "") {


                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/document/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');

                        if ($scope.uploaderDiagnostic.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderDiagnostic.uploadAll();
                        } else {
                            $scope.clearHealthDamageQsDiagnosticDocument()
                            $scope.reloadDataHealthDamageQsDiagnosticDocument()
                            $scope.reloadDataHealthDamageQsDocument()
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
        $scope.clearHealthDamageQsDiagnosticDocument = function () {
            $scope.diagnosticAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.diagnostic.id,
                entityCode: 'diagnostic',
                entityName: 'Diagnóstico',
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


        //----------------------------------------------------------------FIRST OPPORTUNITY TAB

        $scope.saveHealthDamageQsFirstOpportunity = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.opportunity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/qs/opportunity/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {

                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');

                    $scope.opportunity = response.data.result;

                    $scope.opportunity.dateOf = new Date($scope.opportunity.dateOf.date);
                    $scope.opportunity.notificationDate = new Date($scope.opportunity.notificationDate.date);
                    $scope.opportunity.filingDate = new Date($scope.opportunity.filingDate.date);

                    $scope.clearHealthDamageQsFirstOpportunityDetail();
                    $scope.clearHealthDamageQsFirstOpportunityDocument();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }


        //------------------------------------------------------------------------FIRST OPPORTUNITY DETAIL
        request.customer_health_damage_id = $scope.opportunity.id;

        $scope.dtInstanceHealthDamageQsOpportunityDetail = {};
        $scope.dtOptionsHealthDamageQsOpportunityDetail = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQsFirstOpportunityId = $scope.opportunity.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-opportunity-detail',
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
                loadRowQsOpportunityDetail();
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

        $scope.dtColumnsHealthDamageQsOpportunityDetail = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("seguimiento_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("seguimiento_delete")) {
                        actions += deleteTemplate;
                    }

                    return !$scope.isView ? actions : null;
                }),
            DTColumnBuilder.newColumn('diagnostic').withTitle("Diagnóstico").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('origen').withTitle("Origen calificado").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado de controversia").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRowQsOpportunityDetail = function () {

            $("#dtHealthDamageQsOpportunityDetail a.editRow").on("click", function () {
                var id = $(this).data("id");
                console.log(id);
                $scope.editHealthDamageQsFirstOpportunityDetail(id);
            });

            $("#dtHealthDamageQsOpportunityDetail a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/opportunity-detail/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {
                                $scope.reloadDataHealthDamageQsFirstOpportunityDetail();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsOpportunityDetailCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsOpportunityDetail = dtInstance;
            $scope.reloadDataHealthDamageQsFirstOpportunityDetail();
        };

        $scope.reloadDataHealthDamageQsFirstOpportunityDetail = function () {
            request.customer_health_damage_id = $scope.opportunity.id;
            if ($scope.dtInstanceHealthDamageQsOpportunityDetail.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsOpportunityDetail.reloadData();
            }
        };

        $scope.editHealthDamageQsFirstOpportunityDetail = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/opportunity-detail',
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
                            $scope.opportunityDetail = response.data.result;
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
        };

        $scope.saveHealthDamageQsFirstOpportunityDetail = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();

            var data = JSON.stringify($scope.opportunityDetail);
            req.data = Base64.encode(data);
            if ($scope.opportunityDetail.diagnostic != null && $scope.opportunityDetail.qualifiedOrigin != null && $scope.opportunityDetail.controversyStatus != null) {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/opportunity-detail/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        $scope.clearHealthDamageQsFirstOpportunityDetail()
                        $scope.reloadDataHealthDamageQsFirstOpportunityDetail()
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

        $scope.clearHealthDamageQsFirstOpportunityDetail = function () {
            $scope.opportunityDetail = {
                id: 0,
                customerHealthDamageQsFirstOpportunityId: $scope.opportunity.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };
        };


        //------------------------------------------------------------------------FIRST OPPORTUNITY DOCUMENT
        $scope.dtInstanceHealthDamageQsOpportunityDocument = {};
        $scope.dtOptionsHealthDamageQsOpportunityDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationSourceId = $scope.qs.id;
                    d.entityCode = "opportunity";
                    d.entityId = $scope.opportunity.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-document',
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
                loadRowQsFirstOpportunityDocument();
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

        $scope.dtColumnsHealthDamageQsOpportunityDocument = [
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
            DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 200).withOption('defaultContent', ''),
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

        var loadRowQsFirstOpportunityDocument = function () {

            $("#dtHealthDamageQsOpportunityDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsFirstOpportunityDocument(id);
            });

            $("#dtHealthDamageQsOpportunityDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQsOpportunityDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/document/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsFirstOpportunityDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsOpportunityDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsOpportunityDocument = dtInstance;
            $scope.reloadDataHealthDamageQsFirstOpportunityDocument();
        };

        $scope.reloadDataHealthDamageQsFirstOpportunityDocument = function () {
            if ($scope.dtInstanceHealthDamageQsOpportunityDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsOpportunityDocument.reloadData();
            }
        };

        $scope.editHealthDamageQsFirstOpportunityDocument = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/document',
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

        $scope.saveHealthDamageQsFirstOpportunityDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.opportunityAttachment);
            req.data = Base64.encode(data);
            if ($scope.opportunityAttachment.type != null && $scope.opportunityAttachment.name != "" && $scope.opportunityAttachment.status != null && $scope.opportunityAttachment.description != "") {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/document/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderOpportunity.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderOpportunity.uploadAll();
                        } else {
                            $scope.clearHealthDamageQsFirstOpportunityDocument();
                            $scope.reloadDataHealthDamageQsFirstOpportunityDocument();
                            $scope.reloadDataHealthDamageQsDocument();
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

        $scope.clearHealthDamageQsFirstOpportunityDocument = function () {
            $scope.opportunityAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.opportunity.id,
                entityCode: 'opportunity',
                entityName: 'Calificación Primera Oportunidad',
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

        $scope.saveHealthDamageQsRegionalBoard = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.regional);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/qs/regional/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                    $scope.regional = response.data.result;

                    $scope.regional.dateOf = new Date($scope.regional.dateOf.date);
                    $scope.regional.notificationDate = new Date($scope.regional.notificationDate.date);
                    $scope.regional.filingDate = new Date($scope.regional.filingDate.date);

                    $scope.clearHealthDamageQsRegionalBoardDetail();
                    $scope.clearHealthDamageQsRegionalBoardDocument();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }


        //------------------------------------------------------------------------REGIONAL BOARD DETAIL
        request.customer_health_damage_regional_id = $scope.regional.id;

        $scope.dtInstanceHealthDamageQsRegionalDetail = {};
        $scope.dtOptionsHealthDamageQsRegionalDetail = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQsRegionalBoardId = $scope.regional.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-regional-detail',
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
                loadRowQsRegionalDetail();
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

        $scope.dtColumnsHealthDamageQsRegionalDetail = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("seguimiento_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("seguimiento_delete")) {
                        actions += deleteTemplate;
                    }

                    return !$scope.isView ? actions : null;
                }),
            DTColumnBuilder.newColumn('diagnostic').withTitle("Diagnóstico").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('origen').withTitle("Origen calificado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado de controversia").withOption('width', 200).withOption('defaultContent', ''),

        ];

        var loadRowQsRegionalDetail = function () {

            $("#dtHealthDamageQsRegionalDetail a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsRegionalBoardDetail(id);
            });

            $("#dtHealthDamageQsRegionalDetail a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/regional-detail/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsRegionalBoardDetail();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsRegionalDetailCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsRegionalDetail = dtInstance;
            $scope.reloadDataHealthDamageQsRegionalBoardDetail();
        };

        $scope.reloadDataHealthDamageQsRegionalBoardDetail = function () {
            request.customer_health_damage_regional_id = $scope.regional.id;
            if ($scope.dtInstanceHealthDamageQsRegionalDetail.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsRegionalDetail.reloadData();
            }
        };

        $scope.editHealthDamageQsRegionalBoardDetail = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/regional-detail',
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
                            $scope.regionalDetail = response.data.result;
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
        };

        $scope.saveHealthDamageQsRegionalBoardDetail = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();

            var data = JSON.stringify($scope.regionalDetail);
            req.data = Base64.encode(data);
            if ($scope.regionalDetail.diagnostic != null && $scope.regionalDetail.qualifiedOrigin != null && $scope.regionalDetail.controversyStatus != null) {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/regional-detail/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        $scope.clearHealthDamageQsRegionalBoardDetail()
                        $scope.reloadDataHealthDamageQsRegionalBoardDetail()
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

        $scope.clearHealthDamageQsRegionalBoardDetail = function () {
            $scope.regionalDetail = {
                id: 0,
                customerHealthDamageQsRegionalBoardId: $scope.regional.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };
        };


        //------------------------------------------------------------------------REGIONAL BOARD DOCUMENT
        $scope.dtInstanceHealthDamageQsRegionalDocument = {};
        $scope.dtOptionsHealthDamageQsRegionalDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationSourceId = $scope.qs.id;
                    d.entityCode = "regional";
                    d.entityId = $scope.regional.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-document',
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
                loadRowQsRegionalDocument();
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

        $scope.dtColumnsHealthDamageQsRegionalDocument = [
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
            DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 200).withOption('defaultContent', ''),
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

        var loadRowQsRegionalDocument = function () {

            $("#dtHealthDamageQsRegionalDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsRegionalBoardDocument(id);
            });

            $("#dtHealthDamageQsRegionalDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQsRegionalDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/document/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsRegionalBoardDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsRegionalDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsRegionalDocument = dtInstance;
            $scope.reloadDataHealthDamageQsRegionalBoardDocument();
        };

        $scope.reloadDataHealthDamageQsRegionalBoardDocument = function () {
            if ($scope.dtInstanceHealthDamageQsRegionalDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsRegionalDocument.reloadData();
            }
        };

        $scope.editHealthDamageQsRegionalBoardDocument = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/document',
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

        $scope.saveHealthDamageQsRegionalBoardDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.regionalAttachment);
            req.data = Base64.encode(data);
            if ($scope.regionalAttachment.type != null && $scope.regionalAttachment.name != "" && $scope.regionalAttachment.status != null && $scope.regionalAttachment.description != "") {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/document/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderRegional.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderRegional.uploadAll();
                        } else {
                            $scope.clearHealthDamageQsRegionalBoardDocument();
                            $scope.reloadDataHealthDamageQsRegionalBoardDocument();
                            $scope.reloadDataHealthDamageQsDocument();
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

        $scope.clearHealthDamageQsRegionalBoardDocument = function () {
            $scope.regionalAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.regional.id,
                entityCode: 'regional',
                entityName: 'Calificación Junta Regional',
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

        $scope.saveHealthDamageQsNationalBoard = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.national);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/qs/national/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                    $scope.national = response.data.result;

                    $scope.national.dateOf = new Date($scope.national.dateOf.date);
                    $scope.national.notificationDate = new Date($scope.national.notificationDate.date);

                    $scope.clearHealthDamageQsNationalBoardDetail()
                    $scope.clearHealthDamageQsNationalBoardDocument()
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }


        //------------------------------------------------------------------------NATIONAL BOARD DETAIL
        request.customer_health_damage_national_id = $scope.national.id;

        $scope.dtInstanceHealthDamageQsNationalDetail = {};
        $scope.dtOptionsHealthDamageQsNationalDetail = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQsNationalId = $scope.national.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-national-detail',
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
                loadRowQsNationalDetail();
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

        $scope.dtColumnsHealthDamageQsNationalDetail = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("seguimiento_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("seguimiento_delete")) {
                        actions += deleteTemplate;
                    }

                    return !$scope.isView ? actions : null;
                }),
            DTColumnBuilder.newColumn('diagnostic').withTitle("Diagnóstico").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('origen').withTitle("Origen calificado").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado de controversia").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRowQsNationalDetail = function () {

            $("#dtHealthDamageQsNationalDetail a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsNationalBoardDetail(id);
            });

            $("#dtHealthDamageQsNationalDetail a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/national-detail/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsNationalBoardDetail();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsNationalDetailCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsNationalDetail = dtInstance;
            $scope.reloadDataHealthDamageQsNationalBoardDetail();
        };

        $scope.reloadDataHealthDamageQsNationalBoardDetail = function () {
            request.customer_health_damage_national_id = $scope.national.id;
            if ($scope.dtInstanceHealthDamageQsNationalDetail.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsNationalDetail.reloadData();
            }
        };

        $scope.editHealthDamageQsNationalBoardDetail = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/national-detail',
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
                            $scope.nationalDetail = response.data.result;
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
        };

        $scope.saveHealthDamageQsNationalBoardDetail = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();

            var data = JSON.stringify($scope.nationalDetail);
            req.data = Base64.encode(data);
            if ($scope.nationalDetail.diagnostic != null && $scope.nationalDetail.qualifiedOrigin != null && $scope.nationalDetail.controversyStatus != null) {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/national-detail/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        $scope.clearHealthDamageQsNationalBoardDetail()
                        $scope.reloadDataHealthDamageQsNationalBoardDetail()
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

        $scope.clearHealthDamageQsNationalBoardDetail = function () {
            $scope.nationalDetail = {
                id: 0,
                customerHealthDamageQsNationalBoardId: $scope.national.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };
        };


        //------------------------------------------------------------------------NATIONAL BOARD DOCUMENT
        $scope.dtInstanceHealthDamageQsNationalDocument = {};
        $scope.dtOptionsHealthDamageQsNationalDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationSourceId = $scope.qs.id;
                    d.entityCode = "national";
                    d.entityId = $scope.national.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-document',
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
                loadRowQsNationalDocument();
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

        $scope.dtColumnsHealthDamageQsNationalDocument = [
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
            DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 200).withOption('defaultContent', ''),
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

        var loadRowQsNationalDocument = function () {

            $("#dtHealthDamageQsNationalDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsNationalBoardDocument(id);
            });

            $("#dtHealthDamageQsNationalDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQsNationalDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/document/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsNationalBoardDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsNationalDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsNationalDocument = dtInstance;
            $scope.reloadDataHealthDamageQsNationalBoardDocument();
        };

        $scope.reloadDataHealthDamageQsNationalBoardDocument = function () {
            if ($scope.dtInstanceHealthDamageQsNationalDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsNationalDocument.reloadData();
            }
        };

        $scope.editHealthDamageQsNationalBoardDocument = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/document',
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

        $scope.saveHealthDamageQsNationalBoardDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.nationalAttachment);
            req.data = Base64.encode(data);
            if ($scope.nationalAttachment.type != null && $scope.nationalAttachment.name != "" && $scope.nationalAttachment.status != null && $scope.nationalAttachment.description != "") {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/document/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderNational.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderNational.uploadAll();
                        } else {
                            $scope.clearHealthDamageQsNationalBoardDocument();
                            $scope.reloadDataHealthDamageQsNationalBoardDocument();
                            $scope.reloadDataHealthDamageQsDocument();
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

        $scope.clearHealthDamageQsNationalBoardDocument = function () {
            $scope.nationalAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.national.id,
                entityCode: 'national',
                entityName: 'Calificación Junta Nacional',
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

        $scope.saveHealthDamageQsJusticeBoardFirst = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.justiceFirst);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/qs/justice/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                    $scope.justiceFirst = response.data.result;

                    $scope.justiceFirst.dateOf = new Date($scope.justiceFirst.dateOf.date);
                    $scope.clearHealthDamageQsJusticeBoardFirstDetail()
                    $scope.clearHealthDamageQsJusticeBoardFirstDocument()
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }


        //------------------------------------------------------------------------JUSTICE BOARD FIRST DETAIL
        request.customer_health_damage_justiceFirst_id = $scope.justiceFirst.id;

        $scope.dtInstanceHealthDamageQsJusticeFirstDetail = {};
        $scope.dtOptionsHealthDamageQsJusticeFirstDetail = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQsId = $scope.justiceFirst.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-justice-detail',
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
                loadRowQsJusticeFirstDetail();
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

        $scope.dtColumnsHealthDamageQsJusticeFirstDetail = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("seguimiento_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("seguimiento_delete")) {
                        actions += deleteTemplate;
                    }

                    return !$scope.isView ? actions : null;
                }),

            DTColumnBuilder.newColumn('diagnostic').withTitle("Diagnóstico").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('origen').withTitle("Origen calificado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado de controversia").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRowQsJusticeFirstDetail = function () {

            $("#dtHealthDamageQsJusticeFirstDetail a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsJusticeBoardFirstDetail(id);
            });

            $("#dtHealthDamageQsJusticeFirstDetail a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/justice-detail/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsJusticeBoardFirstDetail();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsJusticeFirstDetailCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsJusticeFirstDetail = dtInstance;
            $scope.reloadDataHealthDamageQsJusticeBoardFirstDetail();
        };

        $scope.reloadDataHealthDamageQsJusticeBoardFirstDetail = function () {
            request.customer_health_damage_justiceFirst_id = $scope.justiceFirst.id;
            if ($scope.dtInstanceHealthDamageQsJusticeFirstDetail.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsJusticeFirstDetail.reloadData();
            }
        };

        $scope.editHealthDamageQsJusticeBoardFirstDetail = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/justice-detail',
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
                            $scope.justiceFirstDetail = response.data.result;
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
        };

        $scope.saveHealthDamageQsJusticeBoardFirstDetail = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();

            var data = JSON.stringify($scope.justiceFirstDetail);
            req.data = Base64.encode(data);
            if ($scope.justiceFirstDetail.diagnostic != null && $scope.justiceFirstDetail.qualifiedOrigin != null && $scope.justiceFirstDetail.controversyStatus != null) {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/justice-detail/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        $scope.clearHealthDamageQsJusticeBoardFirstDetail()
                        $scope.reloadDataHealthDamageQsJusticeBoardFirstDetail()
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

        $scope.clearHealthDamageQsJusticeBoardFirstDetail = function () {
            $scope.justiceFirstDetail = {
                id: 0,
                customerHealthDamageQsJusticeBoardId: $scope.justiceFirst.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };
        };


        //------------------------------------------------------------------------JUSTICE BOARD FIRST DOCUMENT
        $scope.dtInstanceHealthDamageQsJusticeFirstDocument = {};
        $scope.dtOptionsHealthDamageQsJusticeFirstDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationSourceId = $scope.qs.id;
                    d.entityCode = "justiceFirst";
                    d.entityId = $scope.justiceFirst.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-document',
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
                loadRowQsJusticeFirstDocument();
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

        $scope.dtColumnsHealthDamageQsJusticeFirstDocument = [
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
            DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 200).withOption('defaultContent', ''),
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

        var loadRowQsJusticeFirstDocument = function () {

            $("#dtHealthDamageQsJusticeFirstDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsJusticeBoardFirstDocument(id);
            });

            $("#dtHealthDamageQsJusticeFirstDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQsJusticeFirstDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/document/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsJusticeBoardFirstDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsJusticeFirstDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsJusticeFirstDocument = dtInstance;
            $scope.reloadDataHealthDamageQsJusticeBoardFirstDocument();
        };

        $scope.reloadDataHealthDamageQsJusticeBoardFirstDocument = function () {
            if ($scope.dtInstanceHealthDamageQsJusticeFirstDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsJusticeFirstDocument.reloadData();
            }
        };

        $scope.editHealthDamageQsJusticeBoardFirstDocument = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/document',
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

        $scope.saveHealthDamageQsJusticeBoardFirstDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.justiceFirstAttachment);
            req.data = Base64.encode(data);
            if ($scope.justiceFirstAttachment.type != null && $scope.justiceFirstAttachment.name != "" && $scope.justiceFirstAttachment.status != null && $scope.justiceFirstAttachment.description != "") {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/document/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderJusticeFirst.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderJusticeFirst.uploadAll();
                        } else {
                            $scope.clearHealthDamageQsJusticeBoardFirstDocument();
                            $scope.reloadDataHealthDamageQsJusticeBoardFirstDocument();
                            $scope.reloadDataHealthDamageQsDocument();
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

        $scope.clearHealthDamageQsJusticeBoardFirstDocument = function () {
            $scope.justiceFirstAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.justiceFirst.id,
                entityCode: 'justiceFirst',
                entityName: 'Calificación Justicia Laboral / Primera Instancia',
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

        $scope.saveHealthDamageQsJusticeBoardSecond = function () {

            if ($scope.justiceSecond.dateOf != null && $scope.justiceFirst.dateOf != null) {

                var result = compare($scope.justiceFirst.dateOf, $scope.justiceSecond.dateOf);

                if (result == 1) {
                    toaster.pop('error', 'Validacion', 'La fecha de la segunda instancia no puede ser menor a la fecha de la primera instancia');
                    $scope.justiceSecond.dateOf = null;
                    return;
                }
            }

            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.justiceSecond);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/qs/justice/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                    $scope.justiceSecond = response.data.result;

                    if ($scope.justiceSecond.dateOf != null) {
                        $scope.justiceSecond.dateOf = new Date($scope.justiceSecond.dateOf.date);
                    }

                    $scope.clearHealthDamageQsJusticeBoardSecondDetail()
                    $scope.clearHealthDamageQsJusticeBoardSecondDocument()
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }


        //------------------------------------------------------------------------JUSTICE BOARD SECOND DETAIL
        request.customer_health_damage_justiceSecond_id = $scope.justiceSecond.id;

        $scope.dtInstanceHealthDamageQsJusticeSecondDetail = {};
        $scope.dtOptionsHealthDamageQsJusticeSecondDetail = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQsId = $scope.justiceSecond.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-justice-detail',
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
                loadRowQsJusticeSecondDetail();
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

        $scope.dtColumnsHealthDamageQsJusticeSecondDetail = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("seguimiento_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("seguimiento_delete")) {
                        actions += deleteTemplate;
                    }

                    return !$scope.isView ? actions : null;
                }),
            DTColumnBuilder.newColumn('diagnostic').withTitle("Diagnóstico").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('origen').withTitle("Origen calificado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado de controversia").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRowQsJusticeSecondDetail = function () {

            $("#dtHealthDamageQsJusticeSecondDetail a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsJusticeBoardSecondDetail(id);
            });

            $("#dtHealthDamageQsJusticeSecondDetail a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/justice-detail/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsJusticeBoardSecondDetail();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsJusticeSecondDetailCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsJusticeSecondDetail = dtInstance;
            $scope.reloadDataHealthDamageQsJusticeBoardSecondDetail();
        };

        $scope.reloadDataHealthDamageQsJusticeBoardSecondDetail = function () {
            request.customer_health_damage_justiceSecond_id = $scope.justiceSecond.id;
            if ($scope.dtInstanceHealthDamageQsJusticeSecondDetail.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsJusticeSecondDetail.reloadData();
            }
        };

        $scope.editHealthDamageQsJusticeBoardSecondDetail = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/justice-detail',
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
                            $scope.justiceSecondDetail = response.data.result;
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
        };

        $scope.saveHealthDamageQsJusticeBoardSecondDetail = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();

            var data = JSON.stringify($scope.justiceSecondDetail);
            req.data = Base64.encode(data);
            if ($scope.justiceSecondDetail.diagnostic != null && $scope.justiceSecondDetail.qualifiedOrigin != null && $scope.justiceSecondDetail.controversyStatus != null) {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/justice-detail/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        $scope.clearHealthDamageQsJusticeBoardSecondDetail()
                        $scope.reloadDataHealthDamageQsJusticeBoardSecondDetail()
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

        $scope.clearHealthDamageQsJusticeBoardSecondDetail = function () {
            $scope.justiceSecondDetail = {
                id: 0,
                customerHealthDamageQsJusticeBoardId: $scope.justiceSecond.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };
        };


        //------------------------------------------------------------------------JUSTICE BOARD SECOND DOCUMENT
        $scope.dtInstanceHealthDamageQsJusticeSecondDocument = {};
        $scope.dtOptionsHealthDamageQsJusticeSecondDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationSourceId = $scope.qs.id;
                    d.entityCode = "justiceSecond";
                    d.entityId = $scope.justiceSecond.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-document',
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
                loadRowQsJusticeSecondDocument();
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

        $scope.dtColumnsHealthDamageQsJusticeSecondDocument = [
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
            DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 200).withOption('defaultContent', ''),
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

        var loadRowQsJusticeSecondDocument = function () {

            $("#dtHealthDamageQsJusticeSecondDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsJusticeBoardSecondDocument(id);
            });

            $("#dtHealthDamageQsJusticeSecondDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQsJusticeSecondDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/document/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsJusticeBoardSecondDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsJusticeSecondDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsJusticeSecondDocument = dtInstance;
            $scope.reloadDataHealthDamageQsJusticeBoardSecondDocument();
        };

        $scope.reloadDataHealthDamageQsJusticeBoardSecondDocument = function () {
            if ($scope.dtInstanceHealthDamageQsJusticeSecondDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsJusticeSecondDocument.reloadData();
            }
        };

        $scope.editHealthDamageQsJusticeBoardSecondDocument = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/document',
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

        $scope.saveHealthDamageQsJusticeBoardSecondDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.justiceSecondAttachment);
            req.data = Base64.encode(data);
            if ($scope.justiceSecondAttachment.type != null && $scope.justiceSecondAttachment.name != "" && $scope.justiceSecondAttachment.status != null && $scope.justiceSecondAttachment.description != "") {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/document/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderJusticeSecond.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderJusticeSecond.uploadAll();
                        } else {
                            $scope.clearHealthDamageQsJusticeBoardSecondDocument();
                            $scope.reloadDataHealthDamageQsJusticeBoardSecondDocument();
                            $scope.reloadDataHealthDamageQsDocument();
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

        $scope.clearHealthDamageQsJusticeBoardSecondDocument = function () {
            $scope.justiceSecondAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.justiceSecond.id,
                entityCode: 'justiceSecond',
                entityName: 'Calificación Justicia Laboral / Segunda Instancia',
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


        //----------------------------------------------------------------JUSTICE BOARD THIRD TAB

        $scope.saveHealthDamageQsJusticeBoardThird = function () {

            if ($scope.justiceThird.dateOf != null && $scope.justiceSecond.dateOf != null) {

                var result = compare($scope.justiceSecond.dateOf, $scope.justiceThird.dateOf);

                if (result == 1) {
                    toaster.pop('error', 'Validacion', 'La fecha de la  instancia de casacion no puede ser menor a la fecha de la segunda instancia');
                    $scope.justiceThird.dateOf = null;
                    return;
                }
            }

            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();
            var data = JSON.stringify($scope.justiceThird);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/health-damage/qs/justice/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                    $scope.justiceThird = response.data.result;

                    $scope.justiceThird.dateOf = new Date($scope.justiceThird.dateOf.date);
                    $scope.clearHealthDamageQsJusticeBoardThirdDetail()
                    $scope.clearHealthDamageQsJusticeBoardThirdDocument()
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        }


        //------------------------------------------------------------------------JUSTICE BOARD THIRD DETAIL
        request.customer_health_damage_justiceThird_id = $scope.justiceThird.id;

        $scope.dtInstanceHealthDamageQsJusticeThirdDetail = {};
        $scope.dtOptionsHealthDamageQsJusticeThirdDetail = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQsId = $scope.justiceThird.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-health-damage-qs-justice-detail',
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
                loadRowQsJusticeThirdDetail();
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

        $scope.dtColumnsHealthDamageQsJusticeThirdDetail = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("seguimiento_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("seguimiento_delete")) {
                        actions += deleteTemplate;
                    }

                    return !$scope.isView ? actions : null;
                }),
            DTColumnBuilder.newColumn('diagnostic').withTitle("Diagnóstico").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('origen').withTitle("Origen calificado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado de controversia").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRowQsJusticeThirdDetail = function () {

            $("#dtHealthDamageQsJusticeThirdDetail a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsJusticeBoardThirdDetail(id);
            });

            $("#dtHealthDamageQsJusticeThirdDetail a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/justice-detail/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsJusticeBoardThirdDetail();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsJusticeThirdDetailCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsJusticeThirdDetail = dtInstance;
            $scope.reloadDataHealthDamageQsJusticeBoardThirdDetail();
        };

        $scope.reloadDataHealthDamageQsJusticeBoardThirdDetail = function () {
            request.customer_health_damage_justiceThird_id = $scope.justiceThird.id;
            if ($scope.dtInstanceHealthDamageQsJusticeThirdDetail.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsJusticeThirdDetail.reloadData();
            }
        };

        $scope.editHealthDamageQsJusticeBoardThirdDetail = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/justice-detail',
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
                            $scope.justiceThirdDetail = response.data.result;
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
        };

        $scope.saveHealthDamageQsJusticeBoardThirdDetail = function () {
            var req = {};

            //$scope.restriction.examinationDate = $scope.restriction.examinationDate.toISOString();

            var data = JSON.stringify($scope.justiceThirdDetail);
            req.data = Base64.encode(data);
            if ($scope.justiceThirdDetail.diagnostic != null && $scope.justiceThirdDetail.qualifiedOrigin != null && $scope.justiceThirdDetail.controversyStatus != null) {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/justice-detail/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        $scope.clearHealthDamageQsJusticeBoardThirdDetail()
                        $scope.reloadDataHealthDamageQsJusticeBoardThirdDetail()
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

        $scope.clearHealthDamageQsJusticeBoardThirdDetail = function () {
            $scope.justiceThirdDetail = {
                id: 0,
                customerHealthDamageQsJusticeBoardId: $scope.justiceThird.id,
                diagnostic: null,
                qualifiedOrigin: null,
                controversyStatus: null,
            };
        };


        //------------------------------------------------------------------------JUSTICE BOARD THIRD DOCUMENT
        $scope.dtInstanceHealthDamageQsJusticeThirdDocument = {};
        $scope.dtOptionsHealthDamageQsJusticeThirdDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerHealthDamageQualificationSourceId = $scope.qs.id;
                    d.entityCode = "justiceThird";
                    d.entityId = $scope.justiceThird.id;
                },
                //data: request,
                url: 'api/customer/health-damage/qs/document',
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
                loadRowQsJusticeThirdDocument();
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

        $scope.dtColumnsHealthDamageQsJusticeThirdDocument = [
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
            DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 200).withOption('defaultContent', ''),
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

        var loadRowQsJusticeThirdDocument = function () {

            $("#dtHealthDamageQsJusticeThirdDocument a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editHealthDamageQsJusticeBoardThirdDocument(id);
            });

            $("#dtHealthDamageQsJusticeThirdDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQsJusticeThirdDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/document/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsJusticeBoardThirdDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsJusticeThirdDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsJusticeThirdDocument = dtInstance;
            $scope.reloadDataHealthDamageQsJusticeBoardThirdDocument();
        };

        $scope.reloadDataHealthDamageQsJusticeBoardThirdDocument = function () {
            if ($scope.dtInstanceHealthDamageQsJusticeThirdDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsJusticeThirdDocument.reloadData();
            }
        };

        $scope.editHealthDamageQsJusticeBoardThirdDocument = function (id) {
            if (id) {
                var req = { id: id };
                $http({
                    method: 'GET',
                    url: 'api/customer/health-damage/qs/document',
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
                            $scope.justiceThirdAttachment = response.data.result;

                            if ($scope.justiceThirdAttachment.startDate != null) {
                                $scope.justiceThirdAttachment.startDate = new Date($scope.justiceThirdAttachment.startDate.date);
                            }

                            if ($scope.justiceThirdAttachment.endDate != null) {
                                $scope.justiceThirdAttachment.endDate = new Date($scope.justiceThirdAttachment.endDate.date);
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

        $scope.saveHealthDamageQsJusticeBoardThirdDocument = function () {
            var req = {};

            var data = JSON.stringify($scope.justiceThirdAttachment);
            req.data = Base64.encode(data);
            if ($scope.justiceThirdAttachment.type != null && $scope.justiceThirdAttachment.name != "" && $scope.justiceThirdAttachment.status != null && $scope.justiceThirdAttachment.description != "") {
                return $http({
                    method: 'POST',
                    url: 'api/customer/health-damage/qs/document/save',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente...');
                        if ($scope.uploaderJusticeThird.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploaderJusticeThird.uploadAll();
                        } else {
                            $scope.clearHealthDamageQsJusticeBoardThirdDocument();
                            $scope.reloadDataHealthDamageQsJusticeBoardThirdDocument();
                            $scope.reloadDataHealthDamageQsDocument();
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

        $scope.clearHealthDamageQsJusticeBoardThirdDocument = function () {
            $scope.justiceThirdAttachment = {
                id: 0,
                customerHealthDamageQualificationSourceId: $scope.qs.id,
                entityId: $scope.justiceThird.id,
                entityCode: 'justiceThird',
                entityName: 'Calificación Justicia Laboral / Tercera Instancia',
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
        $scope.dtInstanceHealthDamageQsDocument = {};
        $scope.dtOptionsHealthDamageQsDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customer_health_damage_qs_id = $scope.qs.id;
                },
                url: 'api/customer/health-damage/qs/document-all',
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
                loadRowQsDocument();
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

        $scope.dtColumnsHealthDamageQsDocument = [
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
            DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 200).withOption('defaultContent', ''),
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

        var loadRowQsDocument = function () {

            $("#dtHealthDamageQsDocument a.downloadRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");

                if (url == "") {
                    toaster.pop("error", "Error en la descarga", "No existe un anexo para descargar");
                } else {
                    jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download?id=" + id;
                }
            });

            $("#dtHealthDamageQsDocument a.delRow").on("click", function () {
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
                                url: 'api/customer/health-damage/qs/document/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataHealthDamageQsDocument();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceHealthDamageQsDocumentCallback = function (dtInstance) {
            $scope.dtInstanceHealthDamageQsDocument = dtInstance;
            $scope.reloadDataHealthDamageQsDocument();
        };

        $scope.reloadDataHealthDamageQsDocument = function () {
            if ($scope.dtInstanceHealthDamageQsDocument.reloadData != undefined) {
                $scope.dtInstanceHealthDamageQsDocument.reloadData();
            }
        };

        $scope.onDownload = function () {
            jQuery("#download")[0].src = "api/customer/health-damage/qs/document/download-all?id=" + $scope.qs.id;
        };

        //----------------------------------------------------------------UPLOADER DIAGNOSTIC
        var uploaderDiagnostic = $scope.uploaderDiagnostic = new FileUploader({
            url: 'api/customer/health-damage/qs/document/upload',
            formData: [],
            removeAfterUpload: true
        });

        uploaderDiagnostic.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS
        uploaderDiagnostic.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = { id: attachmentUploadedId };
            item.formData.push(formData);
        };
        uploaderDiagnostic.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQsDiagnosticDocument();
            $scope.reloadDataHealthDamageQsDiagnosticDocument();
            $scope.reloadDataHealthDamageQsDiagnosticDocument();
        };


        //----------------------------------------------------------------UPLOADER OPPORTUNITY
        var uploaderOpportunity = $scope.uploaderOpportunity = new FileUploader({
            url: 'api/customer/health-damage/qs/document/upload',
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
            var formData = { id: attachmentUploadedId };
            item.formData.push(formData);
        };
        uploaderOpportunity.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQsFirstOpportunityDocument();
            $scope.reloadDataHealthDamageQsFirstOpportunityDocument();
            $scope.reloadDataHealthDamageQsDocument();
        };


        //----------------------------------------------------------------UPLOADER REGIONAL
        var uploaderRegional = $scope.uploaderRegional = new FileUploader({
            url: 'api/customer/health-damage/qs/document/upload',
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
            var formData = { id: attachmentUploadedId };
            item.formData.push(formData);
        };
        uploaderRegional.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQsRegionalBoardDocument();
            $scope.reloadDataHealthDamageQsRegionalBoardDocument();
            $scope.reloadDataHealthDamageQsDocument();
        };


        //----------------------------------------------------------------UPLOADER NATIONAL
        var uploaderNational = $scope.uploaderNational = new FileUploader({
            url: 'api/customer/health-damage/qs/document/upload',
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
            var formData = { id: attachmentUploadedId };
            item.formData.push(formData);
        };
        uploaderNational.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQsNationalBoardDocument();
            $scope.reloadDataHealthDamageQsNationalBoardDocument();
            $scope.reloadDataHealthDamageQsDocument();
        };


        //----------------------------------------------------------------UPLOADER JUSTICE FIRST
        var uploaderJusticeFirst = $scope.uploaderJusticeFirst = new FileUploader({
            url: 'api/customer/health-damage/qs/document/upload',
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
            var formData = { id: attachmentUploadedId };
            item.formData.push(formData);
        };
        uploaderJusticeFirst.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQsJusticeBoardFirstDocument();
            $scope.reloadDataHealthDamageQsJusticeBoardFirstDocument();
            $scope.reloadDataHealthDamageQsDocument();
        };

        //----------------------------------------------------------------UPLOADER JUSTICE SECOND
        var uploaderJusticeSecond = $scope.uploaderJusticeSecond = new FileUploader({
            url: 'api/customer/health-damage/qs/document/upload',
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
            var formData = { id: attachmentUploadedId };
            item.formData.push(formData);
        };
        uploaderJusticeSecond.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQsJusticeBoardSecondDocument();
            $scope.reloadDataHealthDamageQsJusticeBoardSecondDocument();
            $scope.reloadDataHealthDamageQsDocument();
        };


        //----------------------------------------------------------------UPLOADER JUSTICE THIRD
        var uploaderJusticeThird = $scope.uploaderJusticeThird = new FileUploader({
            url: 'api/customer/health-damage/qs/document/upload',
            formData: [],
            removeAfterUpload: true
        });

        uploaderJusticeThird.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS
        uploaderJusticeThird.onBeforeUploadItem = function (item) {
            console.info('onBeforeUploadItem', item);
            var formData = { id: attachmentUploadedId };
            item.formData.push(formData);
        };
        uploaderJusticeThird.onCompleteAll = function () {
            console.info('onCompleteAll');
            $scope.clearHealthDamageQsJusticeBoardThirdDocument();
            $scope.reloadDataHealthDamageQsJusticeBoardThirdDocument();
            $scope.reloadDataHealthDamageQsDocument();
        };

        //----------------------------------------------------------------EMPLOYEE

        $scope.onAddEmployee = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification/customer_absenteeism_disability_employee_modal.htm",
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

        $scope.onAddDisabilityEmployeeList = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideHealthQualificationSourceEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (employee) {
                //loadEmployees();
                var result = $filter('filter')($scope.employees, { id: employee.id });

                if (result.length == 0) {
                    $scope.employees.push(employee);
                }

                $scope.qs.employee = employee;
            });
        };

        //----------------------------------------------------------------ENABLE BUTTON OPTIONS

        $scope.saveStepQualificationFirstOpportunity = function () {
            toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente se habilitara la pestaña Calificación Primera Oportunidad');
            $scope.qs.stepQualificationFirstOpportunity = true;
            $scope.stepQualificationFirstOpportunityActive = true;

            save();
        }

        $scope.saveStepQualificationRegionalBoard = function () {
            toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente se habilitara la pestaña Calificación Junta Regional');
            $scope.qs.stepQualificationRegionalBoard = true;
            $scope.stepQualificationRegionalBoardActive = true;

            save();
        }

        $scope.saveStepQualificationNationalBoard = function () {
            toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente se habilitara la pestaña Calificación Junta Nacional');
            $scope.qs.stepQualificationNationalBoard = true;
            $scope.stepQualificationNationalBoardActive = true;

            save();
        }

        $scope.saveStepQualificationLaborJustice = function () {
            toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente se habilitara la pestaña Calificación Justicia Laboral');
            $scope.qs.stepQualificationLaborJustice = true;
            $scope.stepQualificationLaborJusticeActive = true
            save();
        }

        $scope.saveStepSecondInstance = function () {
            toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente se habilitara la pestaña Instancia Segunda');
            $scope.qs.stepSecondInstance = true;
            $scope.stepQualificationSecondInstanceActive = true
            save();
        }

        $scope.saveStepThirdInstance = function () {
            toaster.pop('success', 'Operación Exitosa', 'Se guardó la información exitosamente se habilitara la pestaña Instancia Casacion');
            $scope.qs.stepThirdInstance = true;
            $scope.stepQualificationThirdInstanceActive = true
            save();
        }

        //----------------------------------------------------------------DIAGNOSTICS

        $scope.onAddDisabilityDiagnostic = function (index) {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_diagnostic.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/qualification/customer_absenteeism_disability_diagnostic_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                resolve: {
                    employee: function () {
                        return $scope.qs.employee;
                    }
                },
                controller: 'ModalInstanceSideHealthDamageQsDiagnosticListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (diagnostic) {
                //loadEmployees();

                var result = $filter('filter')($scope.diagnostics, { id: diagnostic.id });

                if (result.length == 0) {
                    $scope.diagnostics.push(diagnostic);
                }

                switch (index) {
                    case 'opportunityDetail':
                        $scope.opportunityDetail.diagnostic = diagnostic;
                        break;

                    case 'regionalDetail':
                        $scope.regionalDetail.diagnostic = diagnostic;
                        break;

                    case 'nationalDetail':
                        $scope.nationalDetail.diagnostic = diagnostic;
                        break;

                    case 'justiceFirstDetail':
                        $scope.justiceFirstDetail.diagnostic = diagnostic;
                        break;

                    case 'justiceSecondDetail':
                        $scope.justiceSecondDetail.diagnostic = diagnostic;
                        break;

                    case 'justiceThirdDetail':
                        $scope.justiceThirdDetail.diagnostic = diagnostic;
                        break;

                    default:
                        $scope.diagnostic.codeCIE10 = diagnostic;
                }
            });
        };


        //----------------------------------------------------------------WATCHERS

        var convert = function (d) {
            // Converts the date in d to a date-object. The input can be:
            //   a date object: returned without modification
            //  an array      : Interpreted as [year,month,day]. NOTE: month is 0-11.
            //   a number     : Interpreted as number of milliseconds
            //                  since 1 Jan 1970 (a timestamp)
            //   a string     : Any format supported by the javascript engine, like
            //                  "YYYY/MM/DD", "MM/DD/YYYY", "Jan 31 2009" etc.
            //  an object     : Interpreted as an object with year, month and date
            //                  attributes.  **NOTE** month is 0-11.
            return (
                d.constructor === Date ? d :
                    d.constructor === Array ? new Date(d[0], d[1], d[2]) :
                        d.constructor === Number ? new Date(d) :
                            d.constructor === String ? new Date(d) :
                                typeof d === "object" ? new Date(d.year, d.month, d.date) :
                                    NaN
            );
        };

        var compare = function (a, b) {
            // Compare two dates (could be of any type supported by the convert
            // function above) and returns:
            //  -1 : if a < b
            //   0 : if a = b
            //   1 : if a > b
            // NaN : if a or b is an illegal date
            // NOTE: The code inside isFinite does an assignment (=).
            return (
                isFinite(a = convert(a).valueOf()) &&
                    isFinite(b = convert(b).valueOf()) ?
                    (a > b) - (a < b) :
                    NaN
            );
        };

        $scope.$watch("justiceSecond.dateOf", function () {

            if ($scope.justiceSecond.dateOf != null && $scope.justiceFirst.dateOf != null) {

                var result = compare($scope.justiceFirst.dateOf, $scope.justiceSecond.dateOf);

                if (result == 1) {
                    toaster.pop('error', 'Validacion', 'La fecha de la instancia segunda no puede ser menor a la fecha de la primera instancia');
                    $scope.justiceSecond.dateOf = null;
                }
            }

        });

        $scope.$watch("justiceThird.dateOf", function () {

            if ($scope.justiceThird.dateOf != null && $scope.justiceSecond.dateOf != null) {

                var result = compare($scope.justiceSecond.dateOf, $scope.justiceThird.dateOf);

                if (result == 1) {
                    toaster.pop('error', 'Validacion', 'La fecha de la  instancia de casacion no puede ser menor a la fecha de la segunda instancia');
                    $scope.justiceThird.dateOf = null;
                }
            }

        });

    }
]);

app.controller('ModalInstanceSideHealthQualificationSourceEmployeeCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

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

app.controller('ModalInstanceSideHealthDamageQsDiagnosticListCtrl', function ($rootScope, $stateParams, $scope, employee, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var request = {};

    $scope.diagnostic = {
        id: 0,
        code: "",
        description: "",
        isActive: true
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.diagnostic);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.diagnostic.id != 0) {
            var req = {
                id: $scope.diagnostic.id,
            };
            $http({
                method: 'GET',
                url: 'api/disability-diagnostic',
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
                        $scope.diagnostic = response.data.result;
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

                return actions;
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

    $scope.editDisabilityDiagnostic = function (id) {
        $scope.diagnostic.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});
