'use strict';
/**
  * controller for Customers
*/
app.controller('configurationDisabilityDiagnosticListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','$http','SweetAlert' , '$aside', '$document',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
          $rootScope,$timeout, $http, SweetAlert, $aside, $document) {

    var log = $log;
    var request = {};
        log.info("loading..configurationDisabilityDiagnosticListCtrl ");

    $scope.loading = true;
    $scope.isView = false;

    var initialize = function()
    {
        $scope.diagnostic = {
            id: 0,
            code:"",
            description:"",
            isActive: true
        };
    };

    initialize();

    $scope.onLoadRecord = function ()
    {
        if ($scope.diagnostic.id != 0) {
            var req = {
                id: $scope.diagnostic.id,
            };
            $http({
                method: 'GET',
                url: 'api/disability-diagnostic',
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
                        $scope.diagnostic = response.data.result;
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

    $scope.master = $scope.diagnostic;
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
                log.info($scope.diagnostic);
                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                SweetAlert.swal("Validación exitosa", "Guardando información del diagnóstico...", "success");
                //your code for submit
                save();
            }

        },
        reset: function (form) {
            $scope.diagnostic = angular.copy($scope.master);
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
            $scope.$parent.navToSection("summary", "summary", $scope.diagnostic_id);
        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.diagnostic);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/disability-diagnostic/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function(){
                $scope.diagnostic = response.data.result;
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
    $scope.dtOptionsDisabilityDiagnostic = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                return JSON.stringify(d);
            },
            url: 'api/disability-diagnostic',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[1, 'asc']])
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

    $scope.dtColumnsDisabilityDiagnostic = [
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
        DTColumnBuilder.newColumn('description').withTitle("Diagnóstico"),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';

                if (data.isActive != null || data.isActive != undefined) {
                    if (data.isActive) {
                        label = 'label label-success';
                    } else {
                        label = 'label label-danger';
                    }
                }

                var status = '<span class="' + label +'">' + data.status + '</span>';

                return status;
            })
    ];

    var loadRow = function () {

        $("#dtDisabilityDiagnostic a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editDisabilityDiagnostic(id);
        });

        $("#dtDisabilityDiagnostic a.viewRow").on("click", function () {
            var id = $(this).data("id");
            $scope.diagnostic.id = id;
            $scope.viewDisabilityDiagnostic(id);

        });

        $("#dtDisabilityDiagnostic a.delRow").on("click", function () {
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
                            url: 'api/disability-diagnostic/delete',
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

    $scope.dtInstanceDisabilityDiagnosticCallback = function (instance) {
        $scope.dtInstanceDisabilityDiagnostic = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityDiagnostic.reloadData();
    };

    $scope.viewDisabilityDiagnostic = function (id) {
        $scope.diagnostic.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityDiagnostic = function(id){
        $scope.diagnostic.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

    $scope.onUpload = function() {

        var modalInstance = $aside.open({
            templateUrl: 'app_configuration_disability_diagnostic_import.htm',
            placement: 'bottom',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideUploadDisabilityDiagnosticCtrl',
            scope: $scope,
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        });

    };

}]);

app.controller('ModalInstanceSideUploadDisabilityDiagnosticCtrl', function ($stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster) {

    var uploader = $scope.uploader = new FileUploader({
        url: 'api/disability-diagnostic/import',
        formData:[]
    });

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item/*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploader.onWhenAddingFileFailed = function (item/*{File|FileLikeObject}*/, filter, options) {
        console.info('onWhenAddingFileFailed', item, filter, options);
    };
    uploader.onAfterAddingFile = function (fileItem) {
        console.info('onAfterAddingFile', fileItem);
    };
    uploader.onAfterAddingAll = function (addedFileItems) {
        console.info('onAfterAddingAll', addedFileItems);
    };
    uploader.onBeforeUploadItem = function (item) {
        console.info('onBeforeUploadItem', item);
        //var formData = { id: $stateParams.customerId };
        //item.formData.push(formData);
    };
    uploader.onProgressItem = function (fileItem, progress) {
        console.info('onProgressItem', fileItem, progress);
    };
    uploader.onProgressAll = function (progress) {
        console.info('onProgressAll', progress);
    };
    uploader.onSuccessItem = function (fileItem, response, status, headers) {
        console.info('onSuccessItem', fileItem, response, status, headers);
    };
    uploader.onErrorItem = function (fileItem, response, status, headers) {
        console.info('onErrorItem', fileItem, response, status, headers);
    };
    uploader.onCancelItem = function (fileItem, response, status, headers) {
        console.info('onCancelItem', fileItem, response, status, headers);
    };
    uploader.onCompleteItem = function (fileItem, response, status, headers) {
        console.info('onCompleteItem', fileItem, response, status, headers);
    };
    uploader.onCompleteAll = function () {
        console.info('onCompleteAll');
        $uibModalInstance.close(1);
    };

});
