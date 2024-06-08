'use strict';
/**
 * controller for Customers
 */
app.controller('configurationMinimumStandardItemList0312Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside) {


        $scope.isNewMinimumStandardItem = true;

        $scope.dtInstanceMinimumStandardItem0312 = {};
        $scope.dtOptionsMinimumStandardItem0312 = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    return JSON.stringify(d);
                },
                url: 'api/minimum-standard-item-0312',
                contentType: 'application/json',
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

        $scope.dtColumnsMinimumStandardItem0312 = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 180).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play-circle"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    var configTemplate = ' | <a class="btn btn-info btn-xs configRow lnk" href="#"  uib-tooltip="Configurar item" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-cog"></i></a> ';


                    actions += editTemplate;
                    actions += deleteTemplate;
                    actions += configTemplate;

                    return actions;
                }),

            DTColumnBuilder.newColumn('cycle').withTitle("Ciclo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('parentNumeral').withTitle("Numeral (Padre)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('parentDescription').withTitle("Estándar (Padre)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('standardNumeral').withTitle("Numeral (Hijo)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('standardDescription').withTitle("Estándar (Hijo)").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('numeral').withTitle("Numeral").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Item").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('value').withTitle("Valor").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Activo").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = data.status;

                    if (data || data.isActive == 1) {
                        label = 'label label-success';
                    } else {
                        label = 'label label-danger';
                    }

                    return '<span class="' + label + '">' + text + '</span>';
                }),
        ];

        var loadRow = function () {

            $("#dtMinimumStandardItem0312 a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.editMinimumStandardItem(id);
            });

            $("#dtMinimumStandardItem0312 a.configRow").on("click", function () {
                var id = $(this).data("id");
                onOpenModal(id);
            });

            $("#dtMinimumStandardItem0312 a.delRow").on("click", function () {
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

        $scope.dtInstanceMinimumStandardItem0312Callback = function (instance) {
            $scope.dtInstanceMinimumStandardItem0312 = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceMinimumStandardItem0312.reloadData(null, false);
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
                templateUrl: $rootScope.app.views.urlRoot + 'modules/configuration/minimum-standard-0312/item/configuration_minimum_standard_associate_modal.htm',
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideMinimumStandardItemAssociation0312Ctrl',
                scope: $scope,
                resolve: {
                    standard: function () {
                        return standard;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {

            });
        }

    }]);

app.controller('ModalInstanceSideMinimumStandardItemAssociation0312Ctrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, standard, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

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
                //your code for submit
                save();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.standard);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/minimum-standard-item-question-0312/batch',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
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


    //-------------------------------------------------------------------------ITEMS SELECTED
    $scope.dtInstanceMinimumStandardQuestionSelected = {};
    $scope.dtOptionsMinimumStandardQuestionSelected = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.minimumStandardItemId = standard.id ? standard.id : 0
                return JSON.stringify(d);
            },
            url: 'api/minimum-standard-item-question-0312',
            contentType: 'application/json',
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

        .withOption('paging', false)
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

                if (data != null && data == 'Tiene') {
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
            data: function(d) {
                d.minimumStandardItemId = standard.id ? standard.id : 0
                return JSON.stringify(d);
            },
            url: 'api/minimum-standard-item-question-0312-available',
            contentType: 'application/json',
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

        .withOption('paging', false)
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

                if (data != null && data == 'Tiene') {
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
        });

    };

    $scope.dtInstanceMinimumStandardQuestionCallback = function (instance) {
        $scope.dtInstanceMinimumStandardQuestion = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceMinimumStandardQuestion.reloadData();
    };


});
