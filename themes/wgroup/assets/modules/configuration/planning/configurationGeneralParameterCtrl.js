'use strict';
/**
  * controller for Customers
*/
app.controller('configurationGeneralParameterCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','$http','SweetAlert' , '$aside', '$document',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
          $rootScope,$timeout, $http, SweetAlert, $aside, $document) {

    var log = $log;

        log.info("loading..configurationGeneralParameterCtrl ");

    $scope.loading = true;
    $scope.isView = false;
    $scope.parameters = [];

    $scope.filter = {
        selectedParameter: null
    };

    var initialize = function()
    {
        $scope.request = {};

        $scope.parameter = {
            id: 0,
            namespace:"wgroup",
            group:"",
            item:"",
            value:""
        };
    };

    var fillList = function()
    {
        $http({
            method: 'POST',
            url: 'api/system-parameter/group',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        }).then(function (response) {
            $timeout(function () {
                $scope.parameters = response.data.data;
            });
        }).catch(function (e) {
        }).finally(function () {
        });
    }

    initialize();
    fillList();

    $scope.onLoadRecord = function ()
    {
        if ($scope.parameter.id != 0) {
            var req = {
                id: $scope.parameter.id,
            };
            $http({
                method: 'GET',
                url: 'api/system-parameter',
                params: req
            })
                .catch(function(e, code){
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () { $state.go(messagered); }, 3000);
                    } else if (code == 404)
                    {
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function(){
                        $scope.parameter = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function(){
                        $scope.loading =  false;
                    }, 400);

                    $timeout(function () {
                        $document.scrollTop(40, 2000);
                    });
                });


        } else {
            $scope.loading = false;
        }
    }

    $scope.onLoadRecord();

    $scope.master = $scope.parameter;
    $scope.form = {

        submit: function (form) {
            var firstError = null;

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
                log.info($scope.parameter);
                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                SweetAlert.swal("Validación exitosa", "Guardando información del parametero...", "success");
                //your code for submit
                save();
            }

        },
        reset: function (form) {
            $scope.parameter = angular.copy($scope.master);
            form.$setPristine(true);
        }
    };

    $scope.onCancel = function(){

        $timeout(function () {
            initialize();
        }, 30);

        $scope.isView = false;
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.parameter);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/system-parameter/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function(){
                $scope.parameter = response.data.result;
                $scope.reloadData();
            });
        }).catch(function(e){
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function(){
            $scope.onCancel();
        });

    };

    $scope.changeParameter = function (item, model) {
        $timeout(function () {
            $scope.request.operation = "parameter";
            $scope.request.namespace = "wgroup";
            $scope.request.group = item.value;
            $scope.parameter.group = item.value;
            $scope.reloadData();
        });
    };

    $scope.clearParameter = function() {
        $timeout(function () {
            $scope.filter.selectedParameter = null;
            $scope.request.operation = "parameter";
            $scope.request.namespace = "wgroup";
            $scope.request.group = "";
            $scope.parameter.group = "";
            $scope.reloadData();
        });
    }


    // Datatable configuration
    $scope.request.operation = "parameter";
    $scope.request.namespace = "wgroup";
    $scope.request.group = "";

    $scope.dtInstanceGeneralParameter = {};
		$scope.dtOptionsGeneralParameter = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: $scope.request,
            url: 'api/system-parameter',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {
            //log.info("fnDrawCallback");
            loadRow();
            //Pace.stop();

        })
        /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })

        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {

           // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsGeneralParameter = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-edit"></i></a> ';

                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';

                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if($rootScope.can("clientes_view")){
                    actions += viewTemplate;
                }

                if($rootScope.can("clientes_edit")){
                    actions += editTemplate;
                }

                if($rootScope.can("clientes_delete")){
                    actions += deleteTemplate;
                }


                return actions;
            }),

        DTColumnBuilder.newColumn('value').withTitle("Código").withOption('width', 200),
        DTColumnBuilder.newColumn('item').withTitle("Descripción")
    ];

    var loadRow = function () {

        $("#dtGeneralParameter a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editGeneralParameter(id);
        });

        $("#dtGeneralParameter a.viewRow").on("click", function () {
            var id = $(this).data("id");
            $scope.parameter.id = id;
            $scope.viewGeneralParameter(id);

        });

        $("#dtGeneralParameter a.delRow").on("click", function () {
            var id = $(this).data("id");

            // Aqui se debe hacer la redireccion al formulario de edicion del customer
            log.info("intenta eliminar el registro: " + id);

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
                            url: 'api/system-parameter/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function(e){
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        }).finally(function(){

                            $scope.reloadData();
                        });

                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceGeneralParameter.reloadData();
    };

    $scope.viewGeneralParameter = function (id) {
        $scope.parameter.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editGeneralParameter = function(id){
        $scope.parameter.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };


}]);
