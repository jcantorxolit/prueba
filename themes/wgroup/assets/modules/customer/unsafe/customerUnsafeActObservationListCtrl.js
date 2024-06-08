'use strict';
/**
 * controller for Customers
 */
app.controller('customerUnsafeActObservationListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside', '$q', '$filter',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside, $q, $filter) {

        var $formInstance = null;

        $scope.currentId = $scope.$parent.currentId ? $scope.$parent.currentId : 0;
        $scope.isView = $scope.$parent.action == "view";

        $scope.statusList = $rootScope.parameters("customer_unsafe_act_status").map(function(app) {
            if(app.item == "Completado" || app.item == "Cancelado") {
                return app;
            }
        });

        var onDestroyCustomerUnsafeActLoaded$ = $rootScope.$on('onCustomerUnsafeActLoaded', function (event, args) {
            $scope.currentId = args.newValue;
            onInit();
            $scope.reloadData();
        });

        $scope.$on("$destroy", function() {
            onDestroyCustomerUnsafeActLoaded$();
        });

        var onInit = function () {
            $scope.observation = {
                id: 0,
                customerUnsafeActId: $scope.currentId,
                status: null,
                dateOf: new Date(),
                description: '',
            };

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        };

        onInit();

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
                    save();
                }

            },
            reset: function (form) {
                $scope.clear();
            }
        };

        var save = function () {

            if ($scope.observation.status == null) {
                SweetAlert.swal("Error en el formulario", "Por favor seleccione el estado", "error");
                return false;
            }

            if ($scope.observation.dateOf == null) {
                SweetAlert.swal("Error en el formulario", "Por favor seleccione la fecha", "error");
                return false;
            }

            var req = {};
            var data = JSON.stringify($scope.observation);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/unsafe-act-observation/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    SweetAlert.swal("Validación exitosa", "Registro guardado correctamente.", "success");
                    $scope.onCancel();
                }, 500);
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });

        };

        $scope.dtInstanceCustomerUnsafeActObservation = {};
        $scope.dtOptionsCustomerUnsafeActObservation = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerUnsafeActId = $scope.currentId;
                    return d;
                },
                url: 'api/customer/unsafe-act-observation',
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
                loadRow();
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

        $scope.dtColumnsCustomerUnsafeActObservation = [


            //DTColumnBuilder.newColumn('type').withTitle("Asesor").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('dateOfFormat').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('creator.name').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Observación").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('status.item').withTitle("Estado").withOption('width', 200).withOption('defaultContent', '')
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch (data) {
                        case "Creado":
                            label = 'label label-success';
                            break;

                        case "Revisado":
                            label = 'label label-warning';
                            break;

                        case "Completado":
                            label = 'label label-info';
                            break;

                        case "Cancelado":
                            label = 'label label-danger';
                            break;
                    }

                    var status = '<span class="' + label + '">' + data + '</span>';


                    return status;
                })

        ];

        var loadRow = function () {
        };

        $scope.dtInstanceCustomerUnsafeActObservationCallback = function (instance) {
            $scope.dtInstanceCustomerUnsafeActObservation = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerUnsafeActObservation.reloadData();
        };


        $scope.onCancel = function (form) {
            if (form) {
                $formInstance = form;
            }
            onInit();
            $scope.reloadData();
        };


    }]);
