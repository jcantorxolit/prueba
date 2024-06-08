'use strict';
/**
 * controller for Customers
 */
app.controller('customerResourceCtrl', ['$scope', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$document',
    function ($scope, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document) {

        var log = $log;

        $scope.currentUser = $rootScope.currentUser();
        $scope.isAgent = $scope.currentUser.wg_type == "agent";
        $scope.isAdmin = $scope.currentUser.wg_type == "system";
        $scope.isCustomerAdmin = $scope.currentUser.wg_type == "customerAdmin";
        $scope.isCustomerUser = $scope.currentUser.wg_type == "customerUser";

        $scope.canEdit = $scope.isCustomerAdmin || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
        $rootScope.canEditRoot = $scope.canEdit;

        $scope.isView = $state.is("app.clientes.view");
        $scope.isCreate = $state.is("app.clientes.create");


        $scope.onLoadRecord = function () {
            if ($scope.customer.id) {
                var req = {
                    id: $scope.customer.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer',
                    params: req
                })
                    .catch(function (response) {
                        if (response.status == 403) {
                            var messagered = 'app.clientes.list';
                            //var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (response.status == 404) {
                            SweetAlert.swal("Información no disponible", "Cliente no encontrado", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                        }
                    })
                    .then(function (response) {
                        $timeout(function () {
                            $scope.customer = response.data.result;

                            if ($scope.isCustomerAdmin) {
                                if ($scope.currentUser.company != $scope.customer.id) {
                                    $scope.canEdit = true;
                                } else {
                                    $scope.canEdit = true;
                                }
                            }

                            $rootScope.canEditRoot = $scope.canEdit;
                        });

                    }).finally(function () {

                    });
            }
        };

        var randomString = function (len) {
            var str = "";                                         // String result
            for (var i = 0; i < len; i++) {                             // Loop `len` times
                var rand = Math.floor(Math.random() * 62);        // random: 0..61
                var charCode = rand += rand > 9 ? (rand < 36 ? 55 : 61) : 48; // Get correct charCode
                str += String.fromCharCode(charCode);             // add Character to str
            }
            return str;       // After all loops are done, return the concatenated string
        }

        $scope.onInstall = function (plan) {

            var resource = {
                instanceId: randomString(20),
                planId: plan.id,
                customerId: $scope.customer.id,
                users: plan.featureList[0].min,
                contractors: plan.featureList[1].min,
                disk: plan.featureList[2].min,
                employees: plan.featureList[3].min,
            }

            var req = {};
            var data = JSON.stringify(resource);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/nephos-integration/install-customer',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.customer.resource = response.data.result;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                SweetAlert.swal("Instalación exitosa", "Se instaló correctamente el plan " + plan.name, "success");
            });
        };

        $scope.onViewHistorical = function () {
            var filter = {};
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_customer_resource_historical.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/resource/customer_resource_historical_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerResourceHistoricalCtrl',
                scope: $scope,
                resolve: {
                    filter: function () {
                        return filter;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        };

        $scope.onAddResource = function (type) {
            var filter = {
                type: type,
                resource: $scope.customer.resource,
            };

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_customer_resource_edit.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/resource/customer_resource_edit_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerResourceEditCtrl',
                scope: $scope,
                resolve: {
                    filter: function () {
                        return filter;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        };



    }]);

app.controller('ModalInstanceSideCustomerResourceHistoricalCtrl', function ($rootScope, $stateParams, $scope, filter, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var request = {};

    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    request.operation = "diagnostic";
    request.customer_id = $stateParams.customerId;
    request.data = "";

    $scope.dtInstanceCustomerHealthDamageDisabilityDiagnosticHistorical = {};
    $scope.dtOptionsCustomerHealthDamageDisabilityDiagnosticHistorical = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/nephos-integration',
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

    $scope.dtColumnsCustomerHealthDamageDisabilityDiagnosticHistorical = [
        DTColumnBuilder.newColumn('command').withTitle("Acción").withOption('width', 200).withOption('defaultContent', ''),

        DTColumnBuilder.newColumn('plan').withTitle("Plan").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('users').withTitle("Usuarios").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('contractors').withTitle("Contratistas").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('disk').withTitle("Almacenamiento").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('employees').withTitle("Empleados").withOption('width', 200).withOption('defaultContent', ''),
    ];

    var loadRow = function () {

    };

    $scope.reloadData = function () {
        $scope.dtInstanceCustomerHealthDamageDisabilityDiagnosticHistorical.reloadData();
    };
});

app.controller('ModalInstanceSideCustomerResourceEditCtrl', function ($rootScope, $stateParams, $scope, filter, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {


    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy hh:mm tt"
        //value: $scope.project.deliveryDate.date
    };

    $scope.current = filter;
    $scope.type = filter.type;

    if (filter.type == "U") {
        $scope.current.type = "Usuario"
        $scope.current.allowed = filter.resource.features.user.allowed;
        $scope.current.max = filter.resource.features.user.max;
        $scope.current.quantity = filter.resource.features.user.quantity;
    }

    if (filter.type == "C") {
        $scope.current.type = "Contratista"
        $scope.current.allowed = filter.resource.features.contractor.allowed;
        $scope.current.max = filter.resource.features.contractor.max;
        $scope.current.quantity = filter.resource.features.contractor.quantity;
    }

    if (filter.type == "E") {
        $scope.current.type = "Empleado"
        $scope.current.allowed = filter.resource.features.employee.allowed;
        $scope.current.max = filter.resource.features.employee.max;
        $scope.current.quantity = filter.resource.features.employee.quantity;
    }

    if (filter.type == "H") {
        $scope.current.type = "Espacio de almacenamiento"
        $scope.current.allowed = filter.resource.features.disk.allowed;
        $scope.current.max = filter.resource.features.disk.max;
        $scope.current.quantity = filter.resource.features.disk.quantity;
    }

    $scope.maxValue = parseFloat($scope.current.allowed) - parseFloat($scope.current.max);
    $scope.pending = parseFloat($scope.current.max) - parseFloat($scope.current.quantity);


    $scope.resource = {
        instanceId: filter.resource.instanceId,
        planId: filter.resource.plan.id,
        customerId: $scope.customer.id,
        users: 0,
        contractors: 0,
        disk: 0,
        employees: 0,
        type: filter.type,
    };

    $scope.onClose = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };


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
            $scope.resource = angular.copy($scope.resource);
            form.$setPristine(true);

        }
    };

    var save = function () {
        var req = {};

        var data = JSON.stringify($scope.resource);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/nephos-integration/configure-customer',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.onClose()
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            SweetAlert.swal("Configuración exitosa", "Se configuró correctamente el plan " + filter.resource.plan.name, "success");
        });
    };

});
