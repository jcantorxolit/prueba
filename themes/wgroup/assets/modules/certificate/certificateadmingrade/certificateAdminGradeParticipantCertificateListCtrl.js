'use strict';
/**
 * controller for Customers
 */
app.controller('certificateAdminGradeParticipantCertificateListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $aside) {

        var log = $log;
        var request = {};

        $scope.customerId = 0;
        $scope.grade = {};
        $scope.loading = true;
        $scope.isView = $scope.$parent.formMode == "view";
        $scope.isCreate = $scope.$parent.formMode == "create";
        $scope.currentId = 0;

        $scope.participant = {
            id: $scope.isCreate ? 0 : $scope.currentId,
            certificateGradeId: $scope.$parent.currentId,
            customer: null,
            documentType: null,
            identificationNumber: "",
            name: "",
            lastName: "",
            workCenter: null,
            price: null,
            channel: null,
            countryOrigin: null,
            countryResidence: null,
            isApproved: false,
            contacts: [],
        };

        var errorMessage = function (i) {
            toaster.pop('error', 'Error', 'Por favor diligencie los campos requeridos en este paso, antes de continuar al siguiente nivel.');
        };

        $scope.removeImage = function () {
            $scope.noImage = true;
        };

        $scope.onCancel = function () {
            if ($scope.isView) {
                //$state.go('app.participant.list');
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
                                if($scope.$parent != null){
                                    $scope.$parent.navToSection("list", "list");
                                }
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        $scope.changeProgram = function (item, model) {
            $scope.participant.defaultSpeciality = item.speciality;

            $scope.loadAgents(item.speciality.value);
        };

        $timeout(function () {
            loadGrade();
        }, 10);

        var loadGrade = function () {

            if ($scope.$parent.currentId > 0) {
                var req = {
                    id: $scope.$parent.currentId
                };

                $http({
                    method: 'GET',
                    url: 'api/certificate-grade',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.asesores.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Información no disponible", "Encuesta no encontrada", "error");
                            $timeout(function () {
                                //$state.go('app.grade.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.grade = response.data.result;
                        });
                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);
                    });
            }
        };

        $scope.onRemoveContact = function(index)
        {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado",
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
                            var contact = $scope.participant.contacts[index];

                            $scope.participant.contacts.splice(index, 1);

                            if (contact.id != 0) {
                                var req = {};
                                req.id = contact.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/certificate-grade-participant-contact/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function(e){
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function(){


                                });
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }


        var loadCustomer = function () {
            var req = {};
            var data = JSON.stringify($scope.customer);
            req.data = data;
            return $http({
                method: 'POST',
                url: 'api/project/customer',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {


                $timeout(function () {

                    $scope.customers = response.data.data;

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        loadCustomer();


        request.operation = "participant";
        request.certificate_grade_id = $scope.$parent.currentId;

        $scope.dtInstanceCertificateParticipant = {};
		$scope.dtOptionsCertificateParticipant = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/certificate-grade-participant',
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

        $scope.dtColumnsCertificateParticipant = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    var disabled = (data.hasCertificate) ? "" : "disabled";

                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Descargar certificado" data-id="' + data.id + '" ' + disabled + '  >' +
                        '   <i class="fa fa-download"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver participante" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar participante" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("clientes_view")) {
                        //actions += viewTemplate;
                    }

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_delete")) {
                        //actions += deleteTemplate;
                    }


                    return actions;
                }),

            DTColumnBuilder.newColumn('documentType.item').withTitle("Tipo de Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('identificationNumber').withTitle("Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('name').withTitle("Nombres").withOption('width', 200),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
            DTColumnBuilder.newColumn(null).withTitle("Empresa").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {

                return data.customer.length > 0 ? data.customer[0].item : data.customer.item;
            }),
            DTColumnBuilder.newColumn('price.amount').withTitle("Precio").withOption('width', 200),
            DTColumnBuilder.newColumn(null).withTitle("Anexos").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                var label = 'label label-warning';
                var text = 0;

                if (data.attachment != null || data.attachment != undefined) {
                    text = data.attachment;
                }

                var status = '<span class="' + label +'">' + text + ' Anexos </span>';

                return status;
            }),
            DTColumnBuilder.newColumn(null).withTitle("Cumple requisitos").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isApproved != null || data.isApproved != undefined) {
                    if (data.isApproved) {
                        label = 'label label-success';
                        text = 'Si cumple';
                    } else {
                        label = 'label label-danger';
                        text = 'No Cumple';
                    }
                }

                var status = '<span class="' + label +'">' + text + '</span>';

                return status;
            }),
        ];

        $scope.onViewParticipant = function (id) {
            $scope.currentId = id;
            $scope.participant.id = id;
            $scope.isView = true;
            $scope.onLoadRecord();
        };

        $scope.onEditParticipant = function (id) {
            $scope.workplace.id = id;
            $scope.isview = false;
            $scope.onLoadRecord()
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", 0);
            }
        };

        $scope.onGenerate = function () {
            var req = {};
            req.id = $scope.$parent.currentId;
            $http({
                method: 'POST',
                url: 'api/certificate-grade/generate',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                swal("Generacion Certificados", "Certificados generados satisfactoriamente", "info");
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
            }).finally(function () {

                $scope.reloadData();
            });
        };

        $scope.onClear = function () {
            $timeout(function () {
                $scope.participant = {
                    id: $scope.isCreate ? 0 : $scope.currentId,
                    certificateGradeId: $scope.$parent.currentId,
                    customer: null,
                    documentType: null,
                    identificationNumber: "",
                    name: "",
                    lastName: "",
                    workCenter: null,
                    price: null,
                    channel: null,
                    countryOrigin: null,
                    countryResidence: null,
                    isApproved: false,
                    contacts: [],
                };
            });
            $scope.isView = false;
        };

        var loadRow = function () {

            $("#dtCertificateParticipants a.editRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");
                //$scope.editTracking(id);
                if (url == "")
                {
                    SweetAlert.swal("Error en la descarga", "No existe un anexo para descargar", "error");
                }
                else
                {
                    jQuery("#downloadDocument")[0].src = "api/certificate-grade-participant-certificate/download?id=" + id;
                }
            });

            $("#dtCertificateParticipants a.viewRow").on("click", function () {
                var id = $(this).data("id");

                $scope.participant.id = id;
                $scope.onViewParticipant(id);

            });

            $("#dtCertificateParticipants a.delRow").on("click", function () {
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
                                url: 'api/certificate-grade-participant/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

        $scope.reloadData = function () {
            $scope.dtInstanceCertificateParticipant.reloadData();
        };

        $scope.onAddCustomer = function() {
            var modalInstance = $aside.open({
                templateUrl: 'app_modal_certificate_grade_participant_customer.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerCtrl',
                scope: $scope,
                resolve: {
                    participant: function () {
                        return $scope.participant;
                    }
                }
            });
            modalInstance.result.then(function () {
                loadCustomer();
            });
        };

    }]);



