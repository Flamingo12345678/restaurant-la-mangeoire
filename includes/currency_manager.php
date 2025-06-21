<?php
/**
 * Système de gestion des devises locales
 * Détecte automatiquement la devise de l'utilisateur basée sur sa localisation
 */

class CurrencyManager {
    private static $currencies = [
        // Europe
        'FR' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'DE' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'IT' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'ES' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'PT' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'NL' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'BE' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'AT' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'FI' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'IE' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'GR' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'CH' => ['code' => 'CHF', 'symbol' => 'CHF', 'name' => 'Franc Suisse'],
        'GB' => ['code' => 'GBP', 'symbol' => '£', 'name' => 'Livre Sterling'],
        'NO' => ['code' => 'NOK', 'symbol' => 'kr', 'name' => 'Couronne Norvégienne'],
        'SE' => ['code' => 'SEK', 'symbol' => 'kr', 'name' => 'Couronne Suédoise'],
        'DK' => ['code' => 'DKK', 'symbol' => 'kr', 'name' => 'Couronne Danoise'],
        'PL' => ['code' => 'PLN', 'symbol' => 'zł', 'name' => 'Zloty'],
        'CZ' => ['code' => 'CZK', 'symbol' => 'Kč', 'name' => 'Couronne Tchèque'],
        'HU' => ['code' => 'HUF', 'symbol' => 'Ft', 'name' => 'Forint'],
        
        // Amériques
        'US' => ['code' => 'USD', 'symbol' => '$', 'name' => 'Dollar Américain'],
        'CA' => ['code' => 'CAD', 'symbol' => 'C$', 'name' => 'Dollar Canadien'],
        'MX' => ['code' => 'MXN', 'symbol' => '$', 'name' => 'Peso Mexicain'],
        'BR' => ['code' => 'BRL', 'symbol' => 'R$', 'name' => 'Real Brésilien'],
        'AR' => ['code' => 'ARS', 'symbol' => '$', 'name' => 'Peso Argentin'],
        'CL' => ['code' => 'CLP', 'symbol' => '$', 'name' => 'Peso Chilien'],
        'CO' => ['code' => 'COP', 'symbol' => '$', 'name' => 'Peso Colombien'],
        
        // Afrique
        'CM' => ['code' => 'XAF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'SN' => ['code' => 'XOF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'CI' => ['code' => 'XOF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'BF' => ['code' => 'XOF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'ML' => ['code' => 'XOF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'NE' => ['code' => 'XOF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'TD' => ['code' => 'XAF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'CF' => ['code' => 'XAF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'GA' => ['code' => 'XAF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'CG' => ['code' => 'XAF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'GQ' => ['code' => 'XAF', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
        'ZA' => ['code' => 'ZAR', 'symbol' => 'R', 'name' => 'Rand Sud-Africain'],
        'NG' => ['code' => 'NGN', 'symbol' => '₦', 'name' => 'Naira'],
        'KE' => ['code' => 'KES', 'symbol' => 'KSh', 'name' => 'Shilling Kenyan'],
        'GH' => ['code' => 'GHS', 'symbol' => '₵', 'name' => 'Cedi Ghanéen'],
        'MA' => ['code' => 'MAD', 'symbol' => 'DH', 'name' => 'Dirham Marocain'],
        'TN' => ['code' => 'TND', 'symbol' => 'DT', 'name' => 'Dinar Tunisien'],
        'DZ' => ['code' => 'DZD', 'symbol' => 'DA', 'name' => 'Dinar Algérien'],
        'EG' => ['code' => 'EGP', 'symbol' => '£', 'name' => 'Livre Égyptienne'],
        
        // Asie
        'CN' => ['code' => 'CNY', 'symbol' => '¥', 'name' => 'Yuan Chinois'],
        'JP' => ['code' => 'JPY', 'symbol' => '¥', 'name' => 'Yen Japonais'],
        'KR' => ['code' => 'KRW', 'symbol' => '₩', 'name' => 'Won Sud-Coréen'],
        'IN' => ['code' => 'INR', 'symbol' => '₹', 'name' => 'Roupie Indienne'],
        'TH' => ['code' => 'THB', 'symbol' => '฿', 'name' => 'Baht Thaïlandais'],
        'VN' => ['code' => 'VND', 'symbol' => '₫', 'name' => 'Dong Vietnamien'],
        'MY' => ['code' => 'MYR', 'symbol' => 'RM', 'name' => 'Ringgit Malaisien'],
        'SG' => ['code' => 'SGD', 'symbol' => 'S$', 'name' => 'Dollar de Singapour'],
        'PH' => ['code' => 'PHP', 'symbol' => '₱', 'name' => 'Peso Philippin'],
        'ID' => ['code' => 'IDR', 'symbol' => 'Rp', 'name' => 'Roupie Indonésienne'],
        
        // Océanie
        'AU' => ['code' => 'AUD', 'symbol' => 'A$', 'name' => 'Dollar Australien'],
        'NZ' => ['code' => 'NZD', 'symbol' => 'NZ$', 'name' => 'Dollar Néo-Zélandais'],
        
        // Moyen-Orient
        'AE' => ['code' => 'AED', 'symbol' => 'د.إ', 'name' => 'Dirham des EAU'],
        'SA' => ['code' => 'SAR', 'symbol' => 'ر.س', 'name' => 'Riyal Saoudien'],
        'IL' => ['code' => 'ILS', 'symbol' => '₪', 'name' => 'Shekel Israélien'],
        'TR' => ['code' => 'TRY', 'symbol' => '₺', 'name' => 'Livre Turque'],
    ];

    private static $exchange_rates = [
        // Taux de change par rapport à l'EUR (monnaie de base)
        'EUR' => 1.0,     // Base
        'USD' => 1.08,    // 1 EUR = 1.08 USD
        'GBP' => 0.85,    // 1 EUR = 0.85 GBP
        'CHF' => 0.97,    // 1 EUR = 0.97 CHF
        'CAD' => 1.47,    // 1 EUR = 1.47 CAD
        'AUD' => 1.62,    // 1 EUR = 1.62 AUD
        'JPY' => 160.0,   // 1 EUR = 160 JPY
        'CNY' => 7.85,    // 1 EUR = 7.85 CNY
        'XAF' => 655.96,  // 1 EUR = 655.96 XAF (taux fixe)
        'XOF' => 655.96,  // 1 EUR = 655.96 XOF (même taux que XAF)
        'NOK' => 11.5,    // 1 EUR = 11.5 NOK
        'SEK' => 11.3,    // 1 EUR = 11.3 SEK
        'DKK' => 7.46,    // 1 EUR = 7.46 DKK
        'PLN' => 4.35,    // 1 EUR = 4.35 PLN
        'CZK' => 24.5,    // 1 EUR = 24.5 CZK
        'HUF' => 390.0,   // 1 EUR = 390 HUF
        'MXN' => 18.5,    // 1 EUR = 18.5 MXN
        'BRL' => 5.9,     // 1 EUR = 5.9 BRL
        'ARS' => 270.0,   // 1 EUR = 270 ARS
        'CLP' => 1000.0,  // 1 EUR = 1000 CLP
        'COP' => 4300.0,  // 1 EUR = 4300 COP
        'ZAR' => 20.2,    // 1 EUR = 20.2 ZAR
        'NGN' => 1650.0,  // 1 EUR = 1650 NGN
        'KES' => 140.0,   // 1 EUR = 140 KES
        'GHS' => 13.2,    // 1 EUR = 13.2 GHS
        'MAD' => 10.8,    // 1 EUR = 10.8 MAD
        'TND' => 3.35,    // 1 EUR = 3.35 TND
        'DZD' => 145.0,   // 1 EUR = 145 DZD
        'EGP' => 53.0,    // 1 EUR = 53 EGP
        'KRW' => 1450.0,  // 1 EUR = 1450 KRW
        'INR' => 90.0,    // 1 EUR = 90 INR
        'THB' => 38.5,    // 1 EUR = 38.5 THB
        'VND' => 26500.0, // 1 EUR = 26500 VND
        'MYR' => 5.0,     // 1 EUR = 5.0 MYR
        'SGD' => 1.45,    // 1 EUR = 1.45 SGD
        'PHP' => 61.0,    // 1 EUR = 61 PHP
        'IDR' => 16800.0, // 1 EUR = 16800 IDR
        'NZD' => 1.78,    // 1 EUR = 1.78 NZD
        'AED' => 3.97,    // 1 EUR = 3.97 AED
        'SAR' => 4.05,    // 1 EUR = 4.05 SAR
        'ILS' => 3.98,    // 1 EUR = 3.98 ILS
        'TRY' => 35.0,    // 1 EUR = 35 TRY
    ];

    /**
     * Détecte le pays de l'utilisateur
     */
    public static function detectCountry() {
        // 1. Vérifier si défini en session
        if (isset($_SESSION['user_country'])) {
            return $_SESSION['user_country'];
        }

        // 2. Vérifier les headers HTTP
        $country = null;
        
        // Header CloudFlare
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            $country = strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']);
        }
        
        // Header Accept-Language
        if (!$country && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $lang_to_country = [
                'fr' => 'FR',
                'en' => 'US',
                'de' => 'DE',
                'es' => 'ES',
                'it' => 'IT',
                'pt' => 'BR',
                'ja' => 'JP',
                'zh' => 'CN',
                'ko' => 'KR',
                'ar' => 'SA'
            ];
            if (isset($lang_to_country[$lang])) {
                $country = $lang_to_country[$lang];
            }
        }

        // 3. Par défaut, France (EUR) comme monnaie de base
        if (!$country) {
            $country = 'FR';
        }

        return $country;
    }

    /**
     * Obtient la devise pour un pays
     */
    public static function getCurrencyForCountry($country_code) {
        return self::$currencies[$country_code] ?? self::$currencies['FR']; // Défaut: France (EUR)
    }

    /**
     * Obtient la devise actuelle de l'utilisateur
     */
    public static function getCurrentCurrency() {
        $country = self::detectCountry();
        return self::getCurrencyForCountry($country);
    }

    /**
     * Convertit un prix d'EUR vers la devise locale
     */
    public static function convertPrice($eur_price, $target_currency_code = null) {
        if ($target_currency_code === null) {
            $currency = self::getCurrentCurrency();
            $target_currency_code = $currency['code'];
        }

        // Si c'est déjà en EUR, pas de conversion
        if ($target_currency_code === 'EUR') {
            return $eur_price;
        }

        // Convertir
        $rate = self::$exchange_rates[$target_currency_code] ?? 1.0;
        return $eur_price * $rate;
    }

    /**
     * Formate un prix avec la devise locale
     */
    public static function formatPrice($eur_price, $show_original = false) {
        $currency = self::getCurrentCurrency();
        $converted_price = self::convertPrice($eur_price, $currency['code']);
        
        // Formatage selon la devise
        switch ($currency['code']) {
            case 'EUR':
            case 'USD':
            case 'GBP':
            case 'CHF':
            case 'CAD':
            case 'AUD':
                $formatted = number_format($converted_price, 2, ',', ' ');
                break;
            case 'JPY':
            case 'KRW':
            case 'VND':
            case 'IDR':
            case 'CLP':
            case 'COP':
                $formatted = number_format($converted_price, 0, ',', ' ');
                break;
            case 'XAF':
            case 'XOF':
                $formatted = number_format($converted_price, 0, ',', ' ');
                break;
            default:
                $formatted = number_format($converted_price, 2, '.', ' ');
        }

        $result = $formatted . ' ' . $currency['symbol'];
        
        // Optionnel: afficher le prix original en EUR
        if ($show_original && $currency['code'] !== 'EUR') {
            $result .= ' <small class="text-muted">(' . number_format($eur_price, 2) . ' €)</small>';
        }
        
        return $result;
    }

    /**
     * Permet à l'utilisateur de changer manuellement sa devise
     */
    public static function setCurrency($country_code) {
        if (isset(self::$currencies[$country_code])) {
            $_SESSION['user_country'] = $country_code;
            return true;
        }
        return false;
    }

    /**
     * Obtient la liste des devises disponibles
     */
    public static function getAvailableCurrencies() {
        $currencies = [];
        foreach (self::$currencies as $country => $currency) {
            $key = $currency['code'];
            if (!isset($currencies[$key])) {
                $currencies[$key] = [
                    'code' => $currency['code'],
                    'symbol' => $currency['symbol'],
                    'name' => $currency['name'],
                    'country' => $country
                ];
            }
        }
        return $currencies;
    }
}
?>
