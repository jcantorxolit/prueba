'use strict';
/**
 * controller for Customers
 */
app.controller('customerUserCtrl', ['$scope', '$aside', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', 'flowFactory', 'cfpLoadingBar',
    '$filter', '$document', 'ListService', '$uibModal',
    function ($scope, $aside, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, $compile, toaster, $state,
        SweetAlert, $rootScope, $http, $timeout, flowFactory, cfpLoadingBar, $filter, $document, ListService, $uibModal) {

        var log = $log;

        var $formInstance = null;

        $scope.currentUser = $rootScope.currentUser();
        $scope.isAgent = $scope.currentUser.wg_type == "agent";
        $scope.isAdmin = $scope.currentUser.wg_type == "system";
        $scope.isCustomerAdmin = $scope.currentUser.wg_type == "customerAdmin";
        $scope.isCustomerUser = $scope.currentUser.wg_type == "customerUser";
        $scope.extrainfo = $scope.customer.extraContactInformationList;

        $scope.canEdit = $scope.isCustomerAdmin || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));
        $rootScope.canEditRoot = $scope.canEdit;

        $scope.showRelatedUsersFilter = ($scope.isAdmin || $scope.isCustomerAdmin) && ($scope.customer.classification && $scope.customer.classification.value == 'Contratante' || $scope.customer.hasEconomicGroup)

        $scope.filter = {
            showRelatedUsers: false
        };

        $scope.textProfile = null;
        $scope.textRole = null;

        getList();
        getCustomerListOnDemand();

        function getList() {
            var entities = [
                { name: 'wg_customer_user_profile', value: null },
                { name: 'customer_admin_profile_remove', value: null },
                { name: 'customer_user_role', value: null },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.userProfileList = response.data.data.wg_customer_user_profile;
                    $scope.userRoleList = response.data.data.customer_user_role;

                    console.log('roles')
                    console.log($scope.userRoleList);

                    var $customerAdminProfileRemove = response.data.data.customerAdminProfileRemove;
                    if ($scope.isCustomerAdmin && $customerAdminProfileRemove && $customerAdminProfileRemove.value == "1") {
                        $scope.userProfileList = $scope.userProfileList.filter(function (item) {
                            return item.value != "customerAdmin";
                        });
                    }
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getCustomerListOnDemand() {

            var $criteria = {
                customerId: $stateParams.customerId
            }

            var entities = [
                { name: 'customer_user_customer_list', value: null, criteria: $criteria },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.customerList = response.data.data.customerList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var onInit = function () {
            $scope.user = {
                id: 0,
                customerId: $stateParams.customerId,
                documentNumber: null,
                documentType: null,
                firstName: null,
                lastName: null,
                email: null,
                type: null,
                gender: null,
                availability: 0,
                isActive: true,
                skills: [],
                isEditMode: false,
                profile: null,
                role: null,
                userId: null,
                isUserApp: false,
                informationList: null,
                employeeId: null
            }

            if ($formInstance !== null) {
                $formInstance.$setPristine(true);
            }
        }

        onInit();

        $scope.isView = $state.is("app.clientes.view");
        $scope.isCreate = $state.is("app.clientes.create");

        $scope.genders = $rootScope.parameters("gender");
        $scope.types = $rootScope.parameters("agent_type");
        $scope.userSkills = [];

        var loadUserSkill = function () {

            var reqUser = {};

            reqUser.customer_id = $scope.customer.id;
            reqUser.namespace = "userSkill";

            $http({
                method: 'POST',
                url: 'api/customer-parameter',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(reqUser)
            })
                .catch(function (e, code) {
                })
                .then(function (response) {
                    $timeout(function () {
                        $scope.userSkills = response.data.data;
                    });
                }).finally(function () {
                });
        }

        loadUserSkill();

        $scope.onLoadRecord = function (dataItem) {
            if (dataItem.id != 0) {
                $http({
                    method: 'GET',
                    url: 'api/customer-user/get',
                    params: dataItem
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
                            $scope.user = response.data.result;
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $document.scrollTop(0, 2000);
                        });
                    });
            }
        }

        $scope.onSearchEmployee = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/employee_list_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideUserModalEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (result) {
                if (result) {
                    $scope.user.documentNumber = result.entity.documentNumber;
                    $scope.user.firstName = result.entity.firstName;
                    $scope.user.lastName = result.entity.lastName;
                    $scope.user.gender = result.entity.gender;
                    $scope.user.email = result.email ? result.email.value : null;
                    $scope.user.employeeId = result.id;
                }
            }, function () {

            });
        };

        $scope.master = $scope.user;
        $scope.form = {

            submit: function (form) {
                var firstError = null;

                $formInstance = form;

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

                $scope.user = angular.copy($scope.master);
                form.$setPristine(true);
                $scope.textProfile = null;
                $scope.textRole = null;

            }
        };

        var save = function () {

            if ($scope.user.email == null || $scope.user.email.replace(/^\s+|\s+$/gm, '') == "") {
                SweetAlert.swal("El formulario contiene errores!", "Debe ingresar un E-mail válido.", "error");

                return;
            }

            $scope.user.informationList = $filter('filter')($scope.extrainfo, {item: "Email"});

            var req = {};
            var data = JSON.stringify($scope.user);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-user/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.user = response.data.result;
                    SweetAlert.swal("Operación exitosa", "Información guardada satisfactoriamente", "success");
                    $scope.reloadDataUser();
                    $scope.onClearUser();
                });
            }).catch(function (response) {
                $log.error(response);
                SweetAlert.swal("Error de guardado", response.data.message, "error");
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

        $scope.dtInstanceUser = {};
        $scope.dtOptionsUser = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerId = $stateParams.customerId;

                    return JSON.stringify(d);
                },
                url: 'api/customer-user',
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

        $scope.dtColumnsUser = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" data-relation="' + data.relationCode + '" data-module="' + data.module + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var relateTemplate = '<a class="btn btn-info btn-xs relRow lnk" href="#" uib-tooltip="Relacionar empresas" data-id="' + data.id + '" data-user-id="' + data.userId + '" data-relation="' + data.relationCode + '" >' +
                        '   <i class="fa fa-sitemap"></i></a> ';

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if ($scope.showRelatedUsersFilter) {
                        actions += relateTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
            DTColumnBuilder.newColumn('email').withTitle("E-mail").withOption('width', 200),
            DTColumnBuilder.newColumn('availability').withTitle("Disponibilidad").withOption('width', 200),
            DTColumnBuilder.newColumn('profile').withTitle("Perfil").withOption('width', 200),
            DTColumnBuilder.newColumn('businessName').withTitle("Empresa Ppal").withOption('width', 200),
            DTColumnBuilder.newColumn('relation').withTitle("Relación").withOption('width', 200),
            DTColumnBuilder.newColumn('module').notVisible(),
            DTColumnBuilder.newColumn(null).withTitle("Activo").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = data.status;

                    if (data.statusCode != null || data.statusCode != undefined) {
                        if (data.statusCode == '1' || data.statusCode == true) {
                            label = 'label label-success';
                        } else {
                            label = 'label label-danger';
                        }
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
        ];

        var loadRow = function () {

            angular.element("#dtUser a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                var relation = angular.element(this).data("relation");
                var module = angular.element(this).data("module");

                if (module == "RE" || module == "EA") {
                    console.log("Las cuentas de drummond no se pueden editar.");
                    swal("No Editable", "Las cuentas creadas desde Responsables y Empleados App no se pueden editar.", "error");
                    return;
                }

                $scope.onEdit({
                    id: id,
                    relation: relation
                });
            });

            angular.element("#dtUser a.relRow").on("click", function () {
                var id = angular.element(this).data("id");
                var userId = angular.element(this).data("user-id");
                var relation = angular.element(this).data("relation");
                onRelateCustomer({
                    customerId: $stateParams.customerId,
                    userId: userId,
                    customerUserId: id,
                    relation: relation
                });
            });

            angular.element("#dtUser a.delRow").on("click", function () {
                var id = angular.element(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

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
                                url: 'api/customer-user/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", e.data.message, "error");
                            }).finally(function () {

                                $scope.reloadDataUser();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };


        $scope.onEdit = function (dataItem) {
            $scope.onLoadRecord(dataItem);
        };

        $scope.dtInstanceUserCallback = function (insntace) {
            $scope.dtInstanceUser = insntace;
        };

        $scope.reloadDataUser = function () {
            var url = !$scope.filter.showRelatedUsers ? 'api/customer-user' : 'api/customer-user-contractor-economic-group';
            $scope.dtInstanceUser.DataTable.ajax.url(url);
            $scope.dtInstanceUser.reloadData();
        };

        $scope.onShowRelatedUsers = function () {
            $scope.reloadDataUser();
        }

        $scope.onClearUser = function () {
            onInit();
            $scope.isView = false;
        };

        $scope.onAddSkill = function () {

            $timeout(function () {
                if ($scope.user.skills == null) {
                    $scope.user.skills = [];
                }
                $scope.user.skills.push(
                    {
                        id: 0,
                        customerId: $stateParams.customerId,
                        userId: 0,
                        skill: null
                    }
                );
            });
        };

        $scope.onRemoveSkill = function (index) {
            SweetAlert.swal({
                title: "Está seguro?",
                text: "Eliminará el registro seleccionado.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Si, eliminar!",
                cancelButtonText: "No, cancelar!",
                closeOnConfirm: true,
                closeOnCancel: true
            },
                function (isConfirm) {
                    if (isConfirm) {
                        $timeout(function () {
                            // eliminamos el registro en la posicion seleccionada
                            var date = $scope.user.skills[index];

                            $scope.user.skills.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/user-skill/delete',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function (e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function () {

                                });
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }


        //----------------------------------------------------------------------------CUSTOMER RELATED
        var onRelateCustomer = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/user/customer_profile_user_relate_customer_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerUserCustomerRelatedCtrl',
                scope: $scope,
                resolve: {
                    dataItem: function () {
                        return dataItem;
                    },
                    isView: function () {
                        return $scope.isView
                    }
                }
            });
            modalInstance.result.then(function () {

            }, function () {

            });
        };


        //----------------------------------------------------------------------------IMPORT USERS
        $scope.onImport = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/profile/user/customer_profile_user_import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerUserImportCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                $scope.reloadDataUser();
                if (response.result && response.result.errors && response.result.errors.length > 0) {
                    onShowImportMessages(response.result)
                }
            }, function() {

            });
        };

        var onShowImportMessages = function(data) {
            var modalInstance = $uibModal.open({
                templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/data_table_list_modal.htm',
                controller: 'ModalInstanceSideCustomerUserImportMessagesCtrl',
                windowTopClass: 'top-modal',
                size: 'lg',
                resolve: {
                    data: function () {
                        return data;
                    }
                }
            });
            modalInstance.result.then(function () {

            }, function() {

            });
        }


        $scope.loadText = function(type) {

            var req = {};
            req.type = type == 1 ? "profile" : "role";
            req.id = type == 1 ? $scope.user.profile.value : $scope.user.role.value;

            if(type == 1) {
                $scope.textProfile = null;
            } else {
                $scope.textRole = null;
            }

            $http({
                method: 'POST',
                url: 'api/help-roles-profile/gettext',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            })
            .catch(function (e, code) {
            })
            .then(function (response) {
                $timeout(function () {
                    if(response.data.result && response.data.result.text) {
                        if(type==1) {
                            $scope.textProfile = response.data.result.text;
                        } else {
                            var link = " <a class='text-info' target='_blank' href='" + response.data.result.documentUrl + "'>haga clic aquí</a>"
                            var moreInfo = response.data.result.text + " " + link;
                            $scope.textRole = moreInfo;
                        }
                    }
                });
            }).finally(function () {
            })
        }


    }
]);

app.controller('ModalInstanceSideCustomerUserCustomerRelatedCtrl', function ($scope, $uibModalInstance, dataItem,
    $timeout, SweetAlert, isView, $filter, FileUploader,
    $http, DTOptionsBuilder, DTColumnBuilder, $compile) {
    $scope.isView = isView;

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.dtOptionsCustomerUserRelateCustomer = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customerId = dataItem.customerId;
                d.customerUserId = dataItem.customerUserId;
                d.userId = dataItem.userId;
                d.relation = dataItem.relation;

                return JSON.stringify(d);
            },
            url: 'api/customer-user-customer-related',
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

    $scope.dtColumnsCustomerUserRelateCustomer = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";

                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" target="_blank" href="#" uib-tooltip="Eliminar relación" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                var activateTemplate = ' | <a class="btn btn-success btn-xs toggleRow lnk" target="_blank" href="#" uib-tooltip="Activar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-check"></i></a> ';

                var inactivateTemplate = ' | <a class="btn btn-warning btn-xs toggleRow lnk" target="_blank" href="#" uib-tooltip="Inactivar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-ban"></i></a> ';

                actions += deleteTemplate;
                actions += data.statusCode == '1' || data.statusCode == true ? inactivateTemplate : activateTemplate;

                return actions;
            }),
        DTColumnBuilder.newColumn('documentNumber').withTitle("Nro Documento").withOption('width', 200),
        DTColumnBuilder.newColumn('businessName').withTitle("Razón Social").withOption('width', 200),
        DTColumnBuilder.newColumn('relation').withTitle("Relación").withOption('width', 200),
        DTColumnBuilder.newColumn(null).withTitle("Activo").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = data.status;

                if (data.statusCode != null || data.statusCode != undefined) {
                    if (data.statusCode == '1' || data.statusCode == true) {
                        label = 'label label-success';
                    } else {
                        label = 'label label-danger';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            })
    ];

    var loadRow = function () {
        $("#dtCustomerUserRelateCustomer a.toggleRow").on("click", function () {
            var id = angular.element(this).data("id");

            $http({
                method: 'POST',
                url: 'api/customer-user/toggle-active',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param({
                    id: id
                })
            }).then(function (response) {
                swal("Actualizado", "Registro actualizado satisfactoriamente", "info");
            }).catch(function (e) {
                SweetAlert.swal("Error en la activación", e.data.message, "error");
            }).finally(function () {
                $scope.reloadHistoricalData();
                $scope.reloadData();
            });
        });


        $("#dtCustomerUserRelateCustomer a.delRow").on("click", function () {
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
                        $http({
                            method: 'POST',
                            url: 'api/customer-user/delete',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            data: $.param({
                                id: id
                            })
                        }).then(function (response) {
                            swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function (e) {
                            SweetAlert.swal("Error en la eliminación", e.data.message, "error");
                        }).finally(function () {
                            $scope.reloadHistoricalData();
                            $scope.reloadData();
                        });

                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });
    };

    $scope.dtInstanceCustomerUserRelateCustomerCallback = function (instance) {
        $scope.dtInstanceCustomerUserRelateCustomer = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceCustomerUserRelateCustomer.reloadData();
    };



    //-------------------------------------------------------------
    // AVAILABLE CUSTOMERS
    //-------------------------------------------------------------

    $scope.dtOptionsCustomerUserRelateCustomerAvailable = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customerId = dataItem.customerId;
                d.customerUserId = dataItem.customerUserId;
                d.userId = dataItem.userId;
                d.relation = dataItem.relation;

                return JSON.stringify(d);
            },
            url: 'api/customer-user-customer-available',
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
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {
            //log.info("fnDrawCallback");
            loadRowAvailable();
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

    $scope.dtColumnsCustomerUserRelateCustomerAvailable = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";

                var addTemplate = '<a class="btn btn-success btn-xs addRow lnk" uib-tooltip="Adicionar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-plus"></i></a> ';

                actions += addTemplate;

                return actions;
            }),
        DTColumnBuilder.newColumn('documentNumber').withTitle("Nro Documento").withOption('width', 200),
        DTColumnBuilder.newColumn('businessName').withTitle("Razón Social").withOption('width', 200),
        DTColumnBuilder.newColumn('relation').withTitle("Relación").withOption('width', 200),
        DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
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
    ];

    var loadRowAvailable = function () {
        $("#dtCustomerUserRelateCustomerAvailable a.addRow").on("click", function () {
            var id = $(this).data("id");
            onRelateCustomer(id);
        });
    };

    $scope.dtInstanceCustomerUserRelateCustomerAvailableCallback = function (instance) {
        $scope.dtInstanceCustomerUserRelateCustomerAvailable = instance;
    };

    $scope.reloadHistoricalData = function () {
        $scope.dtInstanceCustomerUserRelateCustomerAvailable.reloadData();
    };

    var onRelateCustomer = function (id) {

        var data = JSON.stringify({
            customerId: id,
            customerUserId: dataItem.customerUserId
        });

        return $http({
            method: 'POST',
            url: 'api/customer-user/relate-customer',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param({
                data: Base64.encode(data)
            }),
        }).then(function (response) {
            $timeout(function () {
                $scope.reloadHistoricalData();
                $scope.reloadData();
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
        });
    };

});

app.controller('ModalInstanceSideCustomerUserImportCtrl', function ($rootScope, ngNotify, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/customer-user/import',
        formData: []
    });

    $scope.download = function () {
        angular.element("#downloadDocument")[0].src = "api/customer-user/download-template?customerId=" + $stateParams.customerId;
    }

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item/*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploader.onBeforeUploadItem = function (item) {
        var formData = { id: $stateParams.customerId, user: $rootScope.currentUser().id };
        item.formData.push(formData);

        ngNotify.set('El archivo se está importando.', {
            position: 'bottom',
            sticky: true,
            button: false,
            html: true
        });
    };
    uploader.onCompleteItem = function (fileItem, response, status, headers) {
        $lastResponse = response;
    };
    uploader.onCompleteAll = function () {
        $uibModalInstance.close($lastResponse);

        ngNotify.set('El proceso ha finalizado.', {
            position: 'bottom',
            sticky: true,
            type: 'success',
            button: true,
            html: true
        });
    };

});

app.controller('ModalInstanceSideCustomerUserImportMessagesCtrl', function (
    $rootScope, $stateParams, $scope, $uibModalInstance, data, $http, toaster,
    DTOptionsBuilder, DTColumnBuilder, $compile, $q) {

        $scope.title = 'Errores Importación'

        $scope.onCloseModal = function () {
            $uibModalInstance.close(1);
        };

        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.fromFnPromise(function() {
            var defer = $q.defer();
            defer.resolve(data.errors.map(function (item) {
                return {
                    message: item
                };
            }));
            return defer.promise;
        })
        .withBootstrap()
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });

        $scope.dtColumnsCommonDataTableList = [
            DTColumnBuilder.newColumn('message').withTitle("Mensaje").withOption('defaultContent', ''),
        ];

        $scope.dtInstanceCommonDataTableListCallback = function (instance) {
            $scope.dtInstanceCommonDataTableList = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCommonDataTableList.reloadData();
        };

});

app.controller('ModalInstanceSideUserModalEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.employee.id != 0) {
            var req = {
                id: $scope.employee.id,
            };
            $http({
                method: 'GET',
                url: 'api/customer-employee',
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
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.employee = response.data.result;
                        if ($scope.employee.entity.details.length) {
                            var $mail = $scope.employee.entity.details.filter(function(value){
                                return value.type.value == "email";
                            });
                             if($mail.length) {
                                 $scope.employee.email = $mail[0];
                             }
                        }
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

    $scope.dtInstanceModalEmployeeList = {};
    $scope.dtOptionsModalEmployeeList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerId = $stateParams.customerId;
                return JSON.stringify(d);
            },
            url: 'api/customer-employee-modal-basic',
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

    $scope.dtColumnsModalEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar empleado" tooltip-placement="right"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200),
        DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200),
        DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
        DTColumnBuilder.newColumn('workPlace').withTitle("Centro de Trabajo").withOption('width', 200),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200),
        DTColumnBuilder.newColumn('neighborhood').withTitle("Centro de Costos").withOption('width', 200),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isActiveCode != null || data.isActiveCode != undefined) {
                    if (data.isActiveCode == 'Activo') {
                        label = 'label label-success';
                        text = 'Activo';
                    } else {
                        label = 'label label-danger';
                        text = 'Inactivo';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            }),
        DTColumnBuilder.newColumn(null).withTitle("Autorización").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isAuthorized != null || data.isAuthorized != undefined) {
                    if (data.isAuthorized == 'Autorizado') {
                        label = 'label label-success';
                        text = 'Autorizado';
                    } else if (data.isAuthorized == 'No Autorizado') {
                        label = 'label label-danger';
                        text = 'No Autorizado';
                    } else {
                        label = 'label label-info';
                        text = 'N/A';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            })
    ];

    var loadRow = function () {
        angular.element("#dtModalEmployeeList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.editDisabilityEmployee(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceModalEmployeeList.reloadData();
    };

    $scope.viewDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});
