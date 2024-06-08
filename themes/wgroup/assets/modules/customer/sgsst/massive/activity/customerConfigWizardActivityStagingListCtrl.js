'use strict';
/**
 * controller for Customers
 */
app.controller('customerConfigWizardActivityStagingListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter', '$aside',
    'ListService', '$translate',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService, $translate) {

        var log = $log;
        var $exportUrl = '';

        $scope.audit = {
            fields: [],
            filters: [],
        };

        getList();

        function getList() {

            var entities = [
                { name: 'export_url', value: null },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $exportUrl = response.data.data.exportUrl.item;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $scope.dtInstanceCustomerConfigWizardActivityStagingDT = {};
        $scope.dtOptionsCustomerConfigWizardActivityStagingDT = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    d.sessionId = $scope.$parent.currentId;

                    return JSON.stringify(d);
                },
                url: 'api/customer-config-activity-staging',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[1, 'asc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
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

        $scope.dtColumnsCustomerConfigWizardActivityStagingDT = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    return data.isValid == 0 || !data.isValid ? editTemplate : null;
                }),
                //<span class="badge badge-danger"> 6</span>
            DTColumnBuilder.newColumn(null).withTitle('Fila').withOption('width', 50)
                .renderWith(function (data, type, full, meta) {

                    var $class = data.isValid == 1 || data.isValid ? 'badge badge-success' : 'badge badge-danger';
                    var $icon = data.isValid == 1 || data.isValid ? ' <i class=" fa fa-check"></i>' : ' <i class=" fa fa-ban"></i>';

                    return '<span class="'+ $class +'">'  + data.index + $icon + '</span>';
                }),
            DTColumnBuilder.newColumn('name').withTitle($translate.instant('grid.matrix.ACTIVITY')).withOption('width', 200),
            DTColumnBuilder.newColumn('status').withTitle('Estado').withOption('width', 200),
            DTColumnBuilder.newColumn('isCritical').withTitle('Crítica').withOption('width', 200),
            DTColumnBuilder.newColumn('classification').withTitle('Clasificación').withOption('width', 200),
            DTColumnBuilder.newColumn('type').withTitle('Tipo Peligro').withOption('width', 200),
            DTColumnBuilder.newColumn('description').withTitle('Descripción').withOption('width', 200),
            DTColumnBuilder.newColumn('healthEffect').withTitle('Efectos en la Salud').withOption('width', 200),
            DTColumnBuilder.newColumn('observationHazard').withTitle('Observación').withOption('width', 200),
            DTColumnBuilder.newColumn('timeExposure').withTitle('Tiempo Exposición').withOption('width', 200),
            DTColumnBuilder.newColumn('controlMethodSourceText').withTitle('Método de Control Fuente').withOption('width', 200),
            DTColumnBuilder.newColumn('controlMethodMediumText').withTitle('Método de Control Medio').withOption('width', 200),
            DTColumnBuilder.newColumn('controlMethodPersonText').withTitle('Método de Control Persona').withOption('width', 200),
            DTColumnBuilder.newColumn('measureNd').withTitle('Nivel Deficiencia (ND)').withOption('width', 200),
            DTColumnBuilder.newColumn('measureNe').withTitle('Nivel Exposición (NE)').withOption('width', 200),
            DTColumnBuilder.newColumn('measureNc').withTitle('Nivel Consecuencia (NC)').withOption('width', 200),
            DTColumnBuilder.newColumn('exposed').withTitle('Trabajadores Vinculados o en Misión').withOption('width', 200),
            DTColumnBuilder.newColumn('contractors').withTitle('Trabajadores Contratistas').withOption('width', 200),
            DTColumnBuilder.newColumn('visitors').withTitle('Visitantes').withOption('width', 200),
            DTColumnBuilder.newColumn('typeIntervention').withTitle('Tipo Medida de Intervención').withOption('width', 200),
            DTColumnBuilder.newColumn('descriptionIntervention').withTitle('Medida').withOption('width', 200),
            DTColumnBuilder.newColumn('trackingIntervention').withTitle('Seguimiento y Medición').withOption('width', 200),
            DTColumnBuilder.newColumn('observationIntervention').withTitle('Observación Intervención').withOption('width', 200),
            DTColumnBuilder.newColumn('observation').withTitle("Observación").withOption('width', 280).withOption('defaultContent', '')
        ];

        var loadRow = function () {
            angular.element("#dataCustomerConfigWizardActivityStagingDT a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                onEdit(id);
            });
        }

        $scope.dtInstanceCustomerConfigWizardActivityStagingDTCallback = function(instance) {
            $scope.dtInstanceCustomerConfigWizardActivityStagingDT = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerConfigWizardActivityStagingDT.reloadData();
        };

        var onEdit = function (id) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/sgsst/massive/activity/customer_profile_config_sgsst_wizard_tab_activity_staging_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerConfigWizardActivityStagingEditCtrl',
                scope: $scope,
                resolve: {
                    dataItem: function () {
                        return { id: id ? id : 0 };
                    },
                    isView : function() {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function (employee) {
                $scope.reloadData();
            }, function() {

            });
        };

        $scope.onCancel = function () {
            $document.scrollTop(40, 2000);
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        }

        $scope.onProcess = function () {

            SweetAlert.swal({
                title: "Confirma la importación de los registros?",
                text: "Se importarán los registros válidos. Una vez realizado este proceso no se podrán realizar cambios.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Si, confirmar!",
                cancelButtonText: "No, cancelar!",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm) {

                    return $http({
                        method: 'POST',
                        url: $exportUrl + 'api/v1/customer-config-activity-confirm',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param({
                            id: $stateParams.customerId,
                            sessionId: $scope.$parent.currentId
                        })
                    }).then(function (response) {
                        $timeout(function () {
                            SweetAlert.swal("Registro", "La información ha sido importada satisfactoriamente", "success");
                            $scope.onCancel();
                        });
                    }).catch(function (e) {
                        $log.error(e);
                        SweetAlert.swal("Error de guardado", e.data.message, "error");
                    }).finally(function () {

                    });
                }
            });
        }

    }
]);

app.controller('ModalInstanceSideCustomerConfigWizardActivityStagingEditCtrl', function ($rootScope, $stateParams, $scope, dataItem, isView, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, $document, $filter, $aside, ListService) {


    $scope.onCloseModal = function () {
        $uibModalInstance.close(null);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    getList();

    function getList() {
        var entities = [
            {name: 'config_hazard_classification', value: null},
            {name: 'config_workplace_status', value: null},
            {name: 'yes_no_letter_options', value: null},
            {name: 'config_type_measure', value: null},
            {name: 'hazard_tracking', value: null},
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.classificationList = response.data.data.configHazardClassification;
                $scope.statusList = response.data.data.config_workplace_status;
                $scope.yesNoList = response.data.data.activeOptions;
                $scope.typesMeasureList = response.data.data.config_type_measure;
                $scope.trackingInterventionList = response.data.data.hazard_tracking;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    function onDemandDescriptionAndEffect() {
        var entities = [
            {name: 'config_hazard_description', value: $scope.entity.type ? $scope.entity.type.id : null},
            {name: 'config_hazard_health_effect', value: $scope.entity.type ? $scope.entity.type.id : null},
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.descriptionList = response.data.data.configHazardDescription;
                $scope.healthEffectList = response.data.data.configHazardHealthEffect;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    function onDemandType() {
        var entities = [
            {name: 'config_hazard_type', value: $scope.entity.classification ? $scope.entity.classification.id : null},
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.typeList = response.data.data.configHazardType;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.isView = isView;

    var init = function () {
        $scope.entity = {
            id: dataItem.id,
            customerId: $scope.customerId,
            name: null,
            status: null,
            isCritital: null,
            classification: null,
            type: null,
            description: null,
            healthEffect: null,
            timeExposure: 0,
            observationHazard: null,
            controlMethodSourceText: null,
            controlMethodMediumText: null,
            controlMethodPersonText: null,
            measureNd: null,
            measureNe: null,
            measureNc: null,
            exposed: 0,
            contractors: 0,
            visitors: 0,
            typeIntervention: null,
            descriptionIntervention: null,
            trackingIntervention: null,
            observationIntervention: null,
        };
    };

    init();

    $scope.onLoadRecord = function () {
        $http({
            method: 'GET',
            url: 'api/customer-config-activity-staging/get',
            params: {
                id: $scope.entity.id
            }
        }).catch(function (e, code) {
        }).then(function (response) {
            $scope.entity = response.data.result;
            onDemandType();
            onDemandDescriptionAndEffect();
        }).finally(function () {
        });
    }

    $scope.onLoadRecord();

    $scope.onSelectClassification = function() {
        onDemandType();
    }

    $scope.onSelectType = function() {
        onDemandDescriptionAndEffect();
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
                save();
            }

        },
        reset: function (form) {

        }
    };

    var isEmptyOrNull = function(value) {
        return value == null || value.replace(/^\s+|\s+$/g, '').length == 0;
    }

    var save = function () {

        if ($scope.entity.typeIntervention != null ||
            !isEmptyOrNull($scope.entity.descriptionIntervention) ||
            $scope.entity.trackingIntervention != null ||
            !isEmptyOrNull($scope.entity.observationIntervention)) {

                var validateDateMessage = [];

                if ($scope.entity.typeIntervention == null) {
                    validateDateMessage.push('Debe seleccionar Tipo Medida de Intervención')
                }

                if (isEmptyOrNull($scope.entity.descriptionIntervention)) {
                    validateDateMessage.push('Debe ingresar Medida')
                }

                if ($scope.entity.trackingIntervention == null) {
                    validateDateMessage.push('Debe seleccionar Seguimiento y Medición')
                }

                if (isEmptyOrNull($scope.entity.observationIntervention)) {
                    validateDateMessage.push('Debe ingresar Observación')
                }

                if (validateDateMessage.length > 0) {
                    SweetAlert.swal({
                        html: true,
                        title: "Error de validación",
                        text: validateDateMessage.join('<br/>'),
                        type: "error"
                    });
                    return;
                }
        }


        var data = JSON.stringify($scope.entity);
        var req = {
            data: Base64.encode(data)
        };
        return $http({
            method: 'POST',
            url: 'api/customer-config-activity-staging/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $uibModalInstance.close(null);
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    };
});
