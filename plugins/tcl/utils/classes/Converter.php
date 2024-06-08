<?php

namespace Tcl\Utils\Classes;

use DbDongle;
use Schema;
use Session;
use Tcl\Utils\Models\Currency;

/**
 * Converter class
 *
 * @package tcl\currency
 * @author Andres Mejia
 */
class Converter {

    use \October\Rain\Support\Traits\Singleton;

    const SESSION_CURRENCY = 'tcl.currency.currency';
    const SESSION_CONFIGURED_CUR = 'tcl.currency.configured';

    /**
     * @var string The currency to use on the front end.
     */
    protected $activeCurrency;

    /**
     * @var string The default currency if no active is set.
     */
    protected $defaultCurrency;

    /**
     * @var boolean Determine if translate plugin is configured and ready to be used.
     */
    protected $isConfigured;

    /**
     * Initialize the singleton
     * @return void
     */
    public function init() {
        $this->defaultCurrency = $this->isConfigured() ? array_get(Currency::getDefault(), 'code', 'usd') : 'usd';
        $this->activeCurrency = $this->defaultCurrency;
    }

    /**
     * Changes the currency in the application and optionally stores it in the session.
     * @param   string  $currency   Locale to use
     * @param   boolean $remember Set to false to not store in the session.
     * @return  boolean Returns true if the currency exists and is set.
     */
    public function setCurrency($currency, $remember = true) {
        $currencies = array_keys(Currency::listEnabled());

        if (in_array($currency, $currencies)) {
            
            //App::setCurrency($currency);
            
            $this->activeCurrency = $currency;

            if ($remember) {
                $this->setSessionCurrency($currency);
            }
            
            // debo marcar al usuario con la nueva moneda
            
            
            return true;
        }

        return false;
    }

    /**
     * Returns the active currency set by this instance.
     * @param  boolean $fromSession Look in the session.
     * @return string
     */
    public function getCurrency($fromSession = false) {
        if ($fromSession && ($currency = $this->getSessionCurrency()))
            return $currency;

        return $this->activeCurrency;
    }

    /**
     * Returns the default currency as set by the application.
     * @return string
     */
    public function getDefaultCurrency() {
        return $this->defaultCurrency;
    }

    /**
     * Check if this plugin is installed and the database is available, 
     * stores the result in the session for efficiency.
     * @return boolean
     */
    public function isConfigured() {
        
        if ($this->isConfigured !== null)
            return $this->isConfigured;

        if (Session::has(self::SESSION_CONFIGURED_CUR)) {
            
            $result = true;
            
        } elseif (DbDongle::hasDatabase() && Schema::hasTable('tcl_currency_currencies')) {
            
            Session::put(self::SESSION_CONFIGURED_CUR, true);
            $result = true;
            
        } else {
            $result = false;
        }

        return $this->isConfigured = $result;
    }

    //
    // Session handling
    //

    public function loadCurrencyFromSession() {
        if ($sessionCurrency = $this->getSessionCurrency())
            $this->setCurrency($sessionCurrency);
    }

    protected function getSessionCurrency() {
        if (!Session::has(self::SESSION_CURRENCY))
            return null;

        return Session::get(self::SESSION_CURRENCY);
    }

    protected function setSessionCurrency($currency) {
        Session::put(self::SESSION_CURRENCY, $currency);
    }

    
    
}
