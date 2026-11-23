<?php
/**
 * Plugin Name: JUCCA LLC Site
 * Description: Serves the JUCCA LLC one-page website in English (/) and Spanish (/es/), with its contact form, sitemap and sharing tags. Deactivate to return to the regular WordPress theme.
 * Version: 1.0.2
 * Author: Worldwise Ventures
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) { exit; }

define('JUCCA_SITE_TO', 'juccallc@yahoo.com');   // where contact form messages are delivered
define('JUCCA_SITE_MAX_PER_HOUR', 5);            // per visitor IP

/* ---------------------------------------------------------------------------
 * Routing: "/" serves English, "/es/" serves Spanish, "/sitemap.xml" the sitemap.
 * Everything else (wp-admin, login, REST, other pages) is left to WordPress.
 * ------------------------------------------------------------------------- */
function jucca_site_request_path() {
    $uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = (string) parse_url($uri, PHP_URL_PATH);
    $home = trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
    $path = trim($path, '/');
    if ($home !== '') {
        if ($path === $home) { return ''; }
        if (strpos($path, $home . '/') === 0) { return substr($path, strlen($home) + 1); }
        return null;
    }
    return $path;
}

function jucca_site_route() {
    if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX) || (defined('DOING_CRON') && DOING_CRON) || (defined('REST_REQUEST') && REST_REQUEST) || (defined('WP_CLI') && WP_CLI)) { return; }
    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') { return; }
    $path = jucca_site_request_path();
    if ($path === null) { return; }

    // Leave WordPress alone when the URL carries one of its own queries (previews, search, ?p=, customizer...).
    foreach (array_keys($_GET) as $key) {
        if (preg_match('/^(p|page_id|s|m|cat|tag|author|feed|attachment_id|preview|preview_id|preview_nonce|rest_route|customize_changeset_uuid|customize_theme|wp_customize|doing_wp_cron|action|replytocom|post_type|name|pagename|paged|page|year|monthnum|day|tb|embed|sitemap|sitemap-subtype|robots|favicon)$/', (string) $key) || strpos((string) $key, 'wp_') === 0 || strpos((string) $key, 'wp-') === 0) { return; }
    }

    if ($path === '') {
        if (isset($_GET['lang']) && $_GET['lang'] === 'es') { wp_safe_redirect(home_url('/es/'), 301); exit; }
        jucca_site_serve('en');
    } elseif ($path === 'es') {
        if (substr((string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH), -1) !== '/') { wp_safe_redirect(home_url('/es/'), 301); exit; }
        jucca_site_serve('es');
    } elseif ($path === 'sitemap.xml') {
        jucca_site_sitemap();
    } elseif ($path === 'robots.txt') {
        jucca_site_robots();
    }
}
add_action('init', 'jucca_site_route', 0);

function jucca_site_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    if (is_ssl()) { header('Strict-Transport-Security: max-age=15552000'); }
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");
}

function jucca_site_serve($lang) {
    $file = plugin_dir_path(__FILE__) . 'templates/' . ($lang === 'es' ? 'es' : 'en') . '.php';
    if (!is_readable($file)) { return; }
    ob_start();
    include $file;
    $html = ltrim((string) ob_get_clean());
    $html = strtr($html, array(
        '{{BASE}}'    => esc_url(plugin_dir_url(__FILE__)),
        '{{HOME_ES}}' => esc_url(home_url('/es/')),
        '{{HOME}}'    => esc_url(home_url('/')),
        '{{AJAX}}'    => esc_url(admin_url('admin-ajax.php')),
    ));
    status_header(200);
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Language: ' . ($lang === 'es' ? 'es' : 'en-US'));
    header('Cache-Control: public, max-age=600');
    header('Vary: Accept-Encoding');
    jucca_site_security_headers();
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'HEAD') { exit; }
    echo $html;
    exit;
}

function jucca_site_sitemap() {
    $mod = gmdate('Y-m-d', (int) filemtime(plugin_dir_path(__FILE__) . 'templates/en.php'));
    $en  = esc_url(home_url('/'));
    $es  = esc_url(home_url('/es/'));
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
    foreach (array($en, $es) as $loc) {
        echo "  <url>\n    <loc>$loc</loc>\n    <lastmod>$mod</lastmod>\n";
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"$en\"/>\n";
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"es\" href=\"$es\"/>\n";
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"$en\"/>\n  </url>\n";
    }
    echo "</urlset>\n";
    exit;
}

function jucca_site_robots() {
    status_header(200);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');
    echo "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: " . esc_url_raw(home_url('/sitemap.xml')) . "\n";
    exit;
}

/* This is a one-page site. For visitors, every other WordPress address (sample
 * posts and pages, archives, feeds, search) goes to the home page, and unknown
 * addresses get a plain 404. Logged-in users still see WordPress normally. */
function jucca_site_front_cleanup() {
    if (is_user_logged_in() || is_admin() || is_robots() || is_favicon()) { return; }
    if (is_404()) {
        status_header(404);
        nocache_headers();
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex');
        jucca_site_security_headers();
        $home = esc_url(home_url('/'));
        echo '<!doctype html><html lang="en-US"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>Page not found | JUCCA LLC</title></head>'
           . '<body style="margin:0;font-family:system-ui,-apple-system,Segoe UI,Arial,sans-serif;background:#F2F6FB;color:#14213A;display:flex;min-height:100vh;align-items:center;justify-content:center;text-align:center">'
           . '<main style="padding:32px"><p style="margin:0 0 8px;font-weight:700;letter-spacing:.18em;font-size:13px;color:#0B5FC4">JUCCA LLC</p>'
           . '<h1 style="margin:0 0 12px;font-size:32px;color:#0E2F6B">Page not found</h1>'
           . '<p style="margin:0 0 24px;font-size:17px">P&aacute;gina no encontrada.</p>'
           . '<a href="' . $home . '" style="display:inline-block;padding:14px 26px;border-radius:6px;background:#0B5FC4;color:#fff;font-weight:700;text-decoration:none">Go to the home page</a></main></body></html>';
        exit;
    }
    wp_safe_redirect(home_url('/'), 301);
    exit;
}
add_action('template_redirect', 'jucca_site_front_cleanup', 0);

// Point search engines at this sitemap and switch off the default WordPress one (it lists sample content).
add_filter('wp_sitemaps_enabled', '__return_false');
add_filter('robots_txt', function ($output) {
    return rtrim((string) $output) . "\n\nSitemap: " . esc_url_raw(home_url('/sitemap.xml')) . "\n";
}, 99);

/* ---------------------------------------------------------------------------
 * Contact form. Posted to admin-ajax.php with action=jucca_contact.
 * ------------------------------------------------------------------------- */
function jucca_site_contact() {
    $post = wp_unslash($_POST);
    $get  = function ($k) use ($post) { return isset($post[$k]) && is_string($post[$k]) ? $post[$k] : ''; };

    // Spam checks. Honeypot and time trap pretend to succeed so bots learn nothing.
    if ($get('website') !== '' || (int) $get('elapsed') < 3000) { wp_send_json(array('success' => true)); }

    $name    = mb_substr(sanitize_text_field($get('name')), 0, 120);
    $company = mb_substr(sanitize_text_field($get('company')), 0, 160);
    $email   = sanitize_email($get('email'));
    $message = mb_substr(sanitize_textarea_field($get('message')), 0, 6000);
    $lang    = $get('lang') === 'es' ? 'es' : 'en';

    if ($name === '' || $message === '' || !is_email($email)) { wp_send_json(array('success' => false, 'error' => 'invalid'), 422); }

    // More than two links in a first message is almost always spam.
    if (preg_match_all('#https?://|www\.#i', $message . ' ' . $name . ' ' . $company) > 2) { wp_send_json(array('success' => true)); }

    // Rate limit per IP.
    $ip   = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
    $key  = 'jucca_form_' . md5($ip);
    $hits = (int) get_transient($key);
    if ($hits >= JUCCA_SITE_MAX_PER_HOUR) { wp_send_json(array('success' => false, 'error' => 'rate'), 429); }
    set_transient($key, $hits + 1, HOUR_IN_SECONDS);

    $host = preg_replace('/^www\./', '', (string) parse_url(home_url('/'), PHP_URL_HOST));
    $subject = 'New message from the JUCCA LLC website: ' . $name;
    $body  = "Name: $name\n";
    $body .= 'Company: ' . ($company !== '' ? $company : '-') . "\n";
    $body .= "Email: $email\n";
    $body .= 'Page language: ' . ($lang === 'es' ? 'Spanish' : 'English') . "\n\n";
    $body .= "Message:\n$message\n\n--\nSent from the contact form on $host\n";
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: JUCCA LLC website <website@' . $host . '>',
        'Reply-To: ' . $name . ' <' . $email . '>',
    );

    $sent = wp_mail(JUCCA_SITE_TO, $subject, $body, $headers);
    wp_send_json(array('success' => (bool) $sent, 'error' => $sent ? '' : 'mail'), $sent ? 200 : 500);
}
add_action('wp_ajax_nopriv_jucca_contact', 'jucca_site_contact');
add_action('wp_ajax_jucca_contact', 'jucca_site_contact');

/* ---------------------------------------------------------------------------
 * Light WordPress hardening. Each item only hides information from visitors.
 * ------------------------------------------------------------------------- */
// Do not advertise the WordPress version.
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

// Block username discovery through /?author=1 for visitors.
add_action('init', function () {
    if (!is_admin() && !is_user_logged_in() && isset($_GET['author']) && preg_match('/^\d+$/', (string) $_GET['author'])) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
}, 1);

// Hide the user list from the public REST API.
add_filter('rest_endpoints', function ($endpoints) {
    if (!is_user_logged_in()) {
        unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $endpoints;
});
