'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeDocumentValidateCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', 'SweetAlert', '$http', '$filter', '$document', 'FileUploader', '$aside', '$localStorage',
    '$ngConfirm', 'ngNotify', 'ListService', 'CustomerEmployeeDocumentService', 'pdfDelegate',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
        $compile, toaster, $state, $rootScope, $timeout, SweetAlert, $http, $filter, $document,
        FileUploader, $aside, $localStorage, $ngConfirm, ngNotify, ListService, CustomerEmployeeDocumentService,
        pdfDelegate) {

        $scope.$storage = $localStorage.$default({
            hideCanceled: true
        });

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        // parametros para seguimientos

        $scope.queryStringExtra = null;
        $scope.isView = true;
        $scope.isFirst = CustomerEmployeeDocumentService.isFirstDocument();
        $scope.isLast = CustomerEmployeeDocumentService.isLastDocument();
        $scope.currentDocumentOrdinal = CustomerEmployeeDocumentService.getCurrentDocumentOrdinal();
        $scope.totalDocuments =  CustomerEmployeeDocumentService.getTotalDocuments();
        $scope.isBackNavigationVisible = true;
        $scope.canValidateDocument = $rootScope.can("empleado_documento_approved_revised") || $rootScope.can("empleado_documento_reviewed_denied")

        $scope.documentStatus = $rootScope.parameters("customer_document_status");
        $scope.isInvalidate = false;
        $scope.customerId = $stateParams.customerId;
        $scope.downloadUrl = "";
        $scope.originalStatusList = [];

        getList();

        function getList() {

            var entities = [
                { name: 'customer_employee_document_type', value: $stateParams.customerId },
                { name: 'customer_employee_document_management', value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.originalStatusList = response.data.data.customer_employee_document_management;
                    $scope.requirements = response.data.data.customerEmployeeDocumentType;
                    checkPermissions();
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var checkPermissions = function () {
            $scope.statusList = $scope.originalStatusList.filter(function(item, index, array) {
                if ($scope.isAdmin || $scope.isAgent || $scope.$parent.isCustomerContractor) {
                    var isVerified = $scope.attachment && $scope.attachment.isVerified ? $scope.attachment.isVerified : null
                    return (item.value == 1 && $rootScope.can("empleado_documento_approved_revised") && isVerified != "Aprobado") ||
                        (item.value == 3 && $rootScope.can("empleado_documento_reviewed_denied") && isVerified != "Denegado");
                } else {
                    return false;
                }
            });
        }


        var initialize = function () {
            $scope.attachment = {
                id: 0,
                customerEmployeeId: $scope.$parent.currentEmployee,
                requirement: null,
                status: $scope.documentStatus && $scope.documentStatus.length > 0 ? $scope.documentStatus[0] : null,
                version: 1,
                description: "",
                startDate: null,
                endDate: null,
                tracking: {
                    action: null,
                    description: ""
                }
            };
        }

        initialize();

        var onLoadRecord = function (id) {
            var req = {};
            var data = JSON.stringify({ id: id, customerId: $scope.customerId });
            req.data = Base64.encode(data);
            $scope.queryStringExtra = null;
            $scope.isImage = false;
            $scope.hasFile = false;
            $scope.isLoading = true;
            $http({
                method: 'POST',
                url: 'api/customer-employee-document/get',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            })
                .catch(function (response) {

                })
                .then(function (response) {
                    $scope.isLoading = false;
                    $timeout(function () {
                        $scope.attachment = response.data.result;
                        $scope.hasFile = $scope.attachment.document != null;
                        if ($scope.attachment.document && $scope.attachment.document.extension == 'pdf') {
                            $scope.queryStringExtra = $rootScope.app.rootUrl + "api/customer-employee/document/stream?id=" + $scope.attachment.id
                            $timeout(function () {
                                pdfDelegate.$getByHandle('pdf-container').load($scope.queryStringExtra);
                            }, 100);
                        } else if ($scope.attachment.document && $scope.attachment.document.content_type.startsWith("image")) {
                            $scope.isImage = true;
                        } else {
                            $scope.isImage = false;
                            $scope.queryStringExtra = null;
                        }
                        initializeDates();
                        checkPermissions();
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        };

        onLoadRecord(CustomerEmployeeDocumentService.getCurrentDocumentId());

        var initializeDates = function () {
            if ($scope.attachment.startDate != null) {
                $scope.attachment.startDate = new Date($scope.attachment.startDate.date);
            }

            if ($scope.attachment.endDate != null) {
                $scope.attachment.endDate = new Date($scope.attachment.endDate.date);
            }
        }

        $scope.$watch("$parent.currentEmployee", function () {
            initialize();
        });

        $scope.form = {

            submit: function (form) {
                var firstError = null;

                if (form.$invalid) {

                    var field = null,
                        firstError = null;
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

                    if ($scope.attachment.tracking.status.value == 1) {
                        approve();
                    } else {
                        denied()
                    }
                }

            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        var denied = function () {
            var req = {};
            $scope.attachment.tracking.action = $scope.attachment.tracking.status.item;
            var data = JSON.stringify($scope.attachment);

            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-employee/document/denied',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Validación exitosa", "Documento revisado denegado...", "success");
                    $scope.attachment.isVerified = 'Denegado';
                    $scope.attachment.observation = $scope.attachment.tracking.description;
                    checkPermissions();
                    $scope.attachment.tracking = null;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        var approve = function () {
            SweetAlert.swal({
                title: "Está seguro?",
                text: "El documento sera marcado como revisado aprobado.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Si, aprobar!",
                cancelButtonText: "No, cancelar!",
                closeOnConfirm: true,
                closeOnCancel: true
            },
                function (isConfirm) {
                    if (isConfirm) {

                        var req = {};
                        var document = { id: $scope.attachment.id };
                        var data = JSON.stringify(document);

                        req.data = Base64.encode(data);
                        return $http({
                            method: 'POST',
                            url: 'api/customer-employee/document/approve',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            data: $.param(req)
                        }).then(function (response) {

                            $timeout(function () {
                                SweetAlert.swal("Validación exitosa", "Documento revisado aprobado...", "success");
                                $scope.attachment.isVerified = 'Aprobado';
                                checkPermissions();
                                $scope.attachment.tracking = null;
                                $scope.attachment.observation = null;
                            });
                        }).catch(function (e) {
                            $log.error(e);
                            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                        }).finally(function () {

                        });

                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        $scope.onBack = function (form) {
            $rootScope.$emit('employeeDocumentNavigate', { newValue: 'list'});
        }

        $scope.onContinue = function (form) {
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
            }

            if (form.$dirty) {
                if ($scope.attachment.tracking.status.value == 1) {
                    approve();
                } else {
                    denied()
                }
                form.$setPristine(true);
            }
        }

        $scope.onNext = function (form) {
            $timeout(function () {
                onLoadRecord(CustomerEmployeeDocumentService.getNextDocumentId());
                $scope.isFirst = CustomerEmployeeDocumentService.isFirstDocument();
                $scope.isLast = CustomerEmployeeDocumentService.isLastDocument();
                $scope.currentDocumentOrdinal = CustomerEmployeeDocumentService.getCurrentDocumentOrdinal();
            }, 100);
        }

        $scope.onPrevious = function (form) {
            $timeout(function () {
                onLoadRecord(CustomerEmployeeDocumentService.getPreviousDocumentId());
                $scope.isFirst = CustomerEmployeeDocumentService.isFirstDocument();
                $scope.isLast = CustomerEmployeeDocumentService.isLastDocument();
                $scope.currentDocumentOrdinal = CustomerEmployeeDocumentService.getCurrentDocumentOrdinal();
            }, 100);
        }

        $scope.onDownload = function() {
            jQuery("#download")[0].src = "api/customer-employee/document/download?id=" + $scope.attachment.id;
        }

        $scope.onSelectStatus = function() {
            $scope.attachment.tracking.description = null;
        }
    }
]);
