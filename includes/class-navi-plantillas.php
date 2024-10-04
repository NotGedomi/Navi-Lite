<?php
class Navi_Plantillas {
    private $db;
    private $navi_database;

    public function __construct() {
        global $wpdb, $navi_database;
        $this->db = $wpdb;
        $this->navi_database = $navi_database;
    }

    public function render_pagina() {
        ?>
        <div class="wrap">
            <h1>Gestionar Plantillas</h1>
            <button id="descargar-plantilla-ejemplo" class="button">Descargar Plantilla de Ejemplo</button>
            <form id="navi-plantilla-form" enctype="multipart/form-data">
                <input type="text" name="nombre_plantilla" placeholder="Nombre de la plantilla" required>
                <input type="file" name="plantilla_excel" accept=".xlsx,.xls" required>
                <button type="submit" class="button button-primary">Cargar Plantilla</button>
            </form>
            <div id="navi-plantilla-mensaje"></div>
            <h2>Plantillas Cargadas</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nombre de la Plantilla</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="navi-plantilla-tabla">
                    <!-- Los datos se cargarán aquí dinámicamente -->
                </tbody>
            </table>
        </div>
        <?php
    }

    public function ajax_cargar_plantilla() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }
    
        if (!isset($_POST['datos']) || !isset($_POST['nombre_plantilla'])) {
            wp_send_json_error('No se han recibido datos suficientes.');
        }
    
        $nombre_plantilla = sanitize_text_field($_POST['nombre_plantilla']);
        $datos = stripslashes($_POST['datos']);
    
        if (empty($datos)) {
            wp_send_json_error('Los datos recibidos no son válidos.');
        }
    
        $datos_array = json_decode($datos, true);
        $nivel1 = $datos_array[0]['nivel1'] ?? '';
        $nivel2 = $datos_array[0]['nivel2'] ?? '';
        $nivel3 = $datos_array[0]['nivel3'] ?? '';
    
        $tabla = $this->db->prefix . 'navi_plantillas';
    
        $resultado = $this->db->insert(
            $tabla,
            array(
                'nombre' => $nombre_plantilla,
                'datos' => $datos,
                'nivel1' => $nivel1,
                'nivel2' => $nivel2,
                'nivel3' => $nivel3
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    
        if ($resultado) {
            wp_send_json_success('Plantilla "' . $nombre_plantilla . '" cargada con éxito.');
        } else {
            wp_send_json_error('No se pudo insertar la plantilla.');
        }
    }
    
    public function ajax_obtener_plantillas() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }
    
        $tabla = $this->db->prefix . 'navi_plantillas';
        $plantillas = $this->db->get_results("SELECT id, nombre FROM $tabla", ARRAY_A);
    
        wp_send_json_success($plantillas);
    }

    public function ajax_eliminar_plantilla() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }

        if (!isset($_POST['id'])) {
            wp_send_json_error('No se ha recibido el ID de la plantilla.');
        }

        $id = intval($_POST['id']);
        $tabla = $this->db->prefix . 'navi_plantillas';

        $resultado = $this->db->delete($tabla, array('id' => $id), array('%d'));

        if ($resultado) {
            wp_send_json_success('Plantilla eliminada con éxito.');
        } else {
            wp_send_json_error('No se pudo eliminar la plantilla.');
        }
    }
}