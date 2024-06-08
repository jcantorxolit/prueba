'use strict';
/**
 * controller for campusEditCtrl
 */
app.controller('positivaFgnProfessionalEditCtrl', function($rootScope, $stateParams, $scope, $log, $timeout, SweetAlert, $http, $compile, ListService, $state, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder, $cookies) {

    $scope.documentTypeList = $rootScope.parameters("employee_document_type");
    $scope.regionalList = [];
    $scope.sectionaList = [];

    var initialize = function() {
        $scope.entity = {
            id: $scope.professionalId || 0,
            isActive: true,
            documentType: null,
            documentNumber: null,
            fullName: null,
            job: null,
            telephone: null,
            email: null
        }
    }

    var initializeSectional = function() {
        $scope.entitySectional = {
            id: 0,
            professionalId: $scope.entity.id,
            modulo: 'profesionales',
            sectionalId: null,
            isActive: true
        }
    }

    initialize();
    initializeSectional();
    onLoad();

    function getList() {
        var entities = [
            { name: 'positiva_fgn_consultant_sectional', criteria: { regionalId: $scope.entitySectional.regionalId ? $scope.entitySectional.regionalId.value : null } }
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
        $scope.entitySectional.sectionalId = null;
        getList();
    }


    $scope.formInsertProfessional = {
        submit: function(formInsertProfessional) {
            $scope.FormInsertProfessional = formInsertProfessional;
            if (formInsertProfessional.$valid) {
                saveInsertProfessional();
                return;
            }

            var field = null,
                firstError = null;
            for (field in formInsertProfessional) {
                if (field[0] != '$') {
                    if (firstError === null && !formInsertProfessional[field].$valid) {
                        firstError = formInsertProfessional[field].$name;
                    }

                    if (formInsertProfessional[field].$pristine) {
                        formInsertProfessional[field].$dirty = true;
                    }
                }
            }

            angular.element('.ng-invalid[name=' + firstError + ']').focus();
            SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
        },
        reset: function() {
            $scope.FormInsertProfessional.$setPristine(true);
            initialize();
        }
    };

    var saveInsertProfessional = function() {
        var data = JSON.stringify($scope.entity);
        var req = {
            data: Base64.encode(data)
        };

        return $http({
            method: 'POST',
            url: 'api/positiva-fgn-professional/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            $scope.entity.id = response.data.result.id;
            SweetAlert.swal("Proceso exitoso", "Se ha almacenado correctamente la información.", "success");
            $scope.onBack();
        }).catch(function(e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        });
    };

    function onLoad() {
        if (!$scope.entity.id) {
            return;
        }

        var req = {
            id: $scope.entity.id,
        };

        $http({
            method: 'GET',
            url: 'api/positiva-fgn-professional/get',
            params: req
        }).catch(function(e, code) {
            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
        }).then(function(response) {
            $timeout(function() {
                $scope.entity = response.data.result;
                console.log(response.data.result);
            });
        });
    }

    $scope.onBack = function() {
        $uibModalInstance.close(1);
    };


    $scope.formSecXProfe = {
        submit: function(formSecXProfe) {
            $scope.FormSectional = formSecXProfe;
            if (formSecXProfe.$valid) {
                saveSectional();
                return;
            }

            var field = null,
                firstError = null;
            for (field in formSecXProfe) {
                if (field[0] != '$') {
                    if (firstError === null && !formSecXProfe[field].$valid) {
                        firstError = formSecXProfe[field].$name;
                    }

                    if (formSecXProfe[field].$pristine) {
                        formSecXProfe[field].$dirty = true;
                    }
                }
            }
            angular.element('.ng-invalid[name=' + firstError + ']').focus();
            SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
        },
        reset: function() {
            $scope.FormSectional.$setPristine(true);
            initializeSectional();
        }
    };

    var saveSectional = function() {
        var data = JSON.stringify($scope.entitySectional);

        var req = {
            data: Base64.encode(data)
        };

        return $http({
            method: 'POST',
            url: 'api/positiva-fgn-professional/saveSectional',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            $scope.formSecXProfe.reset();
            $scope.dtInstancePositivaFgn.reloadData();
        }).catch(function(e) {
            SweetAlert.swal("Error de guardado", e.data.message, "error");
        });
    };

    $scope.dtInstanceSectionalFgn = {};
    $scope.dtOptionsSectionalFgn = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.professionalId = $scope.entity.id;
                return JSON.stringify(d);
            },
            url: 'api/positiva-fgn-professional/listSectional',
            type: 'POST',
            beforeSend: function() {},
            complete: function() {}
        })
        .withDataProp('data')
        .withOption('stateSave', true)
        .withOption('stateSaveCallback', function(settings, data) {
            $cookies.putObject('consultantListCtrl-' + $rootScope.$id, data);
        })
        .withOption('stateLoadCallback', function() {
            return $cookies.getObject('consultantListCtrl-' + $rootScope.$id);
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

    $scope.dtColumnsSectionalFgn = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
        .renderWith(function(data) {
            var actions = '<div align="center">' +
                        '<a class="btn btn-danger btn-xs dropRow lnk" href="#" uib-tooltip="Borrar"  data-id="' + data.id + '">' +
                                '<i class="fa fa-trash"></i>' +
                        '</a>' +
                    '</div>';
            return actions;
        }),

        DTColumnBuilder.newColumn('regional').withTitle("Regional").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('sectional').withTitle("Seccional").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 200).renderWith(function(data, type, full, meta) {
            var label = 'label label-danger';
            var text = data;
            if (data == 'Activo') {
                label = 'label label-success';
            }
            return '<span class="' + label + '">' + text + '</span>';
        }),
    ];

    var loadRow = function() {
        $("#dtInstanceSectionalFgn a.dropRow").on("click", function() {
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
                        $scope.dtInstancePositivaFgn.reloadData();
                    });
                }
            });
    };

    $scope.dtInstanceSectionalFgnCallback = function(instance) {
        $scope.dtInstancePositivaFgn = instance;
    };
});
