<?php
class Navi_Sedes {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function render_pagina() {
        ?>
        <div class="wrap">
            <h1>Gestionar Sedes</h1>
            <form id="navi-sede-form" enctype="multipart/form-data">
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
                    <tr>
                        <th><label for="nombre">Nombre de la Sede</label></th>
                        <td><input type="text" id="nombre" name="nombre" required></td>
                    </tr>
                    <tr>
                        <th><label for="coordenada">Coordenada</label></th>
                        <td><input type="text" id="coordenada" name="coordenada" required
                                   pattern="^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$"
                                   title="Formato: latitud,longitud (ej: 41.40338, 2.17403)"></td>
                    </tr>
                    <tr>
                        <th><label for="logo">Logo</label></th>
                        <td>
                            <input type="file" id="logo" name="logo" accept="image/*">
                            <div id="logo-preview"></div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pais">País</label></th>
                        <td>
                            <select id="pais" name="pais" required>
                                <option value="">Seleccione un país</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="niveles-container">
                        <!-- Los niveles se cargarán dinámicamente aquí -->
                    </tr>
                    <tr>
                        <th><label for="correo">Correo de contacto</label></th>
                        <td><input type="email" id="correo" name="correo" required></td>
                    </tr>
                    <tr>
                        <th><label for="telefono">Número de teléfono</label></th>
                        <td><input type="tel" id="telefono" name="telefono" required pattern="^\+?[0-9]{6,15}$"
                                   title="Número de teléfono (6-15 dígitos, puede incluir + al inicio)"></td>
                    </tr>
                    <tr>
                        <th><label for="direccion">Dirección</label></th>
                        <td><textarea id="direccion" name="direccion" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="pagina_web">Página web</label></th>
                        <td><input type="url" id="pagina_web" name="pagina_web"></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Guardar Sede">
                </p>
            </form>
            <div id="navi-sede-mensaje"></div>
            <h2>Sedes Registradas</h2>
            <select id="filtro-plantilla">
                <option value="">Todas las plantillas</option>
                <?php
                $plantillas = $this->obtener_plantillas();
                foreach ($plantillas as $plantilla) {
                    echo "<option value='{$plantilla['id']}'>{$plantilla['nombre']}</option>";
                }
                ?>
            </select>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Plantilla</th>
                        <th>Coordenada</th>
                        <th>Logo</th>
                        <th>País</th>
                        <th>Nivel 1</th>
                        <th>Nivel 2</th>
                        <th>Nivel 3</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Página web</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="navi-sedes-tabla">
                    <!-- Los datos se cargarán aquí dinámicamente -->
                </tbody>
            </table>
        </div>
        <?php
    }

    private function obtener_plantillas() {
        $tabla = $this->db->prefix . 'navi_plantillas';
        return $this->db->get_results("SELECT DISTINCT id, nombre FROM $tabla", ARRAY_A);
    }

    public function ajax_editar_sede() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }
    
        $sede_id = intval($_POST['sede_id']);
        $plantilla_id = intval($_POST['plantilla_id']);
        $nombre = sanitize_text_field($_POST['nombre']);
        $coordenada = sanitize_text_field($_POST['coordenada']);
        $pais = sanitize_text_field($_POST['pais']);
        $nivel1 = sanitize_text_field($_POST['nivel1']);
        $nivel1_dato = sanitize_text_field($_POST['nivel1_dato']);
        $nivel2 = isset($_POST['nivel2']) ? sanitize_text_field($_POST['nivel2']) : '';
        $nivel2_dato = isset($_POST['nivel2_dato']) ? sanitize_text_field($_POST['nivel2_dato']) : '';
        $nivel3 = isset($_POST['nivel3']) ? sanitize_text_field($_POST['nivel3']) : '';
        $nivel3_dato = isset($_POST['nivel3_dato']) ? sanitize_text_field($_POST['nivel3_dato']) : '';
        $correo = sanitize_email($_POST['correo']);
        $telefono = sanitize_text_field($_POST['telefono']);
        $direccion = sanitize_textarea_field($_POST['direccion']);
        $pagina_web = esc_url_raw($_POST['pagina_web']);
    
        // Validaciones
        if (!preg_match('/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/', $coordenada)) {
            wp_send_json_error('La coordenada no tiene un formato válido.');
        }
    
        if (!is_email($correo)) {
            wp_send_json_error('El correo electrónico no es válido.');
        }
    
        if (!preg_match('/^\+?[0-9]{6,15}$/', $telefono)) {
            wp_send_json_error('El número de teléfono no es válido.');
        }
    
        $datos_actualizar = array(
            'plantilla_id' => $plantilla_id,
            'nombre' => $nombre,
            'coordenada' => $coordenada,
            'prefijo_pais' => $pais,
            'nivel1' => $nivel1,
            'nivel1_dato' => $nivel1_dato,
            'nivel2' => $nivel2,
            'nivel2_dato' => $nivel2_dato,
            'nivel3' => $nivel3,
            'nivel3_dato' => $nivel3_dato,
            'correo' => $correo,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'pagina_web' => $pagina_web
        );
    
        // Manejar la actualización del logo si se proporciona uno nuevo
        if (isset($_FILES['logo']) && !empty($_FILES['logo']['name'])) {
            $upload = wp_handle_upload($_FILES['logo'], array('test_form' => false));
            if (isset($upload['url'])) {
                $datos_actualizar['logo'] = $upload['url'];
            }
        }
    
        $tabla = $this->db->prefix . 'navi_sedes';
        $resultado = $this->db->update(
            $tabla,
            $datos_actualizar,
            array('id' => $sede_id),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
    
        if ($resultado !== false) {
            wp_send_json_success('Sede actualizada con éxito.');
        } else {
            wp_send_json_error('Error al actualizar la sede.');
        }
    }

    public function ajax_guardar_sede() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }

        $plantilla_id = intval($_POST['plantilla_id']);
        $nombre = sanitize_text_field($_POST['nombre']);
        $coordenada = sanitize_text_field($_POST['coordenada']);
        $pais = sanitize_text_field($_POST['pais']);
        $nivel1 = sanitize_text_field($_POST['nivel1']);
        $nivel1_dato = sanitize_text_field($_POST['nivel1_dato']);
        $nivel2 = isset($_POST['nivel2']) ? sanitize_text_field($_POST['nivel2']) : '';
        $nivel2_dato = isset($_POST['nivel2_dato']) ? sanitize_text_field($_POST['nivel2_dato']) : '';
        $nivel3 = isset($_POST['nivel3']) ? sanitize_text_field($_POST['nivel3']) : '';
        $nivel3_dato = isset($_POST['nivel3_dato']) ? sanitize_text_field($_POST['nivel3_dato']) : '';
        $correo = sanitize_email($_POST['correo']);
        $telefono = sanitize_text_field($_POST['telefono']);
        $direccion = sanitize_textarea_field($_POST['direccion']);
        $pagina_web = esc_url_raw($_POST['pagina_web']);

        // Validaciones
        if (!preg_match('/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/', $coordenada)) {
            wp_send_json_error('La coordenada no tiene un formato válido.');
        }

        if (!is_email($correo)) {
            wp_send_json_error('El correo electrónico no es válido.');
        }

        if (!preg_match('/^\+?[0-9]{6,15}$/', $telefono)) {
            wp_send_json_error('El número de teléfono no es válido.');
        }

        // Manejar la subida del logo
        $logo_url = '';
        if (isset($_FILES['logo'])) {
            $upload = wp_handle_upload($_FILES['logo'], array('test_form' => false));
            if (isset($upload['url'])) {
                $logo_url = $upload['url'];
            }
        }

        $tabla = $this->db->prefix . 'navi_sedes';
        $resultado = $this->db->insert(
            $tabla,
            array(
                'plantilla_id' => $plantilla_id,
                'nombre' => $nombre,
                'coordenada' => $coordenada,
                'logo' => $logo_url,
                'prefijo_pais' => $pais,
                'nivel1' => $nivel1,
                'nivel1_dato' => $nivel1_dato,
                'nivel2' => $nivel2,
                'nivel2_dato' => $nivel2_dato,
                'nivel3' => $nivel3,
                'nivel3_dato' => $nivel3_dato,
                'correo' => $correo,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'pagina_web' => $pagina_web
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($resultado) {
            wp_send_json_success('Sede guardada con éxito.');
        } else {
            wp_send_json_error('Error al guardar la sede.');
        }
    }

    public function ajax_obtener_sedes() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');

        // Removida la verificación de permisos para permitir acceso a usuarios no autenticados
        // if (!current_user_can('manage_options')) {
        //     wp_send_json_error('No tienes permisos para realizar esta acción.');
        // }

        $plantilla_id = isset($_POST['plantilla_id']) ? intval($_POST['plantilla_id']) : 0;

        $tabla_sedes = $this->db->prefix . 'navi_sedes';
        $tabla_plantillas = $this->db->prefix . 'navi_plantillas';
        
        $query = "SELECT s.*, p.nombre as nombre_plantilla 
                  FROM $tabla_sedes s
                  JOIN $tabla_plantillas p ON s.plantilla_id = p.id";
        
        if ($plantilla_id > 0) {
            $query .= " WHERE s.plantilla_id = %d";
            $sedes = $this->db->get_results($this->db->prepare($query, $plantilla_id), ARRAY_A);
        } else {
            $sedes = $this->db->get_results($query, ARRAY_A);
        }

        wp_send_json_success($sedes);
    }

    public function ajax_obtener_niveles() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');
    
        // Removida la verificación de permisos para permitir acceso a usuarios no autenticados
        // if (!current_user_can('manage_options')) {
        //     wp_send_json_error('No tienes permisos para realizar esta acción.');
        // }
    
        $plantilla_id = intval($_POST['plantilla_id']);
    
        $tabla = $this->db->prefix . 'navi_plantillas';
        $plantilla = $this->db->get_row($this->db->prepare(
            "SELECT datos FROM $tabla WHERE id = %d LIMIT 1",
            $plantilla_id
        ), ARRAY_A);
    
        if (!$plantilla) {
            wp_send_json_error('Plantilla no encontrada.');
        }
    
        $datos_plantilla = json_decode($plantilla['datos'], true);
        
        if (empty($datos_plantilla)) {
            wp_send_json_error('Los datos de la plantilla no son válidos.');
        }
    
        $paises = array();
        $niveles = array();
    
        foreach ($datos_plantilla as $fila) {
            if (!in_array($fila['prefijo_pais'], array_column($paises, 'prefijo'))) {
                $paises[] = array(
                    'prefijo' => $fila['prefijo_pais'],
                    'nombre' => $fila['nombre_pais']
                );
            }
    
            for ($i = 1; $i <= 3; $i++) {
                $nivel_key = "nivel{$i}";
                if (!empty($fila[$nivel_key]) && !in_array($fila[$nivel_key], array_column($niveles, 'nombre'))) {
                    $niveles[] = array('nivel' => $i, 'nombre' => $fila[$nivel_key]);
                }
            }
        }
    
        $respuesta = array(
            'paises' => $paises,
            'niveles' => $niveles
        );
    
        wp_send_json_success($respuesta);
    }
    
    public function ajax_obtener_niveles_por_pais() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');

        // Removida la verificación de permisos para permitir acceso a usuarios no autenticados
        // if (!current_user_can('manage_options')) {
        //     wp_send_json_error('No tienes permisos para realizar esta acción.');
        // }

        $plantilla_id = intval($_POST['plantilla_id']);
        $pais = sanitize_text_field($_POST['pais']);

        $tabla = $this->db->prefix . 'navi_plantillas';
        $plantilla = $this->db->get_row($this->db->prepare(
            "SELECT datos FROM $tabla WHERE id = %d LIMIT 1",
            $plantilla_id
        ), ARRAY_A);

        if (!$plantilla) {
            wp_send_json_error('Plantilla no encontrada.');
        }

        $datos_plantilla = json_decode($plantilla['datos'], true);
        
        if (empty($datos_plantilla)) {
            wp_send_json_error('Los datos de la plantilla no son válidos.');
        }

        $niveles = array();

        foreach ($datos_plantilla as $fila) {
            if ($fila['prefijo_pais'] === $pais) {
                for ($i = 1; $i <= 3; $i++) {
                    $nivel_key = "nivel{$i}";
                    if (!empty($fila[$nivel_key])) {
                        $niveles[] = array('nombre' => $fila[$nivel_key]);
                    } else {
                        break;
                    }
                }
                break;
            }
        }

        wp_send_json_success($niveles);
    }

    public function ajax_obtener_opciones_nivel() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');
    
        // Removida la verificación de permisos para permitir acceso a usuarios no autenticados
        // if (!current_user_can('manage_options')) {
        //     wp_send_json_error('No tienes permisos para realizar esta acción.');
        // }
    
        $plantilla_id = intval($_POST['plantilla_id']);
        $nivel = intval($_POST['nivel']);
        $pais = sanitize_text_field($_POST['pais']);
        $nivel_anterior_dato = isset($_POST['nivel_anterior']) ? sanitize_text_field($_POST['nivel_anterior']) : '';
    
        $tabla = $this->db->prefix . 'navi_plantillas';
        $plantilla = $this->db->get_row($this->db->prepare(
            "SELECT datos FROM $tabla WHERE id = %d LIMIT 1",
            $plantilla_id
        ), ARRAY_A);
    
        if (!$plantilla) {
            wp_send_json_error('Plantilla no encontrada.');
        }
    
        $datos_plantilla = json_decode($plantilla['datos'], true);
        
        if (empty($datos_plantilla)) {
            wp_send_json_error('Los datos de la plantilla no son válidos.');
        }
    
        $opciones = array();
    
        foreach ($datos_plantilla as $fila) {
            if ($fila['prefijo_pais'] === $pais) {
                $nivel_key = "nivel{$nivel}";
                $nivel_dato_key = "nivel{$nivel}_dato";
    
                if ($nivel === 1 || ($nivel > 1 && $fila["nivel" . ($nivel - 1) . "_dato"] === $nivel_anterior_dato)) {
                    if (!empty($fila[$nivel_dato_key]) && !in_array($fila[$nivel_dato_key], $opciones)) {
                        $opciones[] = $fila[$nivel_dato_key];
                    }
                }
            }
        }
    
        wp_send_json_success($opciones);
    }

    public function ajax_filtrar_sedes() {
        // Verificar el nonce, pero permitir que los usuarios no autenticados accedan
        if (!check_ajax_referer('navi_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Permiso denegado. Nonce no válido.'));
            return;
        }
    
        $filtros = $_POST['filtros'];
        $plantilla_id = intval($filtros['plantilla_id']);
        $tabla = $this->db->prefix . 'navi_sedes';
    
        $query = "SELECT * FROM $tabla WHERE plantilla_id = %d AND prefijo_pais = %s";
        $params = array($plantilla_id, $filtros['pais']);
    
        $niveles = array('nivel1_dato', 'nivel2_dato', 'nivel3_dato');
        foreach ($niveles as $nivel) {
            if (!empty($filtros[$nivel])) {
                $query .= " AND $nivel = %s";
                $params[] = $filtros[$nivel];
            } else {
                break;
            }
        }
    
        $sedes = $this->db->get_results($this->db->prepare($query, $params), ARRAY_A);
    
        // Obtener la configuración de la plantilla
        $config = $this->obtener_config_plantilla($plantilla_id);
        $campos_mostrar = json_decode($config['campos_mostrar'], true);
    
        // Filtrar los campos de las sedes según la configuración
        $sedes_filtradas = array();
        foreach ($sedes as $sede) {
            $sede_filtrada = array();
            foreach ($campos_mostrar as $campo) {
                if (isset($sede[$campo])) {
                    $sede_filtrada[$campo] = $sede[$campo];
                }
            }
            $sedes_filtradas[] = $sede_filtrada;
        }
    
        wp_send_json_success(array(
            'sedes' => $sedes_filtradas,
            'campos_mostrar' => $campos_mostrar,
            'mostrar_mapa' => $config['mostrar_mapa']
        ));
    }
    

    public function shortcode_filtro_sedes($atts) {
        $atts = shortcode_atts(array(
            'plantilla_id' => '',
        ), $atts, 'navi_filtro_sedes');

        if (empty($atts['plantilla_id'])) {
            return 'Error: Plantilla no especificada';
        }

        $plantilla_id = intval($atts['plantilla_id']);
        $config = $this->obtener_config_plantilla($plantilla_id);

        if (!$config) {
            return 'Error: Configuración no encontrada para la plantilla especificada';
        }

        wp_enqueue_script('navi-frontend', NAVI_PLUGIN_URL . 'assets/js/navi-frontend.js', array('jquery'), '1.0', true);

        wp_localize_script('navi-frontend', 'navi_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('navi_ajax_nonce'),
            'mostrar_mapa' => $config['mostrar_mapa']
        ));

        ob_start();
        ?>
        <div class="navi-filtro-sedes" data-plantilla-id="<?php echo $plantilla_id; ?>">
            <div class="navi-filtros">
                <select id="navi-filtro-pais">
                    <option value="">Selecciona un país</option>
                </select>
                <div id="navi-filtro-niveles"></div>
            </div>
            <div id="navi-resultados-sedes"></div>
            <?php if ($config['mostrar_mapa']) : ?>
            <div id="navi-mapa" style="height: 400px;"></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function obtener_config_plantilla($plantilla_id) {
        $tabla = $this->db->prefix . 'navi_config';
        $config = $this->db->get_row($this->db->prepare(
            "SELECT * FROM $tabla WHERE plantilla_id = %d",
            $plantilla_id
        ), ARRAY_A);
    
        if (!$config) {
            // Configuración por defecto si no existe
            $config = array(
                'campos_mostrar' => json_encode(array('nombre', 'direccion', 'telefono', 'correo')),
                'mostrar_mapa' => 1
            );
        }
    
        return $config;
    }

    public function ajax_eliminar_sede() {
        check_ajax_referer('navi_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }

        $sede_id = intval($_POST['id']);
        $tabla = $this->db->prefix . 'navi_sedes';

        $resultado = $this->db->delete($tabla, array('id' => $sede_id), array('%d'));

        if ($resultado) {
            wp_send_json_success('Sede eliminada con éxito.');
        } else {
            wp_send_json_error('No se pudo eliminar la sede.');
        }
    }
}