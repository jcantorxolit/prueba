'use strict';
/**
 * controller for PFFactivityListCtrl
 */
app.controller('PFFactivityListCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, toaster, $state, $rootScope, SweetAlert, $http, $aside) {

        $scope.isView = $localStorage.isView;
        var storeDatatable = 'activityListCtrl-' + window.currentUser.id;
        $scope.dtOptionsPositivaFgnActivity = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.configId = $stateParams.configId;
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-activity',
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
                [3, 'asc']
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

            });

        $scope.dtColumnsPositivaFgnActivity = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 140).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var drop = "";
                var editTemplate = "";

                if ($scope.isView) {
                    editTemplate = '<a class="btn btn-info btn-xs editRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';
                } else {
                    if ($scope.can('positiva_fgn_activity_create_edit')) {
                        editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '">' +
                            '   <i class="fa fa-edit" ></i></a> ';
                    }
                }

                var config = '<a class="btn btn-blue btn-xs configRow lnk" href="#"  uib-tooltip="Configurar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-cog"></i></a> ';
                var configSectional = '<a class="btn btn-info btn-xs configSectionalRow lnk" href="#"  uib-tooltip="Configurar por Seccional" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-th-list"></i></a> ';

                if (!$scope.isView && $scope.can('positiva_fgn_activity_delete')) {
                    drop = '<a class="btn btn-danger btn-xs dropRow lnk" href="#"  uib-tooltip="Eliminar"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash"></i></a> ';
                }

                actions += editTemplate;
                actions += config;
                actions += configSectional;
                actions += drop;
                return actions;
            }),

            DTColumnBuilder.newColumn('axis').withTitle("Eje").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('action').withTitle("Acción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('code').withTitle("Cod. Actividad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Actividad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('strategy').withTitle("Estrategia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCoverage').withTitle("Meta Cobertura").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCompliance').withTitle("Meta Cumplimiento").withOption('width', 200).withOption('defaultContent', ''),
        ];

        $scope.dtInstancePositivaFgnCallback = function(instance) {
            $scope.dtInstancePositivaFgnActivity = instance;
        };


        var loadRow = function() {

            $("#dtPositivaFgnActivity a.editRow").on("click", function() {
                var id = $(this).data("id");
                $state.go("app.positiva-fgn.fgn-activity-edit", { "configId": $stateParams.configId, "activityId": id });
            });

            $("#dtPositivaFgnActivity a.configRow").on("click", function() {
                var id = $(this).data("id");
                $state.go("app.positiva-fgn.fgn-activity-config", { "configId": $stateParams.configId, "activityId": id });
            });

            $("#dtPositivaFgnActivity a.configSectionalRow").on("click", function() {
                var id = $(this).data("id");
                $state.go("app.positiva-fgn.fgn-activity-config-sectional", { "configId": $stateParams.configId, "activityId": id });
            });

            $("#dtPositivaFgnActivity a.dropRow").on("click", function() {
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
                            req.detail = "activity";
                            $http({
                                method: 'POST',
                                url: 'api/positiva-fgn-fgn-activity/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function(response) {
                                SweetAlert.swal("Proceso Exitoso", "Se ha eliminado correctamente el registro.", "success");
                            }).catch(function(response) {
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                            }).finally(function() {
                                $scope.reloadData();
                            });

                        }
                    });
            });
        };

        $scope.reloadData = function() {
            $scope.dtInstancePositivaFgnActivity.reloadData();
        };

        $scope.onCreate = function() {
            $state.go("app.positiva-fgn.fgn-activity-edit", { "configId": $stateParams.configId, "activityId": 0 });
        };

        $scope.onBack = function() {
            $state.go("app.positiva-fgn.fgn");
        }

        $scope.onUpload = function() {

            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalPFFactivityImportCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function(response) {
                $scope.reloadData();
            });

        };

    });

app.controller('ModalPFFactivityImportCtrl', function($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;
    $scope.showTemplate = true;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/positiva-fgn/fgn-activity-import',
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
                uploader.url = $exportUrl + 'api/v1/positiva-fgn/fgn-activity-import';
                $scope.uploader.url = $exportUrl + 'api/v1/positiva-fgn/fgn-activity-import';
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function() {
        angular.element("#downloadDocument")[0].src = "api/positiva-fgn-fgn-activity/download-template";
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
        var formData = { id: $stateParams.customerId, user: $rootScope.currentUser().id };
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