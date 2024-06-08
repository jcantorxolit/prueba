'use strict';
/**
 * controller for Customers
 */
app.controller('configurationProgramPreventionQuestionListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $aside) {

        var log = $log;
        var request = {};
        log.info("loading..customerConfigManagementQuestionesCtrl ");



        // Datatable configuration
        request.operation = "diagnostic";


        //-------------------------------------------------------------------------ITEMS AVAILABLE
        $scope.dtInstanceProgramPreventionQuestion = {};
		$scope.dtOptionsProgramPreventionQuestion = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/configuration/program-prevention-question',
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

        $scope.dtColumnsProgramPreventionQuestion = [
            DTColumnBuilder.newColumn(null).withTitle("").withOption('width', 100).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var configureTemplate = '<a class="btn btn-purple btn-xs configureRow lnk" href="#" uib-tooltip="Configurar pregunta"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-gear"></i></a> ';

                    var configureCustomerSizeTemplate = '<a class="btn btn-danger btn-xs configureSizeRow lnk" href="#" uib-tooltip="Clasificar pregunta"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-bars"></i></a> ';

                    if ($scope.isAdmin) {
                    }

                    actions += configureTemplate;
                    actions += configureCustomerSizeTemplate;

                    return actions;
                }),
            DTColumnBuilder.newColumn('id')
                .withTitle("ID")
                .withOption('width', 50),

            DTColumnBuilder.newColumn('program')
                .withTitle("Programa")
                .withOption('width', 350),

            DTColumnBuilder.newColumn('category')
                .withTitle("Categoría"),

            DTColumnBuilder.newColumn('article')
                .withTitle("Artículo"),

            DTColumnBuilder.newColumn('question')
                .withTitle("Pregunta"),

            DTColumnBuilder.newColumn('classification').withOption('width', 200)
                .withTitle("Clasificación"),

            DTColumnBuilder.newColumn('guide').withTitle("Guía").withOption('width', 100)
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

        var loadRow = function () {

            $("#dtProgramPreventionQuestion a.configureRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onEditQuestion(id);
            });

            $("#dtProgramPreventionQuestion a.configureSizeRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onClassifyQuestion(id);
            });
        };

        $scope.reloadData = function () {
            $scope.dtInstanceProgramPreventionQuestion.reloadData();
        };

        $scope.onEditQuestion = function (id) {

            var question = {
                id: id
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_program_prevention_question.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/configuration/business-programs/program_prevention_question_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideProgramPreventionQuestionCtrl',
                scope: $scope,
                resolve: {
                    question: function () {
                        return question;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        };

        $scope.onClassifyQuestion = function (id) {

            var question = {
                id: id
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_program_prevention_question.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/configuration/business-programs/program_prevention_question_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideProgramPreventionQuestionClassificationCtrl',
                scope: $scope,
                resolve: {
                    question: function () {
                        return question;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        };

    }]);

app.controller('ModalInstanceSideProgramPreventionQuestionCtrl', function ($rootScope, $scope, $uibModalInstance, question, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document) {

    var attachmentUploadedId = 0;

    $scope.isConfigurationMode = true;
    $scope.workplaces = [];
    $scope.macros = [];
    $scope.processes = [];

    $scope.question = question;

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };


    var loadRecord = function () {
        // se debe cargar primero la información actual del cliente..

        if ($scope.question.id) {
            var req = {
                id: $scope.question.id
            };

            $http({
                method: 'GET',
                url: 'api/configuration/program-prevention-question',
                params: req
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.question = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        }
    };

    loadRecord();

    $scope.onSave = function () {

        var req = {};
        var data = JSON.stringify($scope.question);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/configuration/program-prevention-question/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

});

app.controller('ModalInstanceSideProgramPreventionQuestionClassificationCtrl', function ($rootScope, $scope, $uibModalInstance, question, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document) {

    var attachmentUploadedId = 0;

    $scope.isConfigurationMode = false;
    $scope.customerSizeList =  $rootScope.parameters("wg_customer_size");;

    $scope.question = question;
    $scope.entity = {
        id: 0,
        programPreventionQuestionId: question.id,
        size: null
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };


    var loadRecord = function () {
        // se debe cargar primero la información actual del cliente..

        if ($scope.question.id) {
            var req = {
                id: $scope.question.id
            };

            $http({
                method: 'GET',
                url: 'api/configuration/program-prevention-question',
                params: req
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.question = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        }
    };

    loadRecord();

    $scope.onSave = function () {

        if ($scope.entity.size == null) {
            SweetAlert.swal("Error de guardado", "Debe seleccionar el tamaño de la empresa", "error");
            return;
        }

        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/configuration/program-prevention-question-classification/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $scope.reloadData();
                $scope.entity = {
                    id: 0,
                    programPreventionQuestionId: question.id,
                    size: null
                };
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", e.message, "error");
        }).finally(function () {

        });

    };


    var request = {};
    request.operation = "diagnostic";
    request.program_prevention_question_id = question.id ? question.id : 0;

    //-------------------------------------------------------------------------ITEMS SELECTED
    $scope.dtInstanceProgramPreventionQuestionClassification = {};
    $scope.dtOptionsProgramPreventionQuestionClassification = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/configuration/program-prevention-question-classification',
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
        .withOption('serverSide', true)
        .withOption('processing', true)
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
        .withPaginationType('full_numbers')
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })


        .withOption('createdRow', function (row, data, dataIndex) {

            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsProgramPreventionQuestionClassification = [
        DTColumnBuilder.newColumn(null).withTitle("").withOption('width', 70).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var deleteTemplate = '<a class="btn btn-light-red btn-xs delRow lnk" href="#" uib-tooltip="Eliminar registro" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash"></i></a> ';

                if ($scope.isAdmin) {
                }

                actions += deleteTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('customerSize.item')
            .withTitle("Clasificación")
            .withOption('defaultContent', ''),
    ];

    var loadRowSelected = function () {

        $("#dtProgramPreventionQuestionClassification a.delRow").on("click", function () {
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
                            url: 'api/configuration/program-prevention-question-classification/delete',
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

    $scope.dtInstanceProgramPreventionQuestionClassificationCallback = function (instance) {
        $scope.dtInstanceProgramPreventionQuestionClassification = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceProgramPreventionQuestionClassification.reloadData();
    };

});