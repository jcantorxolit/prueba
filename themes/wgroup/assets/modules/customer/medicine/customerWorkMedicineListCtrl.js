'use strict';
/**
 * controller for Customers
 */
app.controller('customerWorkMedicineListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', 'SweetAlert', '$http', '$filter', '$document', '$aside',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state, $rootScope, $timeout, SweetAlert, $http, $filter, $document, $aside) {

        var log = $log;

        $scope.agents = $rootScope.agents();

        $scope.dtOptionsWorkMedicine = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-work-medicine',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function() {
                    // Aqui inicia el loader indicator
                },
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function() {
                //log.info("fnDrawCallback");
                loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })

        .withPaginationType('full_numbers')
            .withOption('createdRow', function(row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });;

        $scope.dtColumnsWorkMedicine = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function(data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if ($rootScope.can("seguimiento_view")) {
                    actions += viewTemplate;
                }

                if ($rootScope.can("seguimiento_edit")) {
                    actions += editTemplate;
                }

                if ($rootScope.can("seguimiento_delete")) {
                    actions += deleteTemplate;
                }

                return actions;
            }),
            DTColumnBuilder.newColumn('examinationDate').withTitle("Fecha Exámen").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('examinationType').withTitle("Tipo de Exámen").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', '')
        ];

        var loadRow = function() {

            $("#dtWorkMedicine a.editRow").on("click", function() {
                var id = $(this).data("id");
                $scope.onEdit(id);
            });

            $("#dtWorkMedicine a.viewRow").on("click", function() {
                var id = $(this).data("id");
                $scope.onView(id);
            });

            $("#dtWorkMedicine a.delRow").on("click", function() {
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
                    function(isConfirm) {
                        if (isConfirm) {
                            var req = {};
                            req.id = id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/work-medicine/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function(response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function(e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function() {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceWorkMedicineCallback = function(instance) {
            $scope.dtInstanceWorkMedicine = instance;
        };

        $scope.reloadData = function() {
            $scope.dtInstanceWorkMedicine.reloadData();
        };


        $scope.onEdit = function(id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("form", "edit", id);
            }
        };

        $scope.onView = function(id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("form", "view", id);
            }
        };

        $scope.onUpload = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/medicine/customer_work_medicine_import_modal.html",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideUploadEmployeeOccupationalExaminationCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function() {
                if (response && response.sessionId) {
                    if (response.hasCustomerEmployeeId) {
                        $rootScope.hasCustomerEmployeeId = response.hasCustomerEmployeeId;
                    } else {
                        $rootScope.hasCustomerEmployeeId = null;
                    }
                    if ($scope.$parent != null) {
                        $scope.$parent.navToSection("stagingEmployee", "stagingEmployee", response.sessionId);
                    }
                }
            });
        };

    }
]);

app.controller('ModalInstanceSideUploadEmployeeOccupationalExaminationCtrl', function($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/customer-employee-occupational-examination-import',
        formData: []
    });

    getList();

    $scope.title = "Importar examenes ocupacionales";
    $scope.buttonDownloadTitle = "Plantilla importación examenes ocupacionales";


    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function(response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + 'api/v1/customer-employee-occupational-examination-import';
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function() {
        angular.element("#downloadDocument")[0].src = "api/customer-work-medicine/download-template";
    }

    uploader.filters.push({
        name: 'customFilter',
        fn: function(item /*{File|FileLikeObject}*/ , options) {
            return this.queue.length < 10;
        }
    });

    uploader.onWhenAddingFileFailed = function(item /*{File|FileLikeObject}*/ , filter, options) {
        console.info('onWhenAddingFileFailed', item, filter, options);
    };
    uploader.onAfterAddingFile = function(fileItem) {
        console.info('onAfterAddingFile', fileItem);
    };
    uploader.onAfterAddingAll = function(addedFileItems) {
        console.info('onAfterAddingAll', addedFileItems);
    };
    uploader.onBeforeUploadItem = function(item) {
        console.info('onBeforeUploadItem', item);
        var formData = { id: $stateParams.customerId };
        item.formData.push(formData);
    };
    uploader.onProgressItem = function(fileItem, progress) {
        console.info('onProgressItem', fileItem, progress);
    };
    uploader.onProgressAll = function(progress) {
        console.info('onProgressAll', progress);
    };
    uploader.onSuccessItem = function(fileItem, response, status, headers) {
        console.info('onSuccessItem', fileItem, response, status, headers);
    };
    uploader.onErrorItem = function(fileItem, response, status, headers) {
        console.info('onErrorItem', fileItem, response, status, headers);
    };
    uploader.onCancelItem = function(fileItem, response, status, headers) {
        console.info('onCancelItem', fileItem, response, status, headers);
    };
    uploader.onCompleteItem = function(fileItem, response, status, headers) {
        console.info('onCompleteItem', fileItem, response, status, headers);
    };
    uploader.onCompleteAll = function() {
        console.info('onCompleteAll');
        swal("Correcto", "Se inserto la información correctamente", "success");
        $scope.dtInstanceWorkMedicine.reloadData();
        $uibModalInstance.close(1);
    };

});