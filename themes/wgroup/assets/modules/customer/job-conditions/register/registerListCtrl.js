'use strict';
/**
 * controller for JobConditions
 */
app.controller('customerJobConditionsRegisterListCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $aside, $document, ListService, $localStorage, jobConditionRegisterNavigationService) {

        $scope.audit = {
            fields: [],
            filters: [],
        };

        getList();

        function getList() {
            var entities = [
                { name: 'criteria_operators', value: null },
                { name: 'criteria_conditions', value: null },
                { name: 'customer_job_conditions_filter_field', value: null },
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

        var storeDatatable = 'jobConditionsRegisterListCtrl-' + window.currentUser.id;
        $scope.dtInstanceJobConditionsRegister = {};
        $scope.dtOptionsJobConditionsRegister = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
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

                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-jobconditions',
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
            });


        $scope.dtColumnsJobConditions = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var disabled = ""

                if ($rootScope.can("view_job_conditions")) {
                    actions += '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <em class="fa fa-eye"></em></a> ';
                }
                if ($rootScope.can("edit_conditions_register")) {
                    actions += '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <em class="fa fa-edit"></em></a> ';
                }

                return actions;
            }),

            DTColumnBuilder.newColumn('documentType').withTitle("Tipo Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('date').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('state').withTitle("Estado").withOption('width', 120)
                .renderWith(function(data) {
                    switch  (data) {
                        case "EN PROCESO":
                            var type = 'label-warning';
                            break;
                        case "COMPLETADA":
                            var type = 'label-success';
                            break;
                        case "INICIAL":
                        default:
                            var type = 'label-info';
                    }

                    return '<span class="label ' + type + '">' + data + '</span>';
                })
        ];

        var loadRow = function() {
            $("#dtJobConditions a.viewRow").on("click", function() {
                var id = $(this).data("id");
                jobConditionRegisterNavigationService.setJobConditionId(id);
                jobConditionRegisterNavigationService.setViewRegisterEdit(true);
                $scope.$parent.navToSection("edit", true, id);
            });

            $("#dtJobConditions a.editRow").on("click", function() {
                var id = $(this).data("id");
                jobConditionRegisterNavigationService.setJobConditionId(id);
                jobConditionRegisterNavigationService.setViewRegisterEdit(false);
                $scope.$parent.navToSection("edit", false, id);
            });
        };

        $scope.reloadData = function() {
            $scope.dtInstanceJobConditionsRegister.reloadData();
        };

        $scope.onCreate = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", false, 0);
            }
        };

        $scope.onUpload = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/job-conditions/register/import/job_condition_import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalJobConditionsImportCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function(response) {
                if (response && response.sessionId) {
                    $rootScope.isAuthorizationTemplate = response.isAuthorizationTemplate;
                    $rootScope.hasCustomerEmployeeId = response.hasCustomerEmployeeId;
                    if ($scope.$parent != null) {
                        $scope.$parent.navToSection("stagingEmployee", "stagingEmployee", response.sessionId);
                    }
                }
            });

        };

    });

app.controller('ModalJobConditionsImportCtrl', function($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {
    var $exportUrl = '';
    var $lastResponse = null;
    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/job-conditions-import',
        formData: []
    });

    getList();

    $scope.title = "Importar condiciones puestos de trabajo";
    $scope.buttonDownloadTitle = "Descargar plantilla";

    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function(response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + 'api/v1/job-conditions-import';
                $scope.uploader.url = $exportUrl + 'api/v1/job-conditions-import';
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function() {
        var customerId = $stateParams.customerId;
        angular.element("#downloadDocument")[0].src = "api/customer-jobconditions/download-template?customerId=" + customerId;
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
        $stateParams.sessionId = response.sessionId;
    };
    uploader.onErrorItem = function(fileItem, response, status, headers) {
        console.info('onErrorItem', fileItem, response, status, headers);
        SweetAlert.swal("No fue posible anexar el archivo seleccionado!", response.message, "error");
    };
    uploader.onCancelItem = function(fileItem, response, status, headers) {
        console.info('onCancelItem', fileItem, response, status, headers);
    };
    uploader.onCompleteItem = function(fileItem, response, status, headers) {
        $lastResponse = response;
    };
    uploader.onCompleteAll = function() {
        $uibModalInstance.close($lastResponse);
    };

});