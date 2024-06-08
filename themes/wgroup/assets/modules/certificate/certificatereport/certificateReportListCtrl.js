'use strict';
/**
 * controller for Customers
 */
app.controller('certificateReportListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', '$aside', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http, $aside, ListService) {

        var log = $log;

        log.info("entrando en... certificateAdminProgramCtrl");

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";
        $scope.currentCustomerId = $rootScope.currentUser().company;

        if ($scope.isAgent) {
            $state.go("app.clientes.list");
        } else if ($scope.isCustomer) {
            //$state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }

        $scope.audit = {
            fields: [],
            filters: [],
        };

        getList();

        function getList() {
            var entities = [
                { name: 'criteria_operators', value: null },
                { name: 'criteria_conditions', value: null },
                { name: 'certificate_search_custom_filter_field', value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.certificateSearchCustomFilterField;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        // Datatable configuration
		$scope.dtOptionsCertificate = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {

                        d.filter =
                            {
                                filters: $scope.audit.filters.filter(function (filter) {
                                    return filter != null && filter.field != null && filter.criteria != null;
                                }).map(function (filter, index, array) {
                                    return {
                                        field: filter.field.name,
                                        operator: filter.criteria.value,
                                        value: filter.value,
                                        condition: { value: 'and' }
                                    };
                                })
                            };
                    }

                    return JSON.stringify(d);
                },
                url: 'api/certificate-grade-participant/search-v2',
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

        $scope.dtColumnsCertificate = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    //var disabled = (data.hasCertificate) ? "" : "disabled";
                    var disabled = "";

                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Descargar certificado" data-origin="' + data.origin + '" data-id="' + data.id + '" ' + disabled + '  >' +
                        '   <i class="fa fa-download"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver participante" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar certificado externo" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("clientes_view")) {
                        //actions += viewTemplate;
                    }

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if (data.origin == "Externo") {
                        actions += deleteTemplate;
                    }


                    return actions;
                }),

            DTColumnBuilder.newColumn('documentType').withTitle("Tipo de Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('identificationNumber').withTitle("Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('name').withTitle("Nombres").withOption('width', 200),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
            DTColumnBuilder.newColumn('customer').withTitle("Empresa").withOption('width', 200),
            DTColumnBuilder.newColumn('grade').withTitle("Curso").withOption('width', 200),
            DTColumnBuilder.newColumn(null).withTitle("Fecha").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                if (data.certificateCreatedAt != null) {
                    return moment(data.certificateCreatedAt).format('DD/MM/YYYY');
                }
                return '';
            }),
            DTColumnBuilder.newColumn(null).withTitle("Fecha Vencimiento").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                if (data.certificateExpirationAt != null) {
                    return moment(data.certificateExpirationAt).format('DD/MM/YYYY');
                }
                return '';
            }),
            DTColumnBuilder.newColumn('origin').withTitle("Origen").withOption('width', 200),
        ];

        var loadRow = function () {

            angular.element("#dtCertificate a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                var url = angular.element(this).data("url");
                var origin = angular.element(this).data("origin");
                //$scope.editTracking(id);
                if (origin == "Externo") {
                    jQuery("#downloadDocument")[0].src = "api/certificate-external/download?id=" + id;
                } else {
                    jQuery("#downloadDocument")[0].src = "api/certificate-grade-participant-certificate/download?id=" + id;
                }
            });

            angular.element("#dtCertificate a.delRow").on("click", function () {
                var id = angular.element(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminara el registro seleccionado.",
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
                                url: 'api/certificate-external/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function(e){
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function(){

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceCertificateCallback = function(instance) {
            $scope.dtInstanceCertificate = instance
        }

        $scope.reloadData = function () {
            log.info("reloading...");
            $scope.dtInstanceCertificate.reloadData();
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
            $scope.reloadData();
        }

        $scope.removeFilter = function (index) {
            $scope.audit.filters.splice(index, 1);

            if ($scope.audit.filters.length == 0) {
                $scope.reloadData();
            }
        }

        $scope.onCleanFilter = function () {
            $scope.audit.filters = [];
            $scope.reloadData();
        }

        $scope.onAddExternalCertificate = function() {
            var modalInstance = $aside.open({
                templateUrl: 'app_modal_certificate_external.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideExternalCertificateCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        };

    }]);

app.controller('ModalInstanceSideExternalCertificateCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var attachmentUploadedId = 0;

    $scope.documentTypes = $rootScope.parameters("tipodoc");

    var initialize = function() {
        $scope.participant = {
            id: 0,
            customerId: $scope.currentCustomerId,
            documentType: null,
            identificationNumber: "",
            name: "",
            lastName: "",
            company: "",
            grade: "",
            expeditionDate: null,
            expirationDate: null,
        };
    };

    initialize();

    var uploader = $scope.uploader = new FileUploader({
        url: 'api/certificate-external/upload',
        formData:[]
    });

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item/*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

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
        console.info('onCompleteAll');
        $scope.onCloseModal();
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancelCertificate = function () {
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

                if ($scope.uploader.queue.length == 0) {
                    SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un certificado e Intentalo de nuevo.", "error");
                    return;
                }

                $scope.onSave();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.onSave = function () {

        var req = {};

        $scope.participant.expeditionDate = $scope.participant.expeditionDate.toISOString();
        $scope.participant.expirationDate = $scope.participant.expirationDate.toISOString();

        var data = JSON.stringify($scope.participant);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/certificate-external/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function(){
                $scope.attachment = response.data.result;
                attachmentUploadedId = response.data.result.id;
                uploader.uploadAll();
                toaster.pop('success', 'Operación Exitosa', 'Registro guardado');
                $scope.onCloseModal();
            });
        }).catch(function(e){
            $log.error(e);
            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
        }).finally(function(){

        });

    };

});
