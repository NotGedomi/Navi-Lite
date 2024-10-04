<?php
// Si WordPress no llamó a este archivo, abortamos
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Cargar la clase de base de datos
require_once plugin_dir_path(__FILE__) . 'includes/class-navi-database.php';

// Crear una instancia de la clase de base de datos
$navi_database = new Navi_Database();

// Eliminar las tablas
$navi_database->eliminar_tablas();

// Eliminar opciones si las hay
delete_option('navi_version');

// Limpiar cualquier caché transitoria
delete_transient('navi_transient_data');

error_log('Plugin Navi desinstalado y tablas eliminadas');