'use strict';
/**
 * controller for Professionals
 */
app.controller('sectionalProfessionalListCtrl', function($scope, $stateParams, $log, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, toaster, $state, $rootScope, $timeout, $http, SweetAlert, $aside, $document, ListService) {
    $scope.addFilter = function() {
        if ($scope.audit.filters == null) {
            $scope.audit.filters = [];
        }
        $scope.audit.filters.push({
            id: 0,
            field: null,
            criteria: $scope.criteria.length > 0 ? $scope.criteria[1] : null,
            condition: $scope.conditions.length > 0 ? $scope.conditions[0] : null,
            value: ""
        });
    };

    $scope.onFilter = function() {
        $scope.reloadData();
    }

    $scope.removeFilter = function(index) {
        $scope.audit.filters.splice(index, 1);
    }

    $scope.onCleanFilter = function() {
        $scope.audit.filters = [];
        $scope.reloadData()
    }

    var storeDatatable = 'sectionalProfessionalListCtrl-' + window.currentUser.id;
    $scope.dtInstanceProfessionalList = {};
    $scope.dtOptionsProfessionalList = DTOptionsBuilder.newOptions().withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                    d.filter = {
                        filters: $scope.audit.filters.filter(function(filter) {
                            return filter != null && filter.field != null && filter.criteria != null;
                        }).map(function(filter, index, array) {
                            return {
                                field: filter.field.name,
                                operator: filter.criteria.value,
                                value: filter.value,
                                condition: filter.condition.value,
                            };
                        })
                    };
                }
                d.sectionalId = $scope.entity.sectionalId;
                return JSON.stringify(d);
            },
            url: 'api/positiva-fgn-sectional/listProfessionals',
            type: 'POST',
            beforeSend: function() {},
            complete: function() {}
        }).withDataProp('data')
        .withOption('stateSave', true)
        .withOption('stateSaveCallback', function(settings, data) {
            $localStorage[storeDatatable] = data;
        })
        .withOption('stateLoadCallback', function() {
            return $localStorage[storeDatatable];
        })
        .withOption('order', [
            [0, 'desc']
        ]).withOption('serverSide', true).withOption('processing', true).withOption('fnPreDrawCallback', function() {
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

    $scope.dtColumnsProfessionalList = [
        DTColumnBuilder.newColumn(null).withTitle("Añadir").withOption('width', 120).notSortable()
        .renderWith(function(data) {
            var actions = "";
            var disabled = "";
            var add = '<div align="center">' +
                '<a class="btn btn-success btn-xs addProfessional lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '" ' + disabled + '>' +
                    '<i class="fas fa-plus"></i>' +
                '</a>'
            '</div>';
            actions += add;
            return actions;
        }),

        DTColumnBuilder.newColumn('documentType').withTitle("Tipo Documento").withOption('width', 150).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('documentNumber').withTitle("# Documento").withOption('width', 170).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('fullName').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', ''),
    ];

    var loadRow = function() {
        $("#dtProfessionalList a.addProfessional").on("click", function() {
            var id = $(this).data("id");
            getProfessional(id);
        });
    };

    var getProfessional = function(id) {
        var req = {
            id: id
        };
        if (!id) {
            return;
        }
        $http({
            method: 'GET',
            url: 'api/positiva-fgn-professional/get',
            params: req
        }).catch(function(e, code) {
            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
        }).then(function(response) {
            $timeout(function() {
                $uibModalInstance.close(response.data.result);
            });
        });
    }

    $scope.onCancel = function() {
        $uibModalInstance.close(1);
    };
});
