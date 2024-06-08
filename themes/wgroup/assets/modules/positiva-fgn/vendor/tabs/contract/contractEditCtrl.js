'use strict';
/**
  * controller for Customers
*/
app.controller('contractEditCtrl',
function ($rootScope, $stateParams, $scope, $log, DTOptionsBuilder, DTColumnBuilder, $timeout, SweetAlert, $http, $compile, $state) {

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
    };

    var initialize = function() {
        $scope.entity = {
            id: 0,
            vendorId: $stateParams.vendorId,
            contractNumber: null,
            startDate: null,
            endDate: null,
            contractValue: null,
            isActive: true
        }
    }
    initialize();


    $scope.form = {
        submit: function (form) {
            var firstError = null;
            $scope.Form = form;
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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
                return;
            } else {
                save();
            }
        },
        reset: function () {
            $scope.Form.$setPristine(true);
            initialize();
        }
    };


    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/positiva-fgn-vendor-contract/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $scope.form.reset();
            $scope.reloadData();
            SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
        }).catch(function(e){
            $log.error(e);
            SweetAlert.swal("Error de guardado", e.data.message , "error");
        });

    };

    $scope.onLoadRecord = function (){
        if($scope.entity.id > 0) {
            var req = {
                id: $scope.entity.id,
            };
            $http({
                method: 'GET',
                url: 'api/positiva-fgn-vendor-contract/get',
                params: req
            })
            .catch(function(e, code){
              SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
            })
            .then(function (response) {
                $timeout(function(){
                    $scope.entity = response.data.result;
                });
            });
        }
    }


  $scope.dtInstancePositivaFgnVendorContract = {};
	$scope.dtOptionsPositivaFgnVendorContract = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.vendorId = $stateParams.vendorId;
                return JSON.stringify(d);
            },
            url: 'api/positiva-fgn-vendor-contract',
            type: 'POST',
            beforeSend: function () {
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return true;
        })
        .withOption('fnDrawCallback', function () {
            loadRow();
        })
        .withOption('language', {
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsPositivaFgnVendorContract = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
            .renderWith(function (data) {
                var actions = "";
                var disabled = "";

                var edit = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var drop = '<a class="btn btn-danger btn-xs dropRow lnk" href="#"  uib-tooltip="Borrar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-trash"></i></a> ';

                actions += edit;
                actions += drop;
                return actions;

            }),

            DTColumnBuilder.newColumn('contractNumber').withTitle("Número de Contrato").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('startDate').withTitle("Fecha Inicio").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('endDate').withTitle("Fecha Finalización").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('contractValue').withTitle("Valor Contrato").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data) {
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

    var loadRow = function () {
        $("#dtPositivaFgnVendorContract a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.entity.id = id;
            $scope.onLoadRecord();
        });

        $("#dtPositivaFgnVendorContract a.dropRow").on("click", function () {
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
            function (isConfirm) {
                if (isConfirm) {
                    var req = {};
                    req.id = id;
                    $http({
                        method: 'POST',
                        url: 'api/positiva-fgn-vendor-contract/delete',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param(req)
                    }).then(function (response) {
                        SweetAlert.swal("Proceso Exitoso", "Se ha eliminado correctamente el registro.", "success");
                    }).catch(function (response) {                                
                        SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                    }).finally(function () {
                        $scope.reloadData();
                    });

                }
            });
        });

    };

    $scope.reloadData = function () {
        $scope.dtInstancePositivaFgnVendorContract.reloadData();
    };


    $scope.onCancel = function () {
        $scope.form.reset();
    };

});