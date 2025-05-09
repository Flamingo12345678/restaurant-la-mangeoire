/**
 * Gestion des cookies pour le restaurant La Mangeoire
 * Ce script permet aux utilisateurs de gérer leurs préférences de cookies
 */

// Fonction pour définir un cookie
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
}

// Fonction pour obtenir la valeur d'un cookie
function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// Fonction pour supprimer un cookie
function eraseCookie(name) {
    document.cookie = name + '=; Max-Age=-99999999; path=/';
}

// Fonction pour vérifier si le consentement aux cookies a été donné
function checkCookieConsent() {
    if (getCookie('cookie_consent') === null) {
        showCookieConsent();
    }
}

// Fonction pour afficher la bannière de consentement
function showCookieConsent() {
    // Créer la bannière
    const consentBanner = document.createElement('div');
    consentBanner.id = 'cookie-consent-banner';
    consentBanner.innerHTML = `
        <div class="cookie-content">
            <h3>Gestion des cookies</h3>
            <p>Nous utilisons des cookies pour améliorer votre expérience sur notre site. Vous pouvez choisir d'accepter ou de refuser les cookies non essentiels.</p>
            <div class="cookie-buttons">
                <button id="accept-cookies" class="cookie-btn accept">Accepter tous les cookies</button>
                <button id="essential-cookies" class="cookie-btn essential">Cookies essentiels uniquement</button>
                <button id="cookie-settings" class="cookie-btn settings">Paramètres des cookies</button>
            </div>
        </div>
    `;
    document.body.appendChild(consentBanner);

    // Ajouter les écouteurs d'événements
    document.getElementById('accept-cookies').addEventListener('click', function() {
        acceptAllCookies();
        hideCookieBanner();
    });

    document.getElementById('essential-cookies').addEventListener('click', function() {
        acceptEssentialCookies();
        hideCookieBanner();
    });

    document.getElementById('cookie-settings').addEventListener('click', function() {
        showCookieSettings();
    });
}

// Fonction pour afficher les paramètres des cookies
function showCookieSettings() {
    // Masquer la bannière de consentement
    const banner = document.getElementById('cookie-consent-banner');
    if (banner) {
        banner.style.display = 'none';
    }

    // Créer la modal des paramètres
    const modal = document.createElement('div');
    modal.id = 'cookie-settings-modal';
    
    // Obtenir les préférences actuelles
    const necessary = true; // Toujours obligatoire
    const analytics = getCookie('analytics_cookies') === 'true';
    const marketing = getCookie('marketing_cookies') === 'true';
    
    modal.innerHTML = `
        <div class="cookie-settings-content">
            <span class="close-btn">&times;</span>
            <h3>Paramètres des cookies</h3>
            <p>Personnalisez vos préférences de cookies ci-dessous.</p>
            
            <div class="cookie-option">
                <label>
                    <input type="checkbox" id="necessary-cookies" checked disabled>
                    <span class="cookie-label">Cookies nécessaires</span>
                </label>
                <p class="cookie-description">Ces cookies sont nécessaires au fonctionnement du site et ne peuvent pas être désactivés.</p>
            </div>
            
            <div class="cookie-option">
                <label>
                    <input type="checkbox" id="analytics-cookies" ${analytics ? 'checked' : ''}>
                    <span class="cookie-label">Cookies analytiques</span>
                </label>
                <p class="cookie-description">Ces cookies nous permettent d'analyser votre utilisation du site pour en améliorer les performances et la navigation.</p>
            </div>
            
            <div class="cookie-option">
                <label>
                    <input type="checkbox" id="marketing-cookies" ${marketing ? 'checked' : ''}>
                    <span class="cookie-label">Cookies marketing</span>
                </label>
                <p class="cookie-description">Ces cookies sont utilisés pour vous proposer des publicités personnalisées sur d'autres sites web.</p>
            </div>
            
            <div class="cookie-buttons">
                <button id="save-settings" class="cookie-btn save">Enregistrer mes préférences</button>
                <button id="accept-all-settings" class="cookie-btn accept">Accepter tous les cookies</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Écouteurs d'événements
    document.querySelector('.close-btn').addEventListener('click', function() {
        closeSettingsModal();
        checkCookieConsent();
    });
    
    document.getElementById('save-settings').addEventListener('click', function() {
        saveCookieSettings();
        closeSettingsModal();
    });
    
    document.getElementById('accept-all-settings').addEventListener('click', function() {
        acceptAllCookies();
        closeSettingsModal();
    });
}

// Fonction pour fermer la modal des paramètres
function closeSettingsModal() {
    const modal = document.getElementById('cookie-settings-modal');
    if (modal) {
        document.body.removeChild(modal);
    }
}

// Fonction pour enregistrer les paramètres des cookies
function saveCookieSettings() {
    const analytics = document.getElementById('analytics-cookies').checked;
    const marketing = document.getElementById('marketing-cookies').checked;
    
    setCookie('cookie_consent', 'custom', 365);
    setCookie('analytics_cookies', analytics.toString(), 365);
    setCookie('marketing_cookies', marketing.toString(), 365);
    
    // Appliquer les paramètres
    applySettings(true, analytics, marketing);
}

// Fonction pour accepter tous les cookies
function acceptAllCookies() {
    setCookie('cookie_consent', 'all', 365);
    setCookie('analytics_cookies', 'true', 365);
    setCookie('marketing_cookies', 'true', 365);
    
    // Appliquer les paramètres
    applySettings(true, true, true);
}

// Fonction pour accepter uniquement les cookies essentiels
function acceptEssentialCookies() {
    setCookie('cookie_consent', 'essential', 365);
    setCookie('analytics_cookies', 'false', 365);
    setCookie('marketing_cookies', 'false', 365);
    
    // Appliquer les paramètres
    applySettings(true, false, false);
}

// Fonction pour masquer la bannière de consentement
function hideCookieBanner() {
    const banner = document.getElementById('cookie-consent-banner');
    if (banner) {
        document.body.removeChild(banner);
    }
}

// Fonction pour appliquer les paramètres de cookies
function applySettings(necessary, analytics, marketing) {
    // Les cookies nécessaires sont toujours activés
    
    // Gérer les cookies analytiques
    if (!analytics) {
        // Désactiver Google Analytics, etc.
        disableAnalyticsCookies();
    }
    
    // Gérer les cookies marketing
    if (!marketing) {
        // Désactiver les cookies marketing, etc.
        disableMarketingCookies();
    }
}

// Fonction pour désactiver les cookies analytiques
function disableAnalyticsCookies() {
    // Désactiver Google Analytics
    window['ga-disable-UA-XXXXXXXX-X'] = true;
    // Supprimer les cookies de Google Analytics existants
    eraseCookie('_ga');
    eraseCookie('_gat');
    eraseCookie('_gid');
    
    // Bloquer les appels aux domaines d'analyse
    if (typeof blockTrackingScripts === 'function') {
        blockTrackingScripts(['www.google-analytics.com', 'analytics.google.com']);
    }
}

// Fonction pour désactiver les cookies marketing
function disableMarketingCookies() {
    // Supprimer les cookies marketing potentiels
    eraseCookie('_fbp'); // Facebook Pixel
    eraseCookie('fr');   // Facebook
    eraseCookie('IDE');  // Google DoubleClick
    
    // Bloquer les appels aux domaines de marketing
    if (typeof blockTrackingScripts === 'function') {
        blockTrackingScripts(['connect.facebook.net', 'facebook.com', 'doubleclick.net']);
    }
}

// Fonction pour bloquer les scripts de tracking
function blockTrackingScripts(domains) {
    // Cette fonction peut être utilisée si nous implémentons une solution avancée
    // pour bloquer les scripts de tracking au niveau du DOM
    console.log("Domaines bloqués pour tracking :", domains);
}

// Fonction pour afficher la bannière de préférences des cookies (accessible via un lien)
function showCookiePreferences() {
    showCookieSettings();
}

// Vérifier le consentement aux cookies lors du chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    checkCookieConsent();
    
    // Appliquer les paramètres des cookies déjà enregistrés
    if (getCookie('cookie_consent') !== null) {
        const analytics = getCookie('analytics_cookies') === 'true';
        const marketing = getCookie('marketing_cookies') === 'true';
        applySettings(true, analytics, marketing);
    }
    
    // Ajouter un écouteur pour le lien de préférences dans le footer
    const preferencesLinks = document.querySelectorAll('.cookie-preferences-link');
    preferencesLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showCookiePreferences();
        });
    });
});
