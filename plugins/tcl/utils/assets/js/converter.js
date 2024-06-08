/*
 * Multi lingual control plugin
 * 
 * Data attributes:
 * - data-control="multicurrency" - enables the plugin on an element
 * - data-default-currency="en" - default currency code
 * - data-placeholder-field="#placeholderField" - an element that contains the placeholder value
 *
 * JavaScript API:
 * $('a#someElement').multiCurrency({ option: 'value' })
 *
 * Dependences: 
 * - Nil
 */

+function ($) { "use strict";

    // MULTILINGUAL CLASS DEFINITION
    // ============================

    var MultiCurrency = function(element, options) {
        var self          = this;
        this.options      = options;
        this.$el          = $(element);

        this.$placeholder  = $(this.options.placeholderField);
        this.$activeButton = this.$el.find('[data-active-currency]');

        this.$el.on('click', '[data-switch-currency]', function(event){
            var selectedLocale = $(this).data('switch-currency');
            self.setCurrency(selectedLocale);

            /*
             * If Ctrl/Cmd key is pressed, find other instances and switch
             */
            if (event.ctrlKey || event.metaKey) {
                $('[data-switch-currency="'+selectedLocale+'"]').click();
            }
        });

        this.$placeholder.on('keyup', function(){
            self.$activeField.val(this.value);
        });

        this.setCurrency(this.options.defaultCurrency);
    };

    MultiCurrency.DEFAULTS = {
        defaultCurrency: 'usd',
        defaultField: null,
        placeholderField: null
    }

    MultiCurrency.prototype.getCurrencyElement = function(currency) {
        var el = this.$el.find('[data-currency-value="'+currency+'"]');
        return el.length ? el : null;
    }

    MultiCurrency.prototype.getLocaleValue = function(currency) {
        var value = this.getCurrencyElement(currency);
        return value ? value.val() : null;
    }

    MultiCurrency.prototype.setCurrency = function(currency) {
        this.activeCurrency = currency;
        this.$activeField = this.getCurrencyElement(currency);
        this.$placeholder.val(this.getLocaleValue(currency));
        this.$activeButton.text(currency);
    }

    // MULTILINGUAL PLUGIN DEFINITION
    // ============================

    var old = $.fn.multiCurrency;

    $.fn.multiCurrency = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result;
        
        this.each(function () {
            var $this   = $(this);
            var data    = $this.data('oc.multicurrency');
            var options = $.extend({}, MultiCurrency.DEFAULTS, $this.data(), typeof option == 'object' && option);
            if (!data) $this.data('oc.multicurrency', (data = new MultiCurrency(this, options)));
            if (typeof option == 'string') result = data[option].apply(data, args);
            if (typeof result != 'undefined') return false;
        });

        return result ? result : this
    };

    $.fn.multiCurrency.Constructor = MultiCurrency;

    // MULTILINGUAL NO CONFLICT
    // =================

    $.fn.multiCurrency.noConflict = function () {
        $.fn.multiCurrency = old;
        return this;
    };

    // MULTILINGUAL DATA-API
    // ===============
    $(document).render(function () {
        $('[data-control="multicurrency"]').multiCurrency();
    });

}(window.jQuery);
