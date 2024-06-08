'use strict';
/**
  * controller for Customers
*/
app.controller('configurationHelpRolesProfileCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
          $rootScope,$timeout, $http, SweetAlert, $aside, $document, flowFactory, ListService, FileUploader) {

    var log = $log;
    log.info("loading..configurationHelpRolesProfileCtrl ");

    $scope.flowConfig = {target: '/api/system-parameter/upload', singleFile: true};
    $scope.loading = true;
    $scope.isEdit = false;
    $scope.entity = {};
    $scope.typeList = [];
    $scope.descriptionList = [];
    $scope.Form = null;
    var attachmentUploadedId = 0;

    $scope.uploader = new Flow();

    var initialize = function() {
        $scope.entity = {
            id: 0,
            type: null,
            description: null,
            text: null,
            attatchment: null
        };

    };
    initialize();

    function getTypes() {
        var entities = [
            {name: 'configuration_help_roles_profile_type'},
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.typeList = response.data.data.typeList;
            }, function (error) {
                log.info('Unable to load types data: ' + error.message);
            });
    }
    getTypes();

    $scope.onSelectType = function() {
        var sName = null;
        if($scope.entity.type.item == "Roles") {
            sName = "customer_user_role";
        } else {
            sName = "wg_customer_user_profile";
        }

        var oEntities = [
            {name: sName},
        ];

        ListService.getDataList(oEntities)
        .then(function (response) {
            $scope.descriptionList = response.data.data[sName];
        }, function (error) {
            log.info('Unable to load roles profiles data: ' + error.message);
        });
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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

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

        if (uploaderResource.queue.length == 0 && $scope.entity.type.value == 1 && !$scope.isEdit) {
            SweetAlert.swal("Validación de datos", "El anexo es obligatorio para el rol.", "warning");
            return;
        }

        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/help-roles-profile/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function(){
                if (uploaderResource.queue.length > 0) {
                    attachmentUploadedId = response.data.result.id;
                    uploaderResource.uploadAll();
                } else {
                    $scope.reloadData();
                }
                $scope.form.reset();
                $scope.isEdit = false;
            });

        }).catch(function(e){
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function(){

        });

    };

    $scope.onLoadRecord = function (id)
    {
        var req = {
            id: id,
        };

        $http({
            method: 'GET',
            url: 'api/help-roles-profile/get',
            params: req
        })
        .catch(function(e, code){
            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
        })
        .then(function (response) {
            $timeout(function(){
                $scope.entity = response.data.result;
                $scope.onSelectType();
                $scope.isEdit = true;
            });
        }).finally(function () {
            $timeout(function(){
                $scope.loading =  false;
            });
        });
    }


    $scope.dtInstanceArl = {};
		$scope.dtOptionsArl = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.customerId = $stateParams.customerId;
                return JSON.stringify(d);
            },
            url: 'api/help-roles-profile',
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

    $scope.dtColumnsArl = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-edit"></i></a> ';

                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                var openFile = "";

                if (data.documentUrl) {
                    var openFile = '<a class="btn btn-info btn-xs openFile lnk" href="' + data.documentUrl + '" target="_blank" uib-tooltip="Abrir archivo" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-file"></i></a> ';
                }

                actions += editTemplate;
                actions += deleteTemplate;
                actions += openFile;
                return actions;
            }),

        DTColumnBuilder.newColumn('typeName').withTitle("Tipo"),
        DTColumnBuilder.newColumn('descriptionName').withTitle("Descripción"),
        DTColumnBuilder.newColumn('text').withTitle("Texto").withOption('width', 300),
    ];

    var loadRow = function () {

        $("#dtArl a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.onLoadRecord(id)
        });

        $("#dtArl a.delRow").on("click", function () {
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
                            url: 'api/help-roles-profile/delete',
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
        $scope.dtInstanceArl.reloadData();
    };

    var uploaderResource = $scope.uploaderResource = new FileUploader({
        url: 'api/help-roles-profile/upload',
        formData: []
    });

    uploaderResource.filters.push({
        name: 'customFilter',
        fn: function (item/*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploaderResource.onWhenAddingFileFailed = function (item/*{File|FileLikeObject}*/, filter, options) {
        console.info('onWhenAddingFileFailed', item, filter, options);
    };
    uploaderResource.onAfterAddingFile = function (fileItem) {
        console.info('onAfterAddingFile', fileItem);
    };
    uploaderResource.onAfterAddingAll = function (addedFileItems) {
        console.info('onAfterAddingAll', addedFileItems);
    };
    uploaderResource.onBeforeUploadItem = function (item) {
        console.info('onBeforeUploadItem', item);
        var formData = {id: attachmentUploadedId};
        item.formData.push(formData);
    };
    uploaderResource.onProgressItem = function (fileItem, progress) {
        console.info('onProgressItem', fileItem, progress);
    };
    uploaderResource.onProgressAll = function (progress) {
        console.info('onProgressAll', progress);
    };
    uploaderResource.onSuccessItem = function (fileItem, response, status, headers) {
        console.info('onSuccessItem', fileItem, response, status, headers);
    };
    uploaderResource.onErrorItem = function (fileItem, response, status, headers) {
        console.info('onErrorItem', fileItem, response, status, headers);
    };
    uploaderResource.onCancelItem = function (fileItem, response, status, headers) {
        console.info('onCancelItem', fileItem, response, status, headers);
    };
    uploaderResource.onCompleteItem = function (fileItem, response, status, headers) {
        console.info('onCompleteItem', fileItem, response, status, headers);
    };
    uploaderResource.onCompleteAll = function () {
        console.info('onCompleteAll');
        toaster.pop('success', 'Operación Exitosa', 'La información ha sido guardada satisfactoriamente');
        $scope.reloadData();
    };

});
