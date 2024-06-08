'use strict';
/**
 * controller for evidenceListCtrl
 */
app.controller('evidenceListCtrl', function($rootScope, $stateParams, $scope, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $log, $timeout, SweetAlert, $http, $compile) {

    $scope.evidenceList = $rootScope.parameters("positiva_fgn_gestpos_evidence");


    var initialize = function() {
        $scope.entity = {
            taskId: 0,
            gestposId: $stateParams.gestposId,
            evidence: null,
            isRequired: null
        }
    }
    initialize();

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
            initialize();
        }
    };


    var save = function() {
        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/positiva-fgn-gestpos-activity-evidence/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            $scope.form.reset();
            $scope.reloadData();
            SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
        }).catch(function(e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", e.data.message, "error");
        });
    };

    $scope.onViewDependents = function() {
        $scope.reloadData();
    }

    var storeDatatable = 'evidenceListCtrl-' + window.currentUser.id;
    $scope.dtInstanceEvidence = {};
    $scope.dtOptionsEvidence = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.gestposId = $stateParams.gestposId;
                d.showDependents = $scope.entity.showDependents;
                return JSON.stringify(d);
            },
            url: 'api/positiva-fgn-gestpos-activity-evidence/',
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
        .withOption('bFilter', false)
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

        });;

    $scope.dtColumnsEvidence = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
        .renderWith(function(data) {
            var actions = "";
            var disabled = "";
            var drop = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar"  data-id="' + data.id + '"' + disabled + ' >' +
                '   <i class="fa fa-trash"></i></a> ';
            actions += drop;
            return actions;
        }),

        DTColumnBuilder.newColumn('evidence').withTitle("Evidencia").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('isRequired').withTitle("Requerido").withOption('width', 200).withOption('defaultContent', ''),
    ];

    var loadRow = function() {
        $("#dtEvidence a.delRow").on("click", function() {
            var id = $(this).data("id");
            onRemove(id);
        });
    };

    $scope.reloadData = function() {
        $scope.dtInstanceEvidence.reloadData();
    };

    $scope.onCloseModal = function() {
        $uibModalInstance.close(1);
    }

    var onRemove = function(id) {
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
                        url: 'api/positiva-fgn-gestpos-activity-evidence/delete',
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

});