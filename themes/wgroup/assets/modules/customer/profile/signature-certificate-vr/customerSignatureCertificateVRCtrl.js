'use strict';
/**
  * controller for Customers
*/
app.controller('customernSignatureCertificateVRCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
          $rootScope,$timeout, $http, SweetAlert, $aside, $document, flowFactory, ListService, FileUploader,
          $filter, bsLoadingOverlayService, $q) {


    $scope.customerId = $stateParams.customerId;
    $scope.uploader = new Flow();
    $scope.uploaderLogo = new Flow();

    $scope.dynamicPopover = {
        templateUrl: 'myPopoverTemplate.html'
    };

    var initialize = function() {
        $scope.entity = {
            id: null,
            customerId: $stateParams.customerId,
            fullName: null,
            job: null,
            is_active: null,
            signature: null,
            logo: null
        };
    };

    initialize();
    onLoad();

    $scope.form = {
        submit: function (form) {
            save();
        },
        reset: function () {
            $scope.Form.$setPristine(true);
            initialize();
        }
    };


    var save = function () {
        var data = JSON.stringify($scope.entity);
        var req = {
            data: Base64.encode(data)
        };

        return $http({
            method: 'POST',
            url: 'api/customer/signature-certificate-vr/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            console.log(response);

            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");

                $scope.entity = response.data.result;

                $scope.uploader.flow.resume();
                $scope.uploaderLogo.flow.resume();
            });
        }).catch(function (response) {
            SweetAlert.swal("Error de guardado", response.data.message , "error");
        });
    };


    function onLoad() {
        $http({
            method: 'GET',
            url: 'api/customer/signature-certificate-vr/get/' + $stateParams.customerId,
        }).then(function (response) {
            console.log(response);
            $scope.entity = response.data.result;
        });
    }

});

