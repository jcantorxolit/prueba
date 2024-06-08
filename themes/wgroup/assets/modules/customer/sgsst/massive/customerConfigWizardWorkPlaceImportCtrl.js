'use strict';
/**
 * controller for Customers
 */
app.controller('customerConfigWizardWorkPlaceImportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document', 'FileUploader',
    '$location', '$translate',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, FileUploader, $location, $translate) {

        var log = $log;
        var request = {};
        log.info("loading..customerConfigWorkPlaceListCtrl ");
        var attachmentUploadedId = 0;

        $scope.loading = true;
        $scope.customerId = $stateParams.customerId;;
        $scope.isView = false;
        $scope.workplace = {};

        var uploader = $scope.uploader = new FileUploader({
            url: 'api/customer/config-sgsst/workplace/import',
            formData:[],
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
            var formData = { customerId: $scope.customerId };
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
            $scope.reloadData();
            $scope.clear();
        };

        $scope.clear = function () {
            $timeout(function () {

            });

            $scope.isView = false;
        };

        $scope.save = function () {
            if ($scope.uploader.queue.length == 0) {
                SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un anexo e Intentalo de nuevo.", "error");
                return;
            }
            uploader.uploadAll();
        };

        $scope.download = function () {
            jQuery("#downloadDocument")[0].src = "api/customer/config-sgsst/workplace/download";
        };

        // default view
        // $rootScope.tracking_section = "list";

        // Datatable configuration
        request.operation = "diagnostic";
        request.customerId = $scope.customerId;

        $scope.dtInstanceConfigWorkPlaceImport = {};
		$scope.dtOptionsConfigWorkPlaceImport = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/config-sgsst/workplace',
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
        ;

        $scope.dtColumnsConfigWorkPlaceImport = [
            DTColumnBuilder.newColumn('name').withTitle($translate.instant('grid.matrix.WORK-PLACE')).withOption('width', 200),
            DTColumnBuilder.newColumn('country.name').withOption('defaultContent', '').withTitle("País").withOption('width', 200),
            DTColumnBuilder.newColumn('state.name').withOption('defaultContent', '').withTitle("Departamento").withOption('width', 200),
            DTColumnBuilder.newColumn('town.name').withOption('defaultContent', '').withTitle("Ciudad").withOption('width', 200),
            DTColumnBuilder.newColumn('status.item').withOption('defaultContent', '').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch  (data)
                    {
                        case "Activo":
                            label = 'label label-success';
                            break;

                        case "Inactivo":
                            label = 'label label-danger';
                            break;

                        case "Retirado":
                            label = 'label label-warning';
                            break;
                    }

                    var status = '<span class="' + label +'">' + data + '</span>';


                    return status;
                }),
        ];

        $scope.viewConfigWorkPlaceImport = function (id) {
            $scope.workplace.id = id;
            $scope.isView = true;
            $scope.onLoadRecord();
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.customerId);
            }
        };

        $scope.reloadData = function () {
            $scope.dtInstanceConfigWorkPlaceImport.reloadData();
        };


    }
]);
