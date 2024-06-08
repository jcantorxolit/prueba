'use strict';
/**
 * controller for indicatorsPFgnListCtrl
 */
app.controller('indicatorsPFgnListCtrl',
    function($scope, DTOptionsBuilder, DTColumnBuilder, $localStorage, $compile, $state) {

        var storeDatatable = 'indicatorsPFgnListCtrl-' + window.currentUser.id;
        $scope.dtInstancePositivaFgnIndicators = {};
        $scope.dtOptionsPositivaFgnIndicators = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-indicator',
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
            .withOption('createdRow', function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsPositivaFgnIndicators = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 90).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var edit = '<a class="btn btn-blue btn-xs playRow lnk" href="#" uib-tooltip="Ir a reporte"  data-id="' + data.id + '" data-code="' + data.code + '" data-title="' + data.title + '" data-description="' + data.description + '">' +
                                '<i class="fa fa-play-circle"></i>' +
                            '</a>';
                actions += edit;
                return actions;
            }),

            DTColumnBuilder.newColumn('title').withTitle("Indicador").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function(data) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data != null || data != undefined) {
                    if (data.isActive == 1) {
                        label = 'label label-success';
                        text = 'Activo';
                    }
                }
                return '<span class="' + label + '">' + text + '</span>';
            }),
        ];

        var loadRow = function() {
            $("#dtPositivaFgnIndicators a.playRow").on("click", function() {
                var code = $(this).data("code");
                var title = $(this).data("title");
                var description = $(this).data("description");
                $localStorage['pfgnInficatorActive' + code] = {
                    title: title,
                    description: description
                }
                $state.go("app.positiva-fgn.fgn-indicators-report", { "id": code });
            });
        };
    });
