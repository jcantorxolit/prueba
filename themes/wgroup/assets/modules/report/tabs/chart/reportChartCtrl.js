'use strict';
/**
 * controller for Customers
 */
app.controller('reportChartCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar',
    '$filter', '$document',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document) {

        var log = $log;


        $scope.fields = [];

        $scope.reportChart = {
            id: 0,
            report: $scope.report,
            chartType: "",
            fieldX: null,
            fieldY: null,
        }

        $scope.clear = function(){
            $timeout(function () {
                $scope.reportChart = {
                    id: 0,
                    report: $scope.report,
                    chartType: "",
                    fieldX: {},
                    fieldY: {},
                }
            });
        }

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

                $scope.reportChart = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            $scope.reportChart.report = $scope.report;
            var data = JSON.stringify($scope.reportChart);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/report-chart/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.onLoadRecord();
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.onCancel = function () {
            if ($scope.isview) {
                $state.go('app.report.list');
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

        $scope.onLoadRecord = function()
        {
            if ($scope.reportChart.report.id != 0) {

                log.info("editando campo calculado : " + $scope.reportChart.id);
                var req = {
                    id: $scope.reportChart.id,
                    report_id: $scope.reportChart.report.id
                };
                $http({
                    method: 'POST',
                    url: 'api/report-chart',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                })
                    .catch(function(e, code){

                    })
                    .then(function (response) {

                        $timeout(function(){
                            $scope.fields = response.data.result
                            $scope.reportChart.fieldX = $filter('filter')($scope.fields, {axisType: "x"});;
                            $scope.reportChart.fieldY = $filter('filter')($scope.fields, {axisType: "y"});;
                        });

                    }).finally(function () {
                        $timeout(function(){
                            $scope.loading =  false;
                        }, 400);

                        $timeout(function () {
                            $document.scrollTop(40, 2000);
                        });
                    });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        }

        $scope.onLoadRecord();

        $scope.onAddField = function()
        {
            if ($scope.reportChart.field != null) {
                $scope.reportChart.expression = $scope.reportChart.expression + $scope.reportChart.field.table + "." + $scope.reportChart.field.name
            }
        }

    }]);



