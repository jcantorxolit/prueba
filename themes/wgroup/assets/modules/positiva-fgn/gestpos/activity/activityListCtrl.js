'use strict';
/**
 * controller for activityListCtrl
 */
app.controller('activityListCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, toaster, $state, $rootScope, $timeout, $http, $aside) {

        var log = $log;
        var storeDatatable = 'activityListCtrl-' + window.currentUser.id;
        $scope.dtInstancePositivaFgnGestposActivity = {};
        $scope.dtOptionsPositivaFgnGestposActivity = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-gestpos-activity',
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

        $scope.dtColumnsPositivaFgnGestposActivity = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 90).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var disabled = "";

                var edit = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var evidence = '<a class="btn btn-info btn-xs evidenceRow lnk" href="#"  uib-tooltip="Evidencias" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-th-list"></i></a> ';

                actions += edit;
                actions += evidence;
                return actions;

            }),

            DTColumnBuilder.newColumn('name').withTitle("Actividad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('code').withTitle("Código").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('strategy').withTitle("Estrategia").withOption('width', 200).withOption('defaultContent', ''),
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
            $("#dtPositivaFgnGestposActivity a.editRow").on("click", function() {
                var id = $(this).data("id");
                onEdit(id);
            });

            $("#dtPositivaFgnGestposActivity a.evidenceRow").on("click", function() {
                var id = $(this).data("id");
                onAddEvidences(id);
            });

        };

        $scope.reloadData = function() {
            $scope.dtInstancePositivaFgnGestposActivity.reloadData();
        };

        $scope.onCreate = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit");
            }
        };

        var onEdit = function(id) {
            if ($scope.$parent != null) {
                $stateParams.gestposId = id;
                $scope.$parent.navToSection("edit", "edit", id);
            }
        }

        var onAddEvidences = function(id) {
            $stateParams.gestposId = id;
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/gestpos/activity/evidence/evidence_list.htm",
                placement: 'right',
                backdrop: 'static',
                size: 'lg',
                controller: 'evidenceListCtrl',
                scope: $scope
            });
            modalInstance.result.then(function() {});
        }

        $scope.onUpload = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalPositivaFgnGestposActivitiesImportCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function(response) {
                $scope.reloadData();
            });
        };

    });




app.controller('ModalPositivaFgnGestposActivitiesImportCtrl', function($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;

    $scope.showTemplate = true;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/positiva-fgn/gestpos-activity-import',
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
                uploader.url = $exportUrl + 'api/v1/positiva-fgn/gestpos-activity-import';
                $scope.uploader.url = $exportUrl + 'api/v1/positiva-fgn/gestpos-activity-import';
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function() {
        jQuery("#downloadDocument")[0].src = "api/positiva-fgn-gestpos-activity/download-template";
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