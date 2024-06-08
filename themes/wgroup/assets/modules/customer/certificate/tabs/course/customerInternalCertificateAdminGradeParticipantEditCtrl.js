'use strict';
/**
 * controller for Customers
 */
app.controller('customerInternalCertificateAdminGradeParticipantEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', '$document',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $aside, $document) {

        var log = $log;
        var request = {};

        $scope.flowConfig = {target: '/api/customer-internal-certificate-grade-participant/upload', singleFile: true};
        $scope.customerId = $stateParams.customerId;
        $scope.grade = {};
        $scope.loading = true;
        $scope.isView = $scope.$parent.formMode == "view";
        $scope.isCreate = $scope.$parent.formMode == "create";
        $scope.currentId = 0;

        $scope.customers = [];
        $scope.employees = [];
        $scope.workCenters = $rootScope.parameters("certificate_grade_work_center");
        $scope.channels = $rootScope.parameters("certificate_grade_channel");
        $scope.countries = $rootScope.countries();

        $scope.documentTypes = $rootScope.parameters("tipodoc");
        $scope.extrainfo = $rootScope.parameters("extrainfo");

        $scope.agents = [];
        $scope.prices = [];

        $scope.participant = {
            id: $scope.isCreate ? 0 : $scope.currentId,
            certificateGradeId: $scope.$parent.currentId,
            logo: "",
            customer: {
                id: $stateParams.customerId
            },
            employee: null,
            price: null,
            channel: null,
            isApproved: false,
        };

        $scope.uploader = new Flow();

        if ($scope.participant.logo == '') {
            $scope.noImage = true;
        }

        var errorMessage = function (i) {
            toaster.pop('error', 'Error', 'Por favor diligencie los campos requeridos en este paso, antes de continuar al siguiente nivel.');
        };

        $scope.removeImage = function () {
            $scope.noImage = true;
        };

        $scope.master = $scope.participant;
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
                    log.info($scope.participant);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información del curso...", "success");
                    //your code for submit
                    log.info($scope.participant);
                    save();
                }

            },
            reset: function (form) {

                $scope.participant = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.participant);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-internal-certificate-grade-participant/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.reloadData();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.onClear();
            });

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
                                if ($scope.$parent != null) {
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
            afterInit();
            loadGrade();
        }, 10);

        var afterInit = function () {

        };

        $scope.onAddInfoDetail = function () {
            $timeout(function () {
                if ($scope.participant.contacts == null) {
                    $scope.participant.contacts = [];
                }
                $scope.participant.contacts.push(
                    {
                        id: 0,
                        value: "",
                        type: null
                    }
                );
            });
        }


        $scope.onLoadRecord = function () {
            console.log($scope.participant);
            if ($scope.participant.id) {
                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.participant.id);
                var req = {
                    id: $scope.participant.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer-internal-certificate-grade-participant',
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
                                //$state.go('app.participant.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.participant = response.data.result;
                            $scope.customerId = $scope.participant.customer.id;
                            loadPrices();

                            if ($scope.participant.logo != null && $scope.participant.logo.path != null) {
                                $scope.noImage = false;
                            } else {
                                $scope.noImage = true;
                            }
                        });
                    }).finally(function () {
                        $timeout(function () {
                            afterInit();
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            $document.scrollTop(40, 2000);
                        });
                    });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo asesor ");
                $scope.loading = false;
            }
        }

        $scope.removeImage = function () {
            $scope.noImage = true;
        };

        var loadPrices = function () {

            var req = {};

            req.customer_id = $scope.customerId;

            return $http({
                method: 'POST',
                url: 'api/customer/certificate-program',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {

                    var prices = response.data.data;

                    if (prices.length == 0) {
                        $scope.prices = []

                        var price = {
                            id: 0,
                            amount: $scope.grade.program.amount,
                        };
                        $scope.prices.push(price);
                    } else {
                        $scope.prices = []

                        var price = {
                            id: 0,
                            amount: $scope.grade.program.amount,
                        };
                        $scope.prices.push(price);

                        angular.forEach(prices, function (value, key) {

                            if (value.program.id == $scope.grade.program.id) {
                                var price = {
                                    id: value.id,
                                    amount: value.amount,
                                };
                                $scope.prices.push(price);
                            }

                        });
                    }
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });


        }

        var loadGrade = function () {

            if ($scope.$parent.currentId > 0) {
                var req = {
                    id: $scope.$parent.currentId
                };

                $http({
                    method: 'GET',
                    url: 'api/customer-internal-certificate-grade',
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
                            loadPrices();
                        });
                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);
                    });
            }
        };

        $scope.changeCustomer = function (item, model) {
            $scope.customerId = item.id;
            $scope.participant.price = null;
            loadPrices();
        };

        $scope.onRemoveContact = function (index) {
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
                                    url: 'api/customer-internal-certificate-grade-participant-contact/delete',
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

        request.operation = "participant";
        request.certificate_grade_id = $scope.$parent.currentId;

        $scope.dtInstanceCustomerInternalParticipant = {};
		$scope.dtOptionsCustomerInternalParticipant = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer-internal-certificate-grade-participant',
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

        $scope.dtColumnsCustomerInternalParticipant = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    var uploadTemplate = '<a class="btn btn-success btn-xs uploadRow lnk" href="#"  uib-tooltip="Adicionar anexo" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-paperclip"></i></a> ';

                    if ($rootScope.can("certificate_program_view")) {
                        actions += viewTemplate;
                    }

                    if ($rootScope.can("certificate_program_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("certificate_program_delete")) {
                        actions += deleteTemplate;
                    }

                    if ($rootScope.can("certificate_program_edit")) {
                        actions += uploadTemplate;
                    }

                    return actions;
                }),

            DTColumnBuilder.newColumn('employee.entity.documentType.item').withTitle("Tipo de Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('employee.entity.documentNumber').withTitle("Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('employee.entity.firstName').withTitle("Nombres").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('employee.entity.lastName').withTitle("Apellidos").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Empresa").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {

                    return data.customer.length > 0 ? data.customer[0].item : data.customer.item;
                }),
            DTColumnBuilder.newColumn('price.amount').withTitle("Precio").withOption('width', 200).withOption('defaultContent', '0'),
            DTColumnBuilder.newColumn(null).withTitle("Anexos").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-warning';
                    var text = 0;

                    if (data.attachment != null || data.attachment != undefined) {
                        text = data.attachment;
                    }

                    var status = '<span class="' + label + '">' + text + ' Anexos </span>';

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

                    var status = '<span class="' + label + '">' + text + '</span>';

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
            $scope.currentId = id;
            $scope.participant.id = id;
            $scope.isView = false;
            $scope.onLoadRecord();
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", 0);
            }
        };

        $scope.onClear = function () {
            $timeout(function () {
                $scope.participant = {
                    id: $scope.isCreate ? 0 : $scope.currentId,
                    certificateGradeId: $scope.$parent.currentId,
                    customer: null,
                    employee: null,
                    price: null,
                    channel: null,
                    isApproved: false,
                };
            });

            $timeout(function () {
                $scope.noImage = true;
                $document.scrollTop(40, 2000);
            });

            $scope.isView = false;
        };

        var loadRow = function () {

            $("#dtCustomerInternalParticipants a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onEditParticipant(id);
            });

            $("#dtCustomerInternalParticipants a.viewRow").on("click", function () {
                var id = $(this).data("id");

                $scope.participant.id = id;
                $scope.onViewParticipant(id);

            });

            $("#dtCustomerInternalParticipants a.uploadRow").on("click", function () {
                var id = $(this).data("id");

                $scope.participant.id = id;
                $scope.onAddDocument();
            });

            $("#dtCustomerInternalParticipants a.delRow").on("click", function () {
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
                                url: 'api/customer-internal-certificate-grade-participant/delete',
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
                        }
                    });
            });
        };

        $scope.onAddDocument = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_certificate_grade_participant_document.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/certificate/tabs/course/certificate_grade_participant_document_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerInternalParticipantDocumentCtrl',
                scope: $scope,
                resolve: {
                    participant: function () {
                        return $scope.participant;
                    },
                    grade: function () {
                        return $scope.grade;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        };

        $scope.dtInstanceCustomerInternalParticipantCallback = function(instance){
            $scope.dtInstanceCustomerInternalParticipant = instance;
        }

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerInternalParticipant.reloadData();
        };

        //----------------------------------------------------------------EMPLOYEE

        $scope.onAddEmployeeList = function () {

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/certificate/tabs/course/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideOccupationalInternalCertificateEmployeeListCtrl',
                scope: $scope,
                resolve: {
                    customer: function () {
                        return {id: $scope.customerId};
                    }
                }
            });
            modalInstance.result.then(function (employee) {
                initializeEmployee(employee);
            });

        };

        var initializeEmployee = function (employee) {
            var result = $filter('filter')($scope.employees, {id: employee.id});

            if (result.length == 0) {
                $scope.employees.push(employee);
            }

            console.log(employee);

            if ( employee.entity.birthDate != null) {
                employee.entity.birthDate = new Date(employee.entity.birthDate.date);
            }

            $scope.participant.employee = employee;
        }

    }]);

app.controller('ModalInstanceSideCustomerInternalParticipantDocumentCtrl', function ($rootScope, $scope, $uibModalInstance, participant, grade, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var attachmentUploadedId = 0;

    $scope.participantDocumentType = [];

    $scope.participantDocumentType = $rootScope.parameters("certificate_program_requirement");

    $scope.participant = participant;

    $scope.attachment = {
        id: 0,
        created_at: $filter('date')(new Date(), "dd/MM/yyyy HH:mm"),
        certificateGradeParticipantId: $scope.participant.id,
        agent: null,
        requirement: null,
        classification: null,
        status: null,
        version: 1,
        description: ""
    };

    var uploader = $scope.uploader = new FileUploader({
        url: 'api/customer-internal-certificate-grade-participant-document/upload',
        formData: []
    });

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item/*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

// CALLBACKS

    uploader.onWhenAddingFileFailed = function (item/*{File|FileLikeObject}*/, filter, options) {
        console.info('onWhenAddingFileFailed', item, filter, options);
    };
    uploader.onAfterAddingFile = function (fileItem) {
        console.info('onAfterAddingFile', fileItem);
    };
    uploader.onAfterAddingAll = function (addedFileItems) {
        console.info('onAfterAddingAll', addedFileItems);
    };
    uploader.onBeforeUploadItem = function (item) {
        console.info('onBeforeUploadItem', item);
        var formData = {id: attachmentUploadedId};
        item.formData.push(formData);
    };
    uploader.onProgressItem = function (fileItem, progress) {
        console.info('onProgressItem', fileItem, progress);
    };
    uploader.onProgressAll = function (progress) {
        console.info('onProgressAll', progress);
    };
    uploader.onSuccessItem = function (fileItem, response, status, headers) {
        console.info('onSuccessItem', fileItem, response, status, headers);
    };
    uploader.onErrorItem = function (fileItem, response, status, headers) {
        console.info('onErrorItem', fileItem, response, status, headers);
    };
    uploader.onCancelItem = function (fileItem, response, status, headers) {
        console.info('onCancelItem', fileItem, response, status, headers);
    };
    uploader.onCompleteItem = function (fileItem, response, status, headers) {
        console.info('onCompleteItem', fileItem, response, status, headers);
    };
    uploader.onCompleteAll = function () {
        console.info('onCompleteAll');
        $scope.clear();
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancelDocument = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onSaveDocument = function () {

        var req = {};
        var data = JSON.stringify($scope.attachment);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-internal-certificate-grade-participant-document/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                $scope.attachment = response.data.result;
                attachmentUploadedId = response.data.result.id;
                uploader.uploadAll();
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                //$scope.onCloseModal();

                $scope.attachment = {
                    id: 0,
                    created_at: $filter('date')(new Date(), "dd/MM/yyyy HH:mm"),
                    certificateGradeParticipantId: $scope.participant.id,
                    agent: null,
                    requirement: null,
                    classification: null,
                    status: null,
                    version: 1,
                    description: ""
                };
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.reloadData();

        });

    };

    var request = {};
    request.operation = "document";
    request.certificate_grade_participant_id = $scope.participant.id;

    $scope.dtInstanceCertificateParticipantDocumentAtt = {};
		$scope.dtOptionsCertificateParticipantDocumentAtt = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/customer-internal-certificate-grade-participant-document',
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

    $scope.dtColumnsCertificateParticipantDocumentAtt = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var url = data.document != null ? data.document.path : "";
                var actions = "";
                var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Descargar anexo" data-id="' + data.id + '" data-url="' + url + '" >' +
                    '   <i class="fa fa-download"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" target="_blank" href="' + url + '" uib-tooltip="Abrir anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-folder-open-o"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Anular anexo" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-ban"></i></a> ';


                if ($rootScope.can("seguimiento_view")) {
                    //actions += viewTemplate;
                }

                if ($rootScope.can("seguimiento_edit")) {
                    actions += editTemplate;
                }

                if ($rootScope.can("seguimiento_delete")) {
                    //actions += deleteTemplate;
                }

                return actions;
            }),
        DTColumnBuilder.newColumn('requirement').withTitle("Tipo de documento").withOption('width', 200),

        DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200),

        DTColumnBuilder.newColumn('date').withTitle("Fecha Creación").withOption('width', 200),
        DTColumnBuilder.newColumn(null).withTitle("Usuario").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var name = "";

                if (data.agent != null) {
                    name = data.agent;
                }

                return name;
            })
    ];

    var loadRow = function () {

        $("#dtDisabilityDocumentAtt a.editRow").on("click", function () {
            var id = $(this).data("id");
            var url = $(this).data("url");
            //$scope.editTracking(id);
            if (url == "") {
                SweetAlert.swal("Error en la descarga", "No existe un anexo para descargar", "error");
            }
            else {
                jQuery("#downloadDocument")[0].src = "api/customer-internal-certificate-grade-participant-document/download?id=" + id;
            }
        });

        $("#dtDisabilityDocumentAtt a.delRow").on("click", function () {
            var id = $(this).data("id");

            // Aqui se debe hacer la redireccion al formulario de edicion del customer
            log.info("intenta eliminar el registro: " + id);

            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Anularás el anexo seleccionado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, anular!",
                    cancelButtonText: "No, cancelar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        //
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });

    };

    $scope.dtInstanceCertificateParticipantDocumentAttCallback = function(instance){
        $scope.dtInstanceCertificateParticipantDocumentAtt = instance;
    }

    $scope.reloadData = function () {
        $scope.dtInstanceCertificateParticipantDocumentAtt.reloadData();
    };

});

app.controller('ModalInstanceSideOccupationalInternalCertificateEmployeeListCtrl', function ($rootScope, $stateParams, $scope, customer, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'Empleados';

    var request = {};

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

    request.operation = "restriction";
    request.customer_id = customer.id;
    request.data = "";

    $scope.dtInstanceDisabilityEmployeeList = {};
		$scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/customer-employee',
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

                if (data.isActive != null || data.isActive != undefined) {
                    if (data.isActive == 'Activo') {
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

    $scope.dtInstanceDisabilityEmployeeListCallback = function(instance){
        $scope.dtInstanceDisabilityEmployeeList = instance;
    }

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityEmployeeList.reloadData();
    };

    $scope.viewDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityEmployee = function (id) {
        console.log($scope.employee.id );
        $scope.employee.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});
