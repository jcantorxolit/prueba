'use strict';
/**
 * controller for customerJobConditionsEvaluationModalCtrl
 */
app.controller('customerJobConditionsEvaluationModalCtrl',
    function ($rootScope, $stateParams, $scope, $log, $timeout, $uibModalInstance,
              SweetAlert, $http, toaster, $filter, $compile, $aside, jobConditionRegisterNavigationService, jobConditionRegisterService, FileUploader, dataSource) {

        $scope.data = jobConditionRegisterService.getCurrentClassification();
        $scope.answerOptions = $rootScope.parameters("wg_customer_job_conditions_answer_types");

        $scope.isView = dataSource.isView;
        $scope.goToBefore = false;

        var uploader = $scope.uploader = new FileUploader({
            url: 'api/customer-jobconditions/evaluation/evidence/upload',
            formData: [],
            removeAfterUpload: true,
            queueLimit: 5
        });

        $scope.conf = {
            imgAnim : 'fadeup'
        };

        uploader.filters.push({
            name: 'imageFilter',
            fn: function (item/*{File|FileLikeObject}*/, options) {
                var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
                return '|jpg|png|jpeg|bmp|gif|'.indexOf(type) !== -1;
            }
        });


        var init = function () {
            $scope.entity = {
                classificationId: $scope.data.id,
                evaluationId: jobConditionRegisterNavigationService.getEvaluationId(),
                questionList: [],
                images: []
            }

            $scope.isLast = jobConditionRegisterService.isLastClassification();
        }
        init();

        $scope.onChangeAnswer = function () {
            if ($scope.entity.questionList[0]) {
                $scope.nextEnable = !$scope.entity.questionList[0].questions.some(function (question) {
                    return question.answer === null;
                });
            }
        };


        $scope.loadQuestions = function () {
            var data = JSON.stringify($scope.entity);
            var req = {
                data: Base64.encode(data)
            };

            $http({
                method: 'post',
                url: 'api/customer-jobconditions/evaluation/get-questions',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.entity.questionList = response.data.result;
                $scope.onChangeAnswer();
            }).catch(function (e) {
                SweetAlert.swal("Error", "Ocurrió un problema cargar la información.", "error");
            });
        };

        $scope.loadEvidences = function () {
            var data = JSON.stringify($scope.entity);
            var req = {
                data: Base64.encode(data)
            };

            $http({
                method: 'post',
                url: 'api/customer-jobconditions/evaluation/evidences',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.entity.images = response.data.result;
                uploader.queueLimit = 5 - $scope.entity.images.length;
            }).catch(function (e) {
                SweetAlert.swal("Error", "Ocurrió un problema cargar la información.", "error");
            });
        };


        var onLoadRecord = function () {
            $scope.loadQuestions();
            $scope.loadEvidences();
            $scope.goToBefore = false;
        }

        onLoadRecord();


        $scope.form = {
            submit: function (form) {
                if ($scope.isView) {
                    loadNextClassificationAfterSave();
                    return;
                }


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
            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-jobconditions/evaluation/save-answers',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    loadNextClassificationAfterSave();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            });
        };

        function loadNextClassificationAfterSave() {
            if ($scope.goToBefore == true) {
                $scope.loadDataBeforeClassification();
            } else if ($scope.isLast) {
                if (!$scope.isView) {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                }

                $uibModalInstance.close(1);
            } else {
                $scope.loadNextClassification();
            }
        }


        $scope.loadNextClassification = function () {
            $scope.data = jobConditionRegisterService.getNextClassification();
            init();
            onLoadRecord();
        };

        $scope.loadBeforeClassification = function () {
            $scope.goToBefore = true;

            if (!$scope.isView) {
                $scope.form.submit(angular.element("form"));
                return;
            }

            $scope.loadDataBeforeClassification();
        };

        $scope.loadDataBeforeClassification = function () {
            $scope.data = jobConditionRegisterService.getBeforeClassification();
            init();
            onLoadRecord();
        };

        $scope.onClose = function () {
            if (!$scope.isView) {
                $scope.form.submit(angular.element("form"));
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
            }

            $uibModalInstance.close(1);
        }


        $scope.onUploadEvidences = function () {
            uploader.uploadAll();
        };

        $scope.onDownload = function () {
            var data = {
                evaluationId: $scope.entity.evaluationId,
                classificationId: $scope.entity.classificationId
            };

            angular.element("#download")[0].src = "api/customer-jobconditions/evaluation/evidence/download?data=" + Base64.encode(JSON.stringify(data));
        };


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
                classificationId: $scope.entity.classificationId,
                evaluationId: jobConditionRegisterNavigationService.getEvaluationId()
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
            $scope.loadEvidences();
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




    });
