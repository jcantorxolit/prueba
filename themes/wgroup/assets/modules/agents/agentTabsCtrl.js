'use strict';
/**
 * controller for Customers
 */
app.controller('agentTabsCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope', '$timeout', '$state', '$filter', 'flowFactory','$http',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout, $state, $filter, flowFactory, $http) {

        var log = $log;
        var request = {};


        //Variables globales en el tab
        if ($state.is("app.asesores.view")) {
            $scope.agent_title_tab = "view";
        } else if ($state.is("app.asesores.create")) {
            $scope.agent_title_tab = "create";
        } else {
            $scope.agent_title_tab = "edit";
        }

        $scope.isCreate = $state.is("app.asesores.create");
        $scope.profileTab = 'basic';

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        if ($scope.isAgent) {
            $state.go("app.clientes.list");
        } else if ($scope.isCustomer) {
            log.info("Step 1")
            $state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }

        $scope.agent = {};
        $scope.agent.id = $scope.iscreate ? 0 : $stateParams.agentId;
        $scope.flowConfig = {target: '/api/agent/upload', singleFile: true};
        $scope.uploader = new Flow();
        $scope.noImage = true;

        $scope.tabsloaded = ["profile"];
        $scope.tabname = "profile";
        $scope.titletab = $scope.agent_title_tab;


        $scope.onLoadRecord = function () {
            if ($scope.agent.id) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.agent.id);
                var req = {
                    id: $scope.agent.id
                };
                $http({
                    method: 'GET',
                    url: 'api/agent',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.asesores.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Información no disponible", "Cliente no encontrado", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.agent = response.data.result;
                            if ($scope.agent.logo != null && $scope.agent.logo.path != null) {
                                $scope.noImage = false;
                            } else {
                                $scope.noImage = true;
                            }
                        });

                    }).finally(function () {
                    });


            } else {
                //Se creara nuevo agents
                log.info("creacion de nuevo asesor agentTabsCtrl");
                $scope.loading = false;
            }
        }

        $scope.onLoadRecord();

        $scope.switchTab = function (tab, titletab) {
            $timeout(function () {
                $scope.tabname = tab;
                $scope.titletab = titletab;
                $scope.tabsloaded.push(tab);
            });
        };

        $scope.switchSubTab = function (subtab) {
            $timeout(function () {
                $scope.subtab = subtab;
            });
        };

    }]);