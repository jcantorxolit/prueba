'use strict';
/**
 * Controller of the angularBootstrapCalendarApp
 */
app.controller('planerCalendarCtrl', ['$scope', '$aside', 'SweetAlert', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', '$filter', 'calendarConfig',
    function ($scope, $aside, SweetAlert, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, $filter, calendarConfig)
    {

        var date = new Date();
        var d = date.getDate();
        var m = date.getMonth();
        var y = date.getFullYear();

        $scope.IsEdited = false;

        $scope.events = [];

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.calendarView = 'month';
        $scope.calendarDay = new Date();
        $scope.calendarDate = new Date();
        $scope.calendarTitle = null;

        $scope.eventClicked = function (event) {
            $scope.IsEdited = true;

            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot +  "modules/planer/planer_calendar_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideTaskPlanerCtrl',
                scope: $scope,
                resolve: {
                    event: function () {
                        return event;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function (selectedEvent, action) {
                loadTask();
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });
        };

        $scope.addEvent = function () {
            $scope.IsEdited = false;

            var
            event = {
                title: '',
                startsAt: new Date(y, m, d, 10, 0),
                endsAt: new Date(y, m, d, 11, 0),
                type: 'job'
            };
            //showModalPlaner('Edited', event);

            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot +  "modules/planer/planer_calendar_customer_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideTaskPlanerCustomerCtrl',
                scope: $scope,
                resolve: {
                    event: function () {
                        return event;
                    },
                    isView: function () {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function (selectedEvent, action) {
                loadTask();
            }, function () {
                $log.info('Modal dismissed at: ' + new Date());
            });

        };

        $scope.toggle = function ($event, field, event) {
            $event.preventDefault();
            $event.stopPropagation();

            event[field] = !event[field];
        };


        var loadTask = function () {
            var req = {};
            var data = JSON.stringify($scope.customer);
            req.data = data;
            return $http({
                method: 'POST',
                url: 'api/project/task',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {

                    $scope.events = response.data.data;
                    $scope.events.forEach(function(entry) {
                        entry.startsAt =  new Date(entry.startsAt.date);
                        entry.endsAt =  new Date(entry.endsAt.date);
                        entry.color = {};

                        switch (entry.type) {
                            case 'job':
                                entry.color.primary = calendarConfig.colorTypes.job;
                                entry.color.secondary = calendarConfig.colorTypes.jobSec;
                            break;
                            case 'home':
                                entry.color.primary = calendarConfig.colorTypes.home;
                                entry.color.secondary = calendarConfig.colorTypes.homeSec;
                            break;
                            case 'off-site-work':
                                entry.color.primary = calendarConfig.colorTypes.offSiteWork;
                                entry.color.secondary = calendarConfig.colorTypes.offSiteWorkSec;
                            break;
                            case 'cancelled':
                                entry.color.primary = calendarConfig.colorTypes.cancelled;
                                entry.color.secondary = calendarConfig.colorTypes.cancelledSec;
                            break;
                            case 'generic':
                                entry.color.primary = calendarConfig.colorTypes.generic;
                                entry.color.secondary = calendarConfig.colorTypes.genericSec;
                            break;
                            case 'to-do':
                                entry.color.primary = calendarConfig.colorTypes.toDo;
                                entry.color.secondary = calendarConfig.colorTypes.toDoSec;
                            break;
                        }
                    });
                    //$scope.tracking.event_date =  new Date($scope.tracking.eventDateTimeTsp.date);
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        }

        loadTask();

    }]);


app.controller('ModalInstanceSideTaskPlanerCtrl', function ($scope, $rootScope, $uibModalInstance, event, $log, $timeout, SweetAlert, isView, $http, toaster) {


    $scope.event = event;
    $scope.isView = true;


    $scope.onClose = function () {
        $uibModalInstance.close(1);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.cancelTask = function (event) {
        event.status = "cancelado";
        update(event);
    };

    $scope.completeTask = function (event) {
        if (event.tableName == "agentTask")
        {
            event.status = "inactivo";
        } else if (event.tableName == "actionPlan" || event.tableName == "tracking")
        {
            event.status = "completado";
        }

        update(event);
    };

    $scope.reloadlTask = function (event) {
        //loadProjectTaskModel(task.id)
    };

    var update = function (event) {
        var req = {};

        var data = JSON.stringify(event);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/project/event/update',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                toaster.pop("success", "Calendario", "La información ha sido guardada satisfactoriamente.");
                $scope.onClose();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };


});


app.controller('ModalInstanceSideTaskPlanerCustomerCtrl', function ($scope, $rootScope, $uibModalInstance, event, $log, $timeout, SweetAlert, isView, $http, $filter, toaster) {

    $scope.agents = $rootScope.agents();
    $scope.typesTraking =  $rootScope.parameters("tracking_tiposeg");

    $scope.event = {
        id : 0,
        created_at : $filter('date')(new Date(), "dd/MM/yyyy HH:mm"),
        customerId : $rootScope.currentUser().company,
        agent: null,
        type:  null,
        isVisible: true,
        isEventSchedule: true,
        isCustomer: true,
        module: "planner",
        observation : "",
        eventDate : new Date(),
        status: { value: 'iniciado' },
        alerts: [
            {
                id:0,
                type:null,
                timeType:null,
                time:0,
                preference:null,
                sent:0,
                status:null
            }
        ]
    };

    $scope.onClose = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

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
                toaster.pop("error", "El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.");
                return;

            } else {
                if ($scope.event.eventDate == null) {
                    toaster.pop("error", "El formulario contiene errores!", "Por favor seleccione la fecha.");
                    return;
                }

                save();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    $scope.reloadlTask = function (event) {
        //loadProjectTaskModel(task.id)
    };

    var save = function () {
        var req = {};

        var data = JSON.stringify($scope.event);

        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/tracking/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function(){
                toaster.pop("success", "Calendario", "La información ha sido guardada satisfactoriamente.");
                $scope.onClose();
            });
        }).catch(function(e){
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function(){

        });

    };

    var onLoadAgent = function () {
        var req = {};
        req.customerId = $rootScope.currentUser().company;
        //var data = JSON.stringify($scope.customer);
        //req.data = data;
        return $http({
            method: 'POST',
            url: 'api/tracking/agent',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {


            $timeout(function () {

                if (response.data.data.length > 0) {
                    $scope.agents = response.data.data;
                }

            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    }

    onLoadAgent();

});
