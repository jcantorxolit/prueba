'use strict';
/**
 * controller for configListCtrl
 */
app.controller('configListCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, toaster, $state, $rootScope, $timeout, $http, $aside) {

        var storeDatatable = 'configListCtrl-' + window.currentUser.id;
        $scope.dtInstancePositivaFgn = {};
        $scope.dtOptionsPositivaFgn = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-config',
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
                [0, 'asc']
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
            .withOption('createdRow', function(row) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsPositivaFgn = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 90).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var disabled = "";

                var edit = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var activity = '<a class="btn btn-blue btn-xs activityRow lnk" href="#"  uib-tooltip="Continuar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-play-circle"></i></a> ';
                var view = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-eye"></i></a> ';

                if (data.isActive != "Inactivo") {
                    if ($rootScope.can("positiva_fgn_periods_create_edit")) {
                        actions += edit;
                    }

                    actions += activity;
                }

                actions += view;

                return actions;

            }),

            DTColumnBuilder.newColumn('period').withTitle("Periodo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('startDate').withTitle("Fecha Inicio").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('endDate').withTitle("Fecha Fin").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function(data) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isActive != null || data.isActive != undefined) {
                    if (data.isActive == 'Activo') {
                        label = 'label label-success';
                        text = 'Activo';
                    }
                }

                return '<span class="' + label + '">' + text + '</span>';
            }),
        ];

        var loadRow = function() {
            $("#dtPositivaFgn a.editRow").on("click", function() {
                var id = $(this).data("id");
                $localStorage.isView = false;
                $scope.onCreate(id, false);
            });

            $("#dtPositivaFgn a.viewRow").on("click", function() {
                var id = $(this).data("id");
                $localStorage.isView = true;
                $scope.onNext(id);
            });

            $("#dtPositivaFgn a.activityRow").on("click", function() {
                var id = $(this).data("id");
                $localStorage.isView = false;
                $scope.onNext(id);
            });

        };

        $scope.reloadData = function() {
            $scope.dtInstancePositivaFgn.reloadData();
        };

        $scope.onNext = function(id) {
            $state.go("app.positiva-fgn.fgn-activity-list", { "configId": id });
        };

        $scope.onCreate = function(id) {
            $stateParams.configId = id;
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/fgn/config/config_edit.htm",
                placement: 'right',
                backdrop: 'static',
                size: 'lg',
                controller: "configEditCtrl",
                scope: $scope
            });
            modalInstance.result.then(function() {
                $scope.reloadData();
            });
        }

    });
