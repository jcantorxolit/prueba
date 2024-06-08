'use strict';
/**
 * controller for Customers
 */
app.controller('customerEvaluationMinimumStandardImport0312Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'FileUploader', '$localStorage', '$aside', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, FileUploader,
              $localStorage, $aside, ListService) {

        console.log("editMode Import", $scope.$parent.editMode);

        var $formInstance = null;

        $scope.isCreate = $scope.$parent.currentId == 0;
        $scope.currentId = $scope.$parent.currentId;

        $scope.parentList = [];
        $scope.standardList = [];
        $scope.standardItemList = [];
        $scope.standardListAll = [];
        $scope.standardItemListAll = [];

        getList();

        function getList() {

            var $criteria = {
                customerId: $stateParams.customerId,
                customerEvaluationMinimumStandardId: $scope.currentId
            }

            var entities = [
                { name: 'customer_evaluation_minimum_stardard_parent_0312', value: null, criteria: $criteria },
                { name: 'customer_evaluation_minimum_stardard_0312', value: null, criteria: $criteria },
                { name: 'customer_evaluation_minimum_stardard_item_0312', value: null, criteria: $criteria },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.parentList = response.data.data.customerEvaluationMinimumStardardParent;
                    $scope.standardListAll = response.data.data.customerEvaluationMinimumStardard;
                    $scope.standardItemListAll = response.data.data.customerEvaluationMinimumStardardItem;

                    getListParent();
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var onInit = function () {
            $scope.entity = {
                id: $scope.$parent.currentId ? $scope.$parent.currentId : 0,
                customerEvaluationMinimumStardardParent: null,
                customerEvaluationMinimumStardard: null,
                customerEvaluationMinimumStardardItem: null,
                documentList: []
            };

            if ($formInstance != null) {
                $formInstance.$setPristine(true);
            }
        }

        onInit();

        $scope.onSelectParent = function() {
            getListParent();
            $scope.entity.customerEvaluationMinimumStardard = null;
            $scope.entity.customerEvaluationMinimumStardardItem = null;
            $scope.reloadData();
            $scope.entity.documentList = [];
        }

        $scope.onSelectStandard = function() {
            getListStandard();
            $scope.entity.customerEvaluationMinimumStardardItem = null;
            $scope.reloadData();
            $scope.entity.documentList = [];
        }

        $scope.onSelectStandardItem = function() {
            $scope.reloadData();
            $scope.entity.documentList = [];
        }

        var getListParent = function() {
            if ($scope.entity.customerEvaluationMinimumStardardParent != null) {
                $scope.standardList = $filter('filter')($scope.standardListAll, {minimumStandardParentId: $scope.entity.customerEvaluationMinimumStardardParent.minimumStandardParentId}, true);
            }
        }

        var getListStandard = function() {
            if ($scope.entity.customerEvaluationMinimumStardard != null) {
                $scope.standardItemList = $filter('filter')($scope.standardItemListAll, {minimumStandardParentId: $scope.entity.customerEvaluationMinimumStardard.minimumStandardId}, true);
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
                url: 'api/customer-evaluation-minimum-standard-item-document-0312/import',
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


        //------------------------------------------------------------------------------------
        //------------------------------------------------------------------------------------

        $scope.dtOptionsMinimumStandardItemDocument0312 = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerEvaluationMinimumStandardItemId = $scope.entity.customerEvaluationMinimumStardardItem ? $scope.entity.customerEvaluationMinimumStardardItem.customerEvaluationMinimumStandardItemId : 0;
                    d.customerId = $scope.entity.customerEvaluationMinimumStardardItem ? $stateParams.customerId : -1;
                    d.statusCode = '2'
                    return JSON.stringify(d);
                },
                url: 'api/customer-evaluation-minimum-standard-item-document-0312-available',
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

        $scope.dtColumnsMinimumStandardItemDocument0312 = [
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
            $("#dtMinimumStandardItemDocument0312 a.downloadDocumentRow").on("click", function () {
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

        $scope.dtInstanceMinimumStandardItemDocument0312Callback = function (instance) {
            $scope.dtInstanceMinimumStandardItemDocument0312 = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceMinimumStandardItemDocument0312.reloadData();
        };


    }
]);
