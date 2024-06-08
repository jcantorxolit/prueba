'use strict';
/**
 * controller for Customers
 */
app.controller('reportDynamicallyCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar',
    '$filter', '$sce', 'ListService', 'ngNotify',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $sce, ListService, ngNotify) {

        var log = $log;
        var $exportUrl = '';

        $scope.settings = {
            bootstrap2: false,
            filterClear: 'Mostrar todo!',
            filterPlaceHolder: 'Filtrar!',
            moveSelectedLabel: 'Mover seleccionados solamente',
            moveAllLabel: 'Mover todos!',
            removeSelectedLabel: 'Remover seleccionados solamente',
            removeAllLabel: 'Remover todos!',
            moveOnSelect: true,
            preserveSelection: 'movido',
            selectedListLabel: '<span class="label label-success">Campos seleccionados</span>',
            nonSelectedListLabel: '<span class="label label-info">Campos disponibles</span>',
            postfix: '_helperz',
            selectMinHeight: 130,
            filter: true,
            filterNonSelected: '',
            filterSelected: '',
            infoAll: 'Mostrando todos {0}!',
            infoFiltered: '<span class="label label-warning">Filtered</span> {0} from {1}!',
            infoEmpty: 'Lista vacia!',
            filterValues: true
        };

        $scope.loading = true;
        $scope.isView = $state.is("app.report.view");
        $scope.isCreate = $state.is("app.report.create");

        $scope.audit = {
            fields: [],
            filters: [],
            hideButtons: true
        };

        getList();

        function getList() {
            var entities = [
                {name: 'criteria_operators', value: null},
                {name: 'criteria_conditions', value: null},
                { name: 'export_url', value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $exportUrl = response.data.data.exportUrl.item;
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var loadCollectionList = function()
        {
            $http({
                method: 'POST',
                url: 'api/collection-data',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.collections = response.data.data;
                        $scope.collectionReportList = $filter('filter')($scope.collections, {type: "report"});
                        $scope.loading = false;
                    });

                }).finally(function () {

                });
        }

        loadCollectionList();

        var init = function() {
            $scope.report = {
                collection: null,
                fields: [],
            }
        }

        init();

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
                    log.info($scope.report);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    onGenerate();
                }
            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        var onGenerate = function () {

            var validateMessage = '';

            if ($scope.report.fields == null || $scope.report.fields.length == 0) {
                validateMessage += "Debe seleccionar al menos un campo disponible de la colección \n";
            }

            if (validateMessage != '') {
                SweetAlert.swal({
                    html: false,
                    title: "Error de validación",
                    text: validateMessage,
                    type: "error"
                });
                return;
            }

            ngNotify.set('<div class="row"><div class="col-sm-5"><div class="loader-spinner pull-right"></div> </div> <div class="col-sm-6 text-left">El reporte se está generando. Por favor espere!</div> </div>', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var $criteria = {
                id: $scope.report.collection.id,
                isQueue: $scope.report.isQueue,
                fields: $scope.report.fields.map(function(field, index, array) {
                    return {
                        name: field.name,
                        alias: field.alias,
                    };
                }),
                userId: $rootScope.currentUser().id
            }

            if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {

                $criteria.filter =
                    {
                        filters: $scope.audit.filters.filter(function (filter) {
                            return filter != null && filter.field != null;
                          }).map(function(filter, index, array) {
                            return {
                                field: filter.field.value,
                                operator: filter.criteria.value,
                                value: filter.value,
                                condition: filter.condition.value
                            };
                        })
                    };
            }

            var request = { data : Base64.encode(JSON.stringify($criteria)) };

            return $http({
                method: 'POST',
                url: $exportUrl + 'api/v1/report-export-dynamically',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(request)
            }).then(function (response) {

                var $url = $exportUrl + 'storage/' + response.data.filename;
                var $link = '<a class="btn btn-wide btn-default" href="' + $url + '" target="_self"><i class="glyphicon glyphicon-download"></i> Descargar el reporte</a>';

                if (response.data.isQueue) {
                    //$url = $state.href(app.user.messages, {}, {absolute: true});
                    $url = 'app/user/messages';
                    $link = response.data.message + ' <a  class="btn btn-wide btn-default" href="' + $url + '" translate="Ver mensajes"> Ver mensajes </a>';

                }

                ngNotify.set($link, {
                    position: 'bottom',
                    sticky: true,
                    type: response.data.isQueue ? 'info' : 'success',
                    button: true,
                    html: true
                });

            }).catch(function (response) {

                if (response.data != null && response.data.message !== undefined) {
                    ngNotify.set(response.data.message, {
                        position: 'bottom',
                        sticky: true,
                        type: 'error',
                        button: true,
                        html: true
                    });
                } else {
                    ngNotify.set("Lo sentimos, ha ocurrido un error en la generación del reporte", {
                        position: 'bottom',
                        sticky: true,
                        type: 'error',
                        button: true,
                        html: true
                    });
                }

            }).finally(function () {

            });

        };

        $scope.onCancel = function () {
            if ($scope.isView) {
                //$state.go('app.report.list');
            } else {
                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Se perderán todos los cambios realizados en esta vista.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Si, cancelar!",
                        cancelButtonText: "No, continuar!",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function (isConfirm) {
                        if (isConfirm) {
                            $timeout(function () {
                                $state.go('app.report.list');
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        $scope.addFilter = function()
        {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }
            $scope.audit.filters.push(
                {
                    id: 0,
                    field: null,
                    criteria: $scope.criteria.length > 0 ? $scope.criteria[1] : null,
                    condition: $scope.conditions.length > 0 ? $scope.conditions[0] : null,
                    value: ""
                }
            );
        }

        $scope.removeFilter = function(index)
        {
            $scope.audit.filters.splice(index, 1);
        }

        $scope.onSelectCollection = function() {
            $scope.audit.filters = $scope.audit.filters.map(function(filter, index, array) {
                filter.field = null;
                return filter;
            });

            $scope.audit.fields = $scope.report.collection.fields.map(function(field, index, array) {
                return {alias: field.alias, value: field.name};
            });
        }
    }
]);



