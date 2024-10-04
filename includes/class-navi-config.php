<?php
class Navi_Config {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function render_pagina() {
        ?>
        <div class="wrap">
            <h1>Configuración de Navi</h1>
            <form id="navi-config-form">
                <table class="form-table">
                    <tr>
                        <th><label for="plantilla_id">Plantilla</label></th>
                        <td>
                            <select id="plantilla_id" name="plantilla_id" required>
                                <option value="">Seleccione una plantilla</option>
                                <?php
                                $plantillas = $this->obtener_plantillas();
                                foreach ($plantillas as $plantilla) {
                                    echo "<option value='{$plantilla['id']}'>{$plantilla['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr id="campos-mostrar-container" style="display:none;">
                        <th>Campos a mostrar</th>
                        <td id="campos-mostrar">
                            <!-- Los campos se cargarán dinámicamente aquí -->
                        </td>
                    </tr>
                    <tr id="mostrar-mapa-container" style="display:none;">
                        <th><label for="mostrar_mapa">Mostrar mapa</label></th>
                        <td>
                            <input type="checkbox" id="mostrar_mapa" name="mostrar_mapa" value="1">
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Guardar Configuración">
                </p>
            </form>
            <div id="navi-config-mensaje"></div>
            <div id="navi-shortcode"></div>
        </div>
        <?php
    }

    private function obtener_plantillas() {
        $tabla = $this->db->prefix . 'navi_plantillas';
        return $this->db->get_results("SELECT id, nombre FROM $tabla", ARRAY_A);
    }

    public function ajax_guardar_config() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }
    
        $plantilla_id = intval($_POST['plantilla_id']);
        $campos_mostrar = json_decode(stripslashes($_POST['campos_mostrar']), true);
        $mostrar_mapa = intval($_POST['mostrar_mapa']);
    
        if (!is_array($campos_mostrar)) {
            wp_send_json_error('Los campos a mostrar no son válidos.');
        }
    
        $campos_mostrar = array_map('sanitize_text_field', $campos_mostrar);
    
        $tabla = $this->db->prefix . 'navi_config';
        $datos = array(
            'plantilla_id' => $plantilla_id,
            'campos_mostrar' => json_encode($campos_mostrar),
            'mostrar_mapa' => $mostrar_mapa
        );
    
        $formato = array('%d', '%s', '%d');
    
        $config_existente = $this->db->get_row($this->db->prepare(
            "SELECT id FROM $tabla WHERE plantilla_id = %d",
            $plantilla_id
        ));
    
        if ($config_existente) {
            $resultado = $this->db->update($tabla, $datos, array('plantilla_id' => $plantilla_id), $formato);
        } else {
            $resultado = $this->db->insert($tabla, $datos, $formato);
        }
    
        if ($resultado !== false) {
            $shortcode = '[navi_filtro_sedes plantilla_id="' . $plantilla_id . '"]';
            wp_send_json_success(array(
                'mensaje' => 'Configuración guardada con éxito.',
                'shortcode' => $shortcode
            ));
        } else {
            wp_send_json_error('Error al guardar la configuración.');
        }
    }

    public function ajax_obtener_config() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }
    
        $plantilla_id = intval($_POST['plantilla_id']);
    
        $tabla_config = $this->db->prefix . 'navi_config';
        $tabla_plantillas = $this->db->prefix . 'navi_plantillas';
    
        $config = $this->db->get_row($this->db->prepare(
            "SELECT c.*, p.nivel1, p.nivel2, p.nivel3
             FROM $tabla_config c
             JOIN $tabla_plantillas p ON c.plantilla_id = p.id
             WHERE c.plantilla_id = %d",
            $plantilla_id
        ), ARRAY_A);
    
        if ($config) {
            $config['campos_mostrar'] = json_decode($config['campos_mostrar'], true);
        } else {
            // Si no existe configuración, crear una por defecto
            $plantilla = $this->db->get_row($this->db->prepare(
                "SELECT nivel1, nivel2, nivel3 FROM $tabla_plantillas WHERE id = %d",
                $plantilla_id
            ), ARRAY_A);
    
            $config = array(
                'plantilla_id' => $plantilla_id,
                'campos_mostrar' => array('nombre', 'coordenada', 'correo', 'telefono', 'direccion', 'pagina_web'),
                'mostrar_mapa' => 1,
                'nivel1' => $plantilla['nivel1'],
                'nivel2' => $plantilla['nivel2'],
                'nivel3' => $plantilla['nivel3']
            );
        }
    
        wp_send_json_success($config);
    }

    // Método auxiliar para depuración
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log('Navi Config Error: ' . $message);
        }
    }
}