'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerPollEditCtrl', ['$scope', '$stateParams', '$log', 'toaster',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    function ($scope, $stateParams, $log, toaster, $compile, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter) {

        var log = $log;

        var request = {};
        var pollId = $scope.$parent.currentPoll;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        log.info("loading..customerPollEditCtrl con el id de poll: ", pollId);

        // parametros para encuesta
        $scope.isView = $scope.$parent.modeDsp == "view";
        $scope.customerId = $stateParams.customerId;
        $scope.answerCount = 0;
        log.info($scope.isView);

        // parametros para alert
        $scope.booleanAnswers = [
            {
                id: "0",
                item: "Si",
                value: "1"
            }, {
                id: "1",
                item: "No",
                value: "0"
            }
        ];

        $scope.customerPoll = {
            id: 0,
            poll: null
        };

        $scope.cancelEdition = function () {
            if ($scope.isView) {
                if ($scope.$parent != null) {
                    $scope.$parent.navToSection("list", "list");
                }
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
                                if ($scope.$parent != null) {
                                    $scope.$parent.navToSection("list", "list");
                                }
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        var onLoadRecord = function (pollId) {
            // se debe cargar primero la información actual del cliente..
            var req = {
                id: pollId
            };

            $http({
                method: 'GET',
                url: 'api/customer/poll',
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
                        SweetAlert.swal("Información no disponible", "Seguimiento no encontrado", "error");
                        $timeout(function () {
                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del seguimiento", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.customerPoll = response.data.result;
                        $scope.answerCount = $scope.customerPoll.answerCount;
                    });

                }).finally(function () {
                    $timeout(function () {
                        afterInit();
                        $scope.loading = false;
                    }, 400);
                });


        };


        $timeout(function () {
            afterInit();
        }, 10);

        var afterInit = function () {

        };

        if (pollId != 0) {
            onLoadRecord(pollId);
        }

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
                    log.info($scope.customer);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                    //your code for submit
                    log.info($scope.customer);
                    save();
                }

            },
            reset: function (form) {

                $scope.poll = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            angular.forEach($scope.customerPoll.poll.questions, function(value, key) {
                $scope.savePartial(value);
            });
        };

        $scope.onSendPoll = function (question) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Luego de enviar la encuesta no se podra modificar.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, enviar!",
                    cancelButtonText: "No, cancelar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        $timeout(function () {

                            var req = {};

                            var data = JSON.stringify($scope.customerPoll);
                            req.data = Base64.encode(data);

                            return $http({
                                method: 'POST',
                                url: 'api/customer/poll/send',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {

                                $timeout(function () {
                                    $scope.$parent.navToSection("list", "list");
                                });

                            }).catch(function (e) {
                                $log.error(e);
                                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
                            }).finally(function () {

                            });

                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });


        }

        $scope.savePartial = function (question) {
            var req = {};

            var answer = {
                customerPollId: $scope.customerPoll.id,
                question: question,
                isActive: 1
            };

            var data = JSON.stringify(answer);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer/poll/answer/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                // recargamos información de los paneles
                //loadData($scope.currentProgramId, true, false);
                question.answerId = response.data.result.id;
                $scope.answerCount = response.data.result.poll.answerCount;
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Actualización exitosa.');
                });

            }).catch(function (e) {
                $log.error(e);
                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
            }).finally(function () {

            });
        }


    }]);
