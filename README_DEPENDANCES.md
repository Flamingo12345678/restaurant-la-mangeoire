# Dépendances PHP

Toutes les dépendances PHP (y compris Mollie) doivent être installées via Composer :

```sh
composer install
```

N’ajoutez jamais de dépendances PHP via des sous-modules git. Si vous voyez une erreur liée à un sous-module, nettoyez le dépôt avec :

```sh
git submodule deinit -f --all
git rm --cached vendor/mollie/mollie-api-php || true
rm -rf vendor/mollie/mollie-api-php || true
git add .
git commit -m "Nettoyage des sous-modules orphelins"
```

Puis relancez `composer install`.
