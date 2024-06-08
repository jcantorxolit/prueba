'use strict';
/**
 * controller for Customers
 */
app.controller('CustomerInfoStructureOrganizationalListCtrl',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside, $document, ListService, $cookies) {

        $scope.reloadData = function () {
            $scope.dtInstancePositivaFgn.reloadData();
        };

        $scope.data = {cleanEntity: JSON.stringify({"_param_": $stateParams.customerId})};
        $scope.dtInstancePositivaFgn = {};
        $scope.dtOptionsPositivaFgn = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-organizational-structure/index',
                type: 'POST',
                beforeSend: function () {
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function (settings, data) {
                $cookies.putObject('consultantListCtrl-' + $rootScope.$id, data);
            })
            .withOption('stateLoadCallback', function () {
                return $cookies.getObject('consultantListCtrl-' + $rootScope.$id);
            })
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsPositivaFgn = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
                .renderWith(function (data) {
                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '"' +
                         disabled + ' > <i class="fa fa-edit"></i></a> ';

                    var trashTemplate = '<a class="btn btn-danger btn-xs trashRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '"' +
                        disabled + '>  <i class="fa fa-trash"></i></a> ';

                    actions += editTemplate;
                    actions += trashTemplate;
                    return actions;
            }),
            DTColumnBuilder.newColumn('location').withTitle("Locación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('department').withTitle("Departamento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('area').withTitle("Área").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('turn').withTitle("Turno").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRow = function () {
            $("#dtPositivaFgn a.editRow").on("click", function () {
                var id = $(this).data("id");
                save(id);
            });

            $("#dtPositivaFgn a.trashRow").on("click", function () {
                var id = $(this).data("id");
                destroy(id);
            });
        };

        $scope.onCreate = function () {
            save(0);
        };

        function save(id) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/organizational-structure/organizational_structure_edit_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'md',
                backdrop: true,
                controller: 'ModalInstanceSideOrganizationStructureEditCtrl',
                scope: $scope,
                resolve: {
                    data: function () {
                        return id;
                    }
                }
            });

            modalInstance.result.then(function (response) {
                $scope.dtInstancePositivaFgn.reloadData();
            });
        }

        function destroy(id) {
            SweetAlert.swal({
                    title: "Estas seguro?",
                    text: "Eliminará el esquema de la estructura organizacional.",
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
                        var req = {
                            data: Base64.encode(JSON.stringify({id: id}))
                        }

                        return $http({
                            method: 'post',
                            url: 'api/customer-organizational-structure/destroy',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
                            $scope.dtInstancePositivaFgn.reloadData();
                        }).catch(function (e) {
                            $log.error(e);
                            SweetAlert.swal("Error al consultar la información.", e.data.message, "error");
                        }).finally(function () {
                            $scope.reloadData();
                        });

                    } else {
                        swal("Cancelado", "Operacion cancelada", "error");
                    }
                });

        }

        $scope.onImport = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalCustomerProfileOrganizationalStructureImportCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function (response) {
                $scope.reloadData();
            });
        }

    });




app.controller('ModalCustomerProfileOrganizationalStructureImportCtrl', function ($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;

    $scope.showTemplate = true;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/customer-organizational-structure/import',
        formData: [
            {customerId: $stateParams.customerId}
        ]
    });

    getList();

    function getList() {

        var entities = [
            { name: 'export_url', value: null }
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + 'api/v1/customer-organizational-structure/import';
                $scope.uploader.url = $exportUrl + 'api/v1/customer-organizational-structure/import';
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function () {
        jQuery("#downloadDocument")[0].src = "api/customer-organizational-structure/download-template?customerId=" + $stateParams.customerId;
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
        var formData = { id: $stateParams.customerId };
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


app.controller('ModalInstanceSideOrganizationStructureEditCtrl',
    function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, data, ModuleListService) {

        $scope.locations = [];
        $scope.departments = [];
        $scope.areas = [];
        $scope.turns = [];


        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        var initialize = function () {
            $scope.entity = {
                id: data || 0,
                customerId: $stateParams.customerId,
                location: null,
                department: null,
                area: null,
                turn: null
            };
        };

        initialize();
        getList();
        load();

        $scope.form = {
            submit: function (form) {
                $scope.Form = form;

                if (form.$valid) {
                    save();
                    return;
                }

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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            }
        };

        var save = function () {
            var data = JSON.stringify($scope.entity);
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-organizational-structure/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $uibModalInstance.close();
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message, "error");
            });
        };

        function getList () {
            var entities = { customerId: $stateParams.customerId };

            ModuleListService.getDataList('/customer-organizational-structure/get-base-data', entities)
                .then(function (response) {
                    $scope.locations = response.data.result.locations;
                    $scope.departments = response.data.result.departments;
                    $scope.areas = response.data.result.areas;
                    $scope.turns = response.data.result.turns;

                }, function (error) {
                    $scope.status = 'Unable to load protocol data: ' + error.message;
                });
        }

        function load() {
            if (!$scope.entity.id) {
                return;
            }

            var data = {
                customerId: $scope.entity.customerId,
                id: $scope.entity.id
            }

            var req = {
                data: Base64.encode(JSON.stringify(data))
            }

            return $http({
                method: 'post',
                url: 'api/customer-organizational-structure/show',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.entity = response.data.result;
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error al consultar la información.", e.data.message, "error");
            });
        }

    });

