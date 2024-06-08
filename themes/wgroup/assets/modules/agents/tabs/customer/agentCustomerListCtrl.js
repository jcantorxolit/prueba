'use strict';
/**
 * controller for Customers
 */
app.controller('agentCustomerListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', 'SweetAlert', '$http',
    '$aside', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
        $compile, toaster, $state, $rootScope, $timeout, SweetAlert, $http,
        $aside, ListService) {


        $scope.dtOptionsCustomerRelation = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.agentId = $stateParams.agentId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-agent/v2',
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

        $scope.dtColumnsCustomerRelation = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-ban"></i></a> ';

                    actions += editTemplate;
                    actions += deleteTemplate;

                    return actions;
                }),
            DTColumnBuilder.newColumn('documentType').withTitle("Tipo de Documento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('documentNumber').withTitle("Nro Documento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('businessName').withTitle("Razón Social").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type').withTitle("Tipo Relación").withOption('width', 200).withOption('defaultContent', ''),
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

        var loadRow = function () {

            angular.element("#dtCustomerRelation a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                openModal(id);
            });

            angular.element("#dtCustomerRelation a.delRow").on("click", function () {
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
                                url: 'api/customer-agent/delete',
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

        $scope.dtInstanceCustomerRelationCallback = function (instace) {
            $scope.dtInstanceCustomerRelation = instace;
        }

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerRelation.reloadData();
        };

        $scope.onEdit = function (id) {
            $scope.attachment.id = id;
            $scope.isView = false;
            loadRecord(id);
        };

        $scope.onAddCustomer = function () {
            openModal(0)
        }

        var openModal = function (id) {

            var entity = { id: id, isEdit: id > 0 }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_resource_library.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/agents/tabs/customer/agent_customer_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideAgentCustomerCtrl',
                scope: $scope,
                resolve: {
                    entity: function () {
                        return entity;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function () {
                $scope.reloadData();
            });
        };


    }]);

app.controller('ModalInstanceSideAgentCustomerCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, entity, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.canShowDataTable = false;

    $scope.typeList = $rootScope.parameters("bunit");

    $scope.filter = {
        selectedType: null
    };

    $scope.toggle = {
        isChecked: false,
        selectAll: false
    };

    $scope.records = {
        hasSelected: false,
        countSelected: 0,
        countSelectedAll: 0
    };

    $scope.isEdit = entity.isEdit;

    var attachmentUploadedId = 0;

    var initialize = function () {
        $scope.entity = {
            id: entity.id ? entity.id : 0,
            agentId: $stateParams.agentId,
            type: null,
            customerUIds: [],
        };
    };

    initialize();

    var $selectedItems = {};
    var $uids = {};
    var $currentPageUids = {};
    var params = null;

    var loadRecord = function () {
        // se debe cargar primero la información actual del cliente..

        if ($scope.entity.id) {
            var req = {
                id: $scope.entity.id
            };

            $http({
                method: 'GET',
                url: 'api/customer-agent/get',
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
                        SweetAlert.swal("Información no disponible", "Anexo no encontrado", "error");
                        $timeout(function () {

                            $state.go('app.clientes.list');
                        });
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del anexo", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.entity = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);
                });
        }
    };

    loadRecord();

    var buildDTColumns = function () {
        var $columns = [
            DTColumnBuilder.newColumn(null).withOption('width', 30)
                .notSortable()
                .withClass("center")
                .renderWith(function (data, type, full, meta) {
                    var checkTemplate = '';
                    var isChecked = $selectedItems[data.id].selected;
                    var checked = isChecked ? "checked" : ""

                    checkTemplate = '<div class="checkbox clip-check check-danger ">' +
                        '<input class="selectedRow" type="checkbox" id="chk_employee_document_select_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label class="padding-left-10" for="chk_employee_document_select_' + data.id + '"> </label></div>';

                    return checkTemplate;
                })
        ];

        $columns.push(buildDTColumn('documentType', 'Tipo de Documento', '', 200));
        $columns.push(buildDTColumn('documentNumber', 'Nro Documento', '', 200));
        $columns.push(buildDTColumn('businessName', 'Razón Social', '', 200));
        $columns.push(buildDTColumn('type', 'Tipo de Cliente', '', 200));
        $columns.push(buildDTColumn('classification', 'Clasificación', '', 200));
        $columns.push(buildDTColumn('status', 'Estado', '', 200).notSortable().renderWith(function (data, type, full, meta) {
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
        $scope.canShowDataTable = true;

        var $lastSearch = '';

        $scope.dtOptionsCustomerAgentRelation = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.agentId = $stateParams.agentId;
                    params = d;
                    return JSON.stringify(d);
                },
                dataSrc: function (response) {
                    $currentPageUids = response.data.map(function (item, index, array) {
                        return item.id;
                    })

                    $uids = response.extra;

                    angular.forEach($uids, function (uid, key) {
                        if ($selectedItems[uid] === undefined || $selectedItems[uid] === null) {
                            $selectedItems[uid] = {
                                selected: false
                            };
                        }
                    });

                    $scope.records.currentPage = $currentPageUids.length;
                    $scope.records.total = $uids.length;

                    if ($lastSearch !== params.search.value) {
                        $scope.toggle.isChecked = false;
                        $scope.toggle.selectAll = false;
                        onCheck($uids, $scope.toggle.isChecked, true);
                        $lastSearch = params.search.value;
                    }

                    return response.data;
                },
                url: 'api/customer-agent/available',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {

                }
            })
            .withDataProp('data')
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withOption('language', {
            })
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsCustomerAgentRelation = buildDTColumns();
    }

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancelModal = function () {
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

                $timeout(function () {
                    toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                }, 500);

                return;

            } else {

                if (!$scope.isEdit && !$scope.records.hasSelected) {
                    SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione una o varias empresas.", "error");
                    return;
                }

                $scope.onSave();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.onSave = function () {

        var req = {};

        $scope.entity.customerUIds = Object.keys($selectedItems).filter(function (key) {
            return $selectedItems[key].selected
        }).map(function (item) {
            return item;
        });

        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: $scope.isEdit ? 'api/customer-agent/save' : 'api/customer-agent/bulk',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                toaster.pop('success', 'Operación Exitosa', 'La información ha sido guardada satisfactoriamente');
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            $log.error(e);
            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
        }).finally(function () {

        });

    };

    var loadRow = function () {

        angular.element("#dtCustomerAgentRelation input.selectedRow").on("change", function () {
            var id = angular.element(this).data("id");

            if (this.className == 'selectedRow') {
                $selectedItems[id].selected = this.checked;
            }

            $timeout(function () {
                var countSelected = 0;

                angular.forEach($selectedItems, function (value, key) {
                    countSelected += value.selected ? 1 : 0;
                });

                $scope.records.hasSelected = countSelected > 0;
                $scope.records.countSelected = countSelected;
            }, 100);
        });
    };


    $scope.dtInstanceCustomerAgentRelationCallback = function (instance) {
        $scope.dtInstanceCustomerAgentRelation = instance;
        $scope.dtInstanceCustomerAgentRelation.DataTable.on('page', function () {
            $timeout(function () {
                $scope.toggle.isChecked = $scope.toggle.selectAll;
            }, 300);
        })

        $scope.dtInstanceCustomerAgentRelation.DataTable.on('order', function () {
            $timeout(function () {
                $scope.toggle.isChecked = $scope.toggle.selectAll;
            }, 300);
        })
    };

    $scope.reloadData = function () {
        if ($scope.dtInstanceCustomerAgentRelation != null) {
            $scope.dtInstanceCustomerAgentRelation.reloadData(null, false);
        }
    };


    $scope.onCancel = function () {
        $scope.onToggleShowList();
    };

    $scope.onToggle = function () {
        $scope.toggle.isChecked = !$scope.toggle.isChecked;
        onCheck($currentPageUids, $scope.toggle.isChecked);
    };

    $scope.onSelectCurrentPage = function () {
        $scope.toggle.isChecked = true;
        if ($scope.toggle.selectAll) {
            onCheck($uids, false);
            $scope.toggle.selectAll = false;
        }
        onCheck($currentPageUids, $scope.toggle.isChecked);
    };

    $scope.onSelectAll = function () {
        $scope.toggle.isChecked = true;
        $scope.toggle.selectAll = true;
        onCheck($uids, $scope.toggle.selectAll);
    };

    $scope.onDeselectAll = function () {
        $scope.toggle.isChecked = false;
        $scope.toggle.selectAll = false;
        onCheck($uids, $scope.toggle.selectAll);
    };

    var onCheck = function ($items, $isCheck, $forceUnCheck) {
        var countSelected = 0;

        angular.forEach($selectedItems, function (uid, key) {
            if ($forceUnCheck !== undefined && $forceUnCheck) {
                $selectedItems[key].selected = false;
            }

            if ($items.indexOf(parseInt(key)) !== -1) {
                $selectedItems[key].selected = $isCheck;
            }
            countSelected += $selectedItems[key].selected ? 1 : 0;
        });

        var $elements = angular.element('.selectedRow');
        angular.forEach($elements, function (elem, key) {
            var $uid = angular.element(elem).data("id");
            angular.element(elem).prop("checked", $selectedItems[$uid].selected);
        });

        $scope.records.hasSelected = countSelected > 0;
        $scope.records.countSelected = countSelected;
    }

    initializeDatatable();
});
