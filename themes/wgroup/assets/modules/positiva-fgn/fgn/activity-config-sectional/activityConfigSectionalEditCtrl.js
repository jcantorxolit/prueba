'use strict';
/**
 * controller for PFFactivityConfigSectionalEditCtrl
 */
app.controller('PFFactivityConfigSectionalEditCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, $compile, $state, $rootScope, $timeout, $http,  $localStorage,
        SweetAlert, $document, $aside, ListService) {

        var log = $log;
        $scope.typeList = $rootScope.parameters("positiva_fgn_consultant_sectional_type");
        $scope.regionalList = [];
        $scope.sectionalList = [];
        $scope.initialEntity = {};
        $scope.isView = $localStorage.isView;

        var initialize = function() {
            $scope.entity = {
                id: 0,
                fgnActivityId: $stateParams.activityId,
                axis: null,
                action: null,
                code: null,
                name: null,
                goalAnnual: null,
                goalCoverage: 0,
                goalCompliance: 0,
                regional: null,
                sectional: null,
                details: { indicator: [] }
            };
        };
        initialize();

        function getList() {
            var entities = [
                { name: 'positiva_fgn_consultant_sectional', criteria: { regionalId: $scope.entity.regional ? $scope.entity.regional.value : null } }
            ];

            ListService.getDataList(entities)
                .then(function(response) {
                    $scope.regionalList = response.data.data.regionalList;
                    $scope.sectionalList = response.data.data.sectionalList;
                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
        getList();

        $scope.filterSectional = function() {
            $scope.entity.sectional = null;
            getList();
        }

        $scope.form = {
            submit: function(form) {
                var firstError = null;
                $scope.Form = form;
                if (form.$invalid) {

                    var field = null,
                        firstError = null;
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
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
                    return;
                } else {
                    save();
                }
            },
            reset: function() {
                $scope.Form.$setPristine(true);
                $scope.entity = angular.copy($scope.initialEntity);
            }
        };


        var save = function() {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/positiva-fgn-fgn-activity-config-sectional/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $scope.form.reset();
                $scope.reloadData();
                $scope.entity.goalCoverage = response.data.result.goalCoverage;
                $scope.entity.goalCompliance = response.data.result.goalCompliance;
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
            }).catch(function(e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message, "error");
            });

        };

        $scope.onLoadRecord = function() {
            if ($scope.entity.fgnActivityId > 0) {
                var req = {
                    id: $scope.entity.fgnActivityId,
                };
                $http({
                        method: 'GET',
                        url: 'api/positiva-fgn-fgn-activity/getClear',
                        params: req
                    })
                    .catch(function(e, code) {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
                    })
                    .then(function(response) {
                        $timeout(function() {
                            $scope.entity = response.data.result;
                            $scope.initialEntity = angular.copy(response.data.result);
                        });
                    });
            }
        }
        $scope.onLoadRecord();

        $scope.calculateGoal = function(indicator) {
            indicator.goal = 0;
            indicator.goal += parseInt(indicator.jan) > 0 ? parseInt(indicator.jan) : 0;
            indicator.goal += parseInt(indicator.feb) > 0 ? parseInt(indicator.feb) : 0;
            indicator.goal += parseInt(indicator.mar) > 0 ? parseInt(indicator.mar) : 0;
            indicator.goal += parseInt(indicator.apr) > 0 ? parseInt(indicator.apr) : 0;
            indicator.goal += parseInt(indicator.may) > 0 ? parseInt(indicator.may) : 0;
            indicator.goal += parseInt(indicator.jun) > 0 ? parseInt(indicator.jun) : 0;
            indicator.goal += parseInt(indicator.jul) > 0 ? parseInt(indicator.jul) : 0;
            indicator.goal += parseInt(indicator.aug) > 0 ? parseInt(indicator.aug) : 0;
            indicator.goal += parseInt(indicator.sep) > 0 ? parseInt(indicator.sep) : 0;
            indicator.goal += parseInt(indicator.oct) > 0 ? parseInt(indicator.oct) : 0;
            indicator.goal += parseInt(indicator.nov) > 0 ? parseInt(indicator.nov) : 0;
            indicator.goal += parseInt(indicator.dec) > 0 ? parseInt(indicator.dec) : 0;
        }

        $scope.onBack = function() {
            $state.go("app.positiva-fgn.fgn-activity-list", { "configId": $stateParams.configId });
        }

        $scope.onCancel = function() {
            $scope.form.reset();
        }


        $scope.dtInstanceActivityConfigSectional = {};
        $scope.dtOptionsActivityConfigSectional = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.fgnActivityId = $stateParams.activityId;
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-activity-config-sectional',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('fnDrawCallback', function() {
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);

            });

        $scope.dtColumnsActivityConfigSectional = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var disabled = ""

                if ($rootScope.isView) {
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Ver"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-eye"></i></a> ';
                } else {
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';
                }

                if (!$rootScope.isView) {
                    var config = '<a class="btn btn-danger btn-xs deleteSectional lnk" href="#"  uib-tooltip="Configurar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash"></i></a> ';
                }

                var configSectional = '<a class="btn btn-info btn-xs configConsultant lnk" href="#"  uib-tooltip="Configurar Asesores" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-user-circle"></i></a> ';

                actions += editTemplate;
                actions += config || '';
                actions += configSectional;
                return actions;
            }),

            DTColumnBuilder.newColumn('regional').withTitle("Regional").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('sectional').withTitle("Seccional").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCoverage').withTitle("Meta Cobertura").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCompliance').withTitle("Meta Cumplimiento").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRow = function() {

            $("#dtActivityConfigSectional a.editRow").on("click", function() {
                var id = $(this).data("id");
                $scope.onEditConfig(id);
            });

            $("#dtActivityConfigSectional a.deleteSectional").on("click", function() {
                var id = $(this).data("id");
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
                    function(isConfirm) {
                        if (isConfirm) {
                            var req = {};
                            req.id = id;
                            $http({
                                method: 'POST',
                                url: 'api/positiva-fgn-fgn-activity-config-sectional/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function(response) {
                                SweetAlert.swal("Proceso Exitoso", "Se ha eliminado correctamente el registro.", "success");
                            }).catch(function(e) {
                                SweetAlert.swal("Error en la eliminación", e.data.message, "error");
                            }).finally(function() {
                                $scope.reloadData();
                                $scope.onLoadRecord();
                            });

                        }
                    });
            });

            $("#dtActivityConfigSectional a.configConsultant").on("click", function() {
                var id = $(this).data("id");
                $scope.onConfigConsultant(id);
            });
        };

        $scope.reloadData = function() {
            $scope.dtInstanceActivityConfigSectional.reloadData();
        };


        $scope.onEditConfig = function(id) {
            var req = {
                id: id,
            };
            $http({
                    method: 'GET',
                    url: 'api/positiva-fgn-fgn-activity-config-sectional/get',
                    params: req
                })
                .catch(function(e, code) {
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
                })
                .then(function(response) {
                    $timeout(function() {
                        $scope.entity.id = response.data.result.id;
                        $scope.entity.sectional = response.data.result.sectional;
                        $scope.entity.regional = response.data.result.regional;
                        $scope.entity.details.indicator = response.data.result.details.indicator;
                    });
                });
        }


        $scope.onConfigConsultant = function(id) {
            $scope.sectionalRelationId = id;
            $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/fgn/activity-config-sectional/config_consultant_edit.htm",
                placement: 'right',
                backdrop: 'static',
                size: 'lg',
                controller: 'PFFconfigConsultantEditCtrl',
                scope: $scope
            });
        }

        $scope.onEdit = function() {
            $state.go("app.positiva-fgn.fgn-activity-edit", { "configId": $stateParams.configId, "activityId": $scope.entity.fgnActivityId });
        }

        $scope.onConfig = function() {
            $state.go("app.positiva-fgn.fgn-activity-config", { "configId": $stateParams.configId, "activityId": $scope.entity.fgnActivityId });
        }

    });