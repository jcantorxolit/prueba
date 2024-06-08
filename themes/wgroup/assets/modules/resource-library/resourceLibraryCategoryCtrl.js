'use strict';
/**
 * controller for Customers
 */
app.controller('resourceLibraryCategoryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', 'SweetAlert', '$http', '$timeout', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, SweetAlert, $http, $timeout, $aside) {

        var log = $log;
        var request = {};

        $scope.resourceLibraryTypeList = $rootScope.parameters("resource_library_type");

        log.info("entrando en... certificateAdminProgramCtrl");

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        if ($scope.isAgent) {
            //$state.go("app.clientes.list");
        } else if ($scope.isCustomer) {
            //$state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }

        $scope.currentMonth = 0;
        $scope.currentYear = 0;
        $scope.filter = {
            selectedMonth: null,
            selectedYear: null,
        };

        $scope.request = {};

        $scope.pageSizeList = [
            {item: "10 registros", value: 10},
            {item: "50 registros", value: 50},
            {item: "100 registros", value: 100}
        ]

        $scope.criteria = {
            itemsPerPage: $scope.pageSizeList[0],
            currentPage: 1,
            keyword: '',
            selectedType: null
        }

        $scope.totalItems = 0;

        $scope.pageChanged = function () {
            loadData($scope.currentProgramId);
        };

        $scope.$watch("criteria.itemsPerPage", function () {
            if ($scope.criteria.itemsPerPage) {
                loadData();
            }
        });

        $scope.maxSize = 5;

        var loadData = function () {

            var req = {};

            req.type = $scope.criteria.selectedType ? $scope.criteria.selectedType.value : '';
            req.keyword = $scope.criteria.keyword;
            req.page_size = $scope.criteria.itemsPerPage.value;
            req.current_page = $scope.criteria.currentPage;

            $http({
                method: 'POST',
                url: 'api/resource-library-category',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $scope.resources = response.data.data.data;
                $scope.totalItems = response.data.data.totalItems;

            }).catch(function (e) {
                SweetAlert.swal("Error Consultando Preguntas", "Se ha presentado un error durante la consulta del cuestionario, por favor intentelo de nuevo.", "error");
            }).finally(function () {

            });

        };

        //loadData();

        $scope.changeType = function (item, model) {
            $timeout(function () {
                loadData();
            });
        };

        $scope.clearType = function () {
            $timeout(function () {
                $scope.criteria.selectedType = null;
                loadData();
            });
        }

        $scope.changeKeyword = function (item, model) {
            $timeout(function () {
                loadData();
            });
        };

        $scope.clearKeyword = function () {
            $timeout(function () {
                $scope.criteria.keyword = ''
                loadData();
            });
        }

        $scope.onDownload = function(id) {
            jQuery("#downloadDocument")[0].src = "api/resource-library/download?id=" + id;
        }

        $scope.onView = function(id) {
            $scope.onAddResourceLibrary(id);
        }

        $scope.onAddResourceLibrary = function (id) {

            var resource = {id: id}

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_resource_library.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/resource-library/resource_library_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideResourceLibraryCategoryViewCtrl',
                scope: $scope,
                resolve: {
                    resource: function () {
                        return resource;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        };

    }]);

app.controller('ModalInstanceSideResourceLibraryCategoryViewCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, resource, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.flowConfig = {target: '/api/resource-library/upload-cover', singleFile: true};
    $scope.uploader = new Flow();

    $scope.resourceLibraryTypeList = $rootScope.parameters("resource_library_type");
    $scope.isView = true;

    var attachmentUploadedId = 0;

    var initialize = function () {
        $scope.resource = {
            id: resource.id ? resource.id : 0,
            type: null,
            dateOf: null,
            name: "",
            author: "",
            subject: "",
            description: "",
            isActive: true,
            keywords: []
        };
    };

    initialize();

    var loadRecord = function () {
        // se debe cargar primero la información actual del cliente..

        if ($scope.resource.id) {
            var req = {
                id: $scope.resource.id
            };

            $http({
                method: 'GET',
                url: 'api/resource-library',
                params: req
            })
                .catch(function (e, code) {
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Anexo no encontrado", "error");
                        $timeout(function () {

                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del anexo", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.resource = response.data.result;

                        if ($scope.resource.dateOf != null) {
                            $scope.resource.dateOf = new Date($scope.resource.dateOf.date);
                        }
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        }
    };

    loadRecord();

    if ($scope.resource.cover == '') {
        $scope.noImage = true;
    }

    $scope.removeImage = function () {
        $scope.noImage = true;
    };

    var uploaderResource = $scope.uploaderResource = new FileUploader({
        url: 'api/resource-library/upload',
        formData: []
    });

    uploaderResource.filters.push({
        name: 'customFilter',
        fn: function (item/*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploaderResource.onWhenAddingFileFailed = function (item/*{File|FileLikeObject}*/, filter, options) {
        console.info('onWhenAddingFileFailed', item, filter, options);
    };
    uploaderResource.onAfterAddingFile = function (fileItem) {
        console.info('onAfterAddingFile', fileItem);
    };
    uploaderResource.onAfterAddingAll = function (addedFileItems) {
        console.info('onAfterAddingAll', addedFileItems);
    };
    uploaderResource.onBeforeUploadItem = function (item) {
        console.info('onBeforeUploadItem', item);
        var formData = {id: attachmentUploadedId};
        item.formData.push(formData);
    };
    uploaderResource.onProgressItem = function (fileItem, progress) {
        console.info('onProgressItem', fileItem, progress);
    };
    uploaderResource.onProgressAll = function (progress) {
        console.info('onProgressAll', progress);
    };
    uploaderResource.onSuccessItem = function (fileItem, response, status, headers) {
        console.info('onSuccessItem', fileItem, response, status, headers);
    };
    uploaderResource.onErrorItem = function (fileItem, response, status, headers) {
        console.info('onErrorItem', fileItem, response, status, headers);
    };
    uploaderResource.onCancelItem = function (fileItem, response, status, headers) {
        console.info('onCancelItem', fileItem, response, status, headers);
    };
    uploaderResource.onCompleteItem = function (fileItem, response, status, headers) {
        console.info('onCompleteItem', fileItem, response, status, headers);
    };
    uploaderResource.onCompleteAll = function () {
        console.info('onCompleteAll');
        $scope.onCloseModal();
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

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

                $timeout(function () {
                    toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                }, 500);

                return;

            } else {

                if ($scope.uploaderResource.queue.length == 0) {
                    SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un archivo e Intentalo de nuevo.", "error");
                    return;
                }

                $scope.onSave();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.onSave = function () {

        var req = {};

        var data = JSON.stringify($scope.resource);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/resource-library/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                $scope.resource = response.data.result;

                $scope.uploader.flow.opts.query.id = response.data.result.id;
                $scope.uploader.flow.resume();

                attachmentUploadedId = response.data.result.id;

                uploaderResource.uploadAll();

                toaster.pop('success', 'Operación Exitosa', 'Registro guardado');
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            $log.error(e);
            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
        }).finally(function () {

        });

    };


    //----------------------------------------------------------------KEYWORDS
    $scope.onAddKeyword = function () {

        $timeout(function () {
            if ($scope.resource.keywords == null) {
                $scope.resource.keywords = [];
            }
            $scope.resource.keywords.push
            (
                { text: '' }
            );
        });
    };

    $scope.onRemoveKeyword = function (index) {
        SweetAlert.swal({
                title: "Está seguro?",
                text: "Desea confirmar la eliminación de este registro ?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Si, eliminar!",
                cancelButtonText: "No, cancelar!",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm) {
                    $timeout(function () {
                        $scope.resource.keywords.splice(index, 1);
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    }

});
