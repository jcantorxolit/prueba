'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticPreventionDocumentEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'FileUploader', '$localStorage',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, FileUploader, $localStorage) {

        var log = $log;
        var request = {};
        var attachmentUploadedId = 0;

        var currentId = $scope.$parent.currentId;
        $scope.loading = true;
        $scope.isView = $state.is("app.program-prevention-document.view");
        $scope.isCreate = $state.is("app.program-prevention-document.create");
        $scope.format = 'dd-MM-yyyy';
        $scope.minDate = new Date() - 1;

        $scope.classifications = $rootScope.parameters("program_prevention_document_classification");
        $scope.documentStatus = $rootScope.parameters("customer_document_status");

        var initialize = function () {
            $scope.attachment = {
                id: currentId,
                customerId: $stateParams.customerId ? $stateParams.customerId : 0,
                classification: null,
                name: "",
                code: "",
                description: "",
                status: $scope.documentStatus.length > 0 ? $scope.documentStatus[0] : null,
                version: 1,
                startDate: null,
                endDate: null,
                questions: []
            };
        }

        initialize();

        var loadRecord = function () {
            // se debe cargar primero la información actual del cliente..

            if ($scope.attachment.id) {
                var req = {
                    id: $scope.attachment.id
                };

                $http({
                    method: 'GET',
                    url: 'api/customer/diagnostic-prevention-document',
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
                            SweetAlert.swal("Información no disponible", "Anexo no encontrado", "error");
                            $timeout(function () {

                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del anexo", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.attachment = response.data.result;
                            //$scope.attachment.version = parseInt($scope.attachment.version) + 1;

                            initializeDates();
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);
                    });
            }
        };

        var initializeDates = function() {
            if ($scope.attachment.startDate != null && $scope.attachment.startDate != "") {
                $scope.attachment.startDate = new Date($scope.attachment.startDate.date);
            }

            if ($scope.attachment.endDate != null && $scope.attachment.endDate != "") {
                $scope.attachment.endDate = new Date($scope.attachment.endDate.date);
            }
        }

        loadRecord();

        $scope.master = $scope.attachment;

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

                    if ($scope.uploader.queue.length == 0) {
                        //SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un anexo e Intentalo de nuevo.", "error");
                        //return;
                    }



                    save();
                }

            },
            reset: function (form) {

                $scope.attachment = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.attachment);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer/diagnostic-prevention-document/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.attachment = response.data.result;
                    attachmentUploadedId = response.data.result.id;
                    request.customer_diagnostic_prevention_document_id = attachmentUploadedId;

                    initializeDates();

                    if ($scope.uploader.queue.length > 0) {
                        uploader.uploadAll();
                    } else {
                        SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                        $scope.reloadData();
                        $scope.reloadDataSelected();
                        $scope.onClear();
                    }

                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el anexo por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.reloadData();
            });

        };

        var uploader = $scope.uploader = new FileUploader({
            url: 'api/customer/diagnostic-prevention-document/upload',
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
            SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
            $scope.reloadData();
            $scope.reloadDataSelected();
            $scope.onClear();
        };

        $scope.onClear = function () {
            if ($scope.$parent != null) {
                $scope.$parent.$parent.navToSection("edit", "edit", $scope.attachment.id);
            }
        };

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.$parent.navToSection("list", "list", 0);
            }
        };


        request.operation = "management";
        request.customer_diagnostic_prevention_document_id = currentId ? currentId : 0;

        //-------------------------------------------------------------------------ITEMS SELECTED
        $scope.dtInstanceCustomerDiagnosticPreventionDocumentQuestionSelected = {};
		$scope.dtOptionsCustomerDiagnosticPreventionDocumentQuestionSelected = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/diagnostic-prevention-document-question/selected',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {
                    $timeout(function () {
                        //$scope.$parent.setDataSetting(data.responseJSON.data);
                    });
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
                loadRowSelected();
                //Pace.stop();

            })
            .withDOM('tr')
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })


            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsCustomerDiagnosticPreventionDocumentQuestionSelected = [
            DTColumnBuilder.newColumn(null).withTitle("").withOption('width', 100).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var deleteTemplate = '<a class="btn btn-light-red btn-xs delRow lnk" href="#" tooltip="Remover anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash"></i></a> ';

                    var configureTemplate = '<a class="btn btn-purple btn-xs configureRow lnk" href="#" tooltip="Configurar pregunta"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-gear"></i></a> ';

                    if ($scope.isAdmin) {


                    }
                    actions += deleteTemplate;

                    return actions;
                }),
            DTColumnBuilder.newColumn('program')
                .withTitle("Programa")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('category')
                .withTitle("Categoría"),

            DTColumnBuilder.newColumn('article')
                .withTitle("Artículo"),

            DTColumnBuilder.newColumn('question')
                .withTitle("Pregunta"),

            DTColumnBuilder.newColumn('guide').withTitle("Guía").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';

                    if (data != null && data != '') {
                        text = 'Tiene';
                        label = 'label label-success';
                    } else {
                        text = 'No tiene';
                        label = 'label label-danger';
                    }

                    var status = '<span class="' + label +'">' + text + '</span>';

                    return status;
                })
        ];

        var loadRowSelected = function () {

            $("#dtCustomerDiagnosticPreventionDocumentQuestionSelected a.editRow").on("click", function () {
                //Open Modal
            });

            $("#dtCustomerDiagnosticPreventionDocumentQuestionSelected a.delRow").on("click", function () {
                var id = $(this).data("id");

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
                                url: 'api/customer/diagnostic-prevention-document-question/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function(data){
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function(e){
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                            }).finally(function(){

                                $scope.reloadDataSelected();
                                $scope.reloadData();
                            });
                        } else {
                            swal("Cancelado", "Operacion cancelada", "error");
                        }
                    });
            });
        };

        $scope.reloadDataSelected = function () {
            $scope.dtInstanceCustomerDiagnosticPreventionDocumentQuestionSelected.reloadData();
        };

        //-------------------------------------------------------------------------ITEMS AVAILABLE
        $scope.dtInstanceCustomerDiagnosticPreventionDocumentQuestion = {};
		$scope.dtOptionsCustomerDiagnosticPreventionDocumentQuestion = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/diagnostic-prevention-document-question',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {
                    $timeout(function () {
                        //$scope.$parent.setDataSetting(data.responseJSON.data);
                    });
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
            .withDOM('tr')
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })


            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsCustomerDiagnosticPreventionDocumentQuestion = [

            DTColumnBuilder.newColumn('program')
                .withTitle("Programa")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('category')
                .withTitle("Categoría"),

            DTColumnBuilder.newColumn('article')
                .withTitle("Artículo"),

            DTColumnBuilder.newColumn('question')
                .withTitle("Pregunta"),

            DTColumnBuilder.newColumn('guide').withTitle("Guía").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';

                    if (data != null && data != '') {
                        text = 'Tiene';
                        label = 'label label-success';
                    } else {
                        text = 'No tiene';
                        label = 'label label-danger';
                    }

                    var status = '<span class="' + label +'">' + text + '</span>';

                    return status;
                }),

            DTColumnBuilder.newColumn(null).withTitle('Acciones').notSortable()
                .renderWith(function(data, type, full, meta) {

                    var actions = "";

                    var checked = (data.selected == "1") ? "checked" : ""

                    var editTemplate = '<input bs-switch ng-model="isSelected" type="checkbox" switch-active="true" ng-click="edit(' + data.id + ')" name="' + data.id + '" ' +
                        'ng-true-value="true" ng-false-value="false" switch-on-text="Si" switch-off-text="No"> ';

                    var editTemplate = '<div class="checkbox clip-check check-success ">' +
                        '<input class="editRow" type="checkbox" id="chk_' + data.customerDiagnosticPreventionQuestionId + '" data-id="' + data.customerDiagnosticPreventionQuestionId + '" data-value="' + data.selected + '" ' + checked + ' ><label for="chk_' + data.customerDiagnosticPreventionQuestionId +'"> Seleccionar </label></div>';
                    actions += editTemplate;

                    return actions;
                })
                .notSortable()
        ];

        var loadRow = function () {

            $("input[type=checkbox]").on("change", function () {
                var id = $(this).data("id");
                var value = $(this).data("value");
                var checked = $(this).is(":checked");

                if (checked) {
                    var question = {
                        programPreventionQuestionId: id
                    }
                    $scope.attachment.questions.push(question);
                } else {
                    $scope.attachment.questions = $filter('filter')($scope.attachment.questions , { programPreventionQuestionId: ('!' + id) });
                }

                log.info($scope.attachment.questions);
            });

        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerDiagnosticPreventionDocumentQuestion.reloadData();
        };

    }]);



