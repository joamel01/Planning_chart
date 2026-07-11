<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/i18n.php';

function assert_i18n(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$catalogs = locale_catalogs();
assert_i18n(array_key_exists('en', $catalogs), 'English must be discovered from locales/en.php.');
assert_i18n(array_key_exists('sv', $catalogs), 'Swedish must be discovered from locales/sv.php.');
assert_i18n(array_key_exists('pt-BR', $catalogs), 'Brazilian Portuguese must be discovered from locales/pt-BR.php.');
assert_i18n(array_key_exists('no', $catalogs), 'Norwegian must be discovered from locales/no.php.');
assert_i18n(array_key_exists('de', $catalogs), 'German must be discovered from locales/de.php.');
assert_i18n(array_key_exists('fr', $catalogs), 'French must be discovered from locales/fr.php.');
assert_i18n(array_key_exists('es', $catalogs), 'Spanish must be discovered from locales/es.php.');
assert_i18n(array_key_exists('fi', $catalogs), 'Finnish must be discovered from locales/fi.php.');
assert_i18n(array_key_exists('da', $catalogs), 'Danish must be discovered from locales/da.php.');
assert_i18n($catalogs['sv']['name'] === 'Svenska', 'Language metadata must be loaded from the locale file.');

set_locale('sv');
assert_i18n(current_locale() === 'sv', 'Selected locale must be stored in the session.');
assert_i18n(t('nav.logout') === 'Logga ut', 'Swedish translations must be returned.');
assert_i18n(weekday_label('MON') === 'MÅN', 'Swedish weekday labels must be returned.');
assert_i18n(t('stats.active_users') === 'Aktiva användare', 'Swedish statistics labels must be returned.');
assert_i18n(t('unknown.key') === 'unknown.key', 'Unknown keys must have a predictable fallback.');

set_locale('en');
assert_i18n(t('nav.logout') === 'Log out', 'English translations must be returned.');

foreach (['pt-BR', 'no', 'de', 'fr', 'es', 'fi', 'da'] as $locale) {
    set_locale($locale);
    assert_i18n(t('nav.logout') !== 'nav.logout', $locale . ' must provide navigation translations.');
}

echo "i18n_test passed\n";
