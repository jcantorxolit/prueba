'use strict';
/**
 * controller for Customers
 */
app.controller('SectionalProfessionalModalCtrl', function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, toaster, $state, $rootScope, $timeout, $http, SweetAlert, $aside, $document, ListService, $uibModalInstance) {

    var initialize = function() {
        $scope.entity = {
            id: $scope.professionalId || 0,
            sectionalId: $scope.sectionalId,
            isActiveProfessional: true,
            documentType: null,
            documentNumber: null,
            fullName: null,
            job: null,
            telephone: null,
            email: null
        }
    }
    initialize();

    var storeDatatable = 'sectionalProfessionalCtrl-' + window.currentUser.id;
    $scope.dtIstanceProfessionalSectional = {};
    $scope.dtOptionsProfessionalSectional = DTOptionsBuilder.newOptions().withBootstrap()
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
            url: 'api/positiva-fgn-sectional/professional',
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

    $scope.dtColumnsProfessionalSectional = [
        DTColumnBuilder.newColumn(null).withTitle("Eliminar").withOption('width', 120).notSortable()
        .renderWith(function(data) {
            var actions = "";
            var disabled = "";
            var add = '<div align="center">' +
                '<a class="btn btn-danger btn-xs deleteProfessional lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '" ' + disabled + '>' +
                '<i class="fa fa-edit"></i>' +
                    '<i class="fas fa-trash"></i>' +
                '</a>' +
            '</div>';
            actions += add;
            return actions;
        }),
        DTColumnBuilder.newColumn('documentType').withTitle("Tipo documento").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('documentNumber').withTitle("# Documento").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('fullName').withTitle("Profesional").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 200)
        .renderWith(function(data) {
            var label = 'label label-danger';
            var text = data;
            if (data == 'Activo') {
                label = 'label label-success';
            }
            return '<span class="' + label + '">' + text + '</span>';
        }),
    ];

    var loadRow = function() {
        $("#dtProfessionalSectional a.deleteProfessional").on("click", function() {
            var id = $(this).data("id");
            onDeleteSectional(id);
        });
    };

    var onDeleteSectional = function(id) {
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
                        url: 'api/positiva-fgn-professional/deleteSectional',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        data: $.param(req)
                    }).then(function(response) {
                        SweetAlert.swal("Proceso Exitoso", "Se ha eliminado correctamente el registro.", "success");
                    }).catch(function(response) {
                        SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                    }).finally(function() {
                        $scope.dtIstanceProfessionalSectional.reloadData();
                    });
                }
            });
    }


    $scope.onCancel = function() {
        $uibModalInstance.close(1);
    };


    $scope.onSelectProfessional = function() {
        var modalListProfessionalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/sectionals/sectional_professional_list.htm",
            placement: 'right',
            windowTopClass: 'top-modal',
            backdrop: 'static',
            size: 'lg',
            controller: 'sectionalProfessionalListCtrl',
            scope: $scope
        });

        modalListProfessionalInstance.result.then(function(result) {
            $scope.entity = {
                id: null,
                modulo: 'seccionales',
                professionalId: result.id,
                isActive: result.isActive,
                documentType: result.documentType,
                documentNumber: result.documentNumber,
                fullName: result.fullName,
                job: result.job,
                telephone: result.telephone,
                email: result.email,
                sectionalId: $scope.sectionalId,
            }
        })
    }

    $scope.onCancel = function() {
        $uibModalInstance.close(1);
    };

    $scope.formProfessional = {
        submit: function(formProfessional) {
            $scope.FormProfessional = formProfessional;
            if (formProfessional.$valid) {
                saveProfessional();
                return;
            }

            var field = null,
                firstError = null;
            for (field in formProfessional) {
                if (field[0] != '$') {
                    if (firstError === null && !formProfessional[field].$valid) {
                        firstError = formProfessional[field].$name;
                    }

                    if (formProfessional[field].$pristine) {
                        formProfessional[field].$dirty = true;
                    }
                }
            }

            angular.element('.ng-invalid[name=' + firstError + ']').focus();
            SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
        },
        reset: function() {
            $scope.FormProfessional.$setPristine(true);
            initialize();
        }
    };

    var saveProfessional = function() {
        var data = JSON.stringify($scope.entity);
        var req = {
            data: Base64.encode(data)
        };
        return $http({
            method: 'POST',
            url: 'api/positiva-fgn-professional/saveSectional',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            if (response.data.result.id != '') {
                $scope.formProfessional.reset();
                SweetAlert.swal("Proceso exitoso", "Se ha almacenado correctamente la información.", "success");
            }
        }).catch(function(e) {
            SweetAlert.swal("Error de guardado", e.data.message, "error");
        }).finally(function() {
            $scope.dtIstanceProfessionalSectional.reloadData();
        });
    };
});
