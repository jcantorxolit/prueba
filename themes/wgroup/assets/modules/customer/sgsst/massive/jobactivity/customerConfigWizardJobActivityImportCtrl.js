'use strict';
/**
 * controller for Customers
 */
app.controller('customerConfigWizardJobActivityImportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document', 'FileUploader',
    '$location', '$translate', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, FileUploader, $location, $translate, ListService) {


        $scope.loading = true;
        $scope.customerId = $stateParams.customerId;

        var $exportUrl = '';
        var $endpoint = 'api/v1/customer-config-job-activity-import';

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
                userId: $rootScope.currentUser().id
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
            angular.element("#downloadDocument")[0].src = "api/customer-config-job-activity-staging/download-template?customerId=" + $stateParams.customerId;
        };

        //-------------------------------------------------LIST
        $scope.dtOptionsCustomerConfigJobActivityImport = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
			.withOption('responsive', true)
            .withOption('ajax', {
				data: function (d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-config-job-activity',
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
            .withOption('serverSide', true)
			.withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
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

        $scope.dtColumnsCustomerConfigJobActivityImport = [
            DTColumnBuilder.newColumn('workplace').withTitle($translate.instant('grid.matrix.WORK-PLACE')).withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('macroprocess').withTitle($translate.instant('grid.matrix.MACROPROCESS')).withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('process').withTitle($translate.instant('grid.matrix.PROCESS')).withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activity').withTitle($translate.instant('grid.matrix.ACTIVITY')).withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('updatedBy').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Fecha Última Actualización").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
               if (typeof data.updatedAt == 'object' && data.updatedAt) {
                    var $updatedAt = new moment(data.updatedAt.date);
                    return $updatedAt.format('DD-MM-YYYY HH:mm');
               }
                return data.updatedAt;
            }),
        ];

		$scope.dtInstanceCustomerConfigJobActivityImportCallback = function (instance) {
            $scope.dtInstanceCustomerConfigJobActivityImport = instance;
        };

        $scope.reloadData = function () {
			if ($scope.dtInstanceCustomerConfigJobActivityImport != null) {
				$scope.dtInstanceCustomerConfigJobActivityImport.reloadData();
			}
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $stateParams.customerId);
            }
        };

    }]);
