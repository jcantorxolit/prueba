'use strict';
/**
 * controller for Customers
 */
app.controller('customerUnsafeActEditCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside', 'GeoLocationService', 'ListService', '$uibModal',
    'FileUploader', '$document',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside, GeoLocationService, ListService, $uibModal, FileUploader, $document) {

        $scope.statusList = $rootScope.parameters("customer_unsafe_act_status");

        var attachmentUploadedId = 0;
        var $formInstance = null;
        var uploader = $scope.uploader = new FileUploader({
            url: 'api/customer/unsafe-act/upload',
            formData: [],
            removeAfterUpload: true,
            queueLimit: 5
        });

        $scope.conf = {
            imgAnim : 'fadeup'
        };



        // FILTERS

        uploader.filters.push({
            name: 'imageFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
                return '|jpg|png|jpeg|bmp|gif|'.indexOf(type) !== -1;
            }
        });

        //$scope.riskTypeList = $rootScope.parameters("customer_unsafe_act_risk_type");


        $scope.isView = $scope.$parent.action == "view";

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", 0);
            }
        };

        $scope.onDownload = function () {
            var data = {id: $scope.entity.id};
            angular.element("#download")[0].src = "api/customer-unsafe-act/export-zip?data=" + Base64.encode(JSON.stringify(data));
        };

        function getClassificationList() {
            var entities = [
                {name: 'unsafe_act_classification', value: $scope.entity.riskType.id}
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.classificationList = response.data.data.unsafe_act_classification;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getRiskTYpeList() {
            var entities = [
                {name: 'unsafe_act_risk_type'}
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.riskTypeList = response.data.data.unsafe_act_risk_type;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getRiskTYpeList();

        var loadList = function () {

            var req = {};
            req.operation = "diagnostic";
            req.customerId = $stateParams.customerId;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/workplace/listProcess',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.workPlaceList = response.data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        loadList();

        var loadResponsibleList = function () {

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

        loadResponsibleList();

        $scope.onSelectRiskType = function ($item, $model) {
            if ($scope.entity.riskType != null) {
                getClassificationList();
            }
        };

        $scope.onReset = function () {
            $scope.entity = {
                id: $scope.$parent.currentId ? $scope.$parent.currentId : 0,
                customerId: $stateParams.customerId,
                status: $scope.statusList.length > 0 ? $scope.statusList[0] : null,
                dateOf: new Date(),
                workPlace: null,
                riskType: null,
                classification: null,
                place: '',
                lat: 0,
                lng: 0,
                description: '',
                images: [],
                responsible: null,
                reportedBy: $rootScope.currentUserName()
            };

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        };

        $scope.onReset();

        $rootScope.$emit('onCustomerUnsafeActLoaded', { newValue: $scope.entity.id, message: 'Unsafe Act with id ' + $scope.entity.id + ' has been loaded!' });

        $scope.onResetObservation = function () {
            $scope.observation = {
                id: 0,
                customerUnsafeActId: $scope.$parent.currentId ? $scope.$parent.currentId : 0,
                status: null,
                dateOf: new Date(),
                description: '',
            };
        };

        $scope.onResetObservation();

        $scope.onLoadRecord = function () {
            if ($scope.entity.id != 0) {

                // se debe cargar primero la información actual del cliente..

                var req = {
                    id: $scope.entity.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/unsafe-act',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta informaci�n.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Informaci�n no disponible", "Centro de trabajo no encontrado", "error");

                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la informaci�n del centro de trabajo", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.entity = response.data.result;

                            $scope.$parent.currentId = $scope.entity.id;

                            getClassificationList();

                            if ($scope.entity.dateOf != null) {
                                $scope.entity.dateOf = new Date($scope.entity.dateOf.date);
                            }

                            if ($scope.entity.image != null && $scope.entity.image.path != null) {
                                $scope.noImage = false;
                            } else {
                                $scope.noImage = true;
                            }

                            if ($scope.entity.images != null) {
                                uploader.queueLimit = 5 - $scope.entity.images.length;
                            }

                            console.log(response);
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);
                    });
            } else {
                GeoLocationService.getCurrentPosition().then(function (position) {
                    console.log(position, 'current position');
                    $scope.entity.lat = position.coords.latitude;
                    $scope.entity.lng = position.coords.longitude;
                });
                $scope.loading = false;
            }
        };

        $scope.onLoadRecord();

        if ($scope.entity.image == null) {
            $scope.noImage = true;
        }

        $scope.removeImage = function () {
            $scope.noImage = true;
        };

        $scope.master = $scope.indicator;
        $scope.form = {

            submit: function (form) {
                $formInstance = form;
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
                    save();
                }

            },
            reset: function (form) {
                $scope.clear();
            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/unsafe-act/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    SweetAlert.swal("Validación exitosa", "Reporte creado correctamente", "success");

                    attachmentUploadedId = response.data.result.id;

                    if (uploader.queue.length > 0) {
                        $scope.entity = response.data.result;
                        uploader.uploadAll();
                    } else {
                        $scope.onCancel();
                    }
                }, 500);
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });


        };


        //-------------------------------------------------------------OBSERVATIONS




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
            var formData = {id: attachmentUploadedId};
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
            //$scope.onCancel();
            $scope.onLoadRecord();
            toaster.pop('success', 'Operación exitosa', 'Se subío correctamente la imagen');
        };

        $scope.addPhoto = function(){
            var n = Math.floor(Math.random() * 13) + 1;
            var id = Math.floor(Math.random() * 9999999999) + 1;
            $scope.images.push(
                {
                    id : id,
                    url : 'https://thatisuday.github.io/ng-image-gallery/demo-images/' + n + '.jpg',
                    thumbUrl : 'https://thatisuday.github.io/ng-image-gallery/demo-images/thumbs/' + n + '.jpg',
                    bubbleUrl : 'https://thatisuday.github.io/ng-image-gallery/demo-images/bubbles/' + n + '.jpg'
                }
            );
        }

        $scope.removePhoto = function(){
            if($scope.images.length > 1) $scope.images.pop();
        }

        // Thumbnails
        $scope.thumbnails = true;
        $scope.toggleThumbnails = function(){
            $scope.thumbnails = !$scope.thumbnails;
        }

        // Inline
        $scope.inline = false;
        $scope.toggleInline = function(){
            $scope.inline = !$scope.inline;
        }

        // Bubbles
        $scope.bubbles = true;
        $scope.toggleBubbles = function(){
            $scope.bubbles = !$scope.bubbles;
        }

        // Image bubbles
        $scope.imgBubbles = false;
        $scope.toggleImgBubbles = function(){
            $scope.imgBubbles = !$scope.imgBubbles;
        }

        // Background close
        $scope.bgClose = false;
        $scope.closeOnBackground = function(){
            $scope.bgClose = !$scope.bgClose;
        }

        // Gallery methods gateway
        $scope.methods = {};
        $scope.openGallery = function(){
            $scope.methods.open();
        };

        // Gallery callbacks
        $scope.opened = function(){
            console.info('Gallery opened!');
        }

        $scope.closed = function(){
            console.warn('Gallery closed!');
        }

        $scope.delete = function(img, cb){
            //cb();
        }

    }]);
