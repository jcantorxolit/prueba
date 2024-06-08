'use strict';
/**
  * controller for Customers
*/
app.controller('customerEmployeeDocumentCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout) {

        var log = $log;
        // default view
        $scope.employee_document_section = "list";
        $scope.currentId = 0;
        $scope.editMode = "create";

        var onDestroyEmployeeDocumentNavigate$ = $rootScope.$on('employeeDocumentNavigate', function (event, args) {
            $scope.navToSection(args.newValue, 'edit', 0)
        });

        $scope.$on("$destroy", function() {
            onDestroyEmployeeDocumentNavigate$();
        });

        $scope.navToSection = function (section, title, currentId) {
            console.log(section);
            $timeout(function () {
                $scope.employee_document_section = section;
                $scope.editMode = title;
                $scope.$parent.switchSubTab(title);
                $scope.currentId = currentId;
            });
        };

    }]);
