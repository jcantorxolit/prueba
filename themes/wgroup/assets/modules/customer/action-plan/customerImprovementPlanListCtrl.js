'use strict';
/**
 * controller for Customers
 */
app.controller('customerImprovementPlanListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', 'SweetAlert', '$http', '$filter', '$document', '$aside', "$uibModal",
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
              $compile, toaster, $state, $rootScope, $timeout, SweetAlert, $http, $filter, $document, $aside, $uibModal) {

        var log = $log;

        $scope.dtInstanceCustomerImprovementPlan = {};
        $scope.dtOptionsCustomerImprovementPlan = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-improvement-plan',
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

        $scope.dtColumnsCustomerImprovementPlan = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play-circle"></i></a> ';

                    var cancelTemplate = '<a class="btn btn-danger btn-xs cancelRow lnk" href="#" uib-tooltip="Cancelar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-ban"></i></a> ';

                    var completeTemplate = ' | <a class="btn btn-success btn-xs completeRow lnk" href="#" uib-tooltip="Completar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-check-circle-o"></i></a> ';

                    var openTemplate = ' | <a class="btn btn-dark-azure btn-xs openRow lnk" href="#" uib-tooltip="Reabrir" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-refresh"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if (data.statusCode == 'CO' || data.statusCode == 'CA') {
                        actions += viewTemplate;
                    } else {
                        actions += editTemplate;
                    }

                    if (data.statusCode == 'AB') {
                        if ($rootScope.can('plan_mejoramiento_complete') && data.canComplete == 1) {
                            actions += completeTemplate;
                        }
                        if ($rootScope.can('plan_mejoramiento_cancel')) {
                            actions += cancelTemplate;
                        }
                    }

                    if (data.statusCode == 'CO') {
                        if ($rootScope.can('plan_mejoramiento_reopen')) {
                            actions += openTemplate;
                        }
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('origin').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Hallazgo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Análisis de Causas").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = (data.isRequiresAnalysis || data.isRequiresAnalysis == 1) ? 'label label-success' : 'label label-danger';
                    var text = data.isRequireAnalysisText;
                    var status = '<span class="' + label + '">' + text + '</span>';
                    return status;
                }),
            DTColumnBuilder.newColumn('responsibleName').withTitle("Responsable").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Fecha Cierre").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                if (typeof data.endDate == 'object' && data.endDate != null) {
                    return moment(data.endDate.date).format('DD/MM/YYYY');
                }
                return data.endDate != null ? moment(data.endDate).format('DD/MM/YYYY') : '';
            }),
            DTColumnBuilder.newColumn(null).withTitle("Planes de Acción?").withOption('width', 200).withOption('defaultContent', '')
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-success';
                var text = data.hasActionPlan;

                switch (data.hasActionPlan) {
                    case "Si":
                        label = 'label label-success'
                        break;
                    case "No":
                        label = 'label label-danger'
                        break;
                }

                return '<span class="' + label + '">' + text + '</span>';
            }),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200).withOption('defaultContent', '')
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-success';
                var text = data.status;

                switch (data.statusCode) {
                    case "AB":
                        label = 'label label-dark-azure'
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

            angular.element("#dtCustomerImprovementPlan a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onView(id);
            });

            angular.element("#dtCustomerImprovementPlan a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onEdit(id);
            });

            angular.element("#dtCustomerImprovementPlan a.cancelRow").on("click", function () {
                var id = angular.element(this).data("id");
                onOpenUpdateModal( {id: id, status: 'CA' });
            });

            angular.element("#dtCustomerImprovementPlan a.openRow").on("click", function () {
                var id = angular.element(this).data("id");
                onOpenUpdateModal( {id: id, status: 'AB' });
            });

            angular.element("#dtCustomerImprovementPlan a.completeRow").on("click", function () {
                var id = angular.element(this).data("id");
                update(id);
            });

            angular.element("#dtCustomerImprovementPlan a.delRow").on("click", function () {
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
                                url: 'api/minimum-standard-item/delete',
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

        $scope.dtInstanceCustomerImprovementPlanCallback = function (instance) {
            $scope.dtInstanceCustomerImprovementPlan = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerImprovementPlan.reloadData();
        };

        $scope.onEdit = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", id);
            }
        };

        $scope.onView = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "view", id);
            }
        };

        $scope.onAttachment = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("attachment", "attachment", id);
            }
        };

        $scope.onDownloadAttachment = function (id) {            
            var modalInstance = $uibModal.open({                
                templateUrl: $rootScope.app.views.urlRoot + 'modules/common/modals/document_filter_modal.htm',
                controller: 'ModalInstanceSideImprovementPlanDownloadAttachmentCtrl',
                windowTopClass: 'top-modal',
                resolve: {
                    improvement: function () {
                        return { id : 0 };
                    },
                    title: function() {
                        return "Descargar anexos de planes de mejoramiento"
                    },
                    action: function () {
                        return "Cancelar";
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {
    
            });
        };
        
        $scope.onExportExcel = function()
        {
            var data = JSON.stringify({
                customerId: $stateParams.customerId
            });
            angular.element("#downloadDocument")[0].src = "api/customer-improvement-plan/export-excel?data=" + Base64.encode(data);
        }

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

        $scope.onFilter = function () {
            $scope.request.data = Base64.encode(JSON.stringify($scope.audit));

            $scope.reloadData();
        }

        $scope.removeFilter = function (index) {
            $scope.audit.filters.splice(index, 1);
        }

        var update = function (id) {

            var data = JSON.stringify(
                {
                    id: id,
                    reason: null,
                    status: { value: 'CO' }
                }
            );
            var req = {
                data: Base64.encode(data)
            };
            return $http({
                method: 'POST',
                url: 'api/customer-improvement-plan/update',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    toaster.pop('success', 'Operación Exitosa', 'Actualización exitosa.');
                    $scope.reloadData();
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.reloadData();
            });
        };

        var onOpenUpdateModal = function (improvement) {

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_improvement_plan_action_plan_task.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/customer/action-plan/tabs/customer_improvement_plan_action_plan_comment_edit_modal.htm',
                placement: 'right',
                size: 'sm',
                backdrop: true,
                controller: 'ModalInstanceSideImprovementPlanUpdateCtrl',
                scope: $scope,
                resolve: {
                    improvement: function () {
                        return improvement;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {
                $scope.reloadData();
            });
        }


    }

]);


app.controller('ModalInstanceSideImprovementPlanUpdateCtrl', function ($rootScope, $stateParams, $scope, $uibModal, $uibModalInstance, improvement, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    $scope.loading = true;

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var init = function() {
        $scope.entity = {
            id: improvement.id,
            reason: '',
            status: { value: improvement.status }
        }
    }

    init();

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

        }
    };

    var save = function () {

        var data = JSON.stringify($scope.entity);
        var req = {
            data: Base64.encode(data)
        };
        return $http({
            method: 'POST',
            url: 'api/customer-improvement-plan/update',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                toaster.pop('success', 'Operación Exitosa', 'Actualización exitosa.');
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    };

});

app.controller('ModalInstanceSideImprovementPlanDownloadAttachmentCtrl', function ($rootScope, $stateParams, $scope, $uibModal, $uibModalInstance, improvement, title, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile, ListService, ngNotify) {

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

    var init = function() {
        $scope.filter = {
            id: $stateParams.customerId,
        }
    }

    init();
        
    getList();

    function getList() {

        var $year = $scope.filter.year ? $scope.filter.year.value : null;

        var entities = [
            { name: 'customer_document_type', value: $stateParams.customerId },
            { name: 'customer_improvement_plan_document_periods', value: $stateParams.customerId, year:  $year}
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.documentType =response.data.data.customerDocumentType;
                $scope.years =response.data.data.customerImprovementPlanDocumentPeriod.years;
                $scope.months =response.data.data.customerImprovementPlanDocumentPeriod.months;                
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
                onFIlter();onFIlter
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
            customerId: $stateParams.customerId,
            audit: $scope.filter
        };

        var req = {};
        var data = JSON.stringify(entity);
        req.data = Base64.encode(data);

        $http({
            method: 'POST',
            url: 'api/customer-improvement-plan-document/export',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {            
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