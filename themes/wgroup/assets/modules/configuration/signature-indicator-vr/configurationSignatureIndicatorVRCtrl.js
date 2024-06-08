'use strict';
/**
  * controller for Customers
*/
app.controller('configurationSignatureIndicatorVRCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
          $rootScope,$timeout, $http, SweetAlert, $aside, $document, flowFactory, ListService, FileUploader,
          $filter, bsLoadingOverlayService, $q) {


    $scope.uploader = new Flow();

    $scope.dynamicPopover = {
        templateUrl: 'myPopoverTemplate.html'
    };

    var initialize = function() {
        $scope.entity = {
            id: null,
            text1: null,
            text2: null,
            signature: null
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
            url: 'api/config/signature-indicator-vr/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            console.log(response);

            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");

                $scope.entity = response.data.result;

                $scope.uploader.flow.resume();
            });
        }).catch(function (response) {
            SweetAlert.swal("Error de guardado", response.data.message , "error");
        });
    };


    function onLoad() {
        $http({
            method: 'GET',
            url: 'api/config/signature-indicator-vr/get',
        }).then(function (response) {
            console.log(response);
            $scope.entity = response.data.result;
        });
    }

});

