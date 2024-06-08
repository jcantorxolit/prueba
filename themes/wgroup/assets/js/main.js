var app = angular.module('wgApp', ['clip-two']);
app.run(['$rootScope', '$state', '$stateParams', '$location', '$http', '$q', '$timeout', '$compile', '$window','DTDefaultOptions', 'bsLoadingOverlayService',
	'SweetAlert', '$analytics', '$localStorage',
    function ($rootScope, $state, $stateParams, $location, $http, $q, $timeout, $compile, $window, DTDefaultOptions,
        bsLoadingOverlayService, SweetAlert, $analytics, $localStorage) {

        //DTDefaultOptions.setLoadingTemplate('<svg class="spinner" width="65px" height="65px" viewBox="0 0 66 66" xmlns="http://www.w3.org/2000/svg"> <circle class="path" fill="none" stroke-width="6" stroke-linecap="round" cx="33" cy="33" r="30"></circle></svg>');
        //DTDefaultOptions.setDOM('lpfrtip');
        DTDefaultOptions.setLanguage({
            //sProcessing: '<svg class="spinner" width="55px" height="55px" viewBox="0 0 66 66" xmlns="http://www.w3.org/2000/svg"> <circle class="path" fill="none" stroke-width="6" stroke-linecap="round" cx="33" cy="33" r="30"></circle></svg>',
            sProcessing: '<div class="loadingoverlay" style="box-sizing: border-box; position: absolute; display: flex; flex-flow: column nowrap; align-items: center; justify-content: space-around; background: rgba(255, 255, 255, 0.8); top: 0px; left: 0px; width: 100%; height: 100%; z-index: 2147483647; opacity: 1;"> <div class="loadingoverlay_element" style="order: 1; box-sizing: border-box; overflow: visible; flex: 0 0 auto; display: flex; justify-content: center; align-items: center; animation-name: loadingoverlay_animation__rotate_right_1; animation-duration: 2000ms; animation-timing-function: linear; animation-iteration-count: infinite; width: 120px; height: 120px;"> <svg style=" width: 100px;height: 100px;margin: 20px;display: inline-block;" version="1.1" id="L7" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve"> <path fill="#e97a01" d="M31.6,3.5C5.9,13.6-6.6,42.7,3.5,68.4c10.1,25.7,39.2,38.3,64.9,28.1l-3.1-7.9c-21.3,8.4-45.4-2-53.8-23.3 c-8.4-21.3,2-45.4,23.3-53.8L31.6,3.5z"> <animateTransform attributeName="transform" attributeType="XML" type="rotate" dur="2s" from="0 50 50" to="360 50 50" repeatCount="indefinite" /> </path> <path fill="#d55327" d="M42.3,39.6c5.7-4.3,13.9-3.1,18.1,2.7c4.3,5.7,3.1,13.9-2.7,18.1l4.1,5.5c8.8-6.5,10.6-19,4.1-27.7 c-6.5-8.8-19-10.6-27.7-4.1L42.3,39.6z"> <animateTransform attributeName="transform" attributeType="XML" type="rotate" dur="1s" from="0 50 50" to="-360 50 50" repeatCount="indefinite" /> </path> <path fill="#39a2dc" d="M82,35.7C74.1,18,53.4,10.1,35.7,18S10.1,46.6,18,64.3l7.6-3.4c-6-13.5,0-29.3,13.5-35.3s29.3,0,35.3,13.5 L82,35.7z"> <animateTransform attributeName="transform" attributeType="XML" type="rotate" dur="2s" from="0 50 50" to="360 50 50" repeatCount="indefinite" /> </path> </svg> </div> </div>',
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "Ningún dato disponible en esta tabla",
            sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
            sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
            sInfoPostFix: "",
            sSearch: "Buscar:",
            sUrl: "",
            sInfoThousands: ",",
            sLoadingRecords: "Cargando...",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: "Siguiente",
                sPrevious: "Anterior"
            },
            oAria: {
                sSortAscending: ": Activar para ordenar la columna de manera ascendente",
                sSortDescending: ": Activar para ordenar la columna de manera descendente"
            }
        });

        $.fn.dataTable.ext.errMode = 'throw'

        $analytics.settings.ga = {
            userId: window.currentUser
          };

        $rootScope.$on("$locationChangeStart", function(event, next, current) {
            if ($rootScope.app.instance == "bolivar") {
                $analytics.pageTrack(next);
            }

             if (!$state.is("app.clientes.view") && !$state.is("app.clientes.edit")) {
                 $rootScope.attentionLines = [];
                 var storeFilters = 'criteria-list-employe-' + window.currentUser.id + "-" + $localStorage.customerId;
                 $localStorage[storeFilters] = [];
             }
        });

		$(window).on('ajaxError', function(event, context, message, jqXHR) {
			if (event && context && message && jqXHR) {
				if (context.handler == "onSignin") {
					var errorMsg = jqXHR.responseText ? jqXHR.responseText : jqXHR.statusText;

					SweetAlert.swal("No Autorizado", errorMsg, "error");

					if (typeof grecaptcha !== undefined) {
						grecaptcha.reset()
					}
				}
			}

			event.preventDefault();
		});

        // Attach Fastclick for eliminating the 300ms delay between a physical tap and the firing of a click event on mobile browsers
        FastClick.attach(document.body);

        // Set some reference to access them from any scope
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;

        // GLOBAL APP SCOPE
        // set below basic information
        $rootScope.app = {
            name: window.appName, // name of your project
            author: 'AdeN', // author's name or company name
            description: '', // brief description
            minDate: window.minDate, // brief description
            version: '1.0', // current version
            year: ((new Date()).getFullYear()), // automatic current year (for copyright information)
            isMobile: (function () {// true if the browser is a mobile device
                var check = false;
                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    check = true;
                }
                ;

                if(!check && bowser.mobile ){
                    check = true;
                }

                return check;
            })(),
            layout: {
                isNavbarFixed: true, //true if you want to initialize the template with fixed header
                isSidebarFixed: true, // true if you want to initialize the template with fixed sidebar
                isSidebarClosed: false, // true if you want to initialize the template with closed sidebar
                isFooterFixed: true, // true if you want to initialize the template with fixed footer
                theme: 'theme-4', // indicate the theme chosen for your project
                logo: 'assets/images/logo.png' // relative path of the project logo
            },
            toaster: {
                position: 'toast-top-right'
            },
            rootUrl: window.rootUrl,
            themeRootUrl: window.themeRootUrl,
            instance: window.instance,
            enableAutoRegister: window.enableAutoRegister,
            views: {
                urlRoot: "themes/wgroup/assets/" //relative path of the project views
            },
            supportHelp: window.supportHelp
        };

        bsLoadingOverlayService.setGlobalConfig({
            delay: 0, // Minimal delay to hide loading overlay in ms.
            activeClass: undefined, // Class that is added to the element where bs-loading-overlay is applied when the overlay is active.
            templateUrl: $rootScope.app.themeRootUrl + 'templates/loading-overlay-full-page-template.htm', // Template url for overlay element. If not specified - no overlay element is created.
            templateOptions: undefined // Options that are passed to overlay template (specified by templateUrl option above).
        });

        $rootScope.user = {
            name: 'Peter',
            job: 'ng-Dev',
            picture: 'themes/wgroup/assets/images/user/logo2.png'
        };

        $rootScope.header = {
            title: '',
            description: '',
            picture: ''
        };

        $rootScope.logout = function (path) {
            $http({
                method: 'GET',
                url: 'api/logout',
                params: {}
            }).finally(function () {
                //$rootScope.redirect("login.signin");
                $timeout(function () {
                    // jQuery("#btnlogout").trigger("click");
                    //$state.forceReload();
                    $state.go($state.$current, null, {reload: true});
                });
            });
            return true;
        };

        $rootScope.hasRole = function (role) {
            var fnd = false;
            if (window.roles !== null && window.roles !== undefined) {
                $.each(window.roles, function (k, v) {
                    if (v == role) {
                        fnd = true;
                        return false;
                    }
                });
            }

            return fnd;
        };

        $rootScope.can = function (permission) {
            var fnd = false;
            if (window.permissions !== null && window.permissions !== undefined) {
                $.each(window.permissions, function (k, v) {
                    if (v == permission) {
                        fnd = true;
                        return false;
                    }
                });
            }

            return fnd;
        };

        $rootScope.parameters = function (group) {
            var params = [];
            if (window.parameters !== null && window.parameters !== undefined) {
                if (group !== null && group !== undefined) {
                    $.each(window.parameters, function (k, v) {
                        if (v.group == group) {
                            params.push(v);
                        }
                    });

                } else {
                    params = window.parameters;
                }
            }

            return params;
        };

        $rootScope.countries = function () {
            var countries = [];
            if (window.countries !== null && window.countries !== undefined) {
                countries = window.countries;
            }

            return countries;
        };

        $rootScope.agents = function () {
            var agents = [];
            if (window.agents !== null && window.agents !== undefined) {
                agents = window.agents;
            }

            return agents;
        };

        $rootScope.currentUser = function () {
            var user = {};
            if (window.currentUser !== null && window.currentUser !== undefined) {
                user = window.currentUser;
            }
            return user;
        };

        $rootScope.isAdmin = function () {
            return $rootScope.currentUser().wg_type == 'system';
        };

        $rootScope.isCustomer = function () {
            return $rootScope.currentUser().wg_type == 'customerAdmin' || $rootScope.currentUser().wg_type == 'customerUser';
        };

        $rootScope.isCustomerAdmin = function () {
            return $rootScope.currentUser().wg_type == 'customerAdmin';
        };

        $rootScope.isCustomerUser = function () {
            return $rootScope.currentUser().wg_type == 'customerUser';
        };

        $rootScope.isAgent = function () {
            return $rootScope.currentUser().wg_type == 'agent';
        };

        $rootScope.isProvider = function () {
            return $rootScope.currentUser().wg_type == 'provider';
        };

        $rootScope.currentUserName = function () {
            return $rootScope.currentUser().name;
        };

        $rootScope.temporaryAgencies = function () {
            var agencies = [];
            if (window.temporaryAgencies !== null && window.temporaryAgencies !== undefined) {
                agencies = window.temporaryAgencies;
            }

            return agencies;
        };

        $rootScope.rates = function () {
            var rates = [];
            if (window.rates !== null && window.rates !== undefined) {
                rates = window.rates;
            }

            return rates;
        };

        $rootScope.groups = function () {
            var groups = [];
            if (window.groups !== null && window.groups !== undefined) {
                groups = window.groups;
            }

            return groups;
        };

        $rootScope.redirect = function (path) {
            $timeout(function () {
                $location.path(path).replace();
            });
        };

        if ($rootScope.currentUser().wg_term_condition == null || $rootScope.currentUser().wg_term_condition != '1') {
            //$window.location.href = "app/logout";
        }

        $rootScope.tutorial = window.tutorial;

        $rootScope.availableUserTopManagement = window.availableUserTopManagement;

        $rootScope.canShowCommercialDashboard = function() {
            var users = $rootScope.parameters('dashboard_commercial_users_allowed');
            if (users.length == 0) {
                return false;
            }

            var emails = users[0].value;

            var exists = emails.some(function (email) {
                if (email == $rootScope.currentUser().username) {
                    return true;
                }

                return false;
            })

            return exists;
        }

    }]);
// translate config
app.config(['$translateProvider',
    function ($translateProvider) {

        //$translateProvider.useSanitizeValueStrategy('sanitizeParameters');

        $translateProvider.registerAvailableLanguageKeys(['es'], {
            'es_*': 'es'
        });

        var $instance = window.instance ? window.instance : 'sylogi';

        // prefix and suffix information  is required to specify a pattern
        // You can simply use the static-files loader with this pattern:
        $translateProvider.useStaticFilesLoader({
            prefix: window.themeRootUrl + 'i18n/' + $instance + '/',
            suffix: '.json'
        });

        // Since you've now registered more then one translation table, angular-translate has to know which one to use.
        // This is where preferredLanguage(langKey) comes in.
        $translateProvider.preferredLanguage('es');


        // Store the language in the local storage
        $translateProvider.useLocalStorage();
        //$translateProvider.fallbackLanguage("en");

        // Enable sanitize
		$translateProvider.useSanitizeValueStrategy('sanitize');

    }]);
// Angular-Loading-Bar
// configuration
app.config(['cfpLoadingBarProvider',
    function (cfpLoadingBarProvider) {
        cfpLoadingBarProvider.includeBar = true;
        cfpLoadingBarProvider.includeSpinner = false;

    }]);
