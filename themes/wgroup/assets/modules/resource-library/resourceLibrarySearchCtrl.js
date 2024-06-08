'use strict';
/**
 * controller for Customers
 */
app.controller('resourceLibrarySearchCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', 'SweetAlert', '$http', '$aside', 'ListService', 'moment',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, SweetAlert, $http, $aside, ListService, moment) {

        var log = $log;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";
        $scope.currentCustomerId = $rootScope.currentUser().company;

        $scope.audit = {
            fields: [],
            filters: [],
        };

        getList();

        function getList() {
            var entities = [
                { name: 'criteria_operators', value: null },
                { name: 'criteria_conditions', value: null },
                { name: 'resource_library_custom_filter_field', value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.resourceLibraryCustomFilterField;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.dtOptionsResourceLibrarySearch = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                        d.filter =
                        {
                            filters: $scope.audit.filters.filter(function (filter) {
                                return filter != null && filter.field != null && filter.criteria != null;
                            }).map(function (filter, index, array) {
                                return {
                                    field: filter.field.name,
                                    operator: filter.criteria.value,
                                    value: filter.value,
                                    condition: { value: 'and' }
                                };
                            })
                        };
                    }

                    return JSON.stringify(d);
                },
                url: 'api/resource-library',
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

        $scope.dtColumnsResourceLibrarySearch = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var url = data.documentUrl ? data.documentUrl : '';
                    var downloadUrl = "api/resource-library/download?id=" + data.id;

                    var actions = "";

                    var disabled = "";

                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" ' + disabled + '  >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var downloadTemplate = '<a target="_self" class="btn btn-info btn-xs downloadDocumentRow lnk" href="' + downloadUrl + '" uib-tooltip="Descargar recurso" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-download"></i></a> ';


                    actions += editTemplate;

                    if (url != '') {
                        actions += downloadTemplate;
                    }

                    return actions;
                }),

            DTColumnBuilder.newColumn('createdAt').withTitle("Fecha Creación").withOption('type', 'date').withOption('width', 200).renderWith(function (data, type, full, meta) {
                return moment(data.date).format('DD/MM/YYYY');
            }),
            DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('dateOf').withTitle("Fecha Recurso").withOption('type', 'date').withOption('width', 200).renderWith(function (data, type, full, meta) {
                return moment(data).format('DD/MM/YYYY');
            }),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('author').withTitle("Autor/Emisor").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('subject').withTitle("Asunto").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-success';
                    var text = data.status;

                    if (!data.isActive) {
                        label = 'label label-danger';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';


                    return status;
                })
        ];

        var loadRow = function () {

            $("#dtResourceLibrarySearch a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onAddResourceLibrary(id);
            });

            $("#dtResourceLibrarySearch a.downloadRow").on("click", function () {
                var id = $(this).data("id");

                jQuery("#downloadDocument")[0].src = "api/resource-library/download?id=" + id;
            });
        };

        $scope.dtInstanceResourceLibrarySearchCallback = function (instance) {
            $scope.dtInstanceResourceLibrarySearch = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceResourceLibrarySearch.reloadData();
        };

        $scope.onCreate = function () {
            $scope.onAddResourceLibrary(0);
        };

        $scope.addFilter = function () {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }
            $scope.audit.filters.push(
                {
                    id: 0,
                    field: null,
                    criteria: $scope.criteria.length > 0 ? $scope.criteria[1] : null,
                    condition: $scope.criteria.length > 0 ? $scope.conditions[0] : null,
                    value: ""
                }
            );
        };

        $scope.onFilter = function () {
            $scope.reloadData();
        }

        $scope.removeFilter = function (index) {
            $scope.audit.filters.splice(index, 1);

            if ($scope.audit.filters.length == 0) {
                $scope.reloadData();
            }
        }

        $scope.onCleanFilter = function () {
            $scope.audit.filters = [];
            $scope.reloadData();
        }

        $scope.onAddResourceLibrary = function (id) {

            var resource = {id: id}

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_resource_library.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/resource-library/resource_library_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideResourceLibraryViewCtrl',
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

app.controller('ModalInstanceSideResourceLibraryViewCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, resource, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

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
