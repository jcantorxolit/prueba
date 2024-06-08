'use strict';
/**
 * controller for Customers
 */
app.controller('consultantAssignmentCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, toaster, $state, $rootScope, $timeout, $http, SweetAlert, $aside, $document, ListService, $cookies) {

        $scope.regionalList = [];
        $scope.sectionalList = [];
        $scope.strategyList = $rootScope.parameters("positiva_fgn_consultant_strategy");

        var initialize = function() {
            $scope.entity = {
                regional: null,
                sectional: null,
                strategy: null
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
                    $scope.status = "Unable to load customer data: " + error.message;
                });
        }
        getList();

        $scope.filterSectional = function() {
            $scope.entity.sectional = null;
            getList();
        };

        $scope.form = {
            submit: function(form) {
                $scope.Form = form;
            },
            reset: function() {
                $scope.Form.$setPristine(true);
                initialize();
            }
        };

        var storeDatatable = 'consultantAssignmentListCtrl-' + window.currentUser.id;
        $scope.dtInstancePositivaFgn = {};
        $scope.dtOptionsPositivaFgn = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    if ($scope.entity.regional) {
                        d.regionalVal = $scope.entity.regional.value;
                    }
                    if ($scope.entity.sectional) {
                        d.sectionalVal = $scope.entity.sectional.value;
                    }
                    if ($scope.entity.strategy) {
                        d.strategyVal = $scope.entity.strategy.value;
                    }
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-management/filterAssignment',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function(settings, data) {
                $localStorage[storeDatatable] = data;
            })
            .withOption('stateLoadCallback', function() {
                return $localStorage[storeDatatable];
            })
            .withOption('order', [
                [1, 'asc']
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

        $scope.dtColumnsPositivaFgn = [
            DTColumnBuilder.newColumn(null).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var disabled = "";
                var configConsultant = '<a class="btn btn-info btn-xs configConsultant lnk margin-top-5 " align="center" href="#"  uib-tooltip="Configurar Asesores" data-id="' + data.id + '" data-activityid="' + data.activityId + '" ' + disabled + ' >' +
                    '   <i class="fa fa-user-circle"></i></a> ';
                var openConfig = '<a class="btn btn-blue btn-xs openConfig lnk margin-top-5 " align="center" href="#"  uib-tooltip="Actividades" data-configid="' + data.configId + '"  ' + disabled + ' >' +
                    '   <i class="fa fa-play-circle"></i></a> ';
                var openConfigSectional = '<a class="btn btn-info btn-xs openConfigSectional lnk margin-top-5 " align="center" href="#"  uib-tooltip="Seccionales" data-configid="' + data.configId + '" data-activityid="' + data.activityId + '" ' + disabled + ' >' +
                    '   <i class="fa fa-th-list"></i></a> ';

                actions += configConsultant + "</br>";
                actions += openConfig + "</br>";
                actions += openConfigSectional + "</br>";
                return actions;
            }),

            DTColumnBuilder.newColumn('axis').withTitle("Eje").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('action').withTitle("Acción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activityCodeFgn').withTitle("Código").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activity').withTitle("Actividad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('strategy').withTitle("Estrategia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activityCode').withTitle("Cod. Actividad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activityGestpos').withTitle("Act. CREA").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('task').withTitle("Tarea").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('regional').withTitle("Regional").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('sectional').withTitle("Seccional").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCoverage').withTitle("Meta Cobertura").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('pendingCoverage').withTitle("Por Asignar").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('assignedCoverage').withTitle("Asignado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCompliance').withTitle("Meta Cumplimiento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('pendingCompliance').withTitle("Por Asignar").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('assignedCompliance').withTitle("Asignado").withOption('width', 200).withOption('defaultContent', ''),
        ];

        $scope.reloadData = function() {
            $scope.dtInstancePositivaFgn.reloadData();
        };

        $scope.onFilter = function() {
            $scope.reloadData();
        }

        var loadRow = function() {
            $("#dtPositivaFgn a.configConsultant").on("click", function() {
                var id = $(this).data("id");
                var activityId = $(this).data("activityid");
                $stateParams.activityId = activityId;
                $scope.onConfigConsultant(id);
            });

            $("#dtPositivaFgn a.openConfig").on("click", function() {
                var configId = $(this).data("configid");
                var $url = $state.href("app.positiva-fgn.fgn-activity-list", { "configId": configId });
                window.open($url, '_blank');

            });

            $("#dtPositivaFgn a.openConfigSectional").on("click", function() {
                var configId = $(this).data("configid");
                var activityId = $(this).data("activityid");
                var $url = $state.href("app.positiva-fgn.fgn-activity-config-sectional", { "configId": configId, "activityId": activityId });
                window.open($url, '_blank');
            });
        };

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

    });