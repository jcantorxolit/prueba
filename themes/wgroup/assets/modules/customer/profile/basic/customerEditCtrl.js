'use strict';
/**
 * controller for Customers
 */
app.controller('customerEditCtrl', ['$scope', '$aside', '$stateParams', '$log', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout',
    '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$document', 'SupportService',
    function ($scope, $aside, $stateParams, $log, $state,
        SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, SupportService) {

        var log = $log;

        $scope.currentUser = $rootScope.currentUser();
        $scope.isAgent = $scope.currentUser.wg_type == "agent";
        $scope.isAdmin = $scope.currentUser.wg_type == "system";
        $scope.isCustomerAdmin = $scope.currentUser.wg_type == "customerAdmin";
        $scope.isCustomerUser = $scope.currentUser.wg_type == "customerUser";
        $scope.showContract = false;


        $scope.canEdit = $scope.isCustomerAdmin || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
        $rootScope.canEditRoot = $scope.canEdit;

        $scope.flowConfig = { target: '/api/upload', singleFile: true };
        $scope.loading = true;
        $scope.isView = $state.is("app.clientes.view");
        $scope.isCreate = $state.is("app.clientes.create");
        $scope.currentYear = {
            id: "0",
            item: "-- Seleccionar --",
            value: new Date().getFullYear()
        };

        $scope.dateConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
            //value: $scope.project.deliveryDate.date
        };

        var init = function () {
            $scope.customer = {
                id: $scope.isCreate ? 0 : $stateParams.customerId,
                logo: "",
                classification: null,
                hasEconomicGroup: false,
                contacts: [
                    {
                        id: 0,
                        value: "",
                        type: null
                    }
                ],
                maincontacts: [
                    {
                        id: 0,
                        name: "",
                        firstname: "",
                        lastname: "",
                        value: "",
                        info: [],
                        role: null
                    }
                ],
                type: null,
                documentType: null,
                status: null,
                arl: null,
                country: null,
                state: null,
                town: null,
                group: null,
                size: null,
                temporalCompany: "",
                temporaryEmployees: 0,
                contractNumber: 0,
                contractStartDate: null,
                contractEndDate: null,
                totalEmployee: null,
                riskLevel: null,
                riskClass: null,
            };
        }

        init();

        $scope.onSelectClasification = function () {
            if ($scope.customer.classification) {
                $scope.showContract = $scope.customer.classification.value == 'Contratista';
                $rootScope.$emit('hasClassification', { newValue: $scope.customer.classification.value, message: 'hasClassification Emit' });
            }
        }

        $scope.changeEconomicGroup = function () {
            $rootScope.$emit('hasEconomicGroup', { newValue: $scope.customer.hasEconomicGroup, message: 'hasEconomicGroup Emit' });
        }

        // Preparamos los parametros por grupos
        $scope.tiposCliente = $rootScope.parameters("tipocliente");
        $scope.estados = $rootScope.parameters("estado");
        $scope.classifications = $rootScope.parameters("customer_classification");
        $scope.arls = $rootScope.parameters("arl");
        $scope.tiposdoc = $rootScope.parameters("tipodoc");
        $scope.sizes = $rootScope.parameters("wg_customer_size");
        $scope.totalEmployeeList = $rootScope.parameters("wg_customer_employee_number");
        $scope.riskLevelList = $rootScope.parameters("wg_customer_risk_level");
        $scope.riskClassList = $rootScope.parameters("wg_customer_risk_class");

        $scope.countries = $rootScope.countries();
        $scope.temporaryAgencies = $rootScope.temporaryAgencies();
        $scope.groups = $rootScope.groups();
        $scope.states = [];
        $scope.towns = [];

        $scope.uploader = new Flow();

        if ($scope.customer.logo == '') {
            $scope.noImage = true;
        }

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
                            $rootScope.attentionLines = $scope.customer.attentionLines;

                            $scope.changeEconomicGroup();

                            if ($scope.isCustomerAdmin) {
                                if ($scope.currentUser.company != $scope.customer.id) {
                                    $scope.canEdit = true;
                                } else {
                                    $scope.canEdit = true;
                                }
                            }

                            $rootScope.canEditRoot = $scope.canEdit;

                            if ($scope.customer.logo != null && $scope.customer.logo.path != null) {
                                $scope.noImage = false;
                            } else {
                                $scope.noImage = true;
                            }

                            var state = $scope.customer.state;
                            var town = $scope.customer.town;

                            $scope.changeCountry($scope.customer.country);
                            $scope.changeState(state);

                            $scope.customer.state = state;
                            $scope.customer.town = town;

                            $scope.onSelectClasification();
                            $scope.validateLicense($scope.customer.id);

                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);
                    });


            } else {
                $scope.loading = false;
            }
        };

        $scope.onLoadRecord();

        $scope.removeImage = function () {
            $scope.noImage = true;
        };

        $scope.master = $scope.customer;
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
                    save();
                }

            },
            reset: function (form) {

                $scope.customer = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            if ($scope.customer.documentType) {
                if ($scope.customer.documentType.code == "N" && isNaN($scope.customer.documentNumber)) {
                    SweetAlert.swal("El formulario contiene errores!", "El campo Número de Documento debe contener caracteres numéricos.", "error");
                    return;
                }

                var pattern = /^[a-zA-Z0-9]+$/;

                if ($scope.customer.documentType.code == "A" && !pattern.test($scope.customer.documentNumber)) {
                    SweetAlert.swal("El formulario contiene errores!", "El campo Número de Documento debe contener caracteres alfanuméricos.", "error");
                    return;
                }
            }

            var req = {};
            var data = JSON.stringify($scope.customer);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {

                SweetAlert.swal("Operación exitosa", "Información guardada satisfactoriamente", "success");

                log.info("uploader::", $scope.uploader);

                $scope.uploader.flow.opts.query.id = response.data.result.id;

                $scope.uploader.flow.resume();

                $timeout(function () {
                    $scope.customer = response.data.result;

                    if ($rootScope.app.supportHelp) {
                        var $hasTotalEmployee = $scope.customer.totalEmployee != null;
                        var $hasriskClass = $scope.customer.riskClass != null;
                        $rootScope.app.supportHelp.hasBasicInformation = $hasTotalEmployee && $hasriskClass;
                    }

                    if ($scope.isCreate) {
                        $state.go("app.clientes.edit", { "customerId": $scope.customer.id });
                    } else {
                        if ($scope.customer.logo != null && $scope.customer.logo.path != null) {
                            $scope.noImage = false;
                        } else {
                            $scope.noImage = true;
                        }
                    }
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.changeCountry = function (item, model) {

            $scope.states = [];
            $scope.towns = [];

            $scope.customer.state = null;
            $scope.customer.town = null;

            var req = {
                cid: item.id
            };

            $http({
                method: 'GET',
                url: 'api/states',
                params: req
            }).catch(function (e, code) {

            }).then(function (response) {
                $scope.states = response.data.result;
                $scope.towns = [];
            }).finally(function () {

            });
        };

        $scope.changeState = function (item, model) {
            $scope.towns = [];
            var req = {
                sid: item.id
            };

            $scope.customer.town = null;

            $http({
                method: 'GET',
                url: 'api/towns',
                params: req
            }).then(function (response) {
                $scope.towns = response.data.result;
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

        $scope.onSearchEconomicActivity = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/customer_serch_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerEconomicActivityListCtrl',
                scope: $scope
            });
            modalInstance.result.then(function (data) {
                $scope.customer.economicActivity = data;
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });

        };

        $scope.$watchCollection("customer.contractorTypeList", function (newValue, oldValue, scope) {

            $scope.contractorTypeList = $scope.customer.contractorTypeList ? $filter('filter')($scope.customer.contractorTypeList, { isActive: true }, true) : [];

        });

        $scope.onSelectRiskClass = function () {
            if ($scope.customer.riskClass) {
                if (parseInt($scope.customer.riskClass.value) > 3) {
                    var $risk = $filter('filter')($scope.riskLevelList, { value: '45' });
                } else {
                    var $risk = $filter('filter')($scope.riskLevelList, { value: '123' });
                }
                $scope.customer.riskLevel = $risk && $risk.length > 0 ? $risk[0] : null;
            }
        }


        $scope.validateLicense = function (customerId) {
            if ($scope.isCustomerAdmin) {
                var data = JSON.stringify({ customerId: customerId });
                var req = {
                    data: Base64.encode(data)
                };

                return $http({
                    method: 'POST',
                    url: 'api/customer-licenses/current-license/close-expire',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: $.param(req)
                }).then(function (response) {
                    if (response.data.result.closeExpire) {
                        SweetAlert.swal("Licencia pronto expira",
                            "La licencia está próxima a vencer, comuníquese con el asesor comercial para renovar la licencia.",
                            "info"
                        );
                    }

                }).catch(function (e) {
                    $log.error(e);
                    SweetAlert.swal("Error al guardar", e.data.message, "error");
                });
            }
        };

    }
]);


app.controller('ModalInstanceSideCustomerEconomicActivityListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'ACTIVIDADES ECONÓMICAS';

    $scope.activity = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.activity);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.activity.id != 0) {
            var req = {
                id: $scope.activity.id,
            };
            $http({
                method: 'GET',
                url: 'api/investigation-al/economic-activity',
                params: req
            })
                .catch(function (response) {
                    if (response.status == 403) {
                        var messagered = response.data.message !== null && response.data.message !== undefined ? response.data.message : 'app.clientes.list';
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (response.status == 404) {
                        SweetAlert.swal("Información no disponible", "Registro no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.activity = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.onCloseModal();
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }

    var request = {
        operation: "restriction",
        data: ""
    };

    $scope.dtInstanceDisabilityEmployeeList = {};
    $scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/investigation-al/economic-activity',
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

    $scope.dtColumnsDisabilityEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar actividad"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('code').withTitle("Código").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('name').withTitle("Actividad Económica").withOption('defaultContent', '')
    ];

    var loadRow = function () {
        angular.element("#dtDisabilityEmployeeList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.onEdit(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityEmployeeList.reloadData();
    };

    $scope.onEdit = function (id) {
        $scope.activity.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});
