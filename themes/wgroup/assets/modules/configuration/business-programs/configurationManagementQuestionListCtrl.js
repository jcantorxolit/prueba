'use strict';
/**
 * controller for Customers
 */
app.controller('configurationManagementQuestionListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $aside) {

        var log = $log;
        var request = {};
        log.info("loading..customerConfigManagementQuestionesCtrl ");

        $scope.loading = true;
        $scope.isview = false;
        $scope.question = {};

        $scope.status = $rootScope.parameters("config_workplace_status");
        $scope.types = $rootScope.parameters("wg_structure_type");
        $scope.programs = [];
        $scope.categories= [];

        $scope.pendingValue = function() {
            var diff = $scope.question.weightedValue - (isNaN($scope.question.originalWeightedValue) ? 0 : $scope.question.originalWeightedValue);
            var result = (100 - $scope.question.program.weightedValueTotal - (isNaN(diff) ? 0 : diff)).toFixed(2);

            return isNaN(result) ? 0 : result;
        }

        $scope.onLoadRecord = function () {
            if ($scope.question.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.question.id);
                var req = {
                    id: $scope.question.id
                };
                $http({
                    method: 'GET',
                    url: 'api/configuration/management-question',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Información no disponible", "Registro no encontrado", "error");

                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del centro de trabajo", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.question = response.data.result;
                            $scope.question.originalWeightedValue = $scope.question.weightedValue;
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            $document.scrollTop(40, 2000);
                        });

                    });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        }

        var loadList = function () {

            var req = {};
            req.operation = "diagnostic";

            return $http({
                method: 'POST',
                url: 'api/configuration/management-program/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.programs = response.data.data;

                    console.log($scope.programs);
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.clear();
            });
        };

        var setDefault = function () {
            $scope.question = {
                id: 0,
                name: "",
                article: "",
                program: null,
                category: null,
                status: null
            };
        };

        setDefault();

        loadList();

        var loadCategory = function()
        {
            if ($scope.question.program != null) {
                var req = {};
                req.operation = "diagnostic";
                req.program_id = $scope.question.program.id;

                return $http({
                    method: 'POST',
                    url: 'api/configuration/management-category/list',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        $scope.categories = response.data.data;
                    });
                }).catch(function (e) {

                }).finally(function () {

                });
            } else {
                $scope.categories= [];
            }
        };

        $scope.$watch("question.program", function () {
            //console.log('new result',result);
            loadCategory();
        });



        $scope.onLoadRecord();

        var errorMessage = function (i) {
            toaster.pop('error', 'Error', 'Por favor diligencie los campos requeridos en este paso, antes de continuar al siguiente nivel.');
        };

        $scope.master = $scope.question;
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
                    log.info($scope.question);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información del centro de trabajo...", "success");
                    //your code for submit
                    //  log.info($scope.question);
                    save();
                }

            },
            reset: function (form) {
                $scope.clear();
            }
        };

        $scope.clear = function () {
            $timeout(function () {
                setDefault();
            });

            $scope.isview = false;
        };

        var save = function () {

            var pend = $scope.pendingValue();
            if ($scope.question.program.isWeighted && pend < 0) {
                SweetAlert.swal("Error de validación", "El valor ingresado supera el 100% para el programa seleccionado!", "error");
                return;
            } 

            var req = {};
            var data = JSON.stringify($scope.question);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/configuration/management-question/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.question = response.data.result;

                    SweetAlert.swal("Validación exitosa", "Información guardada", "success");

                    $scope.reloadData();

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.clear();
            });

        };

        // default view
        // $rootScope.tracking_section = "list";

        // Datatable configuration
        request.operation = "diagnostic";
        request.customerId = $scope.customerId;

        $scope.dtInstanceConfigManagementQuestion = {};
		$scope.dtOptionsConfigManagementQuestion = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/configuration/management-question',
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

        $scope.dtColumnsConfigManagementQuestion = [
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

                    var configTemplate = '<a class="btn btn-dark-azure btn-xs configRow lnk" href="#"  uib-tooltip="Configurar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-cogs"></i></a> ';

                    if ($rootScope.can("clientes_view")) {
                        actions += viewTemplate;
                    }

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_delete")) {
                        actions += deleteTemplate;
                    }

                    if ($rootScope.can('programa_empresarial_resource_manage')) {
                        actions += configTemplate;
                    }

                    return actions;
                }),

            DTColumnBuilder.newColumn('program.name').withTitle("Programa").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('category.name').withTitle("Categoría").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Pregunta"),
            DTColumnBuilder.newColumn('article').withTitle("Artículo").withOption('width', 200),
            DTColumnBuilder.newColumn('weightedValue').withTitle("Vlr. Pond.").withOption('width', 200).withOption('defaultContent', ''),
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

        $scope.viewConfigManagementQuestion = function (id) {
            $scope.question.id = id;
            $scope.isview = true;
            $scope.onLoadRecord();
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.customerId);
            }
        };

        var loadRow = function () {

            angular.element("#dtConfigManagementQuestion a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.editConfigManagementQuestion(id);
            });

            angular.element("#dtConfigManagementQuestion a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");

                $scope.question.id = id;
                $scope.viewConfigManagementQuestion(id);

            });

            angular.element("#dtConfigManagementQuestion a.configRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onConfig({ id : id });
            });

            angular.element("#dtConfigManagementQuestion a.delRow").on("click", function () {
                var id = angular.element(this).data("id");

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
                                url: 'api/configuration/management-question/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
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
            $scope.dtInstanceConfigManagementQuestion.reloadData();
        };


        $scope.editConfigManagementQuestion = function (id) {
            $scope.question.id = id;
            $scope.isview = false;
            $scope.onLoadRecord()
        };

        $scope.createConfigManagementQuestion = function () {
            var req = {};
            var request = {
                id: 0,
                customerId: $stateParams.customerId,
                status: {
                    id: 0,
                    item: "Iniciado",
                    value: "iniciado"
                }
            };

            var data = JSON.stringify(request);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: 'api/configuration/management-question/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                if ($scope.$parent != null) {
                    $scope.reloadData();
                    swal("Creado", "Pregunta adicionada satisfactoriamente", "info");
                }
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Creando", "Se ha presentado un error durante la creación del centro de trabajo por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        };

        $scope.refreshWorkPlace = function()
        {
            loadList();
        }

        $scope.refreshMacro = function()
        {
            loadCategory();
        }


         //----------------------------------------------------------------------------DOCUMENT
         $scope.onConfig = function (dataItem) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/configuration/business-programs/configuration_management_question_resource_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: 'static',
                controller: 'ModalInstanceSideManagementQuestionResourceCtrl',
                scope: $scope,
                resolve: {
                    dataItem: function () {
                        return dataItem;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function () {

            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        };

        $scope.refreshProgram = function()
        {
            loadList();
        }

        $scope.refreshCategory = function()
        {
            loadCategory();
        }

    }]);
