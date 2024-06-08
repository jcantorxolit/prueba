'use strict';
/**
  * controller for Customers
*/
app.controller('configurationProjectTaskTypeListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','$http','SweetAlert' , '$aside', '$document',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
          $rootScope,$timeout, $http, SweetAlert, $aside, $document) {

    var log = $log;
    var request = {};
        log.info("loading..configurationProjectTaskTypeListCtrl ");

    $scope.loading = true;
    $scope.isView = false;

    var initialize = function()
    {
        $scope.projectTaskType = {
            id: 0,
            code:"",
            description:"",
            price: 0,
            isActive: true
        };
    };

    initialize();

    $scope.onLoadRecord = function ()
    {
        if ($scope.projectTaskType.id != 0) {
            var req = {
                id: $scope.projectTaskType.id,
            };
            $http({
                method: 'GET',
                url: 'api/project-task-type',
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
                        $scope.projectTaskType = response.data.result;
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

    $scope.master = $scope.projectTaskType;
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
                log.info($scope.projectTaskType);
                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                SweetAlert.swal("Validación exitosa", "Guardando información del projectTaskTypeo...", "success");
                //your code for submit
                save();
            }

        },
        reset: function (form) {
            $scope.projectTaskType = angular.copy($scope.master);
            form.$setPristine(true);
        }
    };

    $scope.onCancel = function(){

        $timeout(function () {
            initialize();
        }, 30);

        $scope.isView = false;
    };

    $scope.cancelEdition = function (index) {
        if ($scope.$parent != null) {
            $scope.$parent.navToSection("summary", "summary", $scope.projectTaskType_id);
        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.projectTaskType);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/project-task-type/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function(){
                $scope.projectTaskType = response.data.result;
                $scope.reloadData();
            });
        }).catch(function(e){
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function(){
            $scope.onCancel();
        });

    };

    // Datatable configuration
    request.operation = "projectTaskType";

    $scope.dtInstanceProjectTaskType = {};
		$scope.dtOptionsProjectTaskType = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/project-task-type',
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

    $scope.dtColumnsProjectTaskType = [
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

        DTColumnBuilder.newColumn('code').withTitle("Código").withOption('width', 200),
        DTColumnBuilder.newColumn('description').withTitle("Tipo Tarea"),
        DTColumnBuilder.newColumn('price').withTitle("Valor Hora").withOption('width', 200),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isActive != null || data.isActive != undefined) {
                    if (data.isActive) {
                        label = 'label label-success';
                        text = 'Activo';
                    } else {
                        label = 'label label-danger';
                        text = 'Inactivo';
                    }
                }

                var status = '<span class="' + label +'">' + text + '</span>';

                return status;
            })
    ];

    var loadRow = function () {

        $("#dtProjectTaskType a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editProjectTaskType(id);
        });

        $("#dtProjectTaskType a.viewRow").on("click", function () {
            var id = $(this).data("id");
            $scope.projectTaskType.id = id;
            $scope.viewProjectTaskType(id);

        });

        $("#dtProjectTaskType a.delRow").on("click", function () {
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
                            url: 'api/project-task-type/delete',
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
        $scope.dtInstanceProjectTaskType.reloadData();
    };

    $scope.viewProjectTaskType = function (id) {
        $scope.projectTaskType.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editProjectTaskType = function(id){
        $scope.projectTaskType.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

}]);
