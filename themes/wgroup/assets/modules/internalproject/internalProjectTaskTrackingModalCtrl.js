
app.controller('ModalInstanceTrackingCtrl', function ($scope, $uibModalInstance, task, action, $log, $timeout, SweetAlert, $http) {

    $scope.task = task;

    $scope.task.tracking = {
        action: action,
        description: ""
    }

    $scope.ok = function () {
        $uibModalInstance.close(1);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.saveTracking = function () {
        save();
    };

    var save = function () {
        var req = {};

        var data = JSON.stringify($scope.task);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/internal-project/task/update',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $uibModalInstance.close(1);
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };
});
