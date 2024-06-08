'use strict';
/**
 * controller for Customers
 */
app.controller('ModalInstanceSideCustomerConfigJobActivityHazardCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document',
    '$aside', '$location', 'ListService', '$filter', '$uibModalInstance', '$uibModal', 'activity',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $aside, $location, ListService, $filter,
              $uibModalInstance, $uibModal, activity) {

        var $formInstance = null;

        $scope.relation = activity;

        var init = function() {
            $scope.entity = {
                id: 0,
                customerConfigJobActivityId: $scope.relation.id,
                customerConfigJobActivityHazard: null,
                jobActivityId: 0
            };

            if ($formInstance !== null) {
                $formInstance.$setPristine(true);
            }
        }

        init();

        $scope.onLoadRecordRelation = function () {
            if ($scope.relation.id != 0) {
                var req = {
                    id: $scope.relation.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer-config-job-activity/get',
                    params: req
                })
                    .catch(function (e, code) {
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.relation = response.data.result;
                            $scope.reloadDataAvailable();
                        });

                    }).finally(function () {
                    });
            } else {
                $scope.loading = false;
            }
        };

        $scope.onLoadRecordRelation();

        $scope.onCloseModal = function () {
            $uibModalInstance.close(1);
        };

        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.onClear = function () {
            init();
        }

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

                    $timeout(function () {
                        toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                    }, 500);

                    return;

                } else {
                    $scope.onSave();
                }

            },
            reset: function (form) {
                form.$setPristine(true);
            }
        };

        $scope.onSave = function () {

            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-config-job-activity-hazard-relation/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (data) {

                $timeout(function () {
                    toaster.pop("success", "Registro", "La información ha sido guardada satisfactoriamente");
                    $scope.onClear();
                    $scope.reloadData();
                    $scope.reloadDataAvailable();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.onBatch = function () {

            $scope.entity.jobActivityId = $scope.relation.activityList ? $scope.relation.activityList[0].id : 0;

            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-config-job-activity-hazard-relation/batch',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (data) {

                $timeout(function () {
                    toaster.pop("success", "Registro", "La información ha sido guardada satisfactoriamente");
                    $scope.onClear();
                    $scope.reloadData();
                    $scope.reloadDataAvailable();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        var buildDTColumn = function(field, title, defaultContent, width) {
            return DTColumnBuilder.newColumn(field)
                .withTitle(title)
                .withOption('defaultContent', defaultContent)
                .withOption('width', width);
        };


        $scope.dtOptionsCustomerConfigJobActivityHazardRelation = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerConfigJobActivityId = $scope.relation.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-config-job-activity-hazard-relation',
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


        $scope.dtColumnsCustomerConfigJobActivityHazardRelation = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = '';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash"></i></a> ';

                    actions += deleteTemplate;

                    return actions;
                }),
            buildDTColumn('classification', 'Clasificación', '', 200),
            buildDTColumn('type', 'Tipo Peligro', '', 200),
            buildDTColumn('description', 'Descripción Peligro', '', 200),
            buildDTColumn('effect', 'Efectos a la salud', '', 200),
            buildDTColumn('measureND', 'ND', '', 200),
            buildDTColumn('measureNE', 'NE', '', 200),
            buildDTColumn('measureNC', 'NC', '', 200),
            buildDTColumn('riskValue', 'Valoración del Riesgo', '', 200),
        ];

        var loadRow = function () {

            angular.element("#dtCustomerConfigJobActivityHazardRelation a.delRow").on("click", function () {
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
                                url: 'api/customer-config-job-activity-hazard-relation/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (data) {
                                swal("Eliminado", "Regitro eliminado satisfactoriamente", "info");
                            }).catch(function (response) {
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                            }).finally(function () {
                                $scope.reloadData();
                                $scope.reloadDataAvailable();
                            });

                        } else {
                            swal("Cancelado", "Operacion cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceCustomerConfigJobActivityHazardRelationCallback = function (instance) {
            $scope.dtInstanceCustomerConfigJobActivityHazardRelation = instance;
        };

        $scope.reloadData = function () {
            if ($scope.dtInstanceCustomerConfigJobActivityHazardRelation != null) {
                $scope.dtInstanceCustomerConfigJobActivityHazardRelation.reloadData();
            }
        };



        //-----------------------------------------------------------------------HAZARD AVAILABLE

        $scope.dtOptionsCustomerConfigJobActivityHazardAvailable = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.jobActivityId = $scope.relation.activityList ? $scope.relation.activityList[0].id : 0;
                    d.customerConfigJobActivityId = $scope.relation.id;
                    return JSON.stringify(d);
                },
                url: 'api/customer-config-job-activity-hazard-available',
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
                loadRowAvaiable();
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

        $scope.dtColumnsCustomerConfigJobActivityHazardAvailable = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var addTemplate = '<a class="btn btn-success btn-xs addRow lnk" href="#" uib-tooltip="Adicionar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-plus-square"></i></a> ';

                    actions += addTemplate;

                    return actions;
                }),
            buildDTColumn('classification', 'Clasificación', '', 200),
            buildDTColumn('type', 'Tipo Peligro', '', 200),
            buildDTColumn('description', 'Descripción Peligro', '', 200),
            buildDTColumn('effect', 'Efectos a la salud', '', 200),
            buildDTColumn('measureND', 'ND', '', 200),
            buildDTColumn('measureNE', 'NE', '', 200),
            buildDTColumn('measureNC', 'NC', '', 200),
            buildDTColumn('riskValue', 'Valoración del Riesgo', '', 200),
        ];

        var loadRowAvaiable = function () {
            angular.element("#dtCustomerConfigJobActivityHazardAvailable a.addRow").on("click", function () {
                if (!angular.element(this).hasClass('disabled')) {
                    angular.element(this).addClass('disabled')
                    var id = angular.element(this).data("id");
                    $scope.entity.customerConfigJobActivityHazard = { id: id };
                    $scope.onSave();
                }
            });

            angular.element("#dtCustomerConfigJobActivityHazardAvailable a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onView(id);
            });
        };

        $scope.dtInstanceCustomerConfigJobActivityHazardAvailableCallback = function (instance) {
            $scope.dtInstanceCustomerConfigJobActivityHazardAvailable = instance;
        };

        $scope.reloadDataAvailable = function () {
            if ($scope.dtInstanceCustomerConfigJobActivityHazardAvailable != null) {
                $scope.dtInstanceCustomerConfigJobActivityHazardAvailable.reloadData();
            }
        };

    }
]);
