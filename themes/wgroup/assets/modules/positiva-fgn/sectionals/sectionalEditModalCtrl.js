app.controller('SectionalEditModalCtrl', function($rootScope, $stateParams, $scope, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder,
    DTColumnDefBuilder, $log, $timeout, SweetAlert, $http, ListService) {

    $scope.regionalList = [];

    var initialize = function() {
        $scope.entity = {
            id: $scope.sectionalId || null,
            regional: null,
            name: null,
            nit: null,
            isActive: true,
        }
    }
    initialize();
    getList();
    load();

    function getList() {
        var entities = [
            { name: 'positiva_fgn_consultant_sectional', criteria: { regionalId: null } }
        ];

        ListService.getDataList(entities)
            .then(function(response) {
                $scope.regionalList = response.data.data.regionalList;
            }, function(error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.form = {
        submit: function(form) {
            $scope.Form = form;

            if (form.$valid) {
                save();
                return;
            }

            var field = null,
                firstError = null;
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
        },
        reset: function() {
            $scope.Form.$setPristine(true);
            initialize();
        }
    };

    var save = function() {
        var data = JSON.stringify($scope.entity);
        var req = {
            data: Base64.encode(data)
        };

        return $http({
            method: 'POST',
            url: 'api/positiva-fgn-sectional/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            $scope.form.reset();
            SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
            $scope.onBack();
        }).catch(function(e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", e.data.message, "error");
        });
    };

    function load() {
        if ($scope.entity.id == null) {
            return;
        }

        var req = {};
        var data = JSON.stringify({ id: $scope.entity.id });
        req.data = Base64.encode(data);

        return $http({
            method: 'post',
            url: 'api/positiva-fgn-sectional/show',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            $scope.entity = response.data.result;
        }).catch(function(e) {
            $log.error(e);
            SweetAlert.swal("Error al consultar la información.", e.data.message, "error");
        });
    }

    $scope.onBack = function() {
        $uibModalInstance.close(1);
    }

});