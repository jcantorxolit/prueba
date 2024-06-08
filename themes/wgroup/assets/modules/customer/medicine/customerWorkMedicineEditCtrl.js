'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerWorkMedicineEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', '$document', 'ListService',
    function ($scope, $stateParams, $log, $compile, $state,
              SweetAlert, $rootScope, $http, $timeout, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $uibModal, flowFactory,
              cfpLoadingBar, $filter, $aside, $document, ListService) {

        var log = $log;


        var request = {};
        var currentId = $scope.$parent.currentId;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        log.info("loading..customerWorkMedicineEditCtrl con el id de tracking: ", currentId);

        // parametros para seguimientos
        $scope.examinationTypes = $rootScope.parameters("work_medicine_examination_type");
        $scope.medicalConcepts = $rootScope.parameters("work_medicine_medical_concept");
        $scope.complementaryTests = $rootScope.parameters("work_medicine_complementary_test");
        //$scope.complementaryTestResults = $rootScope.parameters("work_medicine_complementary_test_result");
        $scope.sveTypes = $rootScope.parameters("work_medicine_sve_type");
        $scope.trackingTypes = $rootScope.parameters("work_medicine_tracking_type");
        $scope.contractTypes = $rootScope.parameters("employee_contract_type");
        $scope.documentType = $rootScope.parameters("customer_document_type");
        $scope.tiposdoc = $rootScope.parameters("tipodoc");


        $scope.isView = $scope.$parent.modeDsp == "view";
        console.log($scope.isView);
        $scope.canShow = false;
        $scope.minDateCurrent = new Date();
        $scope.customerId = $stateParams.customerId;

        $scope.maxDate = new Date();
        $scope.dateTimePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy HH:mm"
        };

        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        $scope.employees = [];

        $scope.onLoadRecord = function () {
            if ($scope.medicine.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.medicine.id);
                var req = {
                    id: $scope.medicine.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/work-medicine',
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
                            SweetAlert.swal("Información no disponible", "Centro de trabajo no encontrado", "error");

                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del centro de trabajo", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.medicine = response.data.result;

                            $scope.medicine.examinationDate = new Date($scope.medicine.examinationDate.date);

                            $scope.canShow = true;
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

        $scope.medicine = {
            id: currentId,
            employee: null,
            examinationType: null,
            examinationDate: null,
            occupationalConclusion: "",
            occupationalBehavior: "",
            generalRecommendation: "",
            medicalConcept: null
        };

        var init = function () {
            $scope.complementary = {
                id: 0,
                customerWorkMedicineId: $scope.medicine.id,
                complementaryTest: null,
                result: null,
                interpretation: "",
            };

            $scope.sve = {
                id: 0,
                customerWorkMedicineId: $scope.medicine.id,
                type: null,
                isActive: false
            };

            $scope.tracking = {
                id: 0,
                customerWorkMedicineId: $scope.medicine.id,
                type: null,
                dateOf: null,
                observation: "",
            };
        };

        init();

        $scope.onLoadRecord();

        $scope.master = $scope.medicine;

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
                    log.info($scope.customer);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;

                } else {

                    log.info($scope.customer);
                    save();
                }

            },
            reset: function (form) {

                $scope.tracking = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};

            $scope.medicine.examinationDate = $scope.medicine.examinationDate.toISOString();

            var data = JSON.stringify($scope.medicine);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/work-medicine/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $scope.medicine = response.data.result;
                    $scope.medicine.examinationDate = new Date($scope.medicine.examinationDate.date);
                    $scope.canShow = true;
                    request.customer_work_medicine_id = $scope.medicine.id;
                    currentId = $scope.medicine.id;
                    init();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        };

        $scope.cancelEdition = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        request.customer_work_medicine_id = currentId;

       //------------------------------------------------------------------------COMPLEMENTARY
       $scope.dtInstanceWorkMedicineComplementary = {};
       $scope.dtOptionsWorkMedicineComplementary = DTOptionsBuilder.newOptions()
           // Add Bootstrap compatibility
           .withBootstrap().withOption('responsive', true)
           .withOption('ajax', {
               // Either you specify the AjaxDataProp here
               data: function(d) {
                    d.customer_work_medicine_id = currentId;
                    return d;
               },
               url: 'api/customer/work-medicine/complementary-test',
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

       $scope.dtColumnsWorkMedicineComplementary = [
           DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
               .renderWith(function (data, type, full, meta) {
                   var actions = "";
                   var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                       '   <i class="fa fa-edit"></i></a> ';
                   var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                       '   <i class="fa fa-trash-o"></i></a> ';

                   if ($rootScope.can("seguimiento_edit")) {
                       actions += editTemplate;
                   }

                   if ($rootScope.can("seguimiento_delete")) {
                       actions += deleteTemplate;
                   }

                   return $scope.isView ? '' : actions;
               }),
           DTColumnBuilder.newColumn('complementaryTest.item').withTitle("Prueba Complementaria").withOption('defaultContent', ''),
           DTColumnBuilder.newColumn('result.item').withTitle("Resultado").withOption('defaultContent', ''),
           DTColumnBuilder.newColumn('interpretation').withTitle("Interpretación").withOption('defaultContent', '')
       ];

       var loadRow = function () {

           $("#dtWorkMedicineComplementary a.editRow").on("click", function () {
               var id = $(this).data("id");
               $scope.editWorkMedicineComplementary(id);
           });

           $("#dtWorkMedicineComplementary a.delRow").on("click", function () {
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
                               url: 'api/customer/work-medicine/complementary-test/delete',
                               headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                               data: $.param(req)
                           }).then(function (data) {
                               swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                           }).catch(function (e) {
                               $log.error(e);
                               SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                           }).finally(function () {
                               $scope.reloadDataComplementary();
                           });

                       } else {
                           swal("Cancelación", "La operación ha sido cancelada", "error");
                       }
                   });
           });

       };

       $scope.dtInstanceWorkMedicineComplementaryCallback = function (instance) {
           $scope.dtInstanceWorkMedicineComplementary = instance;
       };

       $scope.reloadDataComplementary = function () {
           $scope.dtInstanceWorkMedicineComplementary.reloadData();
       };

       $scope.editWorkMedicineComplementary = function (id) {
           if (id) {
               var req = {id: id};
               $http({
                   method: 'GET',
                   url: 'api/customer/work-medicine/complementary-test',
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
                           SweetAlert.swal("Información no disponible", "Centro de trabajo no encontrado", "error");

                       } else {
                           SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información", "error");
                       }
                   })
                   .then(function (response) {
                       console.log(response);

                       $timeout(function () {
                           $scope.complementary = response.data.result;

                           var $complementaryTestResult =  $scope.complementary.result;

                           $scope.onSelectComplementaryTest(null, null);

                           $scope.complementary.result = $complementaryTestResult;
                       });

                   }).finally(function () {
                   $timeout(function () {
                       $scope.loading = false;
                   }, 400);

                   $timeout(function () {
                       //$document.scrollTop(40, 2000);
                   });

               });


           } else {
               //Se creara nuevo cliente
               log.info("creacion de nuevo cliente");
               $scope.loading = false;
           }
       };

       $scope.saveWorkMedicineComplementary = function () {
           var req = {};

           var validateDateMessage = '';

           if ($scope.complementary.complementaryTest == null) {
               validateDateMessage += "Debe seleccionar la Prueba complementaria <br/>";
           }

           if ($scope.complementary.result == null) {
               validateDateMessage += "Debe seleccionar el Resultado <br/>";
           }

           if ($scope.complementary.interpretation == null || $scope.complementary.interpretation == '') {
               validateDateMessage += "Debe ingresar la Interpretación <br/>";
           }

           if (validateDateMessage != '') {
               SweetAlert.swal({
                   html: true,
                   title: "Error de validación",
                   text: validateDateMessage,
                   type: "error"
               });
               return;
           }

           var data = JSON.stringify($scope.complementary);
           req.data = Base64.encode(data);
           return $http({
               method: 'POST',
               url: 'api/customer/work-medicine/complementary-test/save',
               headers: {'Content-Type': 'application/x-www-form-urlencoded'},
               data: $.param(req)
           }).then(function (data) {
               $timeout(function () {
                   $scope.clearWorkMedicineComplementary()
                   $scope.reloadDataComplementary()
               });
           }).catch(function (e) {
               $log.error(e);
               SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
           }).finally(function () {
           });
       }

       $scope.clearWorkMedicineComplementary = function () {
           $scope.complementary = {
               id: 0,
               customerWorkMedicineId: $scope.medicine.id,
               complementaryTest: null,
               result: null,
               interpretation: "",
           };
       };


       //------------------------------------------------------------------------SVE
       $scope.dtInstanceWorkMedicineSve = {};
       $scope.dtOptionsWorkMedicineSve = DTOptionsBuilder.newOptions()
           // Add Bootstrap compatibility
           .withBootstrap().withOption('responsive', true)
           .withOption('ajax', {
               // Either you specify the AjaxDataProp here
               data: function(d) {
                    d.customer_work_medicine_id = currentId;
                    return d;
                },
               url: 'api/customer/work-medicine/sve',
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
               loadRowSve();
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

       $scope.dtColumnsWorkMedicineSve = [
           DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
               .renderWith(function (data, type, full, meta) {
                   var actions = "";
                   var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                       '   <i class="fa fa-edit"></i></a> ';
                   var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                       '   <i class="fa fa-trash-o"></i></a> ';

                   if ($rootScope.can("seguimiento_edit")) {
                       actions += editTemplate;
                   }

                   if ($rootScope.can("seguimiento_delete")) {
                       actions += deleteTemplate;
                   }

                   return $scope.isView ? '' : actions;
               }),
           DTColumnBuilder.newColumn('type.item').withTitle("SVE Tipo").withOption('defaultContent', ''),
           DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('defaultContent', '')
               .renderWith(function (data, type, full, meta) {
                   var label = '';
                   var text = '';

                   if (data || data == '1') {
                       text = 'Si';
                       label = 'label label-success';
                   } else {
                       text = 'No';
                       label = 'label label-danger';
                   }

                   return '<span class="' + label + '">' + text + '</span>';
               })
       ];

       var loadRowSve = function () {

           $("#dtWorkMedicineSve a.editRow").on("click", function () {
               var id = $(this).data("id");
               $scope.editWorkMedicineSve(id);
           });

           $("#dtWorkMedicineSve a.delRow").on("click", function () {
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
                               url: 'api/customer/work-medicine/sve/delete',
                               headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                               data: $.param(req)
                           }).then(function (data) {
                               swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                           }).catch(function (e) {
                               $log.error(e);
                               SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                           }).finally(function () {
                               $scope.reloadDataSve();
                           });

                       } else {
                           swal("Cancelación", "La operación ha sido cancelada", "error");
                       }
                   });
           });

       };

       $scope.dtInstanceWorkMedicineSveCallback = function (instance) {
           $scope.dtInstanceWorkMedicineSve = instance;
       };

       $scope.reloadDataSve = function () {
           $scope.dtInstanceWorkMedicineSve.reloadData();
       };

       $scope.editWorkMedicineSve = function (id) {
           if (id) {
               var req = {id: id};
               $http({
                   method: 'GET',
                   url: 'api/customer/work-medicine/sve',
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
                           SweetAlert.swal("Información no disponible", "Centro de trabajo no encontrado", "error");

                       } else {
                           SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información", "error");
                       }
                   })
                   .then(function (response) {

                       $timeout(function () {
                           $scope.sve = response.data.result;
                           //$scope.medicine.examinationDate =  new Date($scope.medicine.examinationDate.date);
                       });

                   }).finally(function () {
                       $timeout(function () {
                           $scope.loading = false;
                       }, 400);

                       $timeout(function () {
                           //$document.scrollTop(40, 2000);
                       });

                   });


           } else {
               //Se creara nuevo cliente
               log.info("creacion de nuevo cliente");
               $scope.loading = false;
           }
       };

       $scope.saveWorkMedicineSve = function () {
           var req = {};

           var validateDateMessage = '';

           if ($scope.sve.type == null) {
               validateDateMessage += "Debe seleccionar la Prueba complementaria <br/>";
           }

           if (validateDateMessage != '') {
               SweetAlert.swal({
                   html: true,
                   title: "Error de validación",
                   text: validateDateMessage,
                   type: "error"
               });
               return;
           }

           var data = JSON.stringify($scope.sve);
           req.data = Base64.encode(data);
           return $http({
               method: 'POST',
               url: 'api/customer/work-medicine/sve/save',
               headers: {'Content-Type': 'application/x-www-form-urlencoded'},
               data: $.param(req)
           }).then(function (data) {
               $timeout(function () {
                   $scope.clearWorkMedicineSve()
                   $scope.reloadDataSve()
               });
           }).catch(function (e) {
               $log.error(e);
               SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
           }).finally(function () {
           });
       }

       $scope.clearWorkMedicineSve = function () {
           $scope.sve = {
               id: 0,
               customerWorkMedicineId: $scope.medicine.id,
               type: null,
               isActive: false
           };
       };


       //------------------------------------------------------------------------TRACKIING
       $scope.dtInstanceWorkMedicineTracking = {};
       $scope.dtOptionsWorkMedicineTracking = DTOptionsBuilder.newOptions()
           // Add Bootstrap compatibility
           .withBootstrap().withOption('responsive', true)
           .withOption('ajax', {
               // Either you specify the AjaxDataProp here
               data: function(d) {
                    d.customer_work_medicine_id = currentId;
                    return d;
               },
               url: 'api/customer/work-medicine/tracking',
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
               loadRowTracking();
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

       $scope.dtColumnsWorkMedicineTracking = [
           DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
               .renderWith(function (data, type, full, meta) {
                   var actions = "";
                   var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                       '   <i class="fa fa-edit"></i></a> ';
                   var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                       '   <i class="fa fa-trash-o"></i></a> ';

                   if ($rootScope.can("seguimiento_edit")) {
                       actions += editTemplate;
                   }

                   if ($rootScope.can("seguimiento_delete")) {
                       actions += deleteTemplate;
                   }

                   return $scope.isView ? '' : actions;
               }),
           DTColumnBuilder.newColumn('dateOfFormat').withTitle("Fecha Seguimiento").withOption('width', 200).withOption('defaultContent', ''),
           DTColumnBuilder.newColumn('type.item').withTitle("Tipo de Seguimiento").withOption('defaultContent', ''),
           DTColumnBuilder.newColumn('observation').withTitle("Observación").withOption('defaultContent', '')
       ];

       var loadRowTracking = function () {

           $("#dtWorkMedicineTracking a.editRow").on("click", function () {
               var id = $(this).data("id");
               $scope.editWorkMedicineTracking(id);
           });

           $("#dtWorkMedicineTracking a.delRow").on("click", function () {
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
                               url: 'api/customer/work-medicine/tracking/delete',
                               headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                               data: $.param(req)
                           }).then(function (data) {
                               swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                           }).catch(function (e) {
                               $log.error(e);
                               SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                           }).finally(function () {
                               $scope.reloadDataTracking();
                           });

                       } else {
                           swal("Cancelación", "La operación ha sido cancelada", "error");
                       }
                   });
           });

       };

       $scope.dtInstanceWorkMedicineTrackingCallback = function (instance) {
           $scope.dtInstanceWorkMedicineTracking = instance;
       };

       $scope.reloadDataTracking = function () {
           $scope.dtInstanceWorkMedicineTracking.reloadData();
       };

       $scope.editWorkMedicineTracking = function (id) {
           if (id) {
               var req = {id: id};
               $http({
                   method: 'GET',
                   url: 'api/customer/work-medicine/tracking',
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
                           SweetAlert.swal("Información no disponible", "Centro de trabajo no encontrado", "error");

                       } else {
                           SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información", "error");
                       }
                   })
                   .then(function (response) {
                       $scope.tracking = response.data.result;
                       $scope.tracking.dateOf =  new Date($scope.tracking.dateOf.date);
                       if ($scope.tracking.dateOf != null) {
                       }
                   }).finally(function () {
                       $timeout(function () {
                           $scope.loading = false;
                       }, 400);

                       $timeout(function () {
                           //$document.scrollTop(40, 2000);
                       });

                   });


           } else {
               //Se creara nuevo cliente
               log.info("creacion de nuevo cliente");
               $scope.loading = false;
           }
       };

       $scope.saveWorkMedicineTracking = function () {
           var req = {};

           var validateDateMessage = '';

           if ($scope.tracking.type == null) {
               validateDateMessage += "Debe seleccionar el Tipo de seguimiento <br/>";
           }

           if ($scope.tracking.dateOf == null || $scope.tracking.dateOf == '') {
               validateDateMessage += "Debe ingresar la Fecha del seguimiento <br/>";
           }

           if ($scope.tracking.observation == null || $scope.tracking.observation == '') {
               validateDateMessage += "Debe ingresar la Observación <br/>";
           }

           if (validateDateMessage != '') {
               SweetAlert.swal({
                   html: true,
                   title: "Error de validación",
                   text: validateDateMessage,
                   type: "error"
               });
               return;
           }

           var data = JSON.stringify($scope.tracking);
           req.data = Base64.encode(data);
           return $http({
               method: 'POST',
               url: 'api/customer/work-medicine/tracking/save',
               headers: {'Content-Type': 'application/x-www-form-urlencoded'},
               data: $.param(req)
           }).then(function (data) {
               $timeout(function () {
                   $scope.clearWorkMedicineTracking()
                   $scope.reloadDataTracking()
               });
           }).catch(function (e) {
               $log.error(e);
               SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
           }).finally(function () {
           });
       }

       $scope.clearWorkMedicineTracking = function () {
           $scope.tracking = {
               id: 0,
               customerWorkMedicineId: $scope.medicine.id,
               type: null,
               dateOf: null,
               observation: "",
           };
       };

        //----------------------------------------------------------------EMPLOYEE

        $scope.onAddEmployee = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/medicine/customer_absenteeism_disability_employee_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideWorkMedicineEmployeeCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function () {
                //loadEmployees();
            });
        };

        $scope.onAddDisabilityEmployeeList = function () {
            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_disability_employee_list.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/medicine/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideWorkMedicineEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (employee) {
                //loadEmployees();
                var result = $filter('filter')($scope.employees, {id: employee.id});

                if (result.length == 0) {
                    $scope.employees.push(employee);
                }

                $scope.medicine.employee = employee;
            });
        };


        $scope.onSelectComplementaryTest = function($item, $model) {

            if ($scope.complementary.complementaryTest != null) {
                $scope.complementary.result = null;
                var entities = [
                    {name: 'work_medicine_complementary_test_result', value: $scope.complementary.complementaryTest.value}
                ];

                ListService.getDataList(entities)
                    .then(function (response) {
                        $scope.complementaryTestResults = response.data.data.workMedicineComplementaryTestResult;
                    }, function (error) {
                        $scope.status = 'Unable to load customer data: ' + error.message;
                    });
            }
        }

    }]);

app.controller('ModalInstanceSideWorkMedicineEmployeeCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.contractTypes = $rootScope.parameters("employee_contract_type");
    $scope.documentTypes = $rootScope.parameters("employee_document_type");

    var initialize = function () {
        $scope.employee = {
            id: 0,
            customerId: $stateParams.customerId,
            isActive: true,
            contractType: null,
            job: null,
            workPlace: null,
            salary: 0,
            entity: {
                id: 0,
                documentType: null,
                documentNumber: "",
                firstName: "",
                lastName: "",
                isActive: true
            }
        };
    };

    initialize();

    var loadWorkPlace = function () {

        var req = {};
        req.operation = "diagnostic";
        req.customerId = $stateParams.customerId;
        ;


        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/workplace/listProcess',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.workPlaces = response.data.data;
            });
        }).catch(function (e) {

        }).finally(function () {

        });

    };

    loadWorkPlace();

    var loadJobs = function () {
        if ($scope.employee.workPlace != null) {
            var req = {};
            req.operation = "diagnostic";
            req.customerId = $stateParams.customerId;
            ;
            req.workPlaceId = $scope.employee.workPlace.id;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/job/listByWorkPlace',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.jobs = response.data.data;
                });
            }).catch(function (e) {

            }).finally(function () {

            });
        } else {
            $scope.jobs = [];
        }
    };

    $scope.$watch("employee.workPlace", function () {
        //console.log('new result',result);
        loadJobs();
    });

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancelEmployee = function () {
        $uibModalInstance.dismiss('cancel');
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

                $timeout(function () {
                    toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                }, 500);

                return;

            } else {
                $scope.onSaveEmployee();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.onSaveEmployee = function () {

        var req = {};
        var data = JSON.stringify($scope.employee);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-employee/quickSave',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                $scope.attachment = response.data.result;
                toaster.pop('success', 'Operación Exitosa', 'Registro eliminado');
                $scope.onCloseModal();
            });
        }).catch(function (e) {
            $log.error(e);
            toaster.pop('error', 'Error', 'Por favor ingrese los campos requeridos.');
        }).finally(function () {

        });

    };

});

app.controller('ModalInstanceSideWorkMedicineEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var request = {};

    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.employee.id != 0) {
            var req = {
                id: $scope.employee.id,
            };
            $http({
                method: 'GET',
                url: 'api/customer-employee',
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
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.employee = response.data.result;
                        initializeDates();
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.onCloseModal();
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }

    var initializeDates = function() {
        if ($scope.employee.entity.expeditionDate != null) {
            $scope.employee.entity.expeditionDate = new Date($scope.employee.entity.expeditionDate.date);
        }

        if ($scope.employee.entity.birthDate != null) {
            $scope.employee.entity.birthDate = new Date($scope.employee.entity.birthDate.date);
        }
    }

    $scope.dtInstanceDisabilityEmployeeList = {};
		$scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.operation = "diagnostic";
                d.customer_id = $stateParams.customerId;
                d.data = "";
                return d;
            },
            url: 'api/customer-employee',
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

    $scope.dtColumnsDisabilityEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar empleado" tooltip-placement="right" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('workPlace').withTitle("Centro de Trabajo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('neighborhood').withTitle("Centro de Costos").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isActive != null || data.isActive != undefined) {
                    if (data.isActive == 'Activo') {
                        label = 'label label-success';
                        text = 'Activo';
                    } else {
                        label = 'label label-danger';
                        text = 'Inactivo';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            }),
        DTColumnBuilder.newColumn(null).withTitle("Autorización").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isAuthorized != null || data.isAuthorized != undefined) {
                    if (data.isAuthorized == 'Autorizado') {
                        label = 'label label-success';
                        text = 'Autorizado';
                    } else if (data.isAuthorized == 'No Autorizado') {
                        label = 'label label-danger';
                        text = 'No Autorizado';
                    } else {
                        label = 'label label-info';
                        text = 'N/A';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            })
    ];

    var loadRow = function () {
        $("#dtDisabilityEmployeeList a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editDisabilityEmployee(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityEmployeeList.reloadData();
    };

    $scope.viewDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});
