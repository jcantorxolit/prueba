'use strict';
/**
 * controller for Customers
 */
app.controller('reportGenerateCtrl', ['$scope', '$stateParams', '$log','$compile', 'toaster', '$state',
    'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    'ListService', 'ngNotify', '$sce',
    function ($scope, $stateParams, $log, $compile, toaster, $state,SweetAlert, $rootScope, $http, $timeout, $uibModal,
        flowFactory, cfpLoadingBar, $filter, ListService, ngNotify, $sce) {

        var log = $log;
        var $exportUrl = '';

        var isCustomer = $rootScope.isCustomer();
        var isAgent = $rootScope.isAgent();
        var isAdmin = $rootScope.isAdmin();

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
                    //$scope.audit.fields = response.data.data.customerAbsenteeismDisabilityFilterField;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $scope.report = {
            id: $scope.isCreate ? 0 : $stateParams.reportId,
            collection: null,
            collectionChart: null,
            name: "",
            description: "",
            isActive: true,
            allowAgent: false,
            allowCustomer: false,
        };

       var onLoadRecord = function() {
            if ($scope.report.id) {
                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.report.id);
                var req = {
                    id: $scope.report.id
                };
                $http({
                    method: 'GET',
                    url: 'api/report',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.asesores.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta informaci�n.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Información no disponible", "Reporte no encontrada", "error");
                            $timeout(function () {
                                //$state.go('app.report.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del reporte", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.report = response.data.result;

                            if ($scope.report.requireFilter && !isCustomer) {
                                $scope.addFilter();
                            }

                            $scope.audit.fields = $scope.report.fields.map(function(field, index, array) {
                                return {alias: field.alias, value: field.name};
                            });
                        });
                    }).finally(function () {
                        $scope.loading = false;
                    });

            } else {
                $scope.loading = false;
            }
       }

       onLoadRecord();

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
                    SweetAlert.swal("El formulario contiene errores!", "Lo sentimos, este reporte requiere de al menos 1 filtro.", "error");

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

            if ($scope.report.requireFilter && !isCustomer) {
                var $filters = $scope.audit.filters.filter(function (filter) {
                    return filter != null && filter.field != null;
                });

                if ($filters.length == 0) {
                    ngNotify.set("Lo sentimos, este reporte requiere de al menos 1 filtro.", {
                        position: 'bottom',
                        sticky: true,
                        type: 'error',
                        button: true,
                        html: true
                    });
                    return;
                }
            }

            ngNotify.set('<div class="row"><div class="col-sm-5"><div class="loader-spinner pull-right"></div> </div> <div class="col-sm-6 text-left">El reporte se está generando. Por favor espere!</div> </div>', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var $criteria = {
                id: $scope.report.id,
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
                url: $exportUrl + 'api/v1/report-export',
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
    }
]);
