'use strict';

app
    .factory('SupportService', ['$http', '$state', function($http, $state) {
        
        var currentStep = 0;
        var shouldRedirect = false;
        var dataFactory = {};

        dataFactory.inCustomerState = function() {
            var $view = $state.is("app.clientes.view");
            var $create = $state.is("app.clientes.create");
            var $edit = $state.is("app.clientes.edit");

            return ($view || $create || $edit);
        }

        dataFactory.setCurrentStep = function(step) {
            currentStep = step;
        }

        dataFactory.getCurrentStep = function(step) {
            return currentStep;
        }

        dataFactory.setShouldRedirect = function(value) {
            shouldRedirect = value;            
        }

        dataFactory.getShouldRedirect = function() {
            return shouldRedirect;   
        }

        return dataFactory;
    }
]);