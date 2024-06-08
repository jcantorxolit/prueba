'use strict';
/**
 * controller for Customers
 */
app.controller('consultantListCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, toaster, $state, $rootScope, $timeout, $http, SweetAlert, $aside, $document, ListService) {

        var log = $log;

        $scope.audit = {
            fields: [],
            filters: [],
        };

        getList();

        function getList() {
            var entities = [
                { name: 'criteria_operators', value: null },
                { name: 'criteria_conditions', value: null },
                { name: 'positiva_fgn_consultant_filter_field', value: null },
            ];

            ListService.getDataList(entities)
                .then(function(response) {
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.filterField;
                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $scope.addFilter = function() {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }
            $scope.audit.filters.push({
                id: 0,
                field: null,
                criteria: $scope.criteria.length > 0 ? $scope.criteria[1] : null,
                condition: $scope.conditions.length > 0 ? $scope.conditions[0] : null,
                value: ""
            });
        };

        $scope.onFilter = function() {
            $scope.reloadData();
        }

        $scope.removeFilter = function(index) {
            $scope.audit.filters.splice(index, 1);
        }

        $scope.onCleanFilter = function() {
            $scope.audit.filters = [];
            $scope.reloadData()
        }

        var storeDatatable = 'consultandListCtrl-' + window.currentUser.id;
        $scope.dtInstancePositivaFgn = {};
        $scope.dtOptionsPositivaFgn = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                        d.filter = {
                            filters: $scope.audit.filters.filter(function(filter) {
                                return filter != null && filter.field != null && filter.criteria != null;
                            }).map(function(filter, index, array) {
                                return {
                                    field: filter.field.name,
                                    operator: filter.criteria.value,
                                    value: filter.value,
                                    condition: filter.condition.value,
                                };
                            })
                        };
                    }
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-consultant',
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
                [0, 'desc']
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

        $scope.dtColumnsPositivaFgn = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var disabled = ""
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-edit"></i></a> ';

                var config = '<a class="btn btn-info btn-xs modalRow lnk" href="#"  uib-tooltip="Configurar seccionales" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="ti-map"></i></a> ';
                actions += editTemplate;
                actions += config;
                return actions;
            }),

            DTColumnBuilder.newColumn('fullName').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('documentType').withTitle("Tipo Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('documentNumber').withTitle("# Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('regional').withTitle("Regional").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('sectional').withTitle("Seccional").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('strategy').withTitle("Estrategia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
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

            $("#dtPositivaFgn a.editRow").on("click", function() {
                var id = $(this).data("id");
                $state.go("app.positiva-fgn.consultants-edit", { "consultantId": id });
            });

            $("#dtPositivaFgn a.modalRow").on("click", function() {
                var id = $(this).data("id");
                onConfigSectional(id);
            });
        };

        $scope.reloadData = function() {
            $scope.dtInstancePositivaFgn.reloadData();
        };

        $scope.onCreate = function() {
            $state.go("app.positiva-fgn.consultants-edit", { "consultantId": "" });
        };


        var onConfigSectional = function(id) {
            $scope.consultantId = id;
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/consultant/consultant_sectional.htm",
                placement: 'right',
                backdrop: 'static',
                size: 'lg',
                controller: 'ModalInstanceSideConsultantSectionalCtrl',
                scope: $scope
            });
            modalInstance.result.then(function() {
                $scope.reloadData();
            });
        }

        $scope.onUpload = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalPositivaFgnConsultantImportCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function(response) {
                $scope.reloadData();
            });
        };

    });


app.controller('ModalInstanceSideConsultantSectionalCtrl', function($rootScope, $stateParams, $scope, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder,
    DTColumnDefBuilder, $log, $timeout, SweetAlert, $http, $compile, ListService) {

    $scope.typeList = $rootScope.parameters("positiva_fgn_consultant_sectional_type");

    $scope.regionalList = [];
    $scope.sectionalList = [];

    var initialize = function() {
        $scope.entity = {
            id: 0,
            consultantId: $scope.consultantId,
            regional: null,
            sectional: null,
            type: null,
            isActive: false
        }
    }
    initialize();

    function getList() {
        var entities = [
            { name: 'positiva_fgn_consultant_sectional', criteria: { regionalId: $scope.entity.regional ? $scope.entity.regional.value : null } }
        ];

        ListService.getDataList(entities)
            .then(function(response) {
                $scope.regionalList = response.data.data.regionalList;
                $scope.sectionalList = response.data.data.sectionalList;
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }
    getList();

    $scope.filterSectional = function() {
        $scope.entity.sectional = null;
        getList();
    }

    $scope.form = {
        submit: function(form) {
            var firstError = null;
            $scope.Form = form;
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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
                return;
            } else {
                save();
            }
        },
        reset: function() {
            $scope.Form.$setPristine(true);
            initialize();
        }
    };


    var save = function() {
        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/positiva-fgn-consultant-sectional/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            $scope.form.reset();
            $scope.reloadData();
            SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
        }).catch(function(e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", e.data.message, "error");
        });

    };

    $scope.onLoadRecord = function() {
        if ($scope.entity.id > 0) {
            var req = {
                id: $scope.entity.id,
            };
            $http({
                    method: 'GET',
                    url: 'api/positiva-fgn-consultant-sectional/get',
                    params: req
                })
                .catch(function(e, code) {
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
                })
                .then(function(response) {
                    $timeout(function() {
                        $scope.entity = response.data.result;
                        getList();
                    });
                });
        }
    }

    $scope.dtInstancePositivaFgnSectional = {};
    $scope.dtOptionsPositivaFgnSectional = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.consultantId = $scope.consultantId;
                return JSON.stringify(d);
            },
            url: 'api/positiva-fgn-consultant-sectional',
            type: 'POST',
            beforeSend: function() {},
            complete: function() {}
        })
        .withDataProp('data')
        .withOption('order', [
            [0, 'desc']
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
        });


    $scope.dtColumnsPositivaFgnSectional = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
        .renderWith(function(data) {
            var actions = "";
            var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"  >' +
                '   <i class="fa fa-edit"></i></a> ';
            actions += editTemplate;
            return actions;
        }),

        DTColumnBuilder.newColumn('regional').withTitle("Regional").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('sectional').withTitle("Seccional").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
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
        $("#dtPositivaFgnSectional a.editRow").on("click", function() {
            var id = $(this).data("id");
            $scope.entity.id = id;
            $scope.onLoadRecord();
        });
    };

    $scope.reloadData = function() {
        $scope.dtInstancePositivaFgnSectional.reloadData();
    };

    $scope.onCloseModal = function() {
        $uibModalInstance.close(1);
    }


    $scope.onUpload = function() {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/consultant/consultant_import_modal.htm",
            placement: 'bottom',
            size: 'lg',
            backdrop: true,
            controller: 'consultantImportModalCtrl',
            scope: $scope,
        });
        modalInstance.result.then(function(response) {
            if (response && response.sessionId && $scope.$parent != null) {
                $scope.$parent.navToSection("staging", "staging", response.sessionId);
            }
            //$scope.reloadData();
        }, function() {});
    };


});


app.controller('ModalPositivaFgnConsultantImportCtrl', function($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;

    $scope.showTemplate = true;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/positiva-fgn/consultant-import',
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
                uploader.url = $exportUrl + 'api/v1/positiva-fgn/consultant-import';
                $scope.uploader.url = $exportUrl + 'api/v1/positiva-fgn/consultant-import';
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function() {
        jQuery("#downloadDocument")[0].src = "api/positiva-fgn-consultant/download-template";
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