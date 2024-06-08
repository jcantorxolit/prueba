<?php

namespace Tcl\Utils\Classes;

use Exception;
use Log;
use NumberFormatter;
use Tcl\Utils\Classes\Converter;
use Tcl\Utils\Classes\ExchangeRate;
use Tcl\Utils\Models\Finance;
use Session;

/**
 * Converter class
 *
 * @package tcl\currency
 * @author Andres Mejia
 */
class ClientConverter {

    use \October\Rain\Support\Traits\Singleton;

    const SESSION_CURRENCY = 'tcl.currency.currency';
    const SESSION_CONFIGURED_CUR = 'tcl.currency.configured';
    const SESSION_LOCALE = 'rainlab.translate.locale';

    protected static $_iso_currency = '^(AED|AFN|ALL|AMD|ANG|AOA|ARS|AUD|AWG|AZN|BAM|BBD|BDT|BGN|BHD|BIF|BMD|BND|BOB|BOV|BRL|BSD|BTN|BWP|BYR|BZD|CAD|CDF|CHE|CHF|CHW|CLF|CLP|CNY|COP|COU|CRC|CUC|CUP|CVE|CZK|DJF|DKK|DOP|DZD|EGP|ERN|ETB|EUR|FJD|FKP|GBP|GEL|GHS|GIP|GMD|GNF|GTQ|GYD|HKD|HNL|HRK|HTG|HUF|IDR|ILS|INR|IQD|IRR|ISK|JMD|JOD|JPY|KES|KGS|KHR|KMF|KPW|KRW|KWD|KYD|KZT|LAK|LBP|LKR|LRD|LSL|LTL|LVL|LYD|MAD|MDL|MGA|MKD|MMK|MNT|MOP|MRO|MUR|MVR|MWK|MXN|MXV|MYR|MZN|NAD|NGN|NIO|NOK|NPR|NZD|OMR|PAB|PEN|PGK|PHP|PKR|PLN|PYG|QAR|RON|RSD|RUB|RWF|SAR|SBD|SCR|SDG|SEK|SGD|SHP|SLL|SOS|SRD|SSP|STD|SVC|SYP|SZL|THB|TJS|TMT|TND|TOP|TRY|TTD|TWD|TZS|UAH|UGX|USD|USN|USS|UYI|UYU|UZS|VEF|VND|VUV|WST|XAF|XAG|XAU|XBA|XBB|XBC|XBD|XCD|XDR|XFU|XOF|XPD|XPF|XPT|XSU|XTS|XUA|XXX|YER|ZAR|ZMW|ZWL)$';
    private $exchangeClient;
    private $converter;
    private $defaultCurrency;
    private $sessionCurrency;
    private $rates;
    private $defaultExchangeProvider;

    /**
     * Initialize the singleton
     * @return void
     */
    public function init() {

        $this->exchangeClient = new ExchangeRate();

        $this->converter = Converter::instance();

        // debemos ssaber cual es la moneda por defecto (origen)
        $this->defaultCurrency = $this->converter->getDefaultCurrency();

        // debemos saber cual es la moneda en session (destino)
        $this->sessionCurrency = $this->converter->getCurrency(true);


        if ($defaultService = Finance::getDefault(true)) {
            $this->defaultExchangeProvider = $defaultService->code;
        } else {
            $this->defaultExchangeProvider = "yahoo"; // Esto debe venir de db
        }

        if ($this->defaultExchangeProvider == 'yahoo' || $this->defaultExchangeProvider == 'ecb') {
            $this->rates = $this->exchangeClient->rates($this->defaultExchangeProvider, $this->defaultCurrency, 'array');
        }
    }

    private function google($from, $to) {

        if ($financeService = Finance::findByCode("google")) {
            $url = $financeService->url;

            // replace values
            $url = str_replace('[FROM]', $from, $url);
            $url = str_replace('[TO]', $to, $url);
            $data = @file_get_contents($url);

            preg_match("/<span class=bld>(.*)<\/span>/", $data, $rate);

            return isset($rate[1]) ? preg_replace("/[^0-9.]/", "", $rate[1]) : false;
        } else {
            return false;
        }
    }

    private function webservicex($from, $to) {

        if ($financeService = Finance::findByCode("x")) {

            $url = $financeService->url;

            $url = str_replace('[FROM]', $from, $url);
            $url = str_replace('[TO]', $to, $url);

            $rate = @simpleXML_load_file($url, "SimpleXMLElement", LIBXML_NOCDATA);

            return ($rate === false) ? false : $rate[0];
        } else {
            return false;
        }
    }

    private function ecbOrYahoo($targetCurrency, $recalcRates = false, $provider = 'yahoo', $baseCurrency = 'USD') {
        try {
            if ($recalcRates) {

                $client = new ExchangeRate();

                $ratesClient = $client->rates($provider, $baseCurrency, 'array');

                return $ratesClient["rates"][$targetCurrency];
            }

            return $this->rates["rates"][$targetCurrency];
        } catch (Exception $exc) {
            return false;
        }
    }

    public function convert($value = 1, $isformatted = true, $to = 'USD', $changeSessionCur = false, $from = 'USD', $changeDefCurrency = false, $changeProvider = false, $provider = 'google') {

        try {
            $from = strtoupper(trim($from));
            $to = strtoupper(trim($to));

            //Validates ISO currency codes
            if (!preg_match('/' . self::$_iso_currency . '/i', $from)) {
                throw new Exception('Currency "FROM" [' . $from . '], is not a valid ISO Code.');
            }

            if (!preg_match('/' . self::$_iso_currency . '/i', $to)) {
                throw new Exception('Currency "TO" [' . $to . '], is not a valid ISO Code.');
            }

            if (!$this->defaultExchangeProvider || $changeProvider) {
                $this->defaultExchangeProvider = $provider;
            }

            if (!$this->sessionCurrency || $changeSessionCur) {
                $this->sessionCurrency = $to;
            }

            if (!$this->defaultCurrency || $changeDefCurrency) {
                $this->defaultCurrency = $from;
            }

            $this->defaultCurrency = strtoupper(trim($this->defaultCurrency));
            $this->sessionCurrency = strtoupper(trim($this->sessionCurrency));

            $rate = false;

            if ($this->defaultCurrency == $this->sessionCurrency) {
                $rate = 1;
            } else {


                switch ($this->defaultExchangeProvider) {

                    case 'yahoo':
                    case 'ecb':
                        $rate = $this->ecbOrYahoo($this->sessionCurrency);
                        break;

                    case 'x':
                        $rate = $this->webservicex($this->defaultCurrency, $this->sessionCurrency);
                        break;

                    case 'google':
                    // default is google
                    default :
                        $rate = $this->google($this->defaultCurrency, $this->sessionCurrency);
                }

                if (!$rate) {

                    //TODO: si el proveedor por defecto no devuelve tasas deberia intentar con otros proveedores configurados!

                    throw new Exception('There has been a mistake, the servers do not respond.');
                }
            }

            $val = (floatval($rate) * floatval($value));
            // $val = round($val, 2);

            if ($isformatted) {

                return $this->formatCurrency($val, $this->sessionCurrency);
              
            }

            return $val;
        } catch (Exception $exc) {
            Log::error($exc->getMessage());
            Log::error($exc->getTraceAsString());
            return $value;
        }
    }

    public function getSessionCurrency(){
        return $this->sessionCurrency;
    }
    
    public function convertForce($value = 1, $isformatted = true, $from = 'USD', $to = 'USD') {

        try {
            $from = strtoupper(trim($from));
            $to = strtoupper(trim($to));

            //Validates ISO currency codes
            if (!preg_match('/' . self::$_iso_currency . '/i', $from)) {
                throw new Exception('Currency "FROM" [' . $from . '], is not a valid ISO Code.');
            }

            if (!preg_match('/' . self::$_iso_currency . '/i', $to)) {
                throw new Exception('Currency "TO" [' . $to . '], is not a valid ISO Code.');
            }

            $rate = false;

            if ($to == $from) {
                $rate = 1;
            } else {


                switch ($this->defaultExchangeProvider) {

                    case 'yahoo':
                    case 'ecb':
                        $rate = $this->ecbOrYahoo($to);
                        break;

                    case 'x':
                        $rate = $this->webservicex($from, $to);
                        break;

                    case 'google':
                    // default is google
                    default :
                        $rate = $this->google($from, $to);
                }

                if (!$rate) {

                    //TODO: si el proveedor por defecto no devuelve tasas deberia intentar con otros proveedores configurados!

                    throw new Exception('There has been a mistake, the servers do not respond.');
                }
            }

            $val = (floatval($rate) * floatval($value));
            // $val = round($val, 2);

            if ($isformatted) {
                 return $this->formatCurrency($val, $to);
            }

            return $val;
        } catch (Exception $exc) {
            return $value;
        }
    }

    public function formatCurrency($value, $to, $digits = 2) {
        
        $fraction = $value - (int) $value;

        if ($fraction <= 0) {
            $digits = 0;
        }

        $code = $this->set_lc_monetary($to);
        $fmt = new NumberFormatter($code, NumberFormatter::CURRENCY);
        $fmt->setTextAttribute(NumberFormatter::CURRENCY_CODE, $to);
        $fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, $digits);
        return $fmt->formatCurrency($value, $to);
        
    }

    function set_lc_monetary($isocode) {

        switch ($isocode) :
            case 'HKD' :
                setlocale(LC_MONETARY, "en_HK");
                return "en_HK";
            case 'CNY' :
                setlocale(LC_MONETARY, "zh_CN");
                return "zh_CN";
            case 'USD' :
                setlocale(LC_MONETARY, "en_US");
                return "en_US";
            case 'MOP' :
                setlocale(LC_MONETARY, "en_MO");
                return "en_MO";
            case 'TWD' :
                setlocale(LC_MONETARY, "en_TW");
                return "en_TW";
            case 'AUD' : setlocale(LC_MONETARY, "en_AU");
                return "en_AU";
            case 'CAD' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'ARS' : setlocale(LC_MONETARY, "es_AR");
                return "es_AR";
            case 'BHD' : setlocale(LC_MONETARY, "ar_BH");
                return "ar_BH";
            case 'GBP' : setlocale(LC_MONETARY, "en_GB");
                return "en_GB";
            case 'EUR' :
                setlocale(LC_MONETARY, 'es_ES');
                return 'es_ES';
            case 'AED' :
                setlocale(LC_MONETARY, "ar_AE");
                return "ar_AE";
            case 'AFN' : setlocale(LC_MONETARY, "uz_AF");
                return "uz_AF";
            case 'ALL' : setlocale(LC_MONETARY, "sq_AL");
                return "sq_AL";
            case 'AMD' : setlocale(LC_MONETARY, "hy_AM");
                return "hy_AM";
            case 'ANG' : setlocale(LC_MONETARY, "nl_AN");
                return "nl_AN";
            case 'AOA' : setlocale(LC_MONETARY, "pt_AO");
                return "pt_AO";
            case 'AWG' : setlocale(LC_MONETARY, "nl_AW");
                return "nl_AW";
            case 'AZN' : setlocale(LC_MONETARY, "az_AZ");
                return "az_AZ";
            case 'BAM' : setlocale(LC_MONETARY, "bs_BA");
                return "bs_BA";
            case 'BBD' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'BDT' : setlocale(LC_MONETARY, "bn_BD");
                return "bn_BD";
            case 'BGN' : setlocale(LC_MONETARY, "bg_BG");
                return "bg_BG";
            case 'BIF' : setlocale(LC_MONETARY, "fr_BI");
                return "fr_BI";
            case 'BMD' : setlocale(LC_MONETARY, "en_BM");
                return "en_BM";
            case 'BND' : setlocale(LC_MONETARY, "ms_BN");
                return "ms_BN";
            case 'BOB' : setlocale(LC_MONETARY, "es_BO");
                return "es_BO";
            case 'BRL' : setlocale(LC_MONETARY, "pt_BR");
                return "pt_BR";
            case 'BSD' : setlocale(LC_MONETARY, "en_BS");
                return "en_BS";
            case 'BTN' : setlocale(LC_MONETARY, "dz_BT");
                return "dz_BT";
            case 'BWP' : setlocale(LC_MONETARY, "en_BW");
                return "en_BW";
            case 'BYR' : setlocale(LC_MONETARY, "be_BY");
                return "be_BY";
            case 'BZD' : setlocale(LC_MONETARY, "en_BZ");
                return "en_BZ";
            case 'CDF' : setlocale(LC_MONETARY, "ln_CD");
                return "ln_CD";
            case 'CHF' : setlocale(LC_MONETARY, "fr_CH");
                return "fr_CH";
            case 'CLP' : setlocale(LC_MONETARY, "es_CL");
                return "es_CL";
            case 'COP' : setlocale(LC_MONETARY, "es_CO");
                return "es_CO";
            case 'CRC' : setlocale(LC_MONETARY, "es_CR");
                return "es_CR";
            case 'RSD' : setlocale(LC_MONETARY, "sr_RS");
                return "sr_RS";
            case 'CUP' : setlocale(LC_MONETARY, "es_CU");
                return "es_CU";
            case 'CVE' : setlocale(LC_MONETARY, "pt_CV");
                return "pt_CV";
            case 'CYP' : setlocale(LC_MONETARY, "el_CY");
                return "el_CY";
            case 'CZK' : setlocale(LC_MONETARY, "cs_CZ");
                return "cs_CZ";
            case 'DJF' : setlocale(LC_MONETARY, "aa_DJ");
                return "aa_DJ";
            case 'DKK' : setlocale(LC_MONETARY, "da_DK");
                return "da_DK";
            case 'DOP' : setlocale(LC_MONETARY, "es_DO");
                return "es_DO";
            case 'DZD' : setlocale(LC_MONETARY, "ar_DZ");
                return "ar_DZ";
            case 'EEK' : setlocale(LC_MONETARY, "et_EE");
                return "et_EE";
            case 'EGP' : setlocale(LC_MONETARY, "ar_EG");
                return "ar_EG";
            case 'ERN' : setlocale(LC_MONETARY, "aa_ER");
                return "aa_ER";
            case 'FJD' : setlocale(LC_MONETARY, "en_FJ");
                return "en_FJ";
            case 'FKP' : setlocale(LC_MONETARY, "en_FK");
                return "en_FK";
            case 'GEL' : setlocale(LC_MONETARY, "ka_GE");
                return "ka_GE";
            case 'GGP' : setlocale(LC_MONETARY, "en_GG");
                return "en_GG";
            case 'GHC' : setlocale(LC_MONETARY, "ak_GH");
                return "ak_GH";
            case 'GIP' : setlocale(LC_MONETARY, "en_GI");
                return "en_GI";
            case 'GMD' : setlocale(LC_MONETARY, "en_GM");
                return "en_GM";
            case 'GNF' : setlocale(LC_MONETARY, "fr_GN");
                return "fr_GN";
            case 'GTQ' : setlocale(LC_MONETARY, "es_GT");
                return "es_GT";
            case 'GYD' : setlocale(LC_MONETARY, "en_GY");
                return "en_GY";
            case 'HNL' : setlocale(LC_MONETARY, "es_HN");
                return "es_HN";
            case 'HRK' : setlocale(LC_MONETARY, "hr_HR");
                return "hr_HR";
            case 'HTG' : setlocale(LC_MONETARY, "ht_HT");
                return "ht_HT";
            case 'HUF' : setlocale(LC_MONETARY, "hu_HU");
                return "hu_HU";
            case 'IDR' : setlocale(LC_MONETARY, "id_ID");
                return "id_ID";
            case 'ILS' : setlocale(LC_MONETARY, "he_IL");
                return "he_IL";
            case 'IMP' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'INR' : setlocale(LC_MONETARY, "hi_IN");
                return "hi_IN";
            case 'IQD' : setlocale(LC_MONETARY, "ar_IQ");
                return "ar_IQ";
            case 'IRR' : setlocale(LC_MONETARY, "fa_IR");
                return "fa_IR";
            case 'ISK' : setlocale(LC_MONETARY, "is_IS");
                return "is_IS";
            case 'JEP' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'JMD' : setlocale(LC_MONETARY, "en_JM");
                return "en_JM";
            case 'JOD' : setlocale(LC_MONETARY, "ar_JO");
                return "ar_JO";
            case 'JPY' : setlocale(LC_MONETARY, "ja_JP");
                return "ja_JP";
            case 'KES' : setlocale(LC_MONETARY, "sw_KE");
                return "sw_KE";
            case 'KGS' : setlocale(LC_MONETARY, "ky_KG");
                return "ky_KG";
            case 'KHR' : setlocale(LC_MONETARY, "km_KH");
                return "km_KH";
            case 'KMF' : setlocale(LC_MONETARY, "en_CA");
                return "km_KH";
            case 'KPW' : setlocale(LC_MONETARY, "ko_KP");
                return "ko_KP";
            case 'KRW' : setlocale(LC_MONETARY, "ko_KR");
                return "ko_KR";
            case 'KWD' : setlocale(LC_MONETARY, "ar_KW");
                return "ar_KW";
            case 'KYD' : setlocale(LC_MONETARY, "en_KY");
                return "en_KY";
            case 'KZT' : setlocale(LC_MONETARY, "kk_KZ");
                return "kk_KZ";
            case 'LAK' : setlocale(LC_MONETARY, "lo_LA");
                return "lo_LA";
            case 'LBP' : setlocale(LC_MONETARY, "ar_LB");
                return "ar_LB";
            case 'LKR' : setlocale(LC_MONETARY, "si_LK");
                return "si_LK";
            case 'LRD' : setlocale(LC_MONETARY, "en_LR");
                return "en_LR";
            case 'LSL' : setlocale(LC_MONETARY, "en_LS");
                return "en_LS";
            case 'LTL' : setlocale(LC_MONETARY, "lt_LT");
                return "lt_LT";
            case 'LVL' : setlocale(LC_MONETARY, "lv_LV");
                return "lv_LV";
            case 'LYD' : setlocale(LC_MONETARY, "ar_LY");
                return "ar_LY";
            case 'MAD' : setlocale(LC_MONETARY, "ar_MA");
                return "ar_MA";
            case 'MDL' : setlocale(LC_MONETARY, "mo_MD");
                return "mo_MD";
            case 'MGA' : setlocale(LC_MONETARY, "mg_MG");
                return "mg_MG";
            case 'MKD' : setlocale(LC_MONETARY, "mk_MK");
                return "mk_MK";
            case 'MMK' : setlocale(LC_MONETARY, "my_MM");
                return "my_MM";
            case 'MNT' : setlocale(LC_MONETARY, "mn_MN");
                return "mn_MN";
            case 'MRO' : setlocale(LC_MONETARY, "ar_MR");
                return "ar_MR";
            case 'MTL' : setlocale(LC_MONETARY, "mt_MT");
                return "mt_MT";
            case 'MUR' : setlocale(LC_MONETARY, "en_MU");
                return "en_MU";
            case 'MVR' : setlocale(LC_MONETARY, "dv_MV");
                return "dv_MV";
            case 'MWK' : setlocale(LC_MONETARY, "ny_MW");
                return "ny_MW";
            case 'MXN' : setlocale(LC_MONETARY, "es_MX");
                return "es_MX";
            case 'MYR' : setlocale(LC_MONETARY, "ms_MY");
                return "ms_MY";
            case 'MZN' : setlocale(LC_MONETARY, "pt_MZ");
                return "pt_MZ";
            case 'NAD' : setlocale(LC_MONETARY, "af_NA");
                return "af_NA";
            case 'NGN' : setlocale(LC_MONETARY, "yo_NG");
                return "yo_NG";
            case 'NIO' : setlocale(LC_MONETARY, "es_NI");
                return "es_NI";
            case 'NOK' : setlocale(LC_MONETARY, "nb_NO");
                return "nb_NO";
            case 'NPR' : setlocale(LC_MONETARY, "ne_NP");
                return "ne_NP";
            case 'NZD' : setlocale(LC_MONETARY, "en_NZ");
                return "en_NZ";
            case 'OMR' : setlocale(LC_MONETARY, "ar_OM");
                return "ar_OM";
            case 'PAB' : setlocale(LC_MONETARY, "es_PA");
                return "es_PA";
            case 'PEN' : setlocale(LC_MONETARY, "es_PE");
                return "es_PE";
            case 'PGK' : setlocale(LC_MONETARY, "en_PG");
                return "en_PG";
            case 'PHP' : setlocale(LC_MONETARY, "en_PH");
                return "en_PH";
            case 'PKR' : setlocale(LC_MONETARY, "pa_PK");
                return "pa_PK";
            case 'PLN' : setlocale(LC_MONETARY, "pl_PL");
                return "pl_PL";
            case 'PYG' : setlocale(LC_MONETARY, "es_PY");
                return "es_PY";
            case 'QAR' : setlocale(LC_MONETARY, "ar_QA");
                return "ar_QA";
            case 'RON' : setlocale(LC_MONETARY, "ro_RO");
                return "ro_RO";
            case 'RSD' : setlocale(LC_MONETARY, "sr_RS");
                return "sr_RS";
            case 'RUB' : setlocale(LC_MONETARY, "ru_RU");
                return "ru_RU";
            case 'RWF' : setlocale(LC_MONETARY, "rw_RW");
                return "rw_RW";
            case 'SAR' : setlocale(LC_MONETARY, "ar_SA");
                return "ar_SA";
            case 'SBD' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'SCR' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'SDG' : setlocale(LC_MONETARY, "ar_SD");
                return "ar_SD";
            case 'SEK' : setlocale(LC_MONETARY, "sv_SE");
                return "sv_SE";
            case 'SGD' : setlocale(LC_MONETARY, "en_SG");
                return "en_SG";
            case 'SHP' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'SIT' : setlocale(LC_MONETARY, "sl_SI");
                return "sl_SI";
            case 'SKK' : setlocale(LC_MONETARY, "sk_SK");
                return "sk_SK";
            case 'SLL' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'SOS' : setlocale(LC_MONETARY, "so_SO");
                return "so_SO";
            case 'SPL' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'SRD' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'STD' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'SVC' : setlocale(LC_MONETARY, "es_SV");
                return "es_SV";
            case 'SYP' : setlocale(LC_MONETARY, "ar_SY");
                return "ar_SY";
            case 'SZL' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'THB' : setlocale(LC_MONETARY, "th_TH");
                return "th_TH";
            case 'TJS' : setlocale(LC_MONETARY, "tg_TJ");
                return "tg_TJ";
            case 'TMM' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'TND' : setlocale(LC_MONETARY, "ar_TN");
                return "ar_TN";
            case 'TOP' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'TRY' : setlocale(LC_MONETARY, "tr_TR");
                return "tr_TR";
            case 'TTD' : setlocale(LC_MONETARY, "en_TT");
                return "en_TT";
            case 'TVD' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'TZS' : setlocale(LC_MONETARY, "sw_TZ");
                return "sw_TZ";
            case 'UAH' : setlocale(LC_MONETARY, "uk_UA");
                return "uk_UA";
            case 'UGX' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'UYU' : setlocale(LC_MONETARY, "es_UY");
                return "es_UY";
            case 'UZS' : setlocale(LC_MONETARY, "uz_UZ");
                return "uz_UZ";
            case 'VEB' : setlocale(LC_MONETARY, "es_VE");
                return "es_VE";
            case 'VND' : setlocale(LC_MONETARY, "vi_VN");
                return "vi_VN";
            case 'VUV' : setlocale(LC_MONETARY, "bi_VU");
                return "bi_VU";
            case 'WST' : setlocale(LC_MONETARY, "en_AS");
                return "en_AS";
            case 'XAF' : setlocale(LC_MONETARY, "sg_CF");
                return "sg_CF";
            case 'XAU' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'XCD' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'XOF' : setlocale(LC_MONETARY, "en_CA");
                return "en_CA";
            case 'XPF' : setlocale(LC_MONETARY, "fr_PF");
                return "fr_PF";
            case 'YER' : setlocale(LC_MONETARY, "ar_YE");
                return "ar_YE";
            case 'ZAR' : setlocale(LC_MONETARY, "af_ZA");
                return "af_ZA";
            case 'ZMK' : setlocale(LC_MONETARY, "en_ZM");
                return "en_ZM";
            case 'ZWD' : setlocale(LC_MONETARY, "en_ZW");
                return "en_ZW";
        endswitch;
    }

}
