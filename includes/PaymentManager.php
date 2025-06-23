<?php
/**
 * Gestionnaire des types de paiement
 * Support pour les paiements locaux et internationaux
 */

class PaymentManager {
    
    /**
     * Types de paiement disponibles par région
     */
    private static $paymentMethods = [
        // Méthodes universelles
        'especes' => [
            'name' => 'Espèces',
            'icon' => '💵',
            'description' => 'Paiement à la livraison ou en magasin',
            'countries' => ['*'], // Disponible partout
            'fees' => 0,
            'processing_time' => 'Immédiat'
        ],
        'carte' => [
            'name' => 'Carte Bancaire',
            'icon' => '💳',
            'description' => 'Visa, Mastercard, American Express',
            'countries' => ['*'],
            'fees' => 2.9,
            'processing_time' => 'Immédiat'
        ],
        
        // Méthodes internationales
        'stripe' => [
            'name' => 'Stripe',
            'icon' => '🔷',
            'description' => 'Paiement sécurisé par Stripe',
            'countries' => ['FR', 'US', 'GB', 'DE', 'IT', 'ES', 'CH', 'CA', 'AU'],
            'fees' => 2.9,
            'processing_time' => 'Immédiat'
        ],
        'paypal' => [
            'name' => 'PayPal',
            'icon' => '🟦',
            'description' => 'Paiement via PayPal',
            'countries' => ['*'],
            'fees' => 3.4,
            'processing_time' => 'Immédiat'
        ],
        'virement' => [
            'name' => 'Virement Bancaire',
            'icon' => '🏦',
            'description' => 'Virement SEPA pour l\'Europe',
            'countries' => ['FR', 'DE', 'IT', 'ES', 'PT', 'NL', 'BE', 'AT', 'FI', 'IE', 'GR'],
            'fees' => 0,
            'processing_time' => '1-3 jours ouvrés'
        ],
        
        // Méthodes africaines
        'orange_money' => [
            'name' => 'Orange Money',
            'icon' => '🧡',
            'description' => 'Paiement mobile Orange Money',
            'countries' => ['SN', 'CI', 'ML', 'BF', 'NE', 'GN', 'CM', 'CD', 'MG', 'MA'],
            'fees' => 1.5,
            'processing_time' => 'Immédiat'
        ],
        'mtn_money' => [
            'name' => 'MTN Mobile Money',
            'icon' => '💛',
            'description' => 'Paiement mobile MTN',
            'countries' => ['GH', 'UG', 'RW', 'ZM', 'CD', 'CI', 'CM', 'BJ'],
            'fees' => 1.5,
            'processing_time' => 'Immédiat'
        ],
        'wave' => [
            'name' => 'Wave',
            'icon' => '🌊',
            'description' => 'Paiement mobile Wave',
            'countries' => ['SN', 'CI', 'UG', 'GM'],
            'fees' => 1.0,
            'processing_time' => 'Immédiat'
        ],
        'moov_money' => [
            'name' => 'Moov Money',
            'icon' => '🔵',
            'description' => 'Paiement mobile Moov',
            'countries' => ['BJ', 'BF', 'CI', 'TG'],
            'fees' => 1.5,
            'processing_time' => 'Immédiat'
        ],
        
        // Méthodes nord-africaines
        'cib' => [
            'name' => 'CIB (Maroc)',
            'icon' => '🏛️',
            'description' => 'Paiement par carte CIB',
            'countries' => ['MA'],
            'fees' => 2.0,
            'processing_time' => 'Immédiat'
        ],
        'edahabia' => [
            'name' => 'Edahabia (Algérie)',
            'icon' => '💚',
            'description' => 'Carte de paiement Edahabia',
            'countries' => ['DZ'],
            'fees' => 1.5,
            'processing_time' => 'Immédiat'
        ],
        
        // Méthodes asiatiques
        'alipay' => [
            'name' => 'Alipay',
            'icon' => '🇨🇳',
            'description' => 'Paiement Alipay pour clients chinois',
            'countries' => ['CN'],
            'fees' => 2.5,
            'processing_time' => 'Immédiat'
        ],
        'wechat_pay' => [
            'name' => 'WeChat Pay',
            'icon' => '💚',
            'description' => 'Paiement WeChat Pay',
            'countries' => ['CN'],
            'fees' => 2.5,
            'processing_time' => 'Immédiat'
        ]
    ];
    
    /**
     * Obtient les méthodes de paiement disponibles pour un pays
     */
    public static function getAvailablePaymentMethods($country_code = null) {
        if (!$country_code) {
            $country_code = CurrencyManager::detectCountry();
        }
        
        $available = [];
        
        foreach (self::$paymentMethods as $key => $method) {
            // Vérifier si la méthode est disponible dans ce pays
            if (in_array('*', $method['countries']) || in_array($country_code, $method['countries'])) {
                $available[$key] = $method;
            }
        }
        
        return $available;
    }
    
    /**
     * Obtient une méthode de paiement spécifique
     */
    public static function getPaymentMethod($method_key) {
        return self::$paymentMethods[$method_key] ?? null;
    }
    
    /**
     * Calcule les frais de paiement
     */
    public static function calculateFees($amount, $method_key) {
        $method = self::getPaymentMethod($method_key);
        if (!$method) {
            return 0;
        }
        
        return ($amount * $method['fees']) / 100;
    }
    
    /**
     * Obtient le montant total avec frais
     */
    public static function getTotalWithFees($amount, $method_key) {
        $fees = self::calculateFees($amount, $method_key);
        return $amount + $fees;
    }
    
    /**
     * Valide si une méthode de paiement est disponible pour un pays
     */
    public static function isPaymentMethodAvailable($method_key, $country_code = null) {
        if (!$country_code) {
            $country_code = CurrencyManager::detectCountry();
        }
        
        $method = self::getPaymentMethod($method_key);
        if (!$method) {
            return false;
        }
        
        return in_array('*', $method['countries']) || in_array($country_code, $method['countries']);
    }
    
    /**
     * Obtient les méthodes de paiement recommandées selon la région
     */
    public static function getRecommendedMethods($country_code = null) {
        if (!$country_code) {
            $country_code = CurrencyManager::detectCountry();
        }
        
        $recommended = [];
        
        // Recommandations par région
        $africanCountries = ['SN', 'CI', 'ML', 'BF', 'NE', 'GN', 'CM', 'CD', 'MG', 'MA', 'GH', 'UG', 'RW', 'ZM', 'BJ', 'TG', 'GM', 'DZ'];
        $europeanCountries = ['FR', 'DE', 'IT', 'ES', 'PT', 'NL', 'BE', 'AT', 'FI', 'IE', 'GR', 'CH', 'GB'];
        
        if (in_array($country_code, $africanCountries)) {
            // Recommandations pour l'Afrique
            $recommended = ['orange_money', 'mtn_money', 'wave', 'especes', 'carte'];
        } elseif (in_array($country_code, $europeanCountries)) {
            // Recommandations pour l'Europe
            $recommended = ['carte', 'stripe', 'paypal', 'virement', 'especes'];
        } elseif ($country_code === 'CN') {
            // Recommandations pour la Chine
            $recommended = ['alipay', 'wechat_pay', 'carte'];
        } else {
            // Recommandations par défaut
            $recommended = ['carte', 'paypal', 'stripe', 'especes'];
        }
        
        // Filtrer les méthodes disponibles
        $available = self::getAvailablePaymentMethods($country_code);
        $result = [];
        
        foreach ($recommended as $method_key) {
            if (isset($available[$method_key])) {
                $result[$method_key] = $available[$method_key];
            }
        }
        
        return $result;
    }
}
?>
