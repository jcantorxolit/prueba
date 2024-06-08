'use strict';
/**
 * controller for Customers
 */
app.controller('resourceLibraryListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', 'SweetAlert', '$http', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, SweetAlert, $http, $aside) {

        var log = $log;

        log.info("entrando en... certificateAdminProgramCtrl");

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";
        $scope.currentCustomerId = $rootScope.currentUser().company;

        if ($scope.isAgent) {
            //$state.go("app.clientes.list");
        } else if ($scope.isCustomer) {
            //$state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }


        $scope.request = {};

        log.info("loading..customerAuditListCtrl ");

        $scope.criteria = [
            {
                name: "Igual",
                value: "="
            }, {
                name: "Contiene",
                value: "LIKE"
            }, {
                name: "Diferente",
                value: "<>"
            }, {
                name: "Mayor que",
                value: ">"
            }, {
                name: "Menor que",
                value: "<"
            }
        ];

        $scope.conditions = [
            {
                name: "Y",
                value: "AND"
            }, {
                name: "O",
                value: "OR"
            }
        ];


        $scope.audit = {
            fields: [
                {
                    name: "resource_library_type.item",
                    alias: "Tipo"
                }, {
                    name: "name",
                    alias: "Nombre"
                }, {
                    name: "subject",
                    alias: "Asunto"
                }, {
                    name: "description",
                    alias: "Descripción"
                }, {
                    name: "dateOf",
                    alias: "Fecha Recurso"
                }, {
                    name: "isActive",
                    alias: "Estado"
                }
            ],
            filters: [],
        };

        // Datatable configuration
        $scope.request.operation = "audit";
        $scope.request.data = "";

        $scope.dtInstanceResourceLibrary = {};
        $scope.dtOptionsResourceLibrary = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/resource-library',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[3, 'asc']])
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

        $scope.dtColumnsResourceLibrary = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var url = data.document != null ? data.document.path : "";

                    var actions = "";

                    //var disabled = (data.hasCertificate) ? "" : "disabled";
                    var disabled = "";

                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" ' + disabled + '  >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var downloadTemplate = '<a class="btn btn-info btn-xs downloadRow lnk" href="#"  uib-tooltip="Descargar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-download"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    actions += editTemplate;

                    if (url != '') {
                        actions += downloadTemplate;
                    }

                    actions += deleteTemplate;

                    return actions;
                }),

            DTColumnBuilder.newColumn('createdAtFormat').withTitle("Fecha Creación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type.item').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('dateOfFormat').withTitle("Fecha Recurso").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('author').withTitle("Autor/Emisor").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('subject').withTitle("Asunto").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';

                    if (data == 1 || data) {
                        label = 'label label-success';
                        text = 'Activo';
                    } else {
                        label = 'label label-danger';
                        text = 'Inactivo';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';


                    return status;
                })
        ];

        var loadRow = function () {

            $("#dtResourceLibrary a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onAddResourceLibrary(id);
            });

            $("#dtResourceLibrary a.downloadRow").on("click", function () {
                var id = $(this).data("id");

                jQuery("#downloadDocument")[0].src = "api/resource-library/download?id=" + id;
            });

            $("#dtResourceLibrary a.delRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminara el recurso de la biblioteca seleccionado.",
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

                            var req = {};
                            req.id = id;
                            $http({
                                method: 'POST',
                                url: 'api/resource-library/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.reloadData = function () {
            $scope.dtInstanceResourceLibrary.reloadData();
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
                    criteria: null,
                    condition: null,
                    value: ""
                }
            );
        };

        $scope.onFilter = function () {

            if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                angular.forEach($scope.audit.filters, function(filter) {
                    filter.condition = { value: 'and'}
                });
            }

            $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

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
                controller: 'ModalInstanceSideResourceLibraryCtrl',
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

app.controller('ModalInstanceSideResourceLibraryCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, resource, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.flowConfig = {target: '/api/resource-library/upload-cover', singleFile: true};
    $scope.uploader = new Flow();

    $scope.resourceLibraryTypeList = $rootScope.parameters("resource_library_type");

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

                if ($scope.resource.id == 0 && $scope.uploaderResource.queue.length == 0) {
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
