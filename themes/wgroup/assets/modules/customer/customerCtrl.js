'use strict';
/**
  * controller for Customers
*/
app.controller('customerCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    '$compile', 'toaster', '$state', '$rootScope', 'SweetAlert', '$http', '$timeout', 'ListService', 'ngNotify', '$uibModal',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, $compile, toaster, $state,
        $rootScope, SweetAlert, $http, $timeout, ListService, ngNotify, $uibModal) {

        var log = $log;

        var isCustomer = $rootScope.isCustomer();
        var isAgent = $rootScope.isAgent();
        var isCustomerContractorAndEconomicGroup = false;

        var url = isAgent ? 'api/customer-agent' : 'api/customer';

        $scope.audit = {
            fields: [],
            filters: [],
        };

        function getList() {
            var entities = [
                { name: 'criteria_operators_productivity', value: null },
                { name: 'customer_custom_filter_field', value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.audit.fields = response.data.data.customerFilterField.filter(function (item) {
                        return isCustomerContractorAndEconomicGroup || (!isCustomerContractorAndEconomicGroup && item.name != 'economicGroup');
                    });
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.showDataTable = !isCustomer;

        var redirectTo = function (id) {
            $state.go("app.clientes.edit", { "customerId": id });
        }

        var buildDTColumns = function () {
            var $columns = [
                DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
                    .renderWith(function (data, type, full, meta) {
                        var actions = "";
                        var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '" >' +
                            '   <i class="fa fa-edit"></i></a> ';
                        var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                            '   <i class="fa fa-eye"></i></a> ';
                        var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                            '   <i class="fa fa-trash-o"></i></a> ';

                        var exportTemplate = ' | <a class="btn btn-success btn-xs exportRow lnk" href="#" uib-tooltip="Descargar anexos" data-id="' + data.id + '" >' +
                            '   <i class="fa fa-arrow-circle-o-down"></i></a> ';

                        if ($rootScope.can("clientes_view")) {
                            actions += viewTemplate;
                        }

                        if ($rootScope.can("clientes_edit")) {
                            actions += editTemplate;
                        }

                        if ($rootScope.can("clientes_delete")) {
                            actions += deleteTemplate;
                        }

                        if ($rootScope.can("clientes_documentos_export")) {
                            actions += exportTemplate;
                        }

                        return actions;
                    })
            ];

            $columns.push(buildDTColumn('documentType', 'Tipo de Documento', '', 200));
            $columns.push(buildDTColumn('documentNumber', 'Nro Documento', '', 200));
            $columns.push(buildDTColumn('businessName', 'Razón Social', '', 200));
            $columns.push(buildDTColumn('type', 'Tipo de Cliente', '', 200));
            $columns.push(buildDTColumn('classification', 'Clasificación', '', 200));
            if (isCustomerContractorAndEconomicGroup) {
                $columns.push(buildDTColumn('economicGroup', 'Grupo Económico', '', 200));
            }
            $columns.push(buildDTColumn('status', 'Estado', '', 200).renderWith(function (data, type, full, meta) {
                var label = '';
                switch (data) {
                    case "Activo":
                        label = 'label label-success';
                        break;

                    case "Inactivo":
                        label = 'label label-danger';
                        break;

                    case "Retirado":
                        label = 'label label-warning';
                        break;
                }

                var status = '<span class="' + label + '">' + data + '</span>';


                return status;
            })
            );

            return $columns;
        }

        var buildDTColumn = function (field, title, defaultContent, width) {
            return DTColumnBuilder.newColumn(field)
                .withTitle(title)
                .withOption('defaultContent', defaultContent)
                .withOption('width', width);
        };

        var initializeDatatable = function () {
            getList();

            $scope.dtOptionsCustomer = DTOptionsBuilder.newOptions()
                // Add Bootstrap compatibility
                .withBootstrap()
                .withOption('responsive', true)
                .withOption('ajax', {
                    // Either you specify the AjaxDataProp here
                    data: function (d) {
                        d.customerId = isCustomer ? $rootScope.currentUser().company : null;

                        if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {

                            d.filter =
                            {
                                filters: $scope.audit.filters.filter(function (filter) {
                                    return filter != null && filter.field != null && filter.criteria != null;
                                }).map(function (filter, index, array) {
                                    return {
                                        field: filter.field.name,
                                        operator: filter.criteria.value,
                                        value: filter.value,
                                        condition: { value: 'and' }
                                    };
                                })
                            };
                        }

                        return JSON.stringify(d);
                    },
                    url: url,
                    contentType: 'application/json',
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
                    return true;
                })
                .withOption('fnDrawCallback', function () {
                    loadRow();
                })
                .withPaginationType('full_numbers')
                .withOption('createdRow', function (row, data, dataIndex) {
                    // Recompiling so we can bind Angular directive to the DT
                    $compile(angular.element(row).contents())($scope);
                });
            ;

            $scope.dtColumnsCustomer = buildDTColumns();
        }

        var loadRow = function () {

            angular.element("#dtCustomer a.editRow").on("click", function () {
                var id = angular.element(this).data("id");

                $state.go("app.clientes.edit", { "customerId": id });
            });

            angular.element("#dtCustomer a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");

                $state.go("app.clientes.view", { "customerId": id });
            });

            angular.element("#dtCustomer a.exportRow").on("click", function () {                
                var id = angular.element(this).data("id");
                var modalInstance = $uibModal.open({
                    templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/document_filter_modal.htm',
                    controller: 'ModalInstanceSideCustomerDownloadAttachmentCtrl',
                    windowTopClass: 'top-modal',
                    resolve: {
                        customerId: function () {
                            return id;
                        },
                        title: function () {
                            return "Descargar anexos de cliente"
                        }                        
                    }
                });
                modalInstance.result.then(function () {

                }, function () {

                });

            });



            angular.element("#dtCustomer a.delRow").on("click", function () {
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
                                url: 'api/customer/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });

            });
        };

        $scope.dtInstanceCustomerCallback = function (instance) {
            $scope.dtInstanceCustomer = instance;
        }

        $scope.reloadData = function () {
            $scope.dtInstanceCustomer.DataTable.ajax.url(url);
            $scope.dtInstanceCustomer.reloadData();
        }

        $scope.createCustomer = function () {
            $state.go("app.clientes.create");
        };

        //-----------------------------------------------------FILTERS
        $scope.addFilter = function () {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }
            $scope.audit.filters.push(
                {
                    id: 0,
                    field: null,
                    criteria: null,
                    condition: null,
                    value: ""
                }
            );
        };

        $scope.addFilter();

        $scope.onFilter = function () {
            $scope.reloadData();
        }

        $scope.removeFilter = function (index) {
            $scope.audit.filters.splice(index, 1);
        }

        $scope.onCleanFilter = function () {
            $scope.audit.filters = [];
            $scope.reloadData();
        }

        if (isCustomer) {

            if ($rootScope.currentUser().company) {

                var req = {
                    id: $rootScope.currentUser().company
                };
                $http({
                    method: 'GET',
                    url: 'api/customer',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                //$state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
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
                            if (response.data.result.classification && response.data.result.classification.value == "Contratante") {
                                if (response.data.result.hasEconomicGroup) {
                                    url = 'api/customer-contractor-economic-group';
                                    isCustomerContractorAndEconomicGroup = true;
                                    initializeDatatable();
                                    $scope.showDataTable = true;
                                } else {
                                    url = 'api/customer-contractor';
                                    initializeDatatable();
                                    $scope.showDataTable = true;
                                }
                            } else if (response.data.result.hasEconomicGroup) {
                                url = 'api/customer-economic-group';
                                initializeDatatable();
                                $scope.showDataTable = true;
                            } else {
                                redirectTo($rootScope.currentUser().company);
                            }
                        });

                    }).finally(function () {

                    }
                    );
            }
        } else {
            initializeDatatable();
        }

    }
]);

app.controller('ModalInstanceSideCustomerDownloadAttachmentCtrl', function ($rootScope, $stateParams, $scope, $uibModal, $uibModalInstance, customerId, title, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile, ListService, ngNotify) {

    var log = $log;

    $scope.loading = true;
    $scope.title = title;

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onSelectYear = function () {
        getList();
    };

    var init = function () {
        $scope.filter = {
            id: customerId           
        }
    }

    init();

    getList();

    function getList() {

        var $year = $scope.filter.year ? $scope.filter.year.value : null;

        var entities = [
            { name: 'customer_document_type', value: customerId },
            { name: 'customer_document_periods', value: customerId, year:  $year}
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.documentType = response.data.data.customerDocumentType;
                $scope.years = response.data.data.customerDocumentPeriod.years;
                $scope.months = response.data.data.customerDocumentPeriod.months;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
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

                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {

                if (!$scope.filter.type && !$scope.filter.year && !$scope.filter.month) {
                    SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione al menos un filtro e intentelo nuevamente.", "error");
                    return;
                }

                onFIlter();
            }

        },
        reset: function (form) {

        }
    };

    var onFIlter = function () {

        ngNotify.set('El archivo se está generando.', {
            position: 'bottom',
            sticky: true,
            button: false,
            html: true
        });

        var entity = {
            customerId: $scope.filter.id,
            audit: $scope.filter
        };

        var req = {};
        var data = JSON.stringify(entity);
        req.data = Base64.encode(data);

        $http({
            method: 'POST',
            url: 'api/customer-document/export',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            var $url = response.data.path + response.data.filename;
            var $link = '<div class="row"><div class="col-sm-12 text-center">Por favor espere y verifique su correo y la bandeja de mensajes!</div> </div>';
            ngNotify.set($link, {
                position: 'bottom',
                sticky: true,
                type: 'success',
                button: true,
                html: true
            });
            $scope.onCloseModal();
        }).catch(function (response) {
            ngNotify.set(response.data.message, {
                position: 'bottom',
                sticky: true,
                type: 'error',
                button: true,
                html: true
            });
        }).finally(function () {

        });

    };

});