'use strict';
/**
 * controller for Customers
 */
app.controller('customerCertificateProgramCtrl', ['$scope', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
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


        function getList() {
            var entities = [
                {name: 'certificate_program'},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.programs = response.data.data.certificateProgram;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();

        var init = function() {
            $scope.certificate = {
                id: 0,
                customerId: $stateParams.customerId,
                program: null,
                amount: 0,
                isActive: true
            }
        }

        init();


        $scope.isView = $state.is("app.clientes.view");
        $scope.isCreate = $state.is("app.clientes.create");


        $scope.onLoadRecord = function () {
            if ($scope.certificate.id != 0) {
                var req = {
                    id: $scope.certificate.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/certificate-program',
                    params: req
                })
                    .catch(function (response) {
                        if (response.status == 403) {
                            var messagered = response.data.message !== null && response.data.message !== undefined ? response.data.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
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
                            $scope.certificate = response.data.result;
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $document.scrollTop(0, 2000);
                        });
                    });
            }
        }

        $scope.master = $scope.certificate;
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

                $scope.certificate = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {

            if ($scope.certificate.program == null || $scope.certificate.amount == 0) {
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;
            }

            var req = {};
            var data = JSON.stringify($scope.certificate);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/certificate-program/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.certificate = response.data.result;
                    SweetAlert.swal("Validación exitosa", "Guardando información del aporte...", "success");
                    $scope.reloadDataCertificate();
                    $scope.clearCertificateProgram();
                });
            }).catch(function (e) {
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
                        text: "Se perderan todos los cambios realizados en esta vista.",
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


        var request = { customer_id: $scope.customer.id };

        $scope.dtInstanceCertificateProgram = {};
        $scope.dtOptionsCertificateProgram = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/certificate-program',
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

        $scope.dtColumnsCertificateProgram = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_delete")) {
                        actions += deleteTemplate;
                    }

                    return (($scope.isAdmin || $scope.isAgent) && !$scope.isView) ? actions : '';
                }),
            DTColumnBuilder.newColumn('program.name').withTitle("Programa").withOption('width', 200),
            DTColumnBuilder.newColumn('program.amount').withTitle("Valor Programa").withOption('width', 200),
            DTColumnBuilder.newColumn('program.currency.item').withTitle("Moneda").withOption('width', 200),
            DTColumnBuilder.newColumn('amount').withTitle("Valor Modificado").withOption('width', 200),
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

            angular.element("#dtCustomerCertificateProgram a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onEdit(id);
            });

            angular.element("#dtCustomerCertificateProgram a.delRow").on("click", function () {
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
                                url: 'api/customer/certificate-program/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadDataCertificate();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.onEdit = function (id) {
            $scope.certificate.id = id;
            $scope.isView = false;
            $scope.onLoadRecord();
        };

        $scope.dtInstanceCertificateProgramCallback = function (instance) {
            $scope.dtInstanceCertificateProgram = instance;
        };

        $scope.reloadDataCertificate = function () {
            $scope.dtInstanceCertificateProgram.reloadData();
        };

        $scope.clearCertificateProgram = function () {
           init();
        };

    }]);
