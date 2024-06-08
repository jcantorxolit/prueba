'use strict';
/**
 * controller for Customers
 */
app.controller('customerManagementImportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'FileUploader', '$localStorage', '$aside', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, FileUploader,
              $localStorage, $aside, ListService) {

        console.log("editMode Import", $scope.$parent.editMode);

        var $formInstance = null;

        $scope.isCreate = $scope.$parent.currentId == 0;
        $scope.currentId = $scope.$parent.currentManagement;
        $scope.currentProgram = $scope.$parent.currentProgram;

        $scope.categoryList = [];
        $scope.questionList = [];
        $scope.questionListAll = [];

        getList();

        function getList() {

            var $criteria = {
                customerId: $stateParams.customerId,
                customerManagementId: $scope.currentId
            }

            var entities = [
                // { name: 'customer_evaluation_minimum_stardard_parent_', value: null, criteria: $criteria },
                // { name: 'customer_evaluation_minimum_stardard_', value: null, criteria: $criteria },
                { name: 'customer_management_category', value: null, criteria: $criteria },
                { name: 'customer_management_program', value: $scope.currentId },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.categoryList = response.data.data.customerManagementCategoryList;
                    $scope.questionListAll = response.data.data.customerManagementQuestionList;

                    $scope.chapters = response.data.data.customerManagementProgram;

                    onInit();
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var onInit = function () {
            $scope.entity = {
                id: $scope.currentId,
                economicSector: null,
                customerWorkplace: null,
                program: null,
                customerManagementProgramCategory: null,
                customerManagementDetail: null,
                documentList: []
            };

            if ($formInstance != null) {
                $formInstance.$setPristine(true);
            }

            if ($scope.chapters && $scope.chapters.length > 0) {
                var entity = $scope.chapters[0];
                $scope.entity.economicSector = {
                    name: entity.economicSector
                };

                $scope.entity.customerWorkplace = {
                    name: entity.workplace
                }

                $scope.entity.program = {
                    name: entity.name,
                    abbreviation: entity.abbreviation,
                }
            }
        }

        onInit();

        $scope.onSelectCategory = function() {
            $scope.reloadData();
            $scope.entity.customerManagementDetail = null;
            $scope.entity.documentList = [];
            filterQuestionList();
        }

        $scope.onSelectQuestion = function() {
            $scope.reloadData();
            $scope.entity.documentList = [];
        }

        $scope.onSearchQuestion = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
                controller: 'ModalInstanceSideManagementImportCtrl',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                scope: $scope,
                resolve: {
                    data: function () {
                        return $scope.questionList;
                    }
                }
            });
            modalInstance.result.then(function (data) {
                $scope.entity.customerManagementDetail = data;
                $scope.onSelectQuestion();
            }, function() {

            });
        }

        var filterQuestionList = function() {
            if ($scope.entity.customerManagementProgramCategory != null) {
                $scope.questionList = $scope.questionListAll.filter(function(question) {
                    return question.categoryId == $scope.entity.customerManagementProgramCategory.id
                });
            }
        }

        $scope.form = {

            submit: function (form) {

                $formInstance = form;

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
                    //your code for submit
                    save();
                }

            },
            reset: function (form) {

                form.$setPristine(true);

            }
        };

        var save = function () {

            if ($scope.entity.documentList == null || $scope.entity.documentList === undefined || $scope.entity.documentList.length == 0) {
                SweetAlert.swal("Información requerida!", "Debes seleccionar al menos 1 anexo para importar.", "warning");
                return;
            }

            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-management-detail-document/import',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                SweetAlert.swal("Registro", "La información ha sido importada satisfactoriamente", "success");
                $timeout(function () {
                    onInit();
                    $scope.reloadData();
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.currentId);
            }
        };

        $scope.onContinue = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", $scope.currentId, $scope.currentProgram);
            }
        };


        //------------------------------------------------------------------------------------
        //------------------------------------------------------------------------------------

        $scope.dtOptionsManagementItemDocument = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.managementDetailId = $scope.entity.customerManagementDetail ? $scope.entity.customerManagementDetail.id : 0;
                    d.program = $scope.entity.program ? $scope.entity.program.abbreviation : -1;
                    d.customerId = $scope.entity.customerManagementDetail ? $stateParams.customerId : -1;
                    d.statusCode = '2'
                    return JSON.stringify(d);
                },
                url: 'api/customer-management-detail-document-available',
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
                loadRow();
            })
            .withOption('bFilter', false)
            .withOption('paging', false)
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsManagementItemDocument = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var url = data.documentUrl ? data.documentUrl : '';

                    var actions = "";
                    var downloadTemplate = '<a target="_self" class="btn btn-primary btn-xs downloadDocumentRow lnk" uib-tooltip="Descargar anexo" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-download"></i></a> ';

                    var isButtonVisible = true;

                    if (url != '') {
                        actions += downloadTemplate;
                    }

                    return isButtonVisible ? actions : "";
                }),
            DTColumnBuilder.newColumn('documentType').withTitle("Tipo de documento").withOption('width', 200),
            DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200),
            DTColumnBuilder.newColumn('version').withTitle("Versión").withOption('width', 200),
            DTColumnBuilder.newColumn('dateOfCreation').withTitle("Fecha Creación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch (data) {
                        case "Vigente":
                            label = 'label label-success';
                            break;

                        case "Anulado":
                            label = 'label label-danger';
                            break;
                    }

                    var status = '<span class="' + label + '">' + data + '</span>';

                    return status;
                }),
            DTColumnBuilder.newColumn(null).withTitle('Acciones').notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var checked = "";

                    var editTemplate = '<div class="checkbox clip-check check-success ">' +
                        '<input class="checkRow" type="checkbox" id="chk_' + data.id + '" data-id="' + data.id + '" data-value="' + data.selected + '" ' + checked + ' ><label for="chk_' + data.id + '"> Seleccionar </label></div>';
                    actions += editTemplate;

                    return actions;
                })
                .notSortable()
        ];

        var loadRow = function () {
            $("#dtManagementItemDocument a.downloadDocumentRow").on("click", function () {
                var id = $(this).data("id");
                angular.element("#download")[0].src = "api/customer-document/download?id=" + id;
            });

            $("input[type=checkbox]").on("change", function () {
                var id = $(this).data("id");
                var value = $(this).data("value");
                var checked = $(this).is(":checked");

                if (checked) {
                    $scope.entity.documentList.push({
                        id: id
                    });
                } else {
                    $scope.entity.documentList = $filter('filter')($scope.entity.documentList, {id: ('!' + id)});
                }
            });
        };

        $scope.dtInstanceManagementItemDocumentCallback = function (instance) {
            $scope.dtInstanceManagementItemDocument = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceManagementItemDocument.reloadData();
        };


    }
]);


app.controller('ModalInstanceSideManagementImportCtrl', function (
    $rootScope, $stateParams, $scope, $uibModalInstance, data, $http, toaster,
    DTOptionsBuilder, DTColumnBuilder, $compile, $q) {

        $scope.title = 'Preguntas'
        $scope.record = {};

        $scope.onCloseModal = function (data) {
            $uibModalInstance.close(data);
        };

        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.fromFnPromise(function() {
            var defer = $q.defer();
            defer.resolve(data);
            return defer.promise;
        })
        .withBootstrap()
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });

        $scope.dtColumnsCommonDataTableList = [
            DTColumnBuilder.newColumn(null).withOption('width', 200).withTitle('Acciones').notSortable()
                .renderWith(function(data, type, full, meta) {
                    $scope.record[data.id] = data;
                    var actions = "";

                    var editTemplate = '<a class="btn btn-success btn-xs" href="#" uib-tooltip="Seleccionar" ng-click="select(record[' + data.id + '])">' +
                    '   <i class="fa fa-plus-square"></i></a> ';
                    actions += editTemplate;

                    return actions;
                })
                .notSortable(),
            DTColumnBuilder.newColumn('article').withOption('width', 200).withTitle("Artículo").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
        ];

        $scope.dtInstanceCommonDataTableListCallback = function (instance) {
            $scope.dtInstanceCommonDataTableList = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCommonDataTableList.reloadData();
        };

        $scope.select = function(data) {
            $scope.onCloseModal(data);
        }
});
