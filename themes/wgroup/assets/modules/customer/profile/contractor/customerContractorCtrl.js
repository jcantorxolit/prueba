'use strict';
/**
 * controller for Customers
 */
app.controller('customerContractorCtrl', ['$scope', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'ListService',
    function ($scope, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, ListService) {

        var log = $log;

        $scope.currentUser = $rootScope.currentUser();
        $scope.isAgent = $scope.currentUser.wg_type == "agent";
        $scope.isAdmin = $scope.currentUser.wg_type == "system";
        $scope.isCustomerAdmin = $scope.currentUser.wg_type == "customerAdmin";
        $scope.isCustomerUser = $scope.currentUser.wg_type == "customerUser";

        $scope.canEdit = $scope.isCustomerAdmin || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
        $rootScope.canEditRoot = $scope.canEdit;

        $scope.customers = [];
        $scope.contractorTypeList = [];

        function getList() {
            var entities = [
                {name: 'contractor_customer', value: $stateParams.customerId},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.customers = response.data.data.contractorCustomer;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();

        $scope.$watchCollection("customer.contractorTypeList", function (newValue, oldValue, scope) {

            $scope.contractorTypeList = $scope.customer.contractorTypeList ? $filter('filter')($scope.customer.contractorTypeList, {isActive: true}, true) : [];

        });

        var init = function () {
            $scope.contractor = {
                id: 0,
                customerId: $stateParams.customerId,
                customer: null,
                contract: "",
                isActive: true,
                type: null,
            }
        };

        init();

         //Contractors
         $scope.onLoadRecord = function () {
            if ($scope.contractor.id != 0) {
                var req = {
                    id: $scope.contractor.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/contractor',
                    params: req
                })
                    .catch(function (response) {
                        if (response.status == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (response.status == 404) {
                            SweetAlert.swal("Información no disponible", "Aporte no encontrado", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del aporte", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.contractor = response.data.result;
                        });

                    }).finally(function () {

                        $timeout(function () {
                            $document.scrollTop(0, 2000);
                        });
                });
            }
        }

        $scope.master = $scope.contractor;
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
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    save();
                }

            },
            reset: function (form) {

                $scope.contractor = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {

            var validateMessage = '';

            if ($scope.contractor.customer == null || $scope.contractor.customer == '') {
                validateMessage += "Debe seleccionar el cliente / razón social\n";
            }

            if ($scope.contractor.type == null || $scope.contractor.type == '') {
                validateMessage += "Debe seleccionar el tipo de contratista \n";
            }

            if ($scope.contractor.contract == null || $scope.contractor.contract == '') {
                validateMessage += "Debe ingresar el número del contrato \n";
            }

            if (validateMessage != '') {
                SweetAlert.swal({
                    html: true,
                    title: "Error de validación",
                    text: validateMessage,
                    type: "error"
                });
                return;
            }

            var req = {};
            var data = JSON.stringify($scope.contractor);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/contractor/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.contractor = response.data.result;
                    SweetAlert.swal("Validación exitosa", "Guardando información del contratista...", "success");
                    $scope.reloadDataContractor();
                    $scope.clearContractor();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        $scope.cancelEdition = function (index) {
            if ($scope.isView) {
                $state.go('app.clientes.list');
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
                                $state.go('app.clientes.list');
                            });
                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            }
        };

        var request = { customer_id: $stateParams.customerId };

        $scope.dtInstanceContractor = {};
        $scope.dtOptionsContractor = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/contractor',
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

        $scope.dtColumnsContractor = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';
                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';


                    actions += editTemplate;

                    actions += deleteTemplate;

                    actions += viewTemplate;

                    return ($rootScope.can('cliente_contratista_manage') && !$scope.isView) ? actions : '';
                    //return actions;
                }),
            DTColumnBuilder.newColumn('customer.documentType.item').withTitle("Tipo de Documento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('customer.documentNumber').withTitle("Documento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('customer.businessName').withTitle("Razón Social").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type.value').withTitle("Tipo de Contratista").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('contract').withTitle("Contrato").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Inactivo';

                    if (data.isActive != null || data.isActive != undefined) {
                        if (data.isActive) {
                            label = 'label label-success';
                            text = 'Activo';
                        } else {
                            label = 'label label-danger';
                            text = 'Inactivo';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
        ];

        var loadRow = function () {

            angular.element("#dtCustomerProfileContractor a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onEdit(id);
            });

            angular.element("#dtCustomerProfileContractor a.delRow").on("click", function () {
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
                                url: 'api/customer/contractor/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataContractor();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.onEdit = function (id) {
            $scope.contractor.id = id;
            $scope.isView = false;
            $scope.onLoadRecord();
        };

        $scope.dtInstanceContractorCallback = function (instance) {
            $scope.dtInstanceContractor = instance;
        };

        $scope.reloadDataContractor = function () {
            $scope.dtInstanceContractor.reloadData();
        };

        $scope.clearContractor = function () {
            init();
            $scope.isView = false;
        };

    }]);
