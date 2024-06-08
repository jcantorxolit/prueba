'use strict';
/**
 * controller for Customers
 */
app.controller('reportEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter) {

        var log = $log;
        var request = {};



        $scope.loading = true;
        $scope.isview = $state.is("app.report.view");
        $scope.isCreate = $state.is("app.report.create");
        $scope.format = 'dd-MM-yyyy';
        $scope.minDate = new Date() - 1;

        $scope.customers = [];
        $scope.collections = [];
        $scope.collectionsReport = [];
        $scope.collectionsChart = [];

        $scope.report = {
            id: $scope.isCreate ? 0 : $stateParams.reportId,
            collection: null,
            collectionChart: null,
            fields: [],
            name: "",
            description: "",
            isActive: true,
            allowAgent: false,
            allowCustomer: false,
            isQueue: false,
            requireFilter: false,
        };

        // Preparamos los parametros por grupo
        $scope.statusQuote = $rootScope.parameters("report_status");

        $scope.open = function($event) {
            $event.preventDefault();
            $event.stopPropagation();

            $scope.opened = true;
        };

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

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
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Cotización no encontrada", "error");
                        $timeout(function () {
                            //$state.go('app.report.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                    }
                })
                .then(function (response) {
                    console.log(response);

                    $timeout(function () {
                        $scope.report = response.data.result;

                        if ($scope.report.collectionChart.id == undefined) {
                            $scope.report.collectionChart = null;
                        }
                    });
                }).finally(function () {
                    $timeout(function () {
                        afterInit();
                        $scope.loading = false;
                    }, 400);
                });


        } else {
            //Se creara nuevo cliente
            log.info("creacion de nuevo asesor ");
            $scope.loading = false;
        }


        var errorMessage = function (i) {
            toaster.pop('error', 'Error', 'Por favor diligencie los campos requeridos en este paso, antes de continuar al siguiente nivel.');
        };

        $scope.removeImage = function () {
            $scope.noImage = true;
        };

        $scope.master = $scope.report;
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
                    SweetAlert.swal("Validación exitosa", "Guardando información del asesor...", "success");
                    //your code for submit
                    log.info($scope.report);
                    save();
                }

            },
            reset: function (form) {

                $scope.report = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.report);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/report/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $state.go("app.report.edit", {"reportId": response.data.result.id});
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.onCancel = function () {
            if ($scope.isview) {
                //$state.go('app.report.list');
            } else {
                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Perderá todos los cambios realizados en este formulario.",
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

        var afterInit = function()
        {
            var req = {};

            req.report_id = $stateParams.reportId ? $stateParams.reportId : 0;

            $http({
                method: 'POST',
                url: 'api/collection-data',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.collections = response.data.data;
                        $scope.collectionsReport = $filter('filter')($scope.collections, {type: "report"});
                        $scope.collectionsChart = $filter('filter')($scope.collections, {type: "chart"});
                    });

                }).finally(function () {

                });
        }

        afterInit();

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


    }]);



