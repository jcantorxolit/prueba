'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeDocumentCriticalListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', '$timeout', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http, $timeout, $aside) {

        var log = $log;

        $scope.isView = $scope.$parent.isCustomerContractor || $scope.$parent.editMode == "view";
        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.document = {
            id: 0,
            customerEmployeeId: $scope.$parent.currentEmployee
        }

        $scope.dtInstanceDocumentCritical = {};
		$scope.dtOptionsDocumentCritical = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.operation = "document";
                    d.customerEmployeeId = $scope.$parent.currentEmployee;
                    d.criticalActivityCustomerEmployeeId = $scope.$parent.currentEmployee;

                    return JSON.stringify(d);
                },
                url: 'api/customer-employee-document-required-critical',
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
        ;

        $scope.dtColumnsDocumentCritical = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    //var disabled = (data.hasCertificate) ? "" : "disabled";
                    var disabled = "";

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Adicionar documento" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-plus"></i></a> ';

                    return !$scope.isView ? viewTemplate : '';
                }),
            DTColumnBuilder.newColumn('documentType').withTitle("Tipo Documento").withOption('defaultContent', '')
        ];

        var loadRow = function () {
            angular.element("#dtDocumentCritical a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.document.id = id;
                $scope.onAddDocument();
            });
        };

        $scope.dtInstanceDocumentCriticalCallback = function (instance) {
            $scope.dtInstanceDocumentCritical = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceDocumentCritical.reloadData();
        };


        //------------------------------------------------------------------REQUIRED
        $scope.dtInstanceDocumentRequired = {};
		$scope.dtOptionsDocumentRequired = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.operation = "document";
                    d.customerEmployeeId = $scope.$parent.currentEmployee;
                    d.customerId = $stateParams.customerId;
                    d.isRequired = 1;

                    return JSON.stringify(d);
                },
                url: 'api/customer-employee-document-required',
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
                loadRowRequired();
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
        ;

        $scope.dtColumnsDocumentRequired = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    //var disabled = (data.hasCertificate) ? "" : "disabled";
                    var disabled = "";

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Adicionar documento" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-plus"></i></a> ';

                    return !$scope.isView ? viewTemplate : '';
                }),
            DTColumnBuilder.newColumn('documentType').withTitle("Tipo Documento").withOption('defaultContent', '')
        ];

        var loadRowRequired = function () {
            angular.element("#dtDocumentRequired a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.document.id = id;
                $scope.onAddDocument();
            });
        };

        $scope.dtInstanceDocumentRequiredCallback = function (instance) {
            $scope.dtInstanceDocumentRequired = instance;
        };

        $scope.reloadRequiredData = function () {
            $scope.dtInstanceDocumentRequired.reloadData();
        };



        //------------------------------------------------------------------DOCUMENT

        $scope.onAddDocument = function() {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_customer_employee_attachment.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/employee/document-critical-activity/customer_employee_tab_attachment_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideEmployeeDocumentCriticalCtrl',
                scope: $scope,
                resolve: {
                    document: function () {
                        return $scope.document;
                    },
                    customert: function () {
                        return $scope.customer;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
                $scope.reloadRequiredData();
            });
        };

    }]);

app.controller('ModalInstanceSideEmployeeDocumentCriticalCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, customert, document, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, $document, ListService) {


    var log = $log;
    var request = {};
    var attachmentUploadedId = 0;

    log.info("loading..customerEmployeeDocumentListCtrl ");

    $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
    $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
    $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

    $scope.documentStatus =  $rootScope.parameters("customer_document_status");
    $scope.isView = $scope.$parent.isCustomerContractor || $scope.$parent.editMode == "view";
    $scope.isInvalidate =  false;
    $scope.customerId = $stateParams.customerId;
    $scope.downloadUrl = "";

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancelDocument = function () {
        $uibModalInstance.dismiss('cancel');
    };

    getList();

    function getList() {

        var entities = [
            {name: 'customer_employee_document_type', value: $stateParams.customerId}
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.requirements =response.data.data.customerEmployeeDocumentType;

                initialize();
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    var initialize = function () {
        $scope.attachment = {
            id: 0,
            customerEmployeeId: document.customerEmployeeId,
            requirement: null,
            status: $scope.documentStatus && $scope.documentStatus.length > 0 ? $scope.documentStatus[0] : null,
            version: 1,
            description: "",
            startDate: null,
            endDate: null,
        };

        angular.forEach($scope.requirements, function(value, key) {
            if (value.value == document.id) {
                $scope.attachment.requirement = value;
            }
        });
    }

    $scope.master = $scope.attachment;

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

                if ($scope.uploader.queue.length == 0) {
                    SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un anexo e Intentalo de nuevo.", "error");
                    return;
                }

                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                //your code for submit
                save();
            }

        },
        reset: function (form) {

            $scope.attachment = angular.copy($scope.master);
            form.$setPristine(true);

        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.attachment);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-employee/document/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function(){
                $scope.attachment = response.data.result;
                attachmentUploadedId = response.data.result.id;
                uploader.uploadAll();
            });
        }).catch(function(e){
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function(){
            $scope.onCloseModal();
        });

    };

    var uploader = $scope.uploader = new FileUploader({
        url: 'api/customer-employee/document/upload',
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
        $scope.reloadData();
        $scope.clear();
    };

    $scope.clear = function(){
        initialize();
        $scope.isInvalidate = false;
    };
});
