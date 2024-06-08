'use strict';
/**
 * controller for Customers
 */
app.controller('positivaFgnSectionalListCtrl', function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, toaster, $state,
    $rootScope, $timeout, $http, SweetAlert, $aside, $document, ListService) {

    $scope.audit = {
        fields: [],
        filters: [],
    };

    getList();

    function getList() {
        var entities = [
            { name: 'criteria_operators', value: null },
            { name: 'criteria_conditions', value: null },
            { name: 'positiva_fgn_seccionals_filter_field', value: null },
        ];

        ListService.getDataList(entities)
            .then(function(response) {
                $scope.criteria = response.data.data.criteriaOperatorList;
                $scope.conditions = response.data.data.criteriaConditionList;
                $scope.audit.fields = response.data.data.filterField;
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

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

    var storeDatatable = 'sectionalListCtrl-' + window.currentUser.id;
    $scope.dtInstancePositivaFgn = {};
    $scope.dtOptionsPositivaFgn = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
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
                return JSON.stringify(d);
            },
            url: 'api/positiva-fgn-sectional',
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

    $scope.dtColumnsPositivaFgn = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
        .renderWith(function(data) {
            var actions = "";
            var disabled = "";
            var edit = '<a class="btn btn-primary btn-xs editRow lnk" href="#"  uib-tooltip="Editar"  data-id="' + data.id + '" data-seccional="' + data.id + '" ' + disabled + '>' +
                            '<i class="fa fa-edit"></i>' +
                        '</a>';
            var drop = '<a class="btn btn-danger btn-xs dropRow lnk" href="#"  uib-tooltip="Borrar"  data-id="' + data.id + '" data-seccional="' + data.id + '" ' + disabled + '>' +
                            '<i class="fa fa-trash"></i>' +
                        '</a>';
            var addUser = '<a class="btn btn-info btn-xs addUser lnk" href="#"  uib-tooltip="Configurar Profesionales" data-id="' + data.id + '" data-seccional="' + data.idSeccional + '" ' + disabled + '>' +
                                '<i class="fa fa-user-plus"></i>' +
                            '</a>';
            actions += edit;
            actions += drop;
            actions += addUser;
            return actions;
        }),
        DTColumnBuilder.newColumn('regional').withTitle("Regional").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('nit').withTitle("Nit").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('name').withTitle("Seccional").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 200)
        .renderWith(function(data, type, full, meta) {
            var label = 'label label-danger';
            var text = 'Inactivo';

            if (data == 1) {
                label = 'label label-success';
                text = 'Activo';
            }
            return '<span class="' + label + '">' + text + '</span>';
        }),
    ];

    var loadRow = function() {
        $("#dtPositivaFgn a.editRow").on("click", function() {
            var id = $(this).data("id");
            onEdit(id);
        });

        $("#dtPositivaFgn a.dropRow").on("click", function() {
            var id = $(this).data("id");
            onDelete(id);
        });

        $("#dtPositivaFgn a.addUser").on("click", function() {
            var id = $(this).data("id");
            var regional = $(this).data("regional");
            var seccional = $(this).data("seccional");
            onAddProfessional(id, regional, seccional);
        });
    };

    $scope.reloadData = function() {
        $scope.dtInstancePositivaFgn.reloadData();
    };

    $scope.onCreate = function() {
        onEdit(0);
    };

    var onEdit = function(id) {
        $scope.sectionalId = id;

        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/sectionals/sectional_edit_modal.htm",
            placement: 'right',
            backdrop: 'static',
            size: 'md',
            controller: 'SectionalEditModalCtrl',
            scope: $scope
        });

        modalInstance.result.then(function() {
            $scope.reloadData();
        });
    }

    var onDelete = function(id) {
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
                    var req = { id: id };
                    $http({
                        method: 'POST',
                        url: 'api/positiva-fgn-sectional/delete',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        data: $.param(req)
                    }).then(function(response) {
                        SweetAlert.swal("Proceso Exitoso", "Se ha eliminado correctamente el registro.", "success");
                    }).catch(function(response) {
                        SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                    }).finally(function() {
                        $scope.reloadData();
                    });
                }
            });
    }

    var onAddProfessional = function(seccional) {
        $scope.sectionalId = seccional;
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/sectionals/sectional_professional.htm",
            placement: 'right',
            backdrop: 'static',
            size: 'lg',
            controller: 'SectionalProfessionalModalCtrl',
            scope: $scope
        });

        modalInstance.result.then(function() {});

    }

});
