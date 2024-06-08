'use strict';
/**
 * controller for Customers
 */
app.controller('customerConfigWizardJobImport', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document', 'FileUploader',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, FileUploader) {

        var log = $log;
        var request = {};
        log.info("loading..customerConfigProcessesCtrl ");

        $scope.loading = true;
        $scope.customerId = $stateParams.customerId;;
        $scope.isView = false;
        $scope.process = {};
        $scope.macros = [];
        $scope.processes = [];

        $scope.job = {
            id: $scope.$parent.currentId,
            customerId: $scope.customerId,
            name: "",
            status: null
        }

        var loadList = function () {

            var req = {};
            req.operation = "diagnostic";
            req.customerId = $scope.customerId;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/workplace/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (result) {
                $timeout(function () {
                    $scope.workplaces = result.data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        loadList();

        var loadMacro = function()
        {
            if ($scope.job.workplace != null) {
                var req = {};
                req.operation = "diagnostic";
                req.customerId = $scope.customerId;
                req.workPlaceId = $scope.job.workplace.id;

                $scope.job.macro = null;
                $scope.job.process = null;

                return $http({
                    method: 'POST',
                    url: 'api/customer/config-sgsst/macro/list',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (result) {
                    $timeout(function () {
                        $scope.macros = result.data.data;
                    });
                }).catch(function (e) {

                }).finally(function () {

                });
            } else {
                $scope.macros = [];
            }
        }

        var loadProcess = function()
        {
            if ($scope.job.macro != null) {
                var req = {};
                req.operation = "diagnostic";
                req.customerId = $scope.customerId;
                req.workPlaceId = $scope.job.workplace.id;
                req.macroProcessid = $scope.job.macro.id;

                $scope.job.process = null;

                return $http({
                    method: 'POST',
                    url: 'api/customer/config-sgsst/process/list',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (result) {
                    $timeout(function () {
                        $scope.processes = result.data.data;
                    });
                }).catch(function (e) {

                }).finally(function () {

                });
            } else {
                $scope.processes = [];
            }
        }


        $scope.$watch("job.workplace", function () {
            //console.log('new result',result);
            loadMacro();
        });

        $scope.$watch("job.macro", function () {
            //console.log('new result',result);
            loadProcess();
        });

        var uploader = $scope.uploader = new FileUploader({
            url: 'api/customer/config-sgsst/job/import',
            formData:[],
            removeAfterUpload: true
        });

        uploader.filters.push({
            name: 'customFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            }
        });

        // CALLBACKS

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
            var formData = {
                customerId: $scope.customerId,
                /*workPlaceId: $scope.job.workplace.id,
                macroProcessId: $scope.job.macro.id,
                processId: $scope.job.process.id*/
            };
            item.formData.push(formData);
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
            $scope.reloadData();
            $scope.clear();
        };

        $scope.clear = function () {

            $scope.isView = false;
        };

        $scope.save = function () {
            if ($scope.uploader.queue.length == 0) {
                SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un anexo e Intentalo de nuevo.", "error");
                return;
            } /*else if ($scope.job.workplace == null) {
                SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un centro de trabajo e Intentalo de nuevo.", "error");
                return;
            } else if ($scope.job.macro == null) {
                SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un macroproceso e Intentalo de nuevo.", "error");
                return;
            } else if ($scope.job.process == null) {
                SweetAlert.swal("El formulario contiene errores!", "Por favor seleccione un proceso e Intentalo de nuevo.", "error");
                return;
            }*/
            uploader.uploadAll();
        };

        $scope.download = function () {
            jQuery("#downloadDocument")[0].src = "api/customer/config-sgsst/job/download";
        };

        // Datatable configuration
        request.operation = "diagnostic";
        request.customerId = $scope.customerId;

        $scope.dtInstanceConfigJobImport = {};
		$scope.dtOptionsConfigJobImport = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/customer/config-sgsst/job-data',
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

        $scope.dtColumnsConfigJobImport = [
            DTColumnBuilder.newColumn('name').withTitle("Cargo"),
            DTColumnBuilder.newColumn('status.item').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch  (data)
                    {
                        case "Activo":
                            label = 'label label-success';
                            break;

                        case "Inactivo":
                            label = 'label label-danger';
                            break;

                        case "Retirado":
                            label = 'label label-warning';
                            break;
                    }

                    var status = '<span class="' + label +'">' + data + '</span>';


                    return status;
                }),
        ];

        $scope.viewConfigJobImport = function (id) {
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", id);
            }
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", $scope.customerId);
            }
        };

        var loadRow = function () {

            $("#dtConfigJobImport a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editConfigJobImport(id);
            });

            $("#dtConfigJobImport a.viewRow").on("click", function () {
                var id = $(this).data("id");

                $scope.process.id = id;
                $scope.viewConfigJobImport(id);

            });

            $("#dtConfigJobImport a.delRow").on("click", function () {
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
                                url: 'api/customer/config-sgsst/job/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (data) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });
        };

        $scope.reloadData = function () {
            $scope.dtInstanceConfigJobImport.reloadData();
        };


        $scope.editConfigJobImport = function (id) {
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", id);
            }
        };

        $scope.createConfigJobImport = function () {
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", 0);
            }
        };

        $scope.refreshWorkPlace = function()
        {
            loadList();
        }

        $scope.refreshMacro = function()
        {
            loadMacro();
        }

        $scope.refreshProcess = function()
        {
            loadProcess();
        }

    }]);
