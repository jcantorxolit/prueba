'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeOccupationalExaminationListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $aside) {

        var log = $log;

        $scope.isView = $scope.$parent.isCustomerContractor || $scope.$parent.$parent.$parent.isView;
        $scope.agents = $rootScope.agents();

        $scope.dtInstanceEmployeeOccupationalExamination = {};
        $scope.dtOptionsEmployeeOccupationalExamination = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerEmployeeId = $scope.$parent.currentEmployee;
                    return JSON.stringify(d);
                },
                url: 'api/customer-work-medicine',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function() {
                    // Aqui inicia el loader indicator
                },
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function() {
                //log.info("fnDrawCallback");
                loadRow();
                //Pace.stop();

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })

        .withPaginationType('full_numbers')
            .withOption('createdRow', function(row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });;

        $scope.dtColumnsEmployeeOccupationalExamination = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function(data, type, full, meta) {
                var actions = "";
                var actionsView = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                var isButtonVisible = !$scope.isView;

                if ($rootScope.can("seguimiento_view")) {
                    actions += viewTemplate;
                }

                if ($rootScope.can("seguimiento_edit")) {
                    actions += editTemplate;
                }

                if ($rootScope.can("seguimiento_delete")) {
                    actions += deleteTemplate;
                }

                if (isButtonVisible == false) {
                    actionsView += viewTemplate;
                }

                return isButtonVisible ? actions : actionsView;
            }),
            DTColumnBuilder.newColumn('examinationDate').withTitle("Fecha Exámen").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('examinationType').withTitle("Tipo de Exámen").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', '')
        ];

        var loadRow = function() {

            $("#dtOccupationalExamination a.editRow").on("click", function() {
                var id = $(this).data("id");
                $scope.onEdit(id);
            });

            $("#dtOccupationalExamination a.viewRow").on("click", function() {
                var id = $(this).data("id");
                $scope.onView(id);
            });

            $("#dtOccupationalExamination a.delRow").on("click", function() {
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
                    function(isConfirm) {
                        if (isConfirm) {
                            var req = {};
                            req.id = id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/work-medicine/delete',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                data: $.param(req)
                            }).then(function(data) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function(e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function() {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });

        };

        $scope.dtInstanceEmployeeOccupationalExaminationCallback = function(instance) {
            $scope.dtInstanceEmployeeOccupationalExamination = instance;
        };

        $scope.reloadData = function() {
            $scope.dtInstanceEmployeeOccupationalExamination.reloadData();
        };

        $scope.onCreateNew = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("form", "edit", 0);
            }
        };

        $scope.onEdit = function(id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("form", "edit", id);
            }
        };

        $scope.onView = function(id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("form", "view", id);
            }
        };

        $scope.onExportExcel = function() {
            var data = {
                customerEmployeeId: $scope.$parent.currentEmployee
            }
            jQuery("#downloadDocument")[0].src = "api/customer-work-medicine/export-excel?data=" + Base64.encode(JSON.stringify(data));
        }
    }
]);