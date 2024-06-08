'use strict';
/**
 * controller for Customers
 */
app.controller('templateManageListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', 'SweetAlert', '$http', '$aside', 'moment', 'ListService', '$localStorage',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, SweetAlert, $http, $aside, moment, ListService, $localStorage) {

        var log = $log;

        $scope.$storage = $localStorage.$default({
            showAllTemplate: false
        });

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
                { name: 'template_manage_custom_filter_field', value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.templateManageCustomFilterField;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.dtOptionsTemplateManage = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
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

                    if ($scope.$storage.showAllTemplate) {
                        d.showAllTemplate = true
                    }

                    return JSON.stringify(d);
                },
                url: 'api/template-manage',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[3, 'desc']])
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

        $scope.dtColumnsTemplateManage = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var hasFile = data.hasFile;
                    var downloadUrl = "api/template-manage/download?id=" + data.id;

                    var actions = "";

                    var disabled = "";

                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" ' + disabled + '  >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var downloadTemplate = '<a target="_self" class="btn btn-success btn-xs downloadDocumentRow lnk" href="' + downloadUrl + '" uib-tooltip="Descargar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-download"></i></a> ';

                    var publishTemplate = '<a class="btn btn-info btn-xs pubRow lnk" href="#"  uib-tooltip="Publicar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play"></i></a> ';


                    if (hasFile) {
                        actions += downloadTemplate;
                        actions += data.status == 'Activo' ? publishTemplate : '';
                    }

                    return actions;
                }),

            DTColumnBuilder.newColumn('createdAt').withTitle("Fecha Creación").withOption('type', 'date').withOption('width', 200).renderWith(function (data, type, full, meta) {
                return moment(data.date).format('DD/MM/YYYY');
            }),
            DTColumnBuilder.newColumn('template').withTitle("Plantilla").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('filename').withTitle("Archivo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('dateOf').withTitle("Fecha Publicación").withOption('type', 'date').withOption('width', 200).renderWith(function (data, type, full, meta) {
                return moment(data).format('DD/MM/YYYY');
            }),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-success';
                    var text = data.status;

                    if (data.status == 'Activo') {
                        label = 'label label-info';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';


                    return status;
                })
        ];

        var loadRow = function () {

            $("#dtTemplateManage a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onAddTemplateManage(id);
            });

            $("#dtTemplateManage a.downloadRow").on("click", function () {
                var id = $(this).data("id");

                jQuery("#downloadDocument")[0].src = "api/template-manage/download?id=" + id;
            });

            $("#dtTemplateManage a.pubRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

                SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Publicará la plantilla seleccionada.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, publicar!",
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
                                url: 'api/template-manage/publish',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Publicado", "Plantilla publicada satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la publicación", "Se ha presentado un error durante la publicación de la plantilla. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceTemplateManageCallback = function (instance) {
            $scope.dtInstanceTemplateManage = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceTemplateManage.reloadData();
        };

        $scope.onShowAllChange = function () {
            $scope.reloadData();
        }

        $scope.onCreate = function () {
            $scope.onAddTemplateManage(0);
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

        $scope.onAddTemplateManage = function (id) {

            var resource = { id: id }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_resource_library.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/configuration/template-manage/template_manage_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideTemplateManageCtrl',
                scope: $scope,
                resolve: {
                    resource: function () {
                        return resource;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function () {
                $scope.reloadData();
            });
        };

    }]);

app.controller('ModalInstanceSideTemplateManageCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, resource, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.uploader = new Flow();

    $scope.templateList = $rootScope.parameters("wg_import_template");

    var attachmentUploadedId = 0;

    var initialize = function () {
        $scope.templateManage = {
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

        if ($scope.templateManage.id) {
            var req = {
                id: $scope.templateManage.id
            };

            $http({
                method: 'GET',
                url: 'api/template-manage',
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
                        $scope.templateManage = response.data.result;

                        $scope.noImage = $scope.templateManage.cover == '';

                        initializeDates();
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        }
    };

    loadRecord();

    var initializeDates = function () {
        if ($scope.templateManage.dateOf != null) {
            $scope.templateManage.dateOf = new Date($scope.templateManage.dateOf.date);
        }
    }

    $scope.removeImage = function () {
        $scope.noImage = true;
    };

    var uploaderResource = $scope.uploaderResource = new FileUploader({
        url: 'api/template-manage/upload',
        formData: []
    });

    uploaderResource.filters.push({
        name: 'customFilter',
        fn: function (item/*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploaderResource.onBeforeUploadItem = function (item) {
        console.info('onBeforeUploadItem', item);
        var formData = { id: attachmentUploadedId };
        item.formData.push(formData);
    };

    uploaderResource.onCompleteAll = function () {
        console.info('onCompleteAll');
        toaster.pop('success', 'Operación Exitosa', 'La información ha sido guardada satisfactoriamente');
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

                if ($scope.templateManage.id == 0 && $scope.uploaderResource.queue.length == 0) {
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

        var data = JSON.stringify($scope.templateManage);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/template-manage/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                if (uploaderResource.queue.length > 0) {
                    attachmentUploadedId = response.data.result.id;
                    uploaderResource.uploadAll();
                } else {
                    toaster.pop('success', 'Operación Exitosa', 'La información ha sido guardada satisfactoriamente');
                    $scope.onCloseModal();
                }
            });
        }).catch(function (e) {
            $log.error(e);
            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
        }).finally(function () {

        });

    };


});
