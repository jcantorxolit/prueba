'use strict';
/**
 * controller for Customers
 */
app.controller('customerConfigWizardProcessImportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document', 'FileUploader',
    '$location', '$translate', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, FileUploader, $location, $translate, ListService) {

        $scope.loading = true;
        $scope.customerId = $stateParams.customerId;

        var $exportUrl = '';
        var $endpoint = 'api/v1/customer-config-process-import';

        var uploader = $scope.uploader = new FileUploader({
            url: $exportUrl + $endpoint,
            formData: [],
            removeAfterUpload: true
        });

        getList();

        function getList() {

            var entities = [
                { name: 'export_url', value: null },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $exportUrl = response.data.data.exportUrl.item;
                    uploader.url = $exportUrl + $endpoint;
                    $scope.uploader.url = $exportUrl + $endpoint;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        uploader.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS

        uploader.onWhenAddingFileFailed = function (item/*{File|FileLikeObject}*/, filter, options) {

        };
        uploader.onAfterAddingFile = function (fileItem) {

        };
        uploader.onAfterAddingAll = function (addedFileItems) {

        };
        uploader.onBeforeUploadItem = function (item) {
            var formData = {
                customerId: $stateParams.customerId,
            };
            item.formData.push(formData);
        };
        uploader.onProgressItem = function (fileItem, progress) {

        };
        uploader.onProgressAll = function (progress) {

        };
        uploader.onSuccessItem = function (fileItem, response, status, headers) {

        };
        uploader.onErrorItem = function (fileItem, response, status, headers) {

        };
        uploader.onCancelItem = function (fileItem, response, status, headers) {

        };
        uploader.onCompleteItem = function (fileItem, response, status, headers) {
            if (response && response.sessionId) {
                if ($scope.$parent != null) {
                    $scope.$parent.navToSection("staging", "staging", response.sessionId);
                }
            }
        };
        uploader.onCompleteAll = function () {
            $scope.reloadData();
        };

        $scope.onImport = function () {
            if ($scope.uploader.queue.length == 0) {
                SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un archivo e Intentalo de nuevo.", "error");
                return;
            }
            uploader.uploadAll();
        };

        $scope.onClear = function () {
        };

        $scope.onDownload = function () {
            angular.element("#downloadDocument")[0].src = "api/customer-config-process/download-template?customerId=" + $stateParams.customerId;
        };


        $scope.dtInstanceConfigProcess = {};
		$scope.dtOptionsConfigProcess = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "diagnostic";
                    d.customerId = $scope.customerId;

                    return JSON.stringify(d);
                },
                url: 'api/customer-config-process',
                contentType: "application/json",
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

        $scope.dtColumnsConfigProcess = [
            DTColumnBuilder.newColumn('workplace').withTitle($translate.instant('grid.matrix.WORK-PLACE')).withOption('width', 200),
            DTColumnBuilder.newColumn('macroprocess').withTitle($translate.instant('grid.matrix.MACROPROCESS')).withOption('width', 200),
            DTColumnBuilder.newColumn('name').withTitle($translate.instant('grid.matrix.PROCESS')).withOption('width', 200),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
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

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.customerId);
            }
        };

        $scope.reloadData = function () {
            $scope.dtInstanceConfigProcess.reloadData();
        };

    }]);
