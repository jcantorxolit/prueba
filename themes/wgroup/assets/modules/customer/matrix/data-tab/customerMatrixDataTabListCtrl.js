'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerMatrixDataTabListCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', '$document','FileUploader', '$localStorage', 'toaster',
    function ($scope, $stateParams, $log, $compile, $state,
              SweetAlert, $rootScope, $http, $timeout, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $uibModal, flowFactory,
              cfpLoadingBar, $filter, $aside, $document, FileUploader, $localStorage, toaster) {

        var log = $log;

        var attachmentUploadedId = 0;
        var request = {};
        var currentId = $scope.$parent.$parent.$parent.$parent.currentId;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.isView = $scope.$parent.modeDsp == "view";
        $scope.minDateCurrent = new Date();
        $scope.customerId = $stateParams.customerId;


        //------------------------------------------------------------------------MATRIX DATA        
        $scope.dtInstanceMatrixData = {};
		$scope.dtOptionsMatrixData = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerMatrixId = currentId;

                    return JSON.stringify(d);
                },
                url: 'api/customer-matrix-data',
                contentType: 'application/json',
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
                //log.info("fnDrawCallback");
                loadRow();
                //Pace.stop();

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
        ;

        $scope.dtColumnsMatrixData = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var url = data.document != null ? data.document.path : "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-dark-orange btn-xs delRow lnk" href="#" uib-tooltip="Plan de acción" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-plus-square"></i></a> ';


                    if ($rootScope.can("seguimiento_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("seguimiento_delete")) {
                        //TODO
                        //actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('project').withTitle("Proyecto").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activity').withTitle("Actividad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('aspect').withTitle("Aspecto Ambiental").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('impact').withTitle("Impacto Ambiental").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('environmentalImpactIn').withTitle("Impacto Ambiental In").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('environmentalImpactEx').withTitle("Impacto Ambiental Ex").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('environmentalImpactPr').withTitle("Impacto Ambiental Pr").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('environmentalImpactRe').withTitle("Impacto Ambiental Re").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('environmentalImpactRv').withTitle("Impacto Ambiental Rv").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('environmentalImpactSe').withTitle("Impacto Ambiental Se").withOption('width', 200).withOption('defaultContent', ''),

            DTColumnBuilder.newColumn('environmentalImpactFr').withTitle("Impacto Ambiental Fr").withOption('width', 200).withOption('defaultContent', ''),

            DTColumnBuilder.newColumn(null).withTitle("NIA").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';

                    var nia = parseFloat(data.nia);

                    if (nia >= 9 && nia <= 18) {
                        label = 'label label-info';
                    } else if (nia >= 19 && nia <= 28) {
                        label = 'label label-warning';
                    } else if (nia >= 29 && nia <= 36) {
                        label = 'label label-danger';
                    } else {
                        label = '';
                    }

                    return '<span class="' + label + '">' + nia + '</span>';
                }),

            DTColumnBuilder.newColumn('legalImpactE').withTitle("Legal E").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('legalImpactC').withTitle("Legal C").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('legalImpactCriterion').withTitle("Criterio Legal CL").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('interestedPartAc').withTitle("Partes Interesadas Ac").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('interestedPartGe').withTitle("Partes Interesadas Ge").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('interestedPartCriterion').withTitle("Criterio Part INT CPI").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('totalAspect').withTitle("Significancia Total Aspecto").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Significancia Total Aspecto").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';

                    var totalAspect = parseFloat(data.totalAspect);

                    if (totalAspect >= 13 && totalAspect <= 26) {
                        label = 'label label-info';
                    } else if (totalAspect >= 27 && totalAspect <= 39) {
                        label = 'label label-warning';
                    } else if (totalAspect >= 40) {
                        label = 'label label-danger';
                    } else {
                        label = '';
                    }

                    return '<span class="' + label + '">' + totalAspect + '</span>';
                }),
            DTColumnBuilder.newColumn('nature').withTitle("Naturaleza").withOption('width', 200).withOption('defaultContent', ''),

            DTColumnBuilder.newColumn('emergencyConditionIn').withTitle("Cond Emerg. In").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('emergencyConditionEx').withTitle("Cond Emerg. Ex").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('emergencyConditionPr').withTitle("Cond Emerg. Pr").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('emergencyConditionRe').withTitle("Cond Emerg. Re").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('emergencyConditionRv').withTitle("Cond Emerg. Rv").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('emergencyConditionSe').withTitle("Cond Emerg. Se").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('emergencyConditionFr').withTitle("Cond Emerg. Fr").withOption('width', 200).withOption('defaultContent', ''),

            DTColumnBuilder.newColumn(null).withTitle("NIA Emerg").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';

                    var emergencyNia = parseFloat(data.emergencyNia);

                    if (emergencyNia >= 9 && emergencyNia <= 18) {
                        label = 'label label-info';
                    } else if (emergencyNia >= 19 && emergencyNia <= 28) {
                        label = 'label label-warning';
                    } else if (emergencyNia >= 29 && emergencyNia <= 36) {
                        label = 'label label-danger';
                    } else {
                        label = '';
                    }

                    return '<span class="' + label + '">' + emergencyNia + '</span>';
                }),
            DTColumnBuilder.newColumn('controlTypeE').withTitle("ELIMINACIÓN").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('controlTypeS').withTitle("SUSTITUCIÓN").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('controlTypeCI').withTitle("CONTROL DE INGENIERIA").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('controlTypeCA').withTitle("CONTROLES ADMINISTRATIVOS").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('controlTypeSL').withTitle("SEÑALIZACIÓN").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('controlTypeEPP').withTitle("ELEMENTOS DE PROTECCIÓN PERSONAL").withOption('width', 200).withOption('defaultContent', ''),

            DTColumnBuilder.newColumn('associateProgram').withTitle("Programa Asociado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('registry').withTitle("Registro").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('responsible').withTitle("Responsables").withOption('width', 200).withOption('defaultContent', ''),

        ];

        $scope.dtInstanceMatrixDataCallback = function (instance) {
            $scope.dtInstanceMatrixData = instance;
        }

        var loadRow = function () {

            $("#dtMatrixData a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onEditRecord(id);
            });

            $("#dtMatrixData a.delRow").on("click", function () {
                
            });

        };

        $scope.reloadData = function () {
            $scope.dtInstanceMatrixData.reloadData();
        };

        $scope.onCreate = function () {
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", 0);
            }
        };

        $scope.onEditRecord = function (id) {
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", id);
            }
        };


        $scope.onCancel = function (id) {
            console.log($scope.$parent);
            if ($scope.$parent != null ) {
                $scope.$parent.$parent.$parent.$parent.$parent.navToSection("list", "list", 0);
            }
        };
    }]);