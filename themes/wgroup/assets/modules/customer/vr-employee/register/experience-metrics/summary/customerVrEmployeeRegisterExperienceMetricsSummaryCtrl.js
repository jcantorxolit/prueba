'use strict';
/**
  * controller for Customers
*/
app.controller('customerVrEmployeeRegisterExperienceMetricsSummaryCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
    $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, customerVrEmployeeService, $uibModal) {

    var vrEmployeeId = customerVrEmployeeService.getId();
    $scope.allData = customerVrEmployeeService.getEntity();
    $scope.dtInstanceVrEmployeeREMS = {};
    $scope.dtOptionsVrEmployeeREMS = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d.customerId = $stateParams.customerId;
                d.customerVrEmployeeId = vrEmployeeId;
                return JSON.stringify(d);
            },
            url: 'api/customer-vr-employee-scene-answer/summary',
            contentType: "application/json",
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
        })
        .withOption('language', {
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsVrEmployeeREMS = [
            DTColumnBuilder.newColumn('experience').withTitle("Experiencia").withOption('width', 220).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('scene').withTitle("Escena").withOption('width', 220).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('question').withTitle("Indicador").withOption('width', 220).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Valoración").withOption('width', 120)
            .renderWith(function (data, type, full, meta) {

                var icon = "";
                if(data.answerVal == "SI") {
                    icon = '<i class=" text-success fa fa-2x fa-check-circle-o" aria-hidden="true"></i>';
                }
                else if(data.answerVal == "NO") {
                    icon = '<i class=" text-danger fa fa-2x fa-ban" aria-hidden="true"></i>';
                }
                else if(data.answerVal == "NA") {
                    icon = '<i class=" text-inverse fa fa-2x fa-minus-circle" aria-hidden="true"></i>';
                }
                else if(data.answerVal == "NU") {
                    icon = '<i class=" text-warning fa fa-2x fa-exclamation-circle" aria-hidden="true"></i>';
                }
                else {
                    icon = '<i class=" text-warning fa fa-2x fa-question-circle" aria-hidden="true"></i>';
                    data.answer = "SELECCIONE";
                }

                var status = '<span class="vertical-align-sub">' + icon + ' </span>' + data.answer;
                return status;
            }),
    ];


    $scope.dtInstanceVrEmployeeREMSCallback = function (instance) {
        $scope.dtInstanceVrEmployeeREMS = instance;
    };


    // ------------------------------------    OBSERVATIONS   ---------------


    $scope.dtInstanceVrEmployeeRegisterREMSO = {};
    $scope.dtOptionsVrEmployeeRegisterREMSO = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d.customerId = $stateParams.customerId;
                d.customerVrEmployeeId = vrEmployeeId;
                return JSON.stringify(d);
            },
            url: 'api/customer-vr-employee-experience-answer/observations',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function () {
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[1, 'desc']])
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


    $scope.dtColumnsVrEmployeeRegisterREMSO = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 40).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var planTemplate = '<a class="btn btn-warning btn-xs planRow lnk" href="#" uib-tooltip="Agregar Plan Mejoramiento" data-experience=' + data.experience + ' data-id=' + data.id + ' data-obstype=' + data.observationType + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += planTemplate;
                return actions;
            }),
            DTColumnBuilder.newColumn('experience').withTitle("Experiencia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observationType').withTitle("Tipo de Observación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observation').withTitle("Observación").withOption('width', 200).withOption('defaultContent', '')
    ];

    var loadRow = function () {
        $("#dtVrEmployeeRegisterREMSO a.planRow").on("click", function () {
            var id = $(this).data("id");
            var experience = $(this).data("experience");
            var obstype = $(this).data("obstype");
            var data = {
                id: id,
                experience: experience,
                observationType: obstype
            }
            $scope.onAddPlan(data);
        });
    };

    $scope.dtInstanceVrEmployeeRegisterREMSOCallback = function (instance) {
        $scope.dtInstanceVrEmployeeRegisterREMSO = instance;
    };

    $scope.onFinish = function() {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/vr-employee/register/experience-metrics/summary/experience_evaluation_modal.htm",
            placement: 'right',
            windowTopClass: 'top-modal',
            size: 'md',
            backdrop: true,
            controller: 'ModalInstanceSideEvaluateExperience',
            scope: $scope,
        });
        modalInstance.result.then(function (response) {

        });
    };

    $scope.onAddPlan = function(entity) {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/customer_improvement_plan_modal.htm",
            placement: 'right',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideVrEmployeeSummaryImprovementPlanCtrl',
            scope: $scope,
            resolve: {
                entity: function () {
                    return entity;
                }
            }
        });
        modalInstance.result.then(function () {
            // $scope.reloadData();
        }, function() {
            // $scope.reloadData();
        });
    }


});

app.controller('ModalInstanceSideEvaluateExperience', function ($rootScope, $stateParams, $scope, $log, $timeout, $uibModalInstance,
    SweetAlert, $http, toaster, $filter, $compile, $aside, customerVrEmployeeService, ListService, FileUploader) {

    var employeeExperience = customerVrEmployeeService.getId();
    $scope.evaluationOptions = [];
    var attachmentUploadedId = 0;
    var init = function() {
        $scope.entity = {
            id: null,
            observationType: null,
            observationValue: null,
            customerVrEmployeeId: employeeExperience,
        }
    }
    init();

    function getList() {
        var entities = [
            { name: 'customer_vr_employee_evaluation_options' },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.evaluationOptions = response.data.data.evaluationOptions;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }
    getList();


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
                if (lineas.length) {
                    var file = dataURLtoFile(miCanvas.toDataURL('image/png'),"firm.png");
                    uploaderResource.addToQueue([file]);
                }
                save();
            }
        },
        reset: function (form) {

        }
    };

    function dataURLtoFile(dataurl, filename) {
        var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
        bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
        while(n--){
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new File([u8arr], filename, {type:mime});
    }

    var save = function () {

        if(lineas.length == 0) {
            SweetAlert.swal("El formulario contiene errores!", "La firma es requerida.", "error");
            return;
        }

        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-vr-employee-experience-evaluation/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.entity = response.data.result;
                if (uploaderResource.queue.length > 0) {
                    attachmentUploadedId = response.data.result.id;
                    uploaderResource.uploadAll();
                }
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        });
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };


    var lineas = [];
    var correccionX = 0;
    var correccionY = 0;
    var pintarLinea = false;
    var miCanvas,posicion;

    $timeout(function(){
        $rootScope.$apply(function(){
            miCanvas = document.querySelector('#pizarra');
            posicion = miCanvas.getBoundingClientRect()
            correccionX = posicion.x;
            correccionY = posicion.y;

            miCanvas.addEventListener('mousedown', empezarDibujo, false);
            miCanvas.addEventListener('mousemove', dibujarLinea, false);
            miCanvas.addEventListener('mouseup', pararDibujar, false);

            // Eventos pantallas táctiles
            miCanvas.addEventListener('touchstart', empezarDibujo, false);
            miCanvas.addEventListener('touchmove', dibujarLinea, false);

        });

    }, 1000)

    function empezarDibujo () {
        pintarLinea = true;
        lineas.push([]);
    };

    function dibujarLinea (event) {
        event.preventDefault();
        if (pintarLinea) {
            var ctx = miCanvas.getContext('2d')
            ctx.lineJoin = ctx.lineCap = 'round';
            ctx.lineWidth = 5;
            ctx.strokeStyle = '#000';
            var nuevaPosicionX = 0;
            var nuevaPosicionY = 0;
            if (event.changedTouches == undefined) {
                nuevaPosicionX = event.layerX;
                nuevaPosicionY = event.layerY;
            } else {
                nuevaPosicionX = event.changedTouches[0].pageX - correccionX;
                nuevaPosicionY = event.changedTouches[0].pageY - correccionY;
            }
            // Guarda la linea
            lineas[lineas.length - 1].push({
                x: nuevaPosicionX,
                y: nuevaPosicionY
            });
            // Redibuja todas las lineas guardadas
            ctx.beginPath();
            lineas.forEach(function (segmento) {
                ctx.moveTo(segmento[0].x, segmento[0].y);
                segmento.forEach(function (punto, index) {
                    ctx.lineTo(punto.x, punto.y);
                });
            });
            ctx.stroke();
        }
    }

    function pararDibujar () {
        pintarLinea = false;
    }

    $scope.onClear = function() {
        lineas = [];
        var ctx = miCanvas.getContext('2d')
        ctx.clearRect(0, 0, miCanvas.width, miCanvas.height);
    }


    var uploaderResource = $scope.uploaderResource = new FileUploader({
        url: 'api/customer-vr-employee-experience-evaluation/upload',
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
        SweetAlert.swal("Procesando", "Estamos generando el certificado, espera un momento.", "info");
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
        SweetAlert.swal({
            title: "Registro",
            text: "La información ha sido guardada satisfactoriamente",
            type: "success",
            showCancelButton: false,
            confirmButtonText: "OK",
        },
         function(){
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
         });
    };


});


app.controller('ModalInstanceSideVrEmployeeSummaryImprovementPlanCtrl', function ($stateParams, $rootScope, $scope, $uibModalInstance, entity,
        $log, $timeout, SweetAlert, $filter, FileUploader, $http, DTOptionsBuilder, DTColumnBuilder, $compile) {

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    console.log(entity)

    $scope.responsibleList = [];

    $scope.typesAlert = $filter('orderBy')($rootScope.parameters("tracking_alert_type"), 'id', false);
    $scope.typesTime = $rootScope.parameters("tracking_alert_timeType");
    $scope.statusAlert = $rootScope.parameters("tracking_alert_status");
    $scope.preferencesAlert = $rootScope.parameters("tracking_alert_preference");
    $scope.typeList = $rootScope.parameters("improvement_plan_type");

    var init = function () {
        $scope.improvement = {
            id: 0,
            customerId: $stateParams.customerId,
            classificationName: entity.experience,
            classificationId: entity.experience,
            entityName: 'RVE',
            entityId: entity.id,
            type: null,
            endDate: null,
            description: entity.observationType,
            observation: '',
            responsible: null,
            isRequiresAnalysis: false,
            status: {
                id: 0,
                value: 'CR',
                item: 'Creada'
            },
            trackingList: [],
            alertList: []
        };
    }

    init();

    $scope.onLoadRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id
            };
            $http({
                method: 'GET',
                url: 'api/customer/improvement-plan',
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
                        if (response.data.result != null && response.data.result != '') {
                            $scope.improvement = response.data.result;

                            initializeDates();
                        }
                    }, 400);

                }).finally(function () {

                });
        } else {
            $scope.loading = false;
        }
    }

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {

    }

    var loadList = function () {

        var req = {
            customer_id: $stateParams.customerId
        };

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan/list-data',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.responsibleList = response.data.data.responsible;
            });
        }).catch(function (e) {
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    loadList();

    $scope.master = $scope.improvement;

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

            $scope.improvement = angular.copy($scope.master);
            form.$setPristine(true);

        }
    };

    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.improvement);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer/improvement-plan/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                init();
            });
        }).catch(function (e) {

            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.reloadData();
        });
    };

    var initializeDates = function () {
        if ($scope.improvement.endDate != null) {
            $scope.improvement.endDate = new Date($scope.improvement.endDate.date);
        }

        angular.forEach($scope.improvement.trackingList, function (model, key) {
            if (model.startDate != null) {
                model.startDate = new Date(model.startDate.date);
            }
        });
    }

    //----------------------------------------------------------------TRACKING
    $scope.onAddTracking = function () {

        $timeout(function () {
            if ($scope.improvement.trackingList == null) {
                $scope.improvement.trackingList = [];
            }
            $scope.improvement.trackingList.push(
                {
                    id: 0,
                    customerImprovementPlanId: 0,
                    responsible: null,
                    startDate: null,
                }
            );
        });
    };

    $scope.onRemoveTracking = function (index) {
        SweetAlert.swal({
                title: "Está seguro?",
                text: "Desea confirmar la eliminación de este registro ?",
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
                    $timeout(function () {
                        // eliminamos el registro en la posicion seleccionada
                        var date = $scope.improvement.trackingList[index];

                        $scope.improvement.trackingList.splice(index, 1);

                        if (date.id != 0) {
                            var req = {};
                            req.id = date.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/improvement-plan-tracking/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                            });
                        }
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    }

    //----------------------------------------------------------------VERIFICATION MODE
    $scope.onAddAlert = function () {

        $timeout(function () {
            if ($scope.improvement.alertList == null) {
                $scope.improvement.alertList = [];
            }
            $scope.improvement.alertList.push(
                {
                    id: 0,
                    customerImprovementPlanId: 0,
                    type: null,
                    preference: null,
                    time: 0,
                    timeType: null,
                    status: null,
                }
            );
        });
    };

    $scope.onRemoveAlert = function (index) {
        SweetAlert.swal({
                title: "Está seguro?",
                text: "Desea confirmar la eliminación de este registro ?",
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
                    $timeout(function () {
                        // eliminamos el registro en la posicion seleccionada
                        var date = $scope.improvement.alertList[index];

                        $scope.improvement.alertList.splice(index, 1);

                        if (date.id != 0) {
                            var req = {};
                            req.id = date.id;
                            $http({
                                method: 'POST',
                                url: 'api/customer/improvement-plan-alert/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
                                swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                            });
                        }
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    }


    //----------------------------------------------------------------IMPROVEMENT PLAN LIST
    $scope.dtInstanceImprovementPlan = {};
    $scope.dtOptionsImprovementPlan = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function (d) {
                d.customerId = $scope.improvement.customerId;
                d.entityId = $scope.improvement.entityId;
                d.entityName = $scope.improvement.entityName;

                return JSON.stringify(d);
            },
            url: 'api/customer-improvement-plan-entity',
            contentType: "application/json",
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

    $scope.dtColumnsImprovementPlan = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if ($rootScope.can('cliente_plan_mejoramiento_edit')) {
                    actions += editTemplate;
                }

                if ($rootScope.can('cliente_plan_mejoramiento_delete')) {
                    actions += deleteTemplate;
                }

				return !$scope.isView ? actions : null;
            }),
        DTColumnBuilder.newColumn('origin').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('description').withTitle("Hallazgo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('responsibleName').withTitle("Responsable").withOption('defaultContent', ''),
        DTColumnBuilder.newColumn(null).withTitle("Fecha Cierre").withOption('width', 200)
        .renderWith(function (data, type, full, meta) {
            if (typeof data.endDate == 'object' && data.endDate != null) {
                return moment(data.endDate.date).format('DD/MM/YYYY');
            }
            return data.endDate != null ? moment(data.endDate).format('DD/MM/YYYY') : '';
        }),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200).withOption('defaultContent', '')
        .renderWith(function (data, type, full, meta) {
            var label = 'label label-success';
            var text = data.status;

            switch (data.statusCode) {
                case "AB":
                    label = 'label label-info'
                    break;

                case "CO":
                    label = 'label label-success'
                    break;

                case "CA":
                    label = 'label label-danger'
                    break;
            }

            return '<span class="' + label + '">' + text + '</span>';
        })
    ];

    var loadRow = function () {

        $("#dtImprovementPlan a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.onLoadRecord(id);
        });

        $("#dtImprovementPlan a.delRow").on("click", function () {
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
                            url: 'api/customer/improvement-plan/delete',
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

    $scope.dtInstanceImprovementPlanCallback = function (dtInstance) {
        $scope.dtInstanceImprovementPlan = dtInstance;
    }

    $scope.reloadData = function () {
        $scope.dtInstanceImprovementPlan.reloadData();
    };

});
