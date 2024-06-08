'use strict';
/**
 * controller for Customers
 */
app.controller('customerManacleCtrl',
    function ($scope, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document) {


        $scope.statusList = [
            {item: "Activo", value: 1},
            {item: "Inactivo", value: 0}
        ];

        $scope.isView = $scope.$parent.editMode == "view";
        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        var init = function() {
            $scope.entity = {
                id: 0,
                registrationDate: new Date(),
                customerId: $stateParams.customerId,
                number: null,
                isActive: $scope.statusList[0]
            }

            $scope.entity.registrationDate = $scope.maxDate;
        }
        init();

        $scope.onLoadRecord = function () {
            if ($scope.entity.id != 0) {
                var req = {
                    id: $scope.entity.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer-manacle/get',
                    params: req
                })
                .catch(function (response) {})
                .then(function (response) {
                    $timeout(function () {
                        $scope.entity = response.data.result;
                        if($scope.entity.registrationDate) {
                            $scope.entity.registrationDate = new Date($scope.entity.registrationDate.date)
                        }
                    });
                }).finally(function () {});
            }
        }

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
                    save();
                }
            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-manacle/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.entity = response.data.result;
                    init();
                    $scope.reloadDataManacle();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message , "error");
            }).finally(function () {});
        };

        $scope.cancelEdition = function (index) {
            init();
        };

        $scope.dtInstanceManacle = {};
        $scope.dtOptionsManacle = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-manacle',
                type: 'POST',
                beforeSend: function () {},
                complete: function () {}
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsManacle = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if(!$scope.isView) {
                        actions += editTemplate;
                        actions += deleteTemplate;
                    }
                    return actions;
                }),
            DTColumnBuilder.newColumn('registrationDate').withTitle("Fecha").withOption('width', 200),
            DTColumnBuilder.newColumn('number').withTitle("Id Manilla").withOption('width', 200),
            DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 200)
            .renderWith(function (data) {
                var label = 'label label-success';
                if (data == "Inactivo") {
                    label = 'label label-danger';
                }
                var status = '<span class="' + label +'">' + data + '</span>';
                return status;
            }),
        ];

        var loadRow = function () {

            angular.element("#dtManacle a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onEdit(id);
            });

            angular.element("#dtManacle a.delRow").on("click", function () {
                var id = angular.element(this).data("id");
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
                                url: 'api/customer-manacle/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error de guardado", e.data.message , "error");
                            }).finally(function () {
                                $scope.reloadDataManacle();
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.onEdit = function (id) {
            $scope.entity.id = id;
            $scope.onLoadRecord();
        };

        $scope.dtInstanceManacleCallback = function (instance) {
            $scope.dtInstanceManacle = instance;
        };

        $scope.reloadDataManacle = function () {
            $scope.dtInstanceManacle.reloadData();
        };

        $scope.onImport = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/manacle/customer_profile_manacle_import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerManacleImportCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                $scope.reloadDataManacle()
            }, function() {});
        };

});

app.controller('ModalInstanceSideCustomerManacleImportCtrl', function ($rootScope, ngNotify, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/customer-manacle-import',
        formData: []
    });

    getList();

    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + 'api/v1/customer-manacle-import';
                $scope.uploader.url = $exportUrl + 'api/v1/customer-manacle-import';
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function () {
        angular.element("#downloadDocument")[0].src = "api/customer-manacle/download-template?customerId=" + $stateParams.customerId;
    }

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
        var formData = { id: $stateParams.customerId, user: $rootScope.currentUser().id };
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
        $lastResponse = response;
    };
    uploader.onCompleteAll = function () {
        console.info('onCompleteAll');
        $uibModalInstance.close($lastResponse);
    };

});
