app.controller('ModalInstanceSideCustomerProfileLicenseFinishCtrl',
    function ($rootScope, $stateParams, $scope, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
              $compile, $log, SweetAlert, $http, ListService, data) {

        $scope.minDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        };


        var initialize = function () {
            $scope.entity = {
                id: data.id,
                finishDate: null,
                reason: null
            };
        };

        initialize();

        $scope.form = {
            submit: function (form) {
                $scope.Form = form;

                if (form.$valid) {
                    save();
                    return;
                }

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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            }
        };


        var save = function () {
            var data = JSON.stringify($scope.entity);
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-licenses/finish',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function () {
                $uibModalInstance.close();
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message, "error");
            });
        };

    });

