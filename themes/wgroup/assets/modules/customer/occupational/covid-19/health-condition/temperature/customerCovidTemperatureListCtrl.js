'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidTemperatureListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside', 'CustomerCovidService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside, CustomerCovidService) {

        var $formInstance = null;
        var currentId = CustomerCovidService.getDailyId();

        $scope.dateTimePickerConfig = {
            culture: "es-CO",
            format: "HH:mm"
        };

        var onInit = function() {
            $scope.entity = {
                id: 0,
                customerCovidId: currentId,
                temperature: null,
                pulse: null,
                oximetria: null,
                registrationDate: new Date(),
                observation: null,
                address: null,
                origin: "MANUAL",
                reload: false
            }

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }

        }
        onInit();

        $scope.onLoadRecord = function () {
            if ($scope.entity.id != 0) {
                var req = {
                    id: $scope.entity.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer-covid-temperature/get',
                    params: req
                })
                .catch(function (e, code) {})
                .then(function (response) {
                    $timeout(function () {
                        $scope.entity = response.data.result;
                        if ($scope.entity.registrationDate) {
                            $scope.entity.registrationDate = new Date($scope.entity.registrationDate.date);
                        }
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    });
                });
            } else {
                $scope.loading = false;
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
                    return true;
                }

            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        $scope.onSave = function (form) {

            if ($scope.entity.registrationDate == null ||
                $scope.entity.temperature == null ||
                $scope.entity.temperature === NaN ||
                $scope.entity.pulse == null ||
                $scope.entity.pulse === NaN ||
                $scope.entity.oximetria == null ||
                $scope.entity.oximetria === NaN) {
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;
            }

            var req = {};

            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-covid-temperature/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    if(response.data.result.reload) {
                        $scope.$emit('realoadQuestion');
                    }
                    $scope.onCancel();
                });
            }).catch(function (response) {
                $log.error(response);
                SweetAlert.swal("Error de guardado", "Error guardando el registro por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        };

        $scope.dtInstanceCustomerCovidTemperature = {};
		$scope.dtOptionsCustomerCovidTemperature = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
			.withOption('responsive', true)
            .withOption('ajax', {
				data: function (d) {
                    d.customerCovidId = currentId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-covid-temperature',
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

        $scope.dtColumnsCustomerCovidTemperature = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" tooltip="Eliminar registro" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if(!$scope.isView) {
                        actions += editTemplate;
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('hour').withTitle("Hora").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('temperature').withTitle("Temperatura").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('pulse').withTitle("Pulso").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('oximetria').withTitle("Oximetría").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observation').withTitle("Observacion").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRow = function () {

            angular.element("#dtCustomerCovidTemperature a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.entity.id = id;
                $scope.onLoadRecord();
            });

            $("#dtCustomerCovidTemperature a.delRow").on("click", function () {
                var id = $(this).data("id");
                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminará el registro seleccionado.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Si, eliminar!",
                        cancelButtonText: "No, continuar!",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function (isConfirm) {
                        if (isConfirm) {
                            var req = {};
                            req.id = id;
                            $http({
                                method: 'POST',
                                url: 'api/customer-covid-temperature/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
                                if(response.data.result) {
                                    $scope.$emit('realoadQuestion');
                                }
                            }).catch(function (response) {
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                            }).finally(function () {
                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelado", "Operacion cancelada", "error");
                        }
                    });
            });

        };

		$scope.dtInstanceCustomerCovidTemperatureCallback = function (instance) {
            $scope.dtInstanceCustomerCovidTemperature = instance;
        };

        $scope.reloadData = function () {
			if ($scope.dtInstanceCustomerCovidTemperature != null) {
				$scope.dtInstanceCustomerCovidTemperature.reloadData();
			}
        };

		$scope.onCancel = function () {
            $scope.reloadData();
            onInit();
        };

    }
]);
