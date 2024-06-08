'use strict';

/**
 * Config constant
 */
app.constant('APP_MEDIAQUERY', {
    'desktopXL': 1200,
    'desktop': 992,
    'tablet': 768,
    'mobile': 480
});
app.constant('JS_REQUIRES', {
    //*** Scripts
    scripts: {
        //*** Javascript Plugins
        'modernizr': ['themes/wgroup/assets/vendor/modernizr/modernizr.js'],

        'moment': ['themes/wgroup/assets/vendor/moment/min/moment.min.js'],

        'momentwl': ['themes/wgroup/assets/vendor/moment/min/moment-with-locales.min.js'],

        'momentlocale': ['themes/wgroup/assets/vendor/moment/locale/es.js'],

        'lodash': ['themes/wgroup/assets/vendor/lodash/lodash.js'],

        'spin': 'themes/wgroup/assets/vendor/spin.js/spin.js',

        'random-color': 'themes/wgroup/assets/vendor/randomcolor/randomColor.js',

        //*** jQuery Plugins
        'perfect-scrollbar-plugin': [
            'themes/wgroup/assets/vendor/perfect-scrollbar/js/min/perfect-scrollbar.jquery.min.js',
            'themes/wgroup/assets/vendor/perfect-scrollbar/css/perfect-scrollbar.min.css'
        ],
        'ladda': [
            'themes/wgroup/assets/vendor/ladda/dist/ladda.min.js',
            'themes/wgroup/assets/vendor/ladda/dist/ladda-themeless.min.css'
        ],
        /*'sweet-alert': [
            'themes/wgroup/assets/vendor/sweetalert/dist/sweetalert.min.js',
            'themes/wgroup/assets/vendor/sweetalert/dist/sweetalert.css'
        ],*/
        'chartjs': 'themes/wgroup/assets/vendor/chart.js/dist/Chart.min.js',
        'jquery-sparkline': 'themes/wgroup/assets/vendor/jquery.sparkline.build/dist/jquery.sparkline.min.js',
        'ckeditor-plugin': 'themes/wgroup/assets/vendor/ckeditor/ckeditor.js',
        'jquery-nestable-plugin': [
            'themes/wgroup/assets/vendor/jquery-nestable/jquery.nestable.js'
        ],
        'touchspin-plugin': [
            'themes/wgroup/assets/vendor/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.min.js',
            'themes/wgroup/assets/vendor/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.min.css'
        ],
        'jquery-datatable': [
            'themes/wgroup/assets/vendor/angular-datatables/dataTables.fontAwesome.css'
        ],
        'spectrum-plugin': [
            'themes/wgroup/assets/vendor/spectrum/spectrum.js',
            'themes/wgroup/assets/vendor/spectrum/spectrum.css'
        ],

        //*** Bootstrap plugins
        'dual-list-box': [
            'themes/wgroup/assets/vendor/bootstrap-duallistbox/jquery.bootstrap-duallistbox.min.js',
            'themes/wgroup/assets/vendor/bootstrap-duallistbox/bootstrap-duallistbox.min.css'
        ],

        'knob-d3': [
            'themes/wgroup/assets/vendor/d3/d3.min.js',
        ],

        'qrcode': [
            'themes/wgroup/assets/vendor/qrcode/lib/qrcode.min.js',
        ],

        'base64': ['themes/wgroup/assets/js/utils/base64.js'],
        'json3': ['themes/wgroup/assets/js/utils/json3.min.js'],

        'pdfjs': [
            //'https://cdn.jsdelivr.net/npm/pdfjs-dist@2.14.305/build/pdf.min.js',
            'https://cdn.jsdelivr.net/npm/pdfjs-dist@1.9.661/build/pdf.min.js'
        ],

        //*** Services
        'listService': ['themes/wgroup/assets/js/services/listService.js'],
        'moduleListService': ['themes/wgroup/assets/js/services/moduleListService.js'],
        'chartService': ['themes/wgroup/assets/js/services/chartService.js'],
        'geoLocationService': ['themes/wgroup/assets/js/services/geoLocationService.js'],

        //*** Controllers


        //--------------------------------------START CUSTOMER-------------------------------------------\\
        'customerController': ['themes/wgroup/assets/dist/js/customerController.min.js'],
        //--------------------------------------END CUSTOMER---------------------------------------------\\



        //------------------------------------------------START CONFIG------------------------------------\\
        'configController': ['themes/wgroup/assets/dist/js/configController.min.js'],
        //------------------------------------------------END CONFIG--------------------------------------\\



        //------------------------------------------------START ASESORES----------------------------------\\
        'agentController': ['themes/wgroup/assets/dist/js/agentController.min.js'],
        //------------------------------------------------END ASESORES------------------------------------\\



        //------------------------------------------------START PLANER------------------------------------\\
        'planerCalendarController': ['themes/wgroup/assets/dist/js/planerCalendarController.min.js'],
        //------------------------------------------------END PLANER--------------------------------------\\



        //------------------------------------------------START REPORT------------------------------------\\
        'reportController': ['themes/wgroup/assets/dist/js/reportController.min.js'],
        //------------------------------------------------END REPORT--------------------------------------\\



        //------------------------------------------------START POLL--------------------------------------\\
        'pollController': ['themes/wgroup/assets/dist/js/pollController.min.js'],
        //------------------------------------------------END POLL----------------------------------------\\



        //------------------------------------------------START QUOTE-------------------------------------\\
        'quoteController': ['themes/wgroup/assets/dist/js/quoteController.min.js'],
        //------------------------------------------------END QUOTE---------------------------------------\\



        //------------------------------------------------START CERTIFICATE-------------------------------\\
        'certificateController': ['themes/wgroup/assets/dist/js/certificateController.min.js'],
        //------------------------------------------------END CERTIFICATE---------------------------------\\



        //------------------------------------------------START LIBRARY-----------------------------------\\
        'resourceLibraryController': ['themes/wgroup/assets/dist/js/resourceLibraryController.min.js'],
        //------------------------------------------------END LIBRARY-------------------------------------\\



        //------------------------------------------------START PROJECT-----------------------------------\\
        'projectController': ['themes/wgroup/assets/dist/js/projectController.min.js'],
        //------------------------------------------------END PROJECT-------------------------------------\\



        //------------------------------------------------START INTERNAL PROJECT--------------------------\\
        'internalProjectController': ['themes/wgroup/assets/dist/js/internalProjectController.min.js'],
        //------------------------------------------------END INTERNAL PROJECT----------------------------\\



        //------------------------------------------------START DASHBOARD---------------------------------\\
        'dashboardController': ['themes/wgroup/assets/dist/js/dashboardController.min.js'],
        //------------------------------------------------END DASHBOARD-----------------------------------\\



        //------------------------------------------------START TERMS CONDITIONS--------------------------\\
        'termConditionController': ['themes/wgroup/assets/dist/js/termConditionController.min.js'],
        //------------------------------------------------END TERMS CONDITIONS----------------------------\\



        //------------------------------------------------START USER CONTROLLER--------------------------\\
        'userController': ['themes/wgroup/assets/dist/js/userController.min.js'],
        //------------------------------------------------END USER CONDITIONS----------------------------\\



        //------------------------------------------------START POSITIVA FGN-------------------------------\\
        'positivaFgnController': ['themes/wgroup/assets/dist/js/positivaFgnController.min.js'],
        //------------------------------------------------END POSITIVA FGN---------------------------------\\



        //*** Filters
        'htmlToPlaintext': 'themes/wgroup/assets/js/filters/htmlToPlaintext.js'
    },
    //*** angularJS Modules
    modules: [{
            name: 'angularMoment',
            files: ['themes/wgroup/assets/vendor/angular-moment/angular-moment.min.js']
        }, {
            name: 'pasvaz.bindonce',
            files: ['themes/wgroup/assets/vendor/bindonce/bindonce.min.js']
        }, {
            name: 'infinite-scroll',
            files: ['themes/wgroup/assets/vendor/ngInfiniteScroll/ng-infinite-scroll.min.js']
        }, {
            name: 'ngScrollSpy',
            files: ['themes/wgroup/assets/vendor/ngScrollSpy/ngScrollSpy.js']
        }, {
            name: 'toaster',
            files: ['themes/wgroup/assets/vendor/AngularJS-Toaster/toaster.js', 'themes/wgroup/assets/vendor/AngularJS-Toaster/toaster.css']
        }, {
            name: 'angularBootstrapNavTree',
            files: [
                'themes/wgroup/assets/vendor/angular-bootstrap-nav-tree/dist/abn_tree_directive.js',
                'themes/wgroup/assets/vendor/angular-bootstrap-nav-tree/dist/abn_tree.css'
            ]
        }, {
            name: 'angular-ladda',
            files: ['themes/wgroup/assets/vendor/angular-ladda/dist/angular-ladda.min.js']
        }, {
            name: 'ngTable',
            files: ['themes/wgroup/assets/vendor/ng-table/dist/ng-table.min.js', 'themes/wgroup/assets/vendor/ng-table/dist/ng-table.min.css']
        }, {
            name: 'ui.select',
            files: [
                'themes/wgroup/assets/vendor/angular-ui-select/dist/select.min.js',
                'themes/wgroup/assets/vendor/angular-ui-select/dist/select.min.css',
                'themes/wgroup/assets/vendor/select2/dist/css/select2.min.css',
                'themes/wgroup/assets/vendor/select2-bootstrap-css/select2-bootstrap.min.css',
                'themes/wgroup/assets/vendor/selectize/dist/css/selectize.bootstrap3.css'
            ]
        },{
            name: 'ui.swiper',
            files: [
                'themes/wgroup/assets/vendor/angular-ui-swiper/dist/angular-ui-swiper.css',
                'themes/wgroup/assets/vendor/angular-ui-swiper/dist/angular-ui-swiper.min.js',
            ]
        }, {
            name: 'ui.mask',
            files: ['themes/wgroup/assets/vendor/angular-ui-mask/dist/mask.min.js']
        }, {
            name: 'ngImgCrop',
            files: [
                'themes/wgroup/assets/vendor/ng-img-crop/compile/minified/ng-img-crop.js',
                'themes/wgroup/assets/vendor/ng-img-crop/compile/minified/ng-img-crop.css'
            ]
        }, {
            name: 'angularFileUpload',
            files: ['themes/wgroup/assets/vendor/angular-file-upload/dist/angular-file-upload.min.js']
        }, {
            name: 'ngAside',
            files: [
                'themes/wgroup/assets/vendor/angular-aside/dist/js/angular-aside.min.js',
                'themes/wgroup/assets/vendor/angular-aside/dist/css/angular-aside.min.css'
            ]
        }, {
            name: 'truncate',
            files: ['themes/wgroup/assets/vendor/angular-truncate/src/truncate.js']
        },
        /*{
            name: 'oitozero.ngSweetAlert',
            files: ['themes/wgroup/assets/vendor/ngSweetAlert/SweetAlert.min.js']
        }, */
        {
            name: 'monospaced.elastic',
            files: ['themes/wgroup/assets/vendor/angular-elastic/elastic.js']
        }, {
            name: 'ngMap',
            files: ['themes/wgroup/assets/vendor/ngmap/build/scripts/ng-map.min.js']
        }, {
            name: 'tc.chartjs',
            files: ['themes/wgroup/assets/vendor/tc-angular-chartjs/dist/tc-angular-chartjs.min.js']
        }, {
            name: 'flow',
            files: ['themes/wgroup/assets/vendor/ng-flow/dist/ng-flow-standalone.min.js']
        }, {
            name: 'uiSwitch',
            files: [
                'themes/wgroup/assets/vendor/angular-ui-switch/angular-ui-switch.min.js',
                'themes/wgroup/assets/vendor/angular-ui-switch/angular-ui-switch.min.css'
            ]
        }, {
            name: 'ckeditor',
            files: ['themes/wgroup/assets/vendor/angular-ckeditor/angular-ckeditor.min.js']
        }, {
            name: 'mwl.calendar',
            files: [
                'themes/wgroup/assets/vendor/angular-bootstrap-calendar/dist/js/angular-bootstrap-calendar-tpls.js',
                'themes/wgroup/assets/vendor/angular-bootstrap-calendar/dist/css/angular-bootstrap-calendar.min.css',
                'themes/wgroup/assets/js/config/config-calendar.js'
            ]
        }, {
            name: 'ng-nestable',
            files: ['themes/wgroup/assets/vendor/ng-nestable/src/angular-nestable.js']
        }, {
            name: 'vAccordion',
            files: [
                'themes/wgroup/assets/vendor/v-accordion/dist/v-accordion.min.js',
                'themes/wgroup/assets/vendor/v-accordion/dist/v-accordion.min.css'
            ]
        }, {
            name: 'xeditable',
            files: [
                'themes/wgroup/assets/vendor/angular-xeditable/dist/js/xeditable.min.js',
                'themes/wgroup/assets/vendor/angular-xeditable/dist/css/xeditable.css',
                'themes/wgroup/assets/vendor/js/config/config-xeditable.js'
            ]
        }, {
            name: 'checklist-model',
            files: ['themes/wgroup/assets/vendor/checklist-model/checklist-model.js']
        }, {
            name: 'angular-notification-icons',
            files: [
                'themes/wgroup/assets/vendor/angular-notification-icons/dist/angular-notification-icons.min.js',
                'themes/wgroup/assets/vendor/angular-notification-icons/dist/angular-notification-icons.min.css'
            ]
        }, {
            name: 'angularSpectrumColorpicker',
            files: ['themes/wgroup/assets/vendor/angular-spectrum-colorpicker/dist/angular-spectrum-colorpicker.min.js']
        }, {
            name: 'datatables',
            files: [
                'themes/wgroup/assets/vendor/angular-datatables/datatables.bootstrap.min.css'
            ]
        },
        {
            name: 'datatables.bootstrap',
            files: [
                'themes/wgroup/assets/vendor/angular-datatables/plugins/bootstrap/angular-datatables.bootstrap.min.js',
                'https://cdn.datatables.net/responsive/2.1.0/css/responsive.dataTables.min.css',
                'https://cdn.datatables.net/responsive/2.1.0/js/dataTables.responsive.min.js',
            ]
        },
        {
            name: 'datatables.scroller',
            files: [
                'themes/wgroup/assets/vendor/angular-datatables/plugins/scroller/angular-datatables.scroller.min.js',
                'https://cdn.datatables.net/scroller/1.4.2/css/scroller.dataTables.min.css'
            ]
        }, {
            name: 'cp.ngConfirm',
            files: [
                'themes/wgroup/assets/vendor/angular-confirm1/dist/angular-confirm.min.js',
                'themes/wgroup/assets/vendor/angular-confirm1/dist/angular-confirm.min.css'
            ]
        }, {
            name: 'ngNotify',
            files: [
                'themes/wgroup/assets/vendor/ng-notify/dist/ng-notify.min.js',
                'themes/wgroup/assets/vendor/ng-notify/dist/ng-notify.min.css'
            ]
        }, {
            name: 'ui.knob',
            files: [
                'themes/wgroup/assets/vendor/ng-knob/dist/ng-knob.min.js',
            ]
        }, {
            name: 'ja.qr',
            files: [
                'themes/wgroup/assets/vendor/angular-qr/angular-qr.min.js',
            ]
        }, {
            name: 'ui.sortable',
            files: [
                'themes/wgroup/assets/vendor/angular-ui-sortable/dist/jquery.ui.sortable.min.js',
                'themes/wgroup/assets/vendor/angular-ui-sortable/dist/sortable.min.js',
            ]
        }, {
            name: 'ui.carousel',
            files: [
                'themes/wgroup/assets/vendor/angular-ui-carousel/dist/ui-carousel.min.js',
                'themes/wgroup/assets/vendor/angular-ui-carousel/dist/ui-carousel.min.css',
            ]
        }, {
            name: 'com.2fdevs.videogular',
            files: [
                'themes/wgroup/assets/vendor/videogular/dist/videogular/videogular.min.js',
            ]
        }, {
            name: 'com.2fdevs.videogular.plugins.controls',
            files: [
                'themes/wgroup/assets/vendor/videogular/dist/controls/vg-controls.min.js',
            ]
        }, {
            name: 'com.2fdevs.videogular.plugins.overlayplay',
            files: [
                'themes/wgroup/assets/vendor/videogular/dist/overlay-play/vg-overlay-play.min.js',
            ]
        }, {
            name: 'info.vietnamcode.nampnq.videogular.plugins.youtube',
            files: [
                'themes/wgroup/assets/vendor/videogular/dist/youtube/youtube.min.js',
            ]
        }, {
            name: 'pdf',
            files: [
                'themes/wgroup/assets/vendor/angularjs-pdf-viewer/dist/angular-pdf-viewer.min.js',
            ]
        }, {
            name: 'daterangepicker',
            files: [
                'themes/wgroup/assets/vendor/bootstrap-daterangepicker/daterangepicker.js',
                'themes/wgroup/assets/vendor/bootstrap-daterangepicker/daterangepicker.css',
                'themes/wgroup/assets/vendor/angular-daterangepicker/js/angular-daterangepicker.js',
            ]
        }
    ]
});
