'use strict';
/**
  * controller for Customers
*/
app.controller('coverageEditCtrl',
function ($rootScope, $stateParams, $scope, $log, DTOptionsBuilder, DTColumnBuilder, $timeout, SweetAlert, $http, $compile, ListService, $state) {

    $scope.regionalList = [];
    $scope.sectionalList = [];
    $scope.departmentList = [];
    $scope.townList = [];

    var initialize = function() {
        $scope.entity = {
            id: 0,
            vendorId: $stateParams.vendorId,
            regional: null,
            sectional: null,
            department: null,
            town: null
        }
    }
    initialize();

    function getList() {
        var entities = [
            {name: 'positiva_fgn_consultant_sectional', criteria: { regionalId: $scope.entity.regional ? $scope.entity.regional.value : null } }
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.regionalList = response.data.data.regionalList;
                $scope.sectionalList = response.data.data.sectionalList;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }
    getList();

    $scope.getDepartments = function () {
        var req = {
            cid: 68
        };
        $http({
            method: 'GET',
            url: 'api/states',
            params: req
        }).then(function (response) {
            $scope.departmentList = response.data.result;
        });
    };
    $scope.getDepartments();

    $scope.changeDepartment = function (item, refreshTown) {
        $scope.townList = [];

        if(refreshTown) {
            $scope.entity.town = null;
        }

        var req = {
            sid: item.id
        };

        $http({
            method: 'GET',
            url: 'api/towns',
            params: req
        }).then(function (response) {
            $scope.townList = response.data.result;
        });

    };

    $scope.filterSectional = function() {
        $scope.entity.sectional = null;
        getList();
    }

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
            url: 'api/positiva-fgn-vendor-coverage/save',
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
                url: 'api/positiva-fgn-vendor-coverage/get',
                params: req
            })
            .catch(function(e, code){
              SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
            })
            .then(function (response) {
                $timeout(function(){
                    $scope.entity = response.data.result;
                    $scope.changeDepartment($scope.entity.department, false);
                });
            });
        }
    }


  $scope.dtInstancePositivaFgnVendorCoverage = {};
	$scope.dtOptionsPositivaFgnVendorCoverage = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.vendorId = $stateParams.vendorId;
                return JSON.stringify(d);
            },
            url: 'api/positiva-fgn-vendor-coverage',
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

    $scope.dtColumnsPositivaFgnVendorCoverage = [
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

            DTColumnBuilder.newColumn('regional').withTitle("Regional").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('sectional').withTitle("Seccional").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('department').withTitle("Departamento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('town').withTitle("Municipio").withOption('width', 200).withOption('defaultContent', ''),
    ];

    var loadRow = function () {
        $("#dtPositivaFgnVendorCoverage a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.entity.id = id;
            $scope.onLoadRecord();
        });

        $("#dtPositivaFgnVendorCoverage a.dropRow").on("click", function () {
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
                        url: 'api/positiva-fgn-vendor-coverage/delete',
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
        $scope.dtInstancePositivaFgnVendorCoverage.reloadData();
    };


    $scope.onCancel = function () {
        $scope.form.reset();
    };

});