'use strict';
/**
 * controller for taskListCtrl
 */
app.controller('taskListCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, toaster, $state, $rootScope, $timeout, $http, SweetAlert, $aside, $document) {

        var log = $log;

        var storeDatatable = 'taskListCtrl-' + window.currentUser.id;
        $scope.dtInstancePositivaFgn = {};
        $scope.dtOptionsPositivaFgn = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-gestpos',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function(settings, data) {
                $localStorage[storeDatatable] = data;
            })
            .withOption('stateLoadCallback', function() {
                return $localStorage[storeDatatable];
            })
            .withOption('order', [
                [0, 'asc']
            ])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('fnDrawCallback', function() {
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);

            });;

        $scope.dtColumnsPositivaFgn = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var disabled = "";

                var edit = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var drop = '<a class="btn btn-danger btn-xs dropRow lnk" href="#"  uib-tooltip="Borrar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-trash"></i></a> ';

                actions += edit;
                actions += drop;
                return actions;

            }),

            DTColumnBuilder.newColumn('number').withTitle("Id").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('code').withTitle("Código").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Tarea").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('mainTask').withTitle("Tarea Principal").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function(data) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isActive != null || data.isActive != undefined) {
                    if (data.isActive == 'Activo') {
                        label = 'label label-success';
                        text = 'Activo';
                    }
                }

                return '<span class="' + label + '">' + text + '</span>';
            }),
        ];

        var loadRow = function() {
            $("#dtPositivaFgn a.editRow").on("click", function() {
                var id = $(this).data("id");
                onEdit(id);
            });

            $("#dtPositivaFgn a.dropRow").on("click", function() {
                var id = $(this).data("id");
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
                                url: 'api/positiva-fgn-gestpos/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function(response) {
                                SweetAlert.swal("Proceso Exitoso", "Se ha eliminado correctamente el registro.", "success");
                            }).catch(function(e) {
                                SweetAlert.swal("Error en la eliminación", e.data.message, "error");
                            }).finally(function() {
                                $scope.reloadData();
                            });

                        }
                    });
            });

        };

        $scope.reloadData = function() {
            $scope.dtInstancePositivaFgn.reloadData();
        };

        $scope.onCreate = function() {
            onEdit(0);
        };


        var onEdit = function(id) {
            $scope.campusId = id;
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/gestpos/task/task_edit.htm",
                placement: 'right',
                backdrop: 'static',
                size: 'lg',
                controller: 'taskEditCtrl',
                scope: $scope
            });
            modalInstance.result.then(function() {
                $scope.reloadData();
            });
        }


        $scope.onUpload = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalPositivaFgnTaskImportCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function(response) {
                $scope.reloadData();
            });
        };

    });




app.controller('ModalPositivaFgnTaskImportCtrl', function($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;

    $scope.showTemplate = true;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/positiva-fgn/gestpos-task-import',
        formData: []
    });

    getList();

    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function(response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + 'api/v1/positiva-fgn/gestpos-task-import';
                $scope.uploader.url = $exportUrl + 'api/v1/positiva-fgn/gestpos-task-import';
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function() {
        jQuery("#downloadDocument")[0].src = "api/positiva-fgn-gestpos/download-template";
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
        $lastResponse = response;
    };
    uploader.onCompleteAll = function() {
        console.info('onCompleteAll');
        $uibModalInstance.close($lastResponse);
    };

});