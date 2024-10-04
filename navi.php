<?php
/**
 * Plugin Name: Navi
 * Description: Plugin para manejar plantillas y sedes con múltiples configuraciones
 * Version: 2.0
 * Author: Tu Nombre
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Definir constantes
define('NAVI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NAVI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir clases
require_once NAVI_PLUGIN_DIR . 'includes/class-navi-database.php';
require_once NAVI_PLUGIN_DIR . 'includes/class-navi-plantillas.php';
require_once NAVI_PLUGIN_DIR . 'includes/class-navi-sedes.php';
require_once NAVI_PLUGIN_DIR . 'includes/class-navi-config.php';

// Inicializar clases
global $navi_database, $navi_plantillas, $navi_sedes, $navi_config;
$navi_database = new Navi_Database();
$navi_plantillas = new Navi_Plantillas();
$navi_sedes = new Navi_Sedes();
$navi_config = new Navi_Config();

// Activación del plugin
register_activation_hook(__FILE__, 'navi_activar_plugin');

function navi_activar_plugin() {
    global $navi_database;
    $navi_database->crear_tablas();
    error_log('Plugin Navi activado y tablas creadas/actualizadas');
}

// Desactivación del plugin
register_deactivation_hook(__FILE__, 'navi_desactivar_plugin');

function navi_desactivar_plugin() {
    // Realizar acciones de limpieza si es necesario
}

// Agregar menús de administración
add_action('admin_menu', 'navi_agregar_menu');

function navi_agregar_menu() {
    add_menu_page('Navi', 'Navi', 'manage_options', 'navi', 'navi_pagina_principal', 'dashicons-location');
    add_submenu_page('navi', 'Plantillas', 'Plantillas', 'manage_options', 'navi-plantillas', array($GLOBALS['navi_plantillas'], 'render_pagina'));
    add_submenu_page('navi', 'Sedes', 'Sedes', 'manage_options', 'navi-sedes', array($GLOBALS['navi_sedes'], 'render_pagina'));
    add_submenu_page('navi', 'Configuración', 'Configuración', 'manage_options', 'navi-config', array($GLOBALS['navi_config'], 'render_pagina'));
}

function navi_pagina_principal() {
    echo '<div class="wrap"><h1>Bienvenido a Navi</h1></div>';
}

// Cargar scripts y estilos en el admin
add_action('admin_enqueue_scripts', 'navi_cargar_scripts_admin');

function navi_cargar_scripts_admin($hook) {
    if (strpos($hook, 'navi') !== false) {
        wp_enqueue_script('sheetjs', NAVI_PLUGIN_URL . 'libraries/sheetjs/xlsx.full.min.js', array(), '1.0', true);
        wp_enqueue_script('leaflet', NAVI_PLUGIN_URL . 'libraries/leaflet/leaflet.min.js', array(), '1.7.1', true);
        wp_enqueue_style('leaflet', NAVI_PLUGIN_URL . 'libraries/leaflet/leaflet.min.css', array(), '1.7.1');
        wp_enqueue_script('leaflet-extra-markers', NAVI_PLUGIN_URL . 'libraries/leaflet/leaflet.extra-markers.min.js', array('leaflet'), '1.7.1', true);
        wp_enqueue_style('leaflet-extra-markers', NAVI_PLUGIN_URL . 'libraries/leaflet/leaflet.extra-markers.min.css', array(), '1.7.1');
        wp_enqueue_script('navi-admin', NAVI_PLUGIN_URL . 'assets/js/navi-admin.js', array('jquery', 'leaflet'), '1.0', true);
        wp_localize_script('navi-admin', 'navi_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('navi_ajax_nonce')
        ));
    }
}

// Registrar acciones AJAX
add_action('wp_ajax_navi_cargar_plantilla', array($GLOBALS['navi_plantillas'], 'ajax_cargar_plantilla'));
add_action('wp_ajax_navi_obtener_plantillas', array($GLOBALS['navi_plantillas'], 'ajax_obtener_plantillas'));
add_action('wp_ajax_navi_guardar_sede', array($GLOBALS['navi_sedes'], 'ajax_guardar_sede'));
add_action('wp_ajax_navi_obtener_sedes', array($GLOBALS['navi_sedes'], 'ajax_obtener_sedes'));
add_action('wp_ajax_navi_obtener_niveles', array($GLOBALS['navi_sedes'], 'ajax_obtener_niveles'));
add_action('wp_ajax_navi_filtrar_sedes', array($GLOBALS['navi_sedes'], 'ajax_filtrar_sedes'));
add_action('wp_ajax_navi_obtener_opciones_nivel', array($GLOBALS['navi_sedes'], 'ajax_obtener_opciones_nivel'));
add_action('wp_ajax_navi_guardar_config', array($GLOBALS['navi_config'], 'ajax_guardar_config'));
add_action('wp_ajax_navi_obtener_config', array($GLOBALS['navi_config'], 'ajax_obtener_config'));
add_action('wp_ajax_navi_eliminar_plantilla', array($GLOBALS['navi_plantillas'], 'ajax_eliminar_plantilla'));
add_action('wp_ajax_navi_eliminar_sede', array($GLOBALS['navi_sedes'], 'ajax_eliminar_sede'));
add_action('wp_ajax_navi_obtener_paises', array($GLOBALS['navi_sedes'], 'ajax_obtener_paises'));
add_action('wp_ajax_navi_obtener_niveles_por_pais', array($GLOBALS['navi_sedes'], 'ajax_obtener_niveles_por_pais'));

// Agregar acciones AJAX para usuarios no logueados
add_action('wp_ajax_nopriv_navi_obtener_plantillas', array($GLOBALS['navi_plantillas'], 'ajax_obtener_plantillas'));
add_action('wp_ajax_nopriv_navi_obtener_sedes', array($GLOBALS['navi_sedes'], 'ajax_obtener_sedes'));
add_action('wp_ajax_nopriv_navi_obtener_niveles', array($GLOBALS['navi_sedes'], 'ajax_obtener_niveles'));
add_action('wp_ajax_nopriv_navi_filtrar_sedes', array($GLOBALS['navi_sedes'], 'ajax_filtrar_sedes'));
add_action('wp_ajax_nopriv_navi_obtener_opciones_nivel', array($GLOBALS['navi_sedes'], 'ajax_obtener_opciones_nivel'));
add_action('wp_ajax_nopriv_navi_obtener_config', array($GLOBALS['navi_config'], 'ajax_obtener_config'));
add_action('wp_ajax_nopriv_navi_obtener_paises', array($GLOBALS['navi_sedes'], 'ajax_obtener_paises'));
add_action('wp_ajax_nopriv_navi_obtener_niveles_por_pais', array($GLOBALS['navi_sedes'], 'ajax_obtener_niveles_por_pais'));

// Agregar acción para verificar la estructura de la base de datos en cada carga de la página de administración
add_action('admin_init', 'navi_verificar_base_datos');

function navi_verificar_base_datos() {
    global $navi_database;
    $navi_database->crear_tablas();
}

// Agregar shortcode para mostrar el filtro de sedes en el frontend
add_shortcode('navi_filtro_sedes', array($GLOBALS['navi_sedes'], 'shortcode_filtro_sedes'));

// Cargar scripts y estilos en el frontend
function navi_cargar_scripts_frontend() {
    global $post;
    // Cargar siempre, no solo cuando el shortcode está presente
    wp_enqueue_script('leaflet', NAVI_PLUGIN_URL . 'libraries/leaflet/leaflet.min.js', array(), '1.7.1', false);
    wp_enqueue_style('leaflet', NAVI_PLUGIN_URL . 'libraries/leaflet/leaflet.min.css', array(), '1.7.1');
    
    // Cargar Extra Markers
    wp_enqueue_style('leaflet-extra-markers', NAVI_PLUGIN_URL . 'libraries/leaflet/leaflet.extra-markers.min.css', array('leaflet'), '1.7.1');
    wp_enqueue_script('leaflet-extra-markers', NAVI_PLUGIN_URL . 'libraries/leaflet/leaflet.extra-markers.min.js', array('leaflet'), '1.7.1', false);
    
    // Cargar script frontend
    wp_enqueue_script('navi-frontend', NAVI_PLUGIN_URL . 'assets/js/navi-frontend.js', array('jquery', 'leaflet', 'leaflet-extra-markers'), '1.0', true);
    
    wp_localize_script('navi-frontend', 'navi_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('navi_ajax_nonce'),
        'plugin_url' => NAVI_PLUGIN_URL
    ));

    // Agregar script inline para verificar la carga de Leaflet
    wp_add_inline_script('leaflet', "
        console.log('Leaflet script loaded');
        window.leafletLoaded = true;
    ");
}
add_action('wp_enqueue_scripts', 'navi_cargar_scripts_frontend', 5);

// Agregar acción para imprimir scripts cargados en el footer
add_action('wp_footer', 'navi_print_loaded_scripts', 999);

function navi_print_loaded_scripts() {
    global $wp_scripts;
    echo '<script>console.log("Scripts cargados:", ' . json_encode($wp_scripts->done) . ');</script>';
}