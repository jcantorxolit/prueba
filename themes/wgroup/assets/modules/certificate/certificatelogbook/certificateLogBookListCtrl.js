'use strict';
/**
 * controller for Customers
 */
app.controller('certificateLogBookListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', 'FileUploader', '$filter', '$timeout',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http, FileUploader, $filter, $timeout) {

        var log = $log;
        var attachmentUploadedId = 0;

        log.info("entrando en... certificateAdminProgramCtrl");

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        if ($scope.isAgent) {
            $state.go("app.clientes.list");
        } else if ($scope.isCustomer) {
            log.info("Step 5");
            $state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }


        $scope.attachment = {
            id : 0,
            created_at : $filter('date')(new Date(), "dd/MM/yyyy HH:mm"),
            certificateGradeParticipantId : 0,
            agent : {
                "id": 0,
                "name": "- Seleccionar - "
            },
            eventDate: new Date(),
            company: "",
            description:  "",
            location : "",
            hourWorked : 0,
            maxHeightWorked : 0,
            category : "experience",
            courseTitle : "",
            certificateNumber : "",
            trainingOrganization : "",
            status:  null,
            version: 1
        };

        var uploader = $scope.uploader = new FileUploader({
            url: 'api/certificate-logbook-document/upload',
            formData:[]
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
            var formData = { id: attachmentUploadedId };
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
            $scope.reloadData();
            $scope.onClear();
        };

        $scope.onCloseModal = function () {
            $uibModalInstance.close(1);
        };

        $scope.onCancelDocument = function () {
            $uibModalInstance.dismiss('cancel');
        };


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
                    log.info($scope.attachment);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información de la encuesta...", "success");
                    //your code for submit
                    log.info($scope.attachment);
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
                url: 'api/certificate-logbook-document/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $scope.attachment = response.data.result;
                attachmentUploadedId = response.data.result.id;
                uploader.uploadAll();
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.onClear();
            });

        };

        $scope.onClear = function () {
            $timeout(function () {
                $scope.attachment = {
                    id : 0,
                    created_at : $filter('date')(new Date(), "dd/MM/yyyy HH:mm"),
                    certificateGradeParticipantId : 0,
                    agent : {
                        "id": 0,
                        "name": "- Seleccionar - "
                    },
                    eventDate: $filter('date')(new Date(), "dd/MM/yyyy HH:mm"),
                    company: "",
                    description:  "",
                    location : "",
                    hourWorked : 0,
                    maxHeightWorked : 0,
                    category : "experience",
                    courseTitle : "",
                    certificateNumber : "",
                    trainingOrganization : "",
                    status:  null,
                    version: 1
                };
            });

            $timeout(function () {
                $scope.noImage = true;
                $document.scrollTop(40, 2000);
            });

            $scope.isView = false;
        };

        var request = {};
        request.operation = "document";
        request.certificate_grade_participant_id = 0;
        $scope.dtInstanceCertificate = {};
		$scope.dtOptionsCertificate = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/certificate-logbook-document',
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

        $scope.dtColumnsCertificate = [
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


                    if($rootScope.can("seguimiento_view")){
                        //actions += viewTemplate;
                    }

                    if($rootScope.can("seguimiento_edit")){
                        actions += editTemplate;
                    }

                    if($rootScope.can("seguimiento_delete")){
                        //actions += deleteTemplate;
                    }

                    return actions;
                }),

            DTColumnBuilder.newColumn('eventDate').withTitle("Fecha").withOption('width', 200),
            DTColumnBuilder.newColumn('company').withTitle("Empresa").withOption('width', 200),
            DTColumnBuilder.newColumn('location').withTitle("Lugar").withOption('width', 200),
            DTColumnBuilder.newColumn('hourWorked').withTitle("Horas Trabajadas").withOption('width', 200),
            DTColumnBuilder.newColumn('maxHeightWorked').withTitle("Máxima Altura Trabajada").withOption('width', 200),
        ];

        var loadRow = function () {

            $("#dtCertificate a.editRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");
                //$scope.editTracking(id);
                if (url == "")
                {
                    SweetAlert.swal("Error en la descarga", "No existe un anexo para descargar", "error");
                }
                else
                {
                    jQuery("#downloadDocument")[0].src = "api/certificate-logbook-document/download?id=" + id;
                }
            });

            $("#dtCertificate a.delRow").on("click", function () {
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

        $scope.onCreateProgram = function(){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", 0);
            }
        };

        $scope.onEditProgram = function(id){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", id);
            }
        };

        $scope.onViewProgram = function(id){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "view", id);
            }
        };

        $scope.reloadData = function () {
            log.info("reloading...");
            $scope.dtInstanceCertificate.reloadData();
        };

        $scope.addFilter = function()
        {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }
            $scope.audit.filters.push(
                {
                    id: 0,
                    field: null,
                    criteria: null,
                    condition: null,
                    value: ""
                }
            );
        };

        $scope.onFilter = function()
        {
            $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

            $scope.reloadData();
        }

        $scope.removeFilter = function(index)
        {
            $scope.audit.filters.splice(index, 1);
        }

    }]);
