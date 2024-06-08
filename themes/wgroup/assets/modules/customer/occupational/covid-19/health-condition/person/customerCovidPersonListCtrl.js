'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidPersonListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside', 'CustomerCovidService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside, CustomerCovidService) {

        var $formInstance = null;
        var currentId = CustomerCovidService.getDailyId();

        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        var onInit = function() {
            $scope.entity = {
                id: 0,
                customerCovidId: currentId,
                place: null,
                person: null,
                registrationDate: new Date(),
            }

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        }

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
                    return true;
                }

            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        $scope.onSave = function () {

            if ($scope.entity.registrationDate == null ||
                $scope.entity.person == null ||
                $scope.entity.place == null) {
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;
            }

            var req = {};

            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-covid-person-in-touch/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
					SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $scope.onCancel();
                });
            }).catch(function (response) {
                $log.error(response);
                SweetAlert.swal("Error de guardado", "Error guardando el registro por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        };

        $scope.dtInstanceCustomerCovidPersonInTouch = {};
		$scope.dtOptionsCustomerCovidPersonInTouch = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
			.withOption('responsive', true)
            .withOption('ajax', {
				data: function (d) {
                    d.customerCovidId = currentId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-covid-person-in-touch',
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

        $scope.dtColumnsCustomerCovidPersonInTouch = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 100).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" tooltip="Eliminar registro" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if(!$scope.isView) {
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('place').withTitle("Lugar").withOption('width', 250).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('person').withTitle("Persona").withOption('width', 350).withOption('defaultContent', ''),
        ];

        var loadRow = function () {
            $("#dtCustomerCovidPersonInTouch a.delRow").on("click", function () {
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
                                url: 'api/customer-covid-person-in-touch/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (data) {
                                swal("Eliminado", "Regitro eliminado satisfactoriamente", "info");
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

		$scope.dtInstanceCustomerCovidPersonInTouchCallback = function (instance) {
            $scope.dtInstanceCustomerCovidPersonInTouch = instance;
        };

        $scope.reloadData = function () {
			if ($scope.dtInstanceCustomerCovidPersonInTouch != null) {
				$scope.dtInstanceCustomerCovidPersonInTouch.reloadData();
			}
        };

		$scope.onCancel = function () {
            onInit();
            $scope.reloadData();
        };

    }
]);
