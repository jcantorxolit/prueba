'use strict';
/**
 * controller for Customers
 */
app.controller('pollEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter) {

        var log = $log;

        $scope.loading = true;
        $scope.isView = $state.is("app.poll.view");
        $scope.isCreate = $state.is("app.poll.create");
        $scope.format = 'dd-MM-yyyy';
        $scope.minDate = new Date() - 1;

        $scope.customers = [];
        $scope.collections = [];
        $scope.collectionsReport = [];

        $scope.poll = {
            id: $scope.isCreate ? 0 : $stateParams.id,
            collection: null,
            name: "",
            description: "",
            isActive: true,
            startDateTime: new Date(),
            endDateTime: new Date(),
        };

        // Preparamos los parametros por grupo
        $scope.openStart = function($event) {
            $event.preventDefault();
            $event.stopPropagation();

            $scope.openedStart = true;
        };

        $scope.openEnd = function($event) {
            $event.preventDefault();
            $event.stopPropagation();

            $scope.openedEnd = true;
        };

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };


        if ($scope.poll.id) {
            // se debe cargar primero la información actual del cliente..
            log.info("editando cliente con código: " + $scope.poll.id);
            var req = {
                id: $scope.poll.id
            };
            $http({
                method: 'GET',
                url: 'api/poll',
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
                        SweetAlert.swal("Información no disponible", "Encuesta no encontrada", "error");
                        $timeout(function () {
                            //$state.go('app.poll.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                    }
                })
                .then(function (response) {
                    console.log(response);

                    $timeout(function () {
                        $scope.poll = response.data.result;
                        $scope.poll.startDateTime = new Date($scope.poll.startDateTime);
                        $scope.poll.endDateTime = new Date($scope.poll.endDateTime);
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

        $scope.master = $scope.poll;
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
                    log.info($scope.poll);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información de la encuesta...", "success");
                    //your code for submit
                    log.info($scope.poll);
                    save();
                }

            },
            reset: function (form) {

                $scope.poll = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.poll);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/poll/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $state.go("app.poll.edit", {"id":response.data.result.id});
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.onCancel = function () {
            if ($scope.isview) {
                //$state.go('app.poll.list');
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
                                $state.go('app.poll.list');
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

            req.poll_id = $stateParams.id ? $stateParams.id : 0;

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
                        $scope.collectionsReport = $filter('filter')($scope.collections, {type: "quote"});
                        //$scope.collectionsChart = $filter('filter')($scope.collections, {type: "chart"});
                    });

                }).finally(function () {

                });
        }

        afterInit();

        $scope.onExport = function()
        {
            jQuery("#download")[0].src = "api/poll/export?id=" + $stateParams.id;
        }

        var request = {};
        request.operation = "poll-summary";
        request.id = $stateParams.id;

        $scope.dtPollResultOptions = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/poll/summary',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                //loadRow();
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
        ;

        $scope.dtPollResultColumns = [
            DTColumnBuilder.newColumn('name').withTitle("Razón Social").withOption('width'),
            DTColumnBuilder.newColumn('questions').withTitle("Preguntas").withOption('width', 200),
            DTColumnBuilder.newColumn('answers').withTitle("Respuestas").withOption('width', 200),
            DTColumnBuilder.newColumn('avance').withTitle("% Avance").withOption('width', 200),
            DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200),
        ];


        // Propiedades de la grafica de avance
        $scope.data_sg = [];

        // Chart.js Options
        $scope.options_sg = {

            // Sets the chart to be responsive
            responsive: true,

            //Boolean - Whether we should show a stroke on each segment
            segmentShowStroke: true,

            //String - The colour of each segment stroke
            segmentStrokeColor: '#fff',

            //Number - The width of each segment stroke
            segmentStrokeWidth: 2,

            //Number - The percentage of the chart that we cut out of the middle
            percentageInnerCutout: 50, // This is 0 for Pie charts

            //Number - Amount of animation steps
            animationSteps: 100,

            //String - Animation easing effect
            animationEasing: 'easeOutBounce',

            //Boolean - Whether we animate the rotation of the Doughnut
            animateRotate: true,

            //Boolean - Whether we animate scaling the Doughnut from the centre
            animateScale: false,

            //String - A legend template
            legendTemplate: '<ul class="tc-chart-js-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>'

        };
        $scope.totalAvg = 0;

        var loadReports = function () {

            var request = {};
            request.operation = "poll-dashboard";
            request.id = $scope.isCreate ? 0 : $stateParams.id;

            $http({
                method: 'POST',
                url: 'api/poll/dashboard',
                data: request,
            }).then(function (response) {

                $timeout(function () {

                    var colors = ["#46BFBD", "#e0d653", "#F7464A", "#46BFBD"];
                    var hcolors = ["#5AD3D1", "#FF5A5E", "#FBF25A", "5AD3D1"];

                    $scope.data_sg = response.data.result.pie;

                    var index = 0;
                    $.each($scope.data_sg, function (k, v) {
                        v.color = colors[index];
                        v.highlight = colors[index];
                        v.value = parseFloat(v.value);
                        index++;
                    });

                    $scope.totalAvg = response.data.result.totalAvg;
                });

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });

        };

        loadReports();

    }]);



