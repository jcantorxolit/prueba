'use strict';
/**
 * controller for Customers
 */
app.controller('configurationMinimumStandardItemListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside) {

        var log = $log;
        var request = {};
        log.info("loading..configurationMinimumStandardItemItemListCtrl ");

        $scope.isNewMinimumStandardItem = true;


        request.operation = "diagnostic";

        $scope.dtInstanceMinimumStandardItem = {};
        $scope.dtOptionsMinimumStandardItem = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/minimum-standard-item',
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

        $scope.dtColumnsMinimumStandardItem = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 180).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play-circle"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    var configTemplate = ' | <a class="btn btn-info btn-xs configRow lnk" href="#"  uib-tooltip="Configurar sistema de gestión" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-cog"></i></a> ';

                    if ($rootScope.can("diagnostico_continue")) {
                    }
                    actions += editTemplate;

                    if ($rootScope.can("clientes_delete")) {
                    }
                    actions += deleteTemplate;
                    actions += configTemplate;

                    return actions;
                }),

            DTColumnBuilder.newColumn('minimumStandard.cycle.name').withTitle("Ciclo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('minimumStandard.parent.numeral').withTitle("Numeral (Padre)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('minimumStandard.parent.description').withTitle("Estándar (Padre)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('minimumStandard.numeral').withTitle("Numeral (Hijo)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('minimumStandard.description').withTitle("Estándar (Hijo)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('numeral').withTitle("Numeral").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Item").withOption('width', 200).withOption('defaultContent', ''),
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

            $("#dtMinimumStandardItem a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editMinimumStandardItem(id);
            });

            $("#dtMinimumStandardItem a.configRow").on("click", function () {
                var id = $(this).data("id");
                onOpenModal(id);
            });

            $("#dtMinimumStandardItem a.delRow").on("click", function () {
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
                                url: 'api/minimum-standard-item/delete',
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
            $scope.dtInstanceMinimumStandardItem.reloadData(null, false);
        };


        $scope.editMinimumStandardItem = function (id) {
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

            var standard = {
                id: id ? id : 0
            }

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_minimum_standard_association.htm',
                templateUrl: $rootScope.app.views.urlRoot + 'modules/configuration/minimum-standard/item/configuration_minimum_standard_associate_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideMinimumStandardItemAssociationCtrl',
                scope: $scope,
                resolve: {
                    standard: function () {
                        return standard;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            });
        }

    }]);

app.controller('ModalInstanceSideMinimumStandardItemAssociationCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, standard, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;


    $scope.standard = {
        minimumStandardItemId: standard.id,
        questions: []
    };


    $scope.master = $scope.standard;

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

            $scope.standard = angular.copy($scope.master);
            form.$setPristine(true);

        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.standard);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/minimum-standard-item-question/insert',
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
    request.minimum_standard_item_id = standard.id ? standard.id : 0;

    //-------------------------------------------------------------------------ITEMS SELECTED
    $scope.dtInstanceMinimumStandardQuestionSelected = {};
    $scope.dtOptionsMinimumStandardQuestionSelected = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/minimum-standard-item-question',
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

    $scope.dtColumnsMinimumStandardQuestionSelected = [
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

        $("#dtMinimumStandardQuestionSelected a.configureRow").on("click", function () {
            var id = $(this).data("id");
            $scope.onEditQuestion(id);
            //Open Modal
        });

        $("#dtMinimumStandardQuestionSelected a.delRow").on("click", function () {
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
                            url: 'api/minimum-standard-item-question/delete',
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

    $scope.dtInstanceMinimumStandardQuestionSelectedCallback = function (instance) {
        $scope.dtInstanceMinimumStandardQuestionSelected = instance;
    };

    $scope.reloadDataSelected = function () {
        $scope.dtInstanceMinimumStandardQuestionSelected.reloadData();
    };

    //-------------------------------------------------------------------------ITEMS AVAILABLE
    $scope.dtInstanceMinimumStandardQuestion = {};
    $scope.dtOptionsMinimumStandardQuestion = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/minimum-standard-item-question-available',
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

    $scope.dtColumnsMinimumStandardQuestion = [
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
                $scope.standard.questions.push(question);
            } else {
                $scope.standard.questions = $filter('filter')($scope.standard.questions, {programPreventionQuestionId: ('!' + id)});
            }

            log.info($scope.standard.questions);
        });

    };

    $scope.dtInstanceMinimumStandardQuestionCallback = function (instance) {
        $scope.dtInstanceMinimumStandardQuestion = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceMinimumStandardQuestion.reloadData();
    };


});
