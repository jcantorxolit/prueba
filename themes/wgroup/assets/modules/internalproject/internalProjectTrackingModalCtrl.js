app.controller('ModalInstanceInternalProjectTrackingCtrl', function ($scope, $uibModalInstance, project, action, filters, customerId, $log, $timeout, SweetAlert, $http) {

    $scope.report = project;

    $scope.report.tracking = {
        action: action,
        description: ""
    }

    $scope.onClose = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.saveTracking = function () {
        save();
    };

    var save = function () {
        var req = {};

        $scope.report.customerId = customerId;
        $scope.report.agentId = filters.selectedAgent != null ? filters.selectedAgent.id : 0;
        $scope.report.month = filters.selectedMonth != null ? filters.selectedMonth.value : 0;
        $scope.report.year = filters.selectedYear != null ? filters.selectedYear.value : 0;
        $scope.report.arl = 0;
        $scope.report.os = filters.selectedOS != "" ? filters.selectedOS : "";

        var data = JSON.stringify($scope.report);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/internal-project/send-status',
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