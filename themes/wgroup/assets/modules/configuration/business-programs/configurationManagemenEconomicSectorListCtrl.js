'use strict';
/**
 * controller for Customers
 */
app.controller('configurationManagemenEconomicSectorListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside, ListService) {

        var $formInstance = null;

        var getList = function() {
            var entities = [
                { name: 'economic_sector',  value: null },
                { name: 'management_program',  value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.economicSectorList = response.data.data.economicSectorList;
                    $scope.programList = response.data.data.managementProgramList;
                }, function (error) {

                });
        }

        getList();

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";


		var onInit = function() {
			$scope.entity = {
                id: 0,
				program: null,
                economicSector: null,
                isActive: true,
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
                    save();
                }

            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        var save = function () {
            var req = {};

            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/program-management-economic-sector/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
					SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $scope.onCancel();
                    $scope.reloadData();
                });
            }).catch(function (error) {
                if (error.status == 400) {
                    SweetAlert.swal("Error de guardado", error.data.message, "error");
                } else {
                    SweetAlert.swal("Error de guardado", "Error guardando el registro por favor verifique los datos ingresados!", "error");
                }

            }).finally(function () {
            });
        };

        $scope.onCancel = function () {
           onInit();
        };

        $scope.onRefresh = function () {
            getList();
         };

        $scope.dtInstanceProgramManagementEconomicSector = {};
		$scope.dtOptionsProgramManagementEconomicSector = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
			.withOption('responsive', true)
            .withOption('ajax', {
				data: function (d) {
                    return JSON.stringify(d);
                },
                url: 'api/program-management-economic-sector',
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

        $scope.dtColumnsProgramManagementEconomicSector = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    actions += deleteTemplate;

                    return actions;
                }),
            DTColumnBuilder.newColumn('economicSector').withTitle("Sector Económico").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('program').withTitle("Programa Empresarial").withOption('width', 500).withOption('defaultContent', '')
        ];

        var loadRow = function () {

            angular.element("#dtProgramManagementEconomicSector a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onEdit(id);
            });

            angular.element("#dtProgramManagementEconomicSector a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onView(id);
            });

            angular.element("#dtProgramManagementEconomicSector a.delRow").on("click", function () {
                var id = angular.element(this).data("id");

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
                                url: 'api/program-management-economic-sector/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (data) {
                                swal("Eliminado", "Regitro eliminado satisfactoriamente", "info");
                            }).catch(function (response) {
                                if (response.status == 400) {
                                    SweetAlert.swal("Error de integridad", response.data.message, "error");
                                } else {
                                    SweetAlert.swal("Error", "Se ha presentado un error inesperado intentando eliminar el registro por favor intente nuevamente!", "error");
                                }
                            }).finally(function () {
                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelado", "Operacion cancelada", "error");
                        }
                    });
            });

        };

		$scope.dtInstanceProgramManagementEconomicSectorCallback = function (instance) {
            $scope.dtInstanceProgramManagementEconomicSector = instance;
        };

        $scope.reloadData = function () {
			if ($scope.dtInstanceProgramManagementEconomicSector != null) {
				$scope.dtInstanceProgramManagementEconomicSector.reloadData();
			}
        };

    }
]);
