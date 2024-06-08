app.controller('ModalInternalProjecCommentInstanceSideCtrl',
    function (
        $stateParams, $rootScope, $scope, $uibModalInstance, item, isView, $timeout, $http, toaster, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.isView = isView;

    var onInit = function() {
        $scope.entity = {
            id: 0,
            customerInternalProjectId: item.id,
            comment: null,
            type: "M",
        };
    }

    onInit();

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onClear = function () {
        onInit();
    }

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
                $scope.onSave();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.onSave = function () {

        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-internal-project-comment/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                toaster.pop('success', 'Operación Exitosa', 'La información ha sido guardada satisfactoriamente.');
                $scope.onClear();
                $scope.reloadData();
            });
        }).catch(function (e) {
            toaster.pop('Error', 'Error inesperado', e);
        }).finally(function () {

        });

    };

    $scope.dtOptionsQuestionComment = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.customerInternalProjectId = item.id;
                return JSON.stringify(d);
            },
            url: 'api/customer-internal-project-comment',
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
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {

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

    $scope.dtColumnsQuestionComment = [
        DTColumnBuilder.newColumn('comment')
            .withTitle("Comentario"),

        DTColumnBuilder.newColumn('createdBy')
            .withTitle("Usuario")
            .withOption('width', 200)
            .withOption('defaultContent', 200),

        DTColumnBuilder.newColumn('createdAt')
            .withTitle("Fecha")
            .withOption('width', 200)
    ];

    $scope.dtInstanceQuestionCommentCallback = function (instance) {
        $scope.dtInstanceQuestionComment = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceQuestionComment.reloadData();
    };

});
