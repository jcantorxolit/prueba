'use strict';
/**
 * controller for Customers
 */
app.controller('configurationRoadSafetyItemListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside) {

        var log = $log;
        var request = {};
        log.info("loading..configurationRoadSafetyItemItemListCtrl ");

        $scope.isNewRoadSafetyItem = true;


        request.operation = "diagnostic";

        $scope.dtInstanceRoadSafetyItem = {};
        $scope.dtOptionsRoadSafetyItem = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/road-safety-item',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
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

        $scope.dtColumnsRoadSafetyItem = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 180).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play-circle"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    actions += editTemplate;
                    actions += deleteTemplate;

                    return actions;
                }),

            DTColumnBuilder.newColumn('roadSafety.cycle.name').withTitle("Módulo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('roadSafety.parent.numeral').withTitle("Numeral (Parámetro)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('roadSafety.parent.description').withTitle("Descripción (Parámetro)").withOption('width', 200).withOption('defaultContent', ''),
            //DTColumnBuilder.newColumn('roadSafety.numeral').withTitle("Numeral (Variable)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('roadSafety.description').withTitle("Descripción (Variable)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('numeral').withTitle("Numeral").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('criterion').withTitle("Criterio").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('value').withTitle("Valor").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';

                    if (data || data == 1) {
                        label = 'label label-success';
                        text = 'Activo';
                    } else {
                        label = 'label label-danger';
                        text = 'Inactivo';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';


                    return status;
                }),
        ];

        var loadRow = function () {

            $("#dtRoadSafetyItem a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editRoadSafetyItem(id);
            });

            $("#dtRoadSafetyItem a.configRow").on("click", function () {
                var id = $(this).data("id");
                onOpenModal(id);
            });

            $("#dtRoadSafetyItem a.delRow").on("click", function () {
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
                                url: 'api/road-safety-item/delete',
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
            $scope.dtInstanceRoadSafetyItem.reloadData(null, false);
        };


        $scope.editRoadSafetyItem = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", id);
            }
        };

        $scope.onCreate = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", 0);
            }
        }

        var onOpenModal = function (id) {

            var roadSafety = {
                id: id ? id : 0
            }

            var modalInstance = $aside.open({
                templateUrl: 'app_modal_road_safety_association.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideRoadSafetyItemAssociationCtrl',
                scope: $scope,
                resolve: {
                    roadSafety: function () {
                        return roadSafety;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        }

    }]);

app.controller('ModalInstanceSideRoadSafetyItemAssociationCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, roadSafety, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;


    $scope.roadSafety = {
        roadSafetyItemId: roadSafety.id,
        questions: []
    };


    $scope.master = $scope.roadSafety;

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
                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                return;

            } else {

                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                //your code for submit
                save();
            }

        },
        reset: function (form) {

            $scope.roadSafety = angular.copy($scope.master);
            form.$setPristine(true);

        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.roadSafety);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/road-safety-item-question/insert',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.reloadData();
                $scope.reloadDataSelected();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };


    var request = {};
    request.operation = "management";
    request.road_safety_item_id = roadSafety.id ? roadSafety.id : 0;

    //-------------------------------------------------------------------------ITEMS SELECTED
    $scope.dtInstanceRoadSafetyQuestionSelected = {};
    $scope.dtOptionsRoadSafetyQuestionSelected = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/road-safety-item-question',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function (data) {
                $timeout(function () {
                    //$scope.$parent.setDataSetting(data.responseJSON.data);
                });
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
            loadRowSelected();
            //Pace.stop();

        })
        .withDOM('tr')
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })


        .withOption('createdRow', function (row, data, dataIndex) {

            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsRoadSafetyQuestionSelected = [
        DTColumnBuilder.newColumn(null).withTitle("").withOption('width', 100).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var deleteTemplate = '<a class="btn btn-light-red btn-xs delRow lnk" href="#" uib-tooltip="Quitar item" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-ban"></i></a> ';

                if ($scope.isAdmin) {
                }

                actions += deleteTemplate;

                return actions;
            }),
        DTColumnBuilder.newColumn('programPreventionQuestionId')
            .withTitle("ID")
            .withOption('width', 200),

        DTColumnBuilder.newColumn('program')
            .withTitle("Programa")
            .withOption('width', 400),

        DTColumnBuilder.newColumn('category')
            .withTitle("Categoría"),

        DTColumnBuilder.newColumn('article')
            .withTitle("Artículo"),

        DTColumnBuilder.newColumn('question')
            .withTitle("Pregunta"),

        DTColumnBuilder.newColumn('guide').withTitle("Guía").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = '';

                if (data != null && data != '') {
                    text = 'Tiene';
                    label = 'label label-success';
                } else {
                    text = 'No tiene';
                    label = 'label label-danger';
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            })
    ];

    var loadRowSelected = function () {

        $("#dtRoadSafetyQuestionSelected a.configureRow").on("click", function () {
            var id = $(this).data("id");
            $scope.onEditQuestion(id);
            //Open Modal
        });

        $("#dtRoadSafetyQuestionSelected a.delRow").on("click", function () {
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
                    cancelButtonText: "No, cancelar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/road-safety-item-question/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function (e) {
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        }).finally(function () {

                            $scope.reloadDataSelected();
                            $scope.reloadData();
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });
    };

    $scope.dtInstanceRoadSafetyQuestionSelectedCallback = function (instance) {
        $scope.dtInstanceRoadSafetyQuestionSelected = instance;
    };

    $scope.reloadDataSelected = function () {
        $scope.dtInstanceRoadSafetyQuestionSelected.reloadData();
    };

    //-------------------------------------------------------------------------ITEMS AVAILABLE
    $scope.dtInstanceRoadSafetyQuestion = {};
    $scope.dtOptionsRoadSafetyQuestion = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/road-safety-item-question-available',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function (data) {
                $timeout(function () {
                    //$scope.$parent.setDataSetting(data.responseJSON.data);
                });
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
        .withDOM('tr')
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })


        .withOption('createdRow', function (row, data, dataIndex) {

            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsRoadSafetyQuestion = [
        DTColumnBuilder.newColumn('programPreventionQuestionId')
            .withTitle("ID")
            .withOption('width', 150),

        DTColumnBuilder.newColumn('program')
            .withTitle("Programa")
            .withOption('width', 400),

        DTColumnBuilder.newColumn('category')
            .withTitle("Categoría"),

        DTColumnBuilder.newColumn('article')
            .withTitle("Artículo"),

        DTColumnBuilder.newColumn('question')
            .withTitle("Pregunta"),

        DTColumnBuilder.newColumn('guide').withTitle("Guía").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = '';

                if (data != null && data != '') {
                    text = 'Tiene';
                    label = 'label label-success';
                } else {
                    text = 'No tiene';
                    label = 'label label-danger';
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            }),

        DTColumnBuilder.newColumn(null).withTitle('Acciones').notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";

                var checked = (data.selected == "1") ? "checked" : ""

                var editTemplate = '<input bs-switch ng-model="isSelected" type="checkbox" switch-active="true" ng-click="edit(' + data.id + ')" name="' + data.id + '" ' +
                    'ng-true-value="true" ng-false-value="false" switch-on-text="Si" switch-off-text="No"> ';

                var editTemplate = '<div class="checkbox clip-check check-success ">' +
                    '<input class="editRow" type="checkbox" id="chk_' + data.programPreventionQuestionId + '" data-id="' + data.programPreventionQuestionId + '" data-value="' + data.selected + '" ' + checked + ' ><label for="chk_' + data.programPreventionQuestionId + '"> Seleccionar </label></div>';
                actions += editTemplate;

                return actions;
            })
            .notSortable()
    ];


    var loadRow = function () {

        $("input[type=checkbox]").on("change", function () {
            var id = $(this).data("id");
            var value = $(this).data("value");
            var checked = $(this).is(":checked");

            if (checked) {
                var question = {
                    programPreventionQuestionId: id
                }
                $scope.roadSafety.questions.push(question);
            } else {
                $scope.roadSafety.questions = $filter('filter')($scope.roadSafety.questions, {programPreventionQuestionId: ('!' + id)});
            }

            log.info($scope.roadSafety.questions);
        });

    };

    $scope.dtInstanceRoadSafetyQuestionCallback = function (instance) {
        $scope.dtInstanceRoadSafetyQuestion = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceRoadSafetyQuestion.reloadData();
    };


});
