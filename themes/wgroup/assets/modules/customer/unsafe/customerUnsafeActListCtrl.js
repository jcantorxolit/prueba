'use strict';
/**
 * controller for Customers
 */
app.filter('nl2br', function($sce){
    return function(msg,is_xhtml) {
        var is_xhtml = is_xhtml || true;
        var breakTag = (is_xhtml) ? '<br />' : '<br>';
        var msg = (msg + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
        return $sce.trustAsHtml(msg);
    }
});
app.controller('customerUnsafeActListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside) {

        var log = $log;

        log.info("loading..customerUnsafeActListCtrl ");

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";
        $scope.isCustomerAdmin = $rootScope.currentUser().wg_type == "customerAdmin";

        $scope.mustFilterByReportedAssigned = !$scope.isAdmin && !$scope.isCustomerAdmin

        $scope.filter = {
            reportedAssigned: $scope.mustFilterByReportedAssigned ? 'A' : null
        };

        // default view
        // $rootScope.tracking_section = "list";
        $scope.isView = false;

        $scope.dtInstanceCustomerUnsafeAct = {};
        $scope.dtOptionsCustomerUnsafeAct = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;

                    if ($scope.mustFilterByReportedAssigned) {
                        if ($scope.filter.reportedAssigned == 'A') {
                            d.assignedToId = true
                        } else if ($scope.filter.reportedAssigned == 'R') {
                            d.reportedById = true
                        }
                    }

                    return JSON.stringify(d);
                },
                url: 'api/customer-unsafe-act',
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

        $scope.dtColumnsCustomerUnsafeAct = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 180).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    if (data.status != null && (data.status == "Completado" || data.status == "Cancelado")) {
                        disabled = 'disabled';
                    }

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk '+ disabled+'" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '">' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs deleteRow lnk '+ disabled +'" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash"></i></a> ';

                    var improvementPlanTemplate = ' | <a class="btn btn-orange btn-xs improvementPlanRow lnk '+ disabled +'" href="#" uib-tooltip="Plan Mejoramiento" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-plus-square"></i></a> ';

                    var downloadTemplate = ' | <a class="btn btn-success btn-xs downloadRow lnk" href="#" uib-tooltip="Descargar Evidencias" data-id="' + data.id + '">' +
                        '   <i class="fa fa-download"></i></a> ';


                    actions += viewTemplate;
                    actions += editTemplate;
                    actions += deleteTemplate;
                    actions += improvementPlanTemplate;

                    if (data.hasImage == 1) {
                        actions += downloadTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('dateOf').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('work_place').withTitle("Centro de Trabajo").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('risk_type').withTitle("Tipo de Peligro").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('place').withTitle("Lugar").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción de la Condición Insegura").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('assignedTo').withTitle("Asignado A").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('reportedBy').withTitle("Reportado Por").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200).withOption('defaultContent', '')
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch (data.status) {
                        case "Creado":
                            label = 'label label-success';
                            break;

                        case "Revisado":
                            label = 'label label-warning';
                            break;

                        case "Completado":
                            label = 'label label-info';
                            break;

                        case "Cancelado":
                            label = 'label label-danger';
                            break;
                    }

                    return '<span class="' + label + '">' + data.status + '</span>';
                })

        ];

        var loadRow = function () {

            angular.element("#dtCustomerUnsafeAct a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.isView = false;
                $scope.onEdit(id);
            });

            angular.element("#dtCustomerUnsafeAct a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.isView = true;
                $scope.onView(id);
            });

            angular.element("#dtCustomerUnsafeAct a.improvementPlanRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onAddImprovementPlan(id);
            });

            angular.element("#dtCustomerUnsafeAct a.downloadRow").on("click", function () {
                var id = angular.element(this).data("id");
                onDownload({id : id});
            });

            angular.element("#dtCustomerUnsafeAct a.imageRow").on("click", function () {
                var id = angular.element(this).data("id");
                var url = angular.element(this).data("url");
                $scope.openLightboxModal([url]);
            });

            angular.element("#dtCustomerUnsafeAct a.deleteRow").on("click", function () {
                var id = angular.element(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("intenta eliminar el registro: " + id);

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminará la gestión seleccionada.",
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
                                url: 'api/customer/unsafe-act/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

        $scope.dtInstanceCustomerUnsafeActCallback = function (instance) {
            $scope.dtInstanceCustomerUnsafeAct = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerUnsafeAct.reloadData();
        };

        $scope.onCreate = function () {
            $scope.isView = false;
            $scope.onEdit(0);
        };

        $scope.onReport = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("indicator", "indicator", 0);
            }
        };

        $scope.onEdit = function (id) {
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", id);
            }
        };

        var onDownload = function (data) {
            angular.element("#download")[0].src = "api/customer-unsafe-act/export-zip?data=" + Base64.encode(JSON.stringify(data));
        };

        $scope.onView = function (id) {
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "view", id);
            }
        };

        $scope.onExportPdf = function () {
            $timeout(function () {
                kendo.drawing.drawDOM(angular.element(".unsafe-act-export-pdf"))
                .then(function (group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: {left: "1cm", top: "1cm", right: "1cm", bottom: "1cm"}
                    });
                })
                .done(function (data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Condiciones_Inseguras.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
            }, 200);
        }

        $scope.onExportExcel = function () {
            var data = {
                customerId: $stateParams.customerId,
            };

            if ($scope.mustFilterByReportedAssigned) {
                if ($scope.filter.reportedAssigned == 'A') {
                    data.assignedToId = true
                } else if ($scope.filter.reportedAssigned == 'R') {
                    data.reportedById = true
                }
            }

            angular.element("#download")[0].src = "api/customer-unsafe-act/export-excel?data=" + Base64.encode(JSON.stringify(data));
        }

        $scope.onExportReport = function () {
            var data = {
                customerId: $stateParams.customerId,
            };

            if ($scope.mustFilterByReportedAssigned) {
                if ($scope.filter.reportedAssigned == 'A') {
                    data.assignedToId = true
                } else if ($scope.filter.reportedAssigned == 'R') {
                    data.reportedById = true
                }
            }

            angular.element("#download")[0].src = "api/customer-unsafe-act/export-report?data=" + Base64.encode(JSON.stringify(data));
        }

        $scope.onMassiveDownload = function() {
            var data = {
                customerId: $stateParams.customerId,
                mustFilterByReportedAssigned: $scope.mustFilterByReportedAssigned,
                reportedAssigned: $scope.filter.reportedAssigned
            };

            if ($scope.mustFilterByReportedAssigned) {
                if ($scope.filter.reportedAssigned == 'A') {
                    data.assignedToId = true
                } else if ($scope.filter.reportedAssigned == 'R') {
                    data.reportedById = true
                }
            }


            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_improvement_plan.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/unsafe/customer_unsafe_act_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerUnsafeActListCtrl',
                scope: $scope,
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


        //----------------------------------------------------------------------------IMPROVEMENT PLAN
        $scope.onAddImprovementPlan = function (id) {
            var item = {id: id};

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_improvement_plan.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_improvement_plan_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerUnsafeImprovementPlanCtrl',
                scope: $scope,
                resolve: {
                    item: function () {
                        return item;
                    },
                    isView: function () {
                        return $scope.isView;
                    },
                    cycle: function () {
                        return null;
                    }
                }
            });
            modalInstance.result.then(function () {

            }, function() {

            });
        };

        //----------------------------------------------------------------------------LIGHTBOX
        $scope.openLightboxModal = function (images) {
            Lightbox.openModal(images, 0);
        };


    }]);


app.controller('ModalInstanceSideCustomerUnsafeImprovementPlanCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, item, cycle,
                                                                               $log, $timeout, SweetAlert, isView, $filter, FileUploader,
                                                                               $http, DTOptionsBuilder, DTColumnBuilder, $compile) {

    var $formInstance = null;
    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.responsibleList = [];

    $scope.typesAlert = $filter('orderBy')($rootScope.parameters("tracking_alert_type"), 'id', false);
    $scope.typesTime = $rootScope.parameters("tracking_alert_timeType");
    $scope.statusAlert = $rootScope.parameters("tracking_alert_status");
    $scope.preferencesAlert = $rootScope.parameters("tracking_alert_preference");

    $scope.typeList = $rootScope.parameters("improvement_plan_type");

    var init = function () {
        $scope.improvement = {
            id: 0,
            customerId: $stateParams.customerId,
            classificationName: "ACTOS",
            classificationId: null,
            entityName: 'UA',
            entityId: item.id,
            type: null,
            endDate: null,
            description: '',
            observation: '',
            responsible: null,
            isRequiresAnalysis: false,
            status: {
                id: 0,
                value: 'CR',
                item: 'Creada'
            },
            trackingList: [],
            alertList: []
        };

        if ($formInstance) {
            $formInstance.$setPristine(true);
        }
    }

    init();

    $scope.onLoadRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id
            };
            $http({
                method: 'GET',
                url: 'api/customer/improvement-plan',
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
                        SweetAlert.swal("Información no disponible", "Registro no encontrado", "error");

                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del centro de trabajo", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        if (response.data.result != null && response.data.result != '') {
                            $scope.improvement = response.data.result;

                            initializeDates();
                        }
                    }, 400);

                }).finally(function () {

                });
        } else {
            $scope.loading = false;
        }
    }

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {

    }

    var loadList = function () {

        var req = {
            customer_id: $stateParams.customerId
        };

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan/list-data',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.responsibleList = response.data.data.responsible;
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    loadList();

    $scope.master = $scope.improvement;

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

            $scope.improvement = angular.copy($scope.master);
            form.$setPristine(true);

        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.improvement);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                SweetAlert.swal("Acción exitosa", "La información ha sido guardada satisfactoriamente", "success");
                init();
            });
        }).catch(function (e) {

            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.reloadData();
        });
    };

    var initializeDates = function () {
        if ($scope.improvement.endDate != null) {
            $scope.improvement.endDate = new Date($scope.improvement.endDate.date);
        }

        angular.forEach($scope.improvement.trackingList, function (model, key) {
            if (model.startDate != null) {
                model.startDate = new Date(model.startDate.date);
            }
        });
    }

    //----------------------------------------------------------------TRACKING
    $scope.onAddTracking = function () {

        $timeout(function () {
            if ($scope.improvement.trackingList == null) {
                $scope.improvement.trackingList = [];
            }
            $scope.improvement.trackingList.push(
                {
                    id: 0,
                    customerImprovementPlanId: 0,
                    responsible: null,
                    startDate: null,
                }
            );
        });
    };

    $scope.onRemoveTracking = function (index) {
        SweetAlert.swal({
                title: "Está seguro?",
                text: "Desea confirmar la eliminación de este registro?",
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
                        var date = $scope.improvement.trackingList[index];

                        $scope.improvement.trackingList.splice(index, 1);

                        if (date.id != 0) {
                            var req = {};
                            req.id = date.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/improvement-plan-tracking/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

    //----------------------------------------------------------------VERIFICATION MODE
    $scope.onAddAlert = function () {

        $timeout(function () {
            if ($scope.improvement.alertList == null) {
                $scope.improvement.alertList = [];
            }
            $scope.improvement.alertList.push(
                {
                    id: 0,
                    customerImprovementPlanId: 0,
                    type: null,
                    preference: null,
                    time: 0,
                    timeType: null,
                    status: null,
                }
            );
        });
    };

    $scope.onRemoveAlert = function (index) {
        SweetAlert.swal({
                title: "Está seguro?",
                text: "Desea confirmar la eliminación de este registro ?",
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
                        var date = $scope.improvement.alertList[index];

                        $scope.improvement.alertList.splice(index, 1);

                        if (date.id != 0) {
                            var req = {};
                            req.id = date.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/improvement-plan-alert/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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


    //----------------------------------------------------------------IMPROVEMENT PLAN LIST
    $scope.dtInstanceImprovementPlan = {};
    $scope.dtOptionsImprovementPlan = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customerId = $scope.improvement.customerId;
                d.entityId = $scope.improvement.entityId;
                d.entityName = $scope.improvement.entityName;

                return JSON.stringify(d);
            },
            url: 'api/customer-improvement-plan-entity',
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

    $scope.dtColumnsImprovementPlan = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if ($rootScope.can('cliente_plan_mejoramiento_edit')) {
                    actions += editTemplate;
                }

                if ($rootScope.can('cliente_plan_mejoramiento_delete')) {
                    actions += deleteTemplate;
                }

				return !$scope.isView ? actions : null;
            }),
        DTColumnBuilder.newColumn('origin').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('description').withTitle("Hallazgo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('responsibleName').withTitle("Responsable").withOption('defaultContent', ''),
        DTColumnBuilder.newColumn(null).withTitle("Fecha Cierre").withOption('width', 200)
        .renderWith(function (data, type, full, meta) {
            if (typeof data.endDate == 'object' && data.endDate != null) {
                return moment(data.endDate.date).format('DD/MM/YYYY');
            }
            return data.endDate != null ? moment(data.endDate).format('DD/MM/YYYY') : '';
        }),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200).withOption('defaultContent', '')
        .renderWith(function (data, type, full, meta) {
            var label = 'label label-success';
            var text = data.status;

            switch (data.statusCode) {
                case "AB":
                    label = 'label label-info'
                    break;

                case "CO":
                    label = 'label label-success'
                    break;

                case "CA":
                    label = 'label label-danger'
                    break;
            }

            return '<span class="' + label + '">' + text + '</span>';
        })
    ];

    var loadRow = function () {

        angular.element("#dtImprovementPlan a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.onLoadRecord(id);
        });

        angular.element("#dtImprovementPlan a.delRow").on("click", function () {
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
                            url: 'api/customer/improvement-plan/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

    $scope.dtInstanceImprovementPlanCallback = function (dtInstance) {
        $scope.dtInstanceImprovementPlan = dtInstance;
    }

    $scope.reloadData = function () {
        $scope.dtInstanceImprovementPlan.reloadData();
    };

});

app.controller('ModalInstanceSideCustomerUnsafeActListCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, data,
                                                                               $log, $timeout, SweetAlert, $filter, FileUploader,
                                                                               $http, DTOptionsBuilder, DTColumnBuilder, $compile, ListService) {


        var subTitle = data.reportedAssigned == 'A' ? " Asignadas" : " Reportadas";

        $scope.title = 'Condiciones Inseguras' + (data.mustFilterByReportedAssigned ? subTitle : '');

        $scope.onCloseModal = function () {
            $uibModalInstance.close(1);
        };

        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.audit = {
            fields: [],
            filters: [],
        };

        getList();

        function getList() {
            var entities = [
                {name: 'criteria_operators', value: null},
                {name: 'criteria_conditions', value: null},
                {name: 'customer_unsafe_act_massive_filter_field', value: null}
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.customerUnsafeActMassiveFilterField;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $scope.addFilter = function () {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }
            $scope.audit.filters.push(
                {
                    id: 0,
                    field: null,
                    criteria: $scope.criteria.length > 0 ? $scope.criteria[1] : null,
                    condition: $scope.conditions.length > 0 ? $scope.conditions[0] : null,
                    value: ""
                }
            );
        };

        $scope.onFilter = function () {
            $scope.reloadData();
        }

        $scope.removeFilter = function (index) {
            $scope.audit.filters.splice(index, 1);
        }

        $scope.onCleanFilter = function () {
            $scope.audit.filters = [];
            $scope.reloadData()
        }

        $scope.toggle = {
            isChecked: false,
            selectAll: false
        };

        $scope.records = {
            hasSelected: false,
            countSelected: 0,
            countSelectedAll: 0
        };

        var $selectedItems = {};
        var $uids = {};
        var $currentPageUids = {};
        var params = null;

        var buildDTColumns = function() {
            var $columns = [
                DTColumnBuilder.newColumn(null).withOption('width', 30)
                .notSortable()
                .withClass("center")
                .renderWith(function (data, type, full, meta) {
                    var checkTemplate = '';
                    var isChecked = $selectedItems[data.id].selected;
                    var checked = isChecked ? "checked" : ""

                    checkTemplate = '<div class="checkbox clip-check check-danger ">' +
                        '<input class="selectedRow" type="checkbox" id="chk_unsafe_act_select_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label class="padding-left-10" for="chk_unsafe_act_select_' + data.id + '"> </label></div>';


                    return checkTemplate;
                })
            ];

            $columns.push(buildDTColumn('dateOf', 'Fecha', '', 200));
            $columns.push(buildDTColumn('work_place', 'Centro de Trabajo', '', 200));
            $columns.push(buildDTColumn('risk_type', 'Tipo de Peligro', '', 200));
            $columns.push(buildDTColumn('description', 'Descripción de la Condición Insegura', '', 200));
            $columns.push(buildDTColumn(null, 'Estado', '', 200).notSortable().renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch (data.status) {
                        case "Creado":
                            label = 'label label-success';
                            break;

                        case "Revisado":
                            label = 'label label-warning';
                            break;

                        case "Completado":
                            label = 'label label-info';
                            break;

                        case "Cancelado":
                            label = 'label label-danger';
                            break;
                    }


                    return '<span class="' + label + '">' + data.status + '</span>';
                })
            );

            return $columns;
        }

        var buildDTColumn = function(field, title, defaultContent, width) {
            return DTColumnBuilder.newColumn(field)
                .withTitle(title)
                .withOption('defaultContent', defaultContent)
                .withOption('width', width);
        };

        var initializeDatatable = function() {
            var $lastSearch = '';

            $scope.dtOptionsCustomerUnsafeActDataTableList = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.customerId = data.customerId;
                    d.assignedToId = data.assignedToId;
                    d.reportedById = data.reportedById;

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
                                    condition: filter.condition.value,
                                };
                            })
                        };
                    }

                    params = d;

                    return JSON.stringify(d);
                },
                dataSrc: function (response) {
                    $currentPageUids = response.data.map(function(item, index, array) {
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
                url: 'api/customer-unsafe-act-massive',
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

            $scope.dtColumnsCustomerUnsafeActDataTableList = buildDTColumns();
        }

        var loadRow = function () {
            angular.element("#dtCustomerUnsafeActDataTableList input.selectedRow").on("change", function () {
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

        initializeDatatable();

        $scope.dtInstanceCustomerUnsafeActDataTableListCallback = function (instance) {
            $scope.dtInstanceCustomerUnsafeActDataTableList = instance;
            $scope.dtInstanceCustomerUnsafeActDataTableList.DataTable.on('page', function() {
                $timeout(function () {
                    $scope.toggle.isChecked = $scope.toggle.selectAll;
                }, 300);
            })

            $scope.dtInstanceCustomerUnsafeActDataTableList.DataTable.on('order', function() {
                $timeout(function () {
                    $scope.toggle.isChecked = $scope.toggle.selectAll;
                }, 300);
            })
        };

        $scope.reloadData = function () {
            if ($scope.dtInstanceCustomerUnsafeActDataTableList != null) {
                $scope.dtInstanceCustomerUnsafeActDataTableList.reloadData(null, false);
            }
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

        var onCheck = function($items, $isCheck, $forceUnCheck) {
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
                //console.log(key, elem);
                var $uid = angular.element(elem).data("id");
                angular.element(elem).prop( "checked", $selectedItems[$uid].selected);
            });

            $scope.records.hasSelected = countSelected > 0;
            $scope.records.countSelected = countSelected;
        }

        $scope.onDownload = function () {
            var data = { customerId: $stateParams.customerId, selectedItems: $selectedItems };
            angular.element("#download")[0].src = "api/customer-unsafe-act/export-massive-zip?data=" + Base64.encode(JSON.stringify(data));
        };
});
