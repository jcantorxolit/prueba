"use strict";
/**
 * controller for Customers
 */
app.controller("customerImprovementPlanDocumentCtrl", [
    "$scope",
    "$stateParams",
    "$log",
    "DTOptionsBuilder",
    "DTColumnBuilder",
    "DTColumnDefBuilder",
    "$compile",
    "toaster",
    "$state",
    "SweetAlert",
    "$rootScope",
    "$http",
    "$timeout",
    "$uibModal",
    "flowFactory",
    "cfpLoadingBar",
    "$filter",
    "$document",
    "FileUploader",
    "$localStorage",
    "$aside",
    "ListService",
    function (
        $scope,
        $stateParams,
        $log,
        DTOptionsBuilder,
        DTColumnBuilder,
        DTColumnDefBuilder,
        $compile,
        toaster,
        $state,
        SweetAlert,
        $rootScope,
        $http,
        $timeout,
        $uibModal,
        flowFactory,
        cfpLoadingBar,
        $filter,
        $document,
        FileUploader,
        $localStorage,
        $aside,
        ListService
    ) {
        $scope.loading = true;
        $scope.isCreate = $scope.$parent.currentId == 0;
        $scope.isView = $scope.$parent.editMode == "view";

        var attachmentUploadedId = 0;
        var lastLabel = "M";

        $scope.documentClassification = $rootScope.parameters(
            "customer_document_classification"
        );
        $scope.documentStatus = $rootScope.parameters(
            "customer_document_status"
        );

        getList();

        function getList() {
            var entities = [
                {
                    name: "customer_document_type",
                    value: $stateParams.customerId,
                },
            ];

            ListService.getDataList(entities).then(
                function (response) {
                    $scope.documentType =
                        response.data.data.customerDocumentType;
                },
                function (error) {
                    $scope.status =
                        "Unable to load customer data: " + error.message;
                }
            );
        }

        var init = function () {
            $scope.attachment = {
                id: 0,
                customerImprovementPlanId:  $scope.$parent.currentId,
                type: null,
                classification: null,
                description: "",
                status: $scope.documentStatus ? $scope.documentStatus[0] : null,
                version: 1,                
                label: lastLabel,
            };
        };

        init();

        $scope.onCloseModal = function () {
            $uibModalInstance.close(1);
        };

        $scope.onCancel = function () {
            $uibModalInstance.dismiss("cancel");
        };

        $scope.onClear = function () {
            init();
        };

        var uploader = ($scope.uploader = new FileUploader({
            url: "api/customer-improvement-plan-document/upload",
            formData: [],
            removeAfterUpload: true,
        }));

        uploader.filters.push({
            name: "customFilter",
            fn: function (item /*{File|FileLikeObject}*/, options) {
                return this.queue.length < 10;
            },
        });

        // CALLBACKS
        uploader.onBeforeUploadItem = function (item) {
            var formData = { id: attachmentUploadedId };
            item.formData.push(formData);
        };

        uploader.onCompleteAll = function () {
            $scope.reloadData();
            $scope.onClear();
        };

        $scope.form = {
            submit: function (form) {
                var firstError = null;

                if (form.$invalid) {
                    var field = null,
                        firstError = null;
                    for (field in form) {
                        if (field[0] != "$") {
                            if (firstError === null && !form[field].$valid) {
                                firstError = form[field].$name;
                            }

                            if (form[field].$pristine) {
                                form[field].$dirty = true;
                            }
                        }
                    }
                    angular
                        .element(".ng-invalid[name=" + firstError + "]")
                        .focus();
                    SweetAlert.swal(
                        "El formulario contiene errores!",
                        "Por favor corrige los errores del formulario e Intentalo de nuevo.",
                        "error"
                    );
                    return;
                } else {
                    SweetAlert.swal(
                        "Validación exitosa",
                        "Procediendo con el guardado...",
                        "success"
                    );
                    save();
                }
            },
            reset: function (form) {
                form.$setPristine(true);
            },
        };

        var save = function () {
            lastLabel = $scope.attachment.label;

            var req = {};
            var data = JSON.stringify($scope.attachment);
            req.data = Base64.encode(data);

            return $http({
                method: "POST",
                url: "api/customer-improvement-plan-document/save",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                data: $.param(req),
            })
                .then(function (response) {
                    $timeout(function () {
                        if (uploader.queue.length > 0) {
                            attachmentUploadedId = response.data.result.id;
                            uploader.uploadAll();
                        } else {
                            $scope.reloadData();
                            $scope.onClear();
                        }
                    });
                })
                .catch(function (e) {
                    SweetAlert.swal(
                        "Error de guardado",
                        "Error guardando el registro. Por favor verifique los datos ingresados!",
                        "error"
                    );
                })
                .finally(function () {
                    $scope.reloadData();
                });
        };

        $scope.dtOptionsCustomerImprovementPlanDoc = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption("responsive", true)
            .withOption("ajax", {
                // Either you specify the AjaxDataProp here
                data: function (d) {
                    d.customerImprovementPlanId =  $scope.$parent.currentId;                    
                    d.statusCode = "2";
                    return JSON.stringify(d);
                },
                url: "api/customer-improvement-plan-document",
                contentType: "application/json",
                type: "POST",
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {},
            })
            .withDataProp("data")
            .withOption("order", [[0, "desc"]])
            .withOption("serverSide", true)
            .withOption("processing", true)
            .withOption("fnPreDrawCallback", function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption("fnDrawCallback", function () {
                //log.info("fnDrawCallback");
                loadRow();
                //Pace.stop();
            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption("language", {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })

            .withPaginationType("full_numbers")
            .withOption("createdRow", function (row, data, dataIndex) {
                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);
            });
        $scope.dtColumnsCustomerImprovementPlanDoc = [
            DTColumnBuilder.newColumn(null)
                .withTitle("Acciones")
                .withOption("width", 150)
                .notSortable()
                .renderWith(function (data, type, full, meta) {
                    var url = data.documentUrl ? data.documentUrl : "";
                    var downloadUrl =
                        "api/customer-improvement-plan-document/download?id=" +
                        data.id;

                    var actions = "";
                    var editTemplate =
                        '<a target="_self" class="btn btn-primary btn-xs downloadDocumentRow lnk" href="' +
                        downloadUrl +
                        '" uib-tooltip="Descargar anexo" data-id="' +
                        data.id +
                        '" >' +
                        '   <i class="fa fa-download"></i></a> ';
                    var viewTemplate =
                        '<a class="btn btn-info btn-xs openDocumentRow lnk" target="_blank" href="' +
                        url +
                        '" uib-tooltip="Abrir anexo" data-id="' +
                        data.id +
                        '" >' +
                        '   <i class="fa fa-folder-open-o"></i></a> ';

                    var isButtonVisible = false;

                    if (data.protectionType == null) {
                        isButtonVisible = true;
                    } else if (data.protectionType == "public") {
                        isButtonVisible = true;
                    } else if (
                        data.protectionType == "private" &&
                        data.hasPermission == 1
                    ) {
                        isButtonVisible = true;
                    }

                    if ($rootScope.can("clientes_anexo_open")) {
                        if (url != "") {
                            actions += viewTemplate;
                        }
                    }

                    if ($rootScope.can("clientes_anexo_download")) {
                        if (url != "") {
                            actions += editTemplate;
                        }
                    }

                    return isButtonVisible ? actions : "";
                }),
            DTColumnBuilder.newColumn("documentType")
                .withTitle("Tipo de documento")
                .withOption("width", 200),
            DTColumnBuilder.newColumn("classification")
                .withTitle("Clasificación")
                .withOption("width", 200),
            DTColumnBuilder.newColumn("description")
                .withTitle("Descripción")
                .withOption("width", 200),
            DTColumnBuilder.newColumn("version")
                .withTitle("Versión")
                .withOption("width", 200),
            DTColumnBuilder.newColumn("createdAt")
                .withTitle("Fecha Creación")
                .withOption("width", 200)
                .withOption("defaultContent", ""),
            DTColumnBuilder.newColumn("status")
                .withTitle("Estado")
                .withOption("width", 200)
                .renderWith(function (data, type, full, meta) {
                    var label = "";
                    switch (data) {
                        case "Vigente":
                            label = "label label-success";
                            break;

                        case "Anulado":
                            label = "label label-danger";
                            break;
                    }

                    var status =
                        '<span class="' + label + '">' + data + "</span>";

                    return status;
                }),
            DTColumnBuilder.newColumn("label")
                .withTitle("Origen")
                .withOption("width", 200)
                .withOption("defaultContent", ""),
        ];

        var loadRow = function () {
            $("#dtCustomerImprovementPlanDoc a.editRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");
                //$scope.editTracking(id);
                if (url == "") {
                    SweetAlert.swal(
                        "Error en la descarga",
                        "No existe un anexo para descargar",
                        "error"
                    );
                } else {
                    jQuery("#downloadDocument")[0].src =
                        "api/customer-improvement-plan-document/download?id=" +
                        id;
                }
            });
        };

        $scope.dtInstanceCustomerImprovementPlanDocCallback = function (instance) {
            $scope.dtInstanceCustomerImprovementPlanDoc = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerImprovementPlanDoc.reloadData();
        };

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.$parent.$parent.$parent.$parent.navToSection("list", "list", 0);
            }
        }
    },
]);
