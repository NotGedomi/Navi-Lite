<?php
class Navi_Database {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function crear_tablas() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql_plantillas = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}navi_plantillas (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            datos longtext NOT NULL,
            nivel1 varchar(100) NOT NULL,
            nivel2 varchar(100),
            nivel3 varchar(100),
            PRIMARY KEY (id)
        ) $charset_collate;"; 

        $sql_sedes = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}navi_sedes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            plantilla_id mediumint(9) NOT NULL,
            nombre varchar(100) NOT NULL,
            coordenada varchar(50) NOT NULL,
            logo varchar(255),
            prefijo_pais varchar(2) NOT NULL,
            nivel1 varchar(100) NOT NULL,
            nivel1_dato varchar(100) NOT NULL,
            nivel2 varchar(100),
            nivel2_dato varchar(100),
            nivel3 varchar(100),
            nivel3_dato varchar(100),
            correo varchar(100) NOT NULL,
            telefono varchar(20) NOT NULL,
            direccion text NOT NULL,
            pagina_web varchar(255),
            PRIMARY KEY (id),
            FOREIGN KEY (plantilla_id) REFERENCES {$this->wpdb->prefix}navi_plantillas(id) ON DELETE CASCADE
        ) $charset_collate;";

        $sql_config = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}navi_config (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            plantilla_id mediumint(9) NOT NULL,
            campos_mostrar text NOT NULL,
            mostrar_mapa tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            FOREIGN KEY (plantilla_id) REFERENCES {$this->wpdb->prefix}navi_plantillas(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_plantillas);
        dbDelta($sql_sedes);
        dbDelta($sql_config);

        $this->actualizar_plantillas_existentes();

        error_log('Tablas de Navi creadas o actualizadas');
    }

    private function actualizar_plantillas_existentes() {
        $tabla = $this->wpdb->prefix . 'navi_plantillas';
        $plantillas = $this->wpdb->get_results("SELECT id, datos FROM $tabla", ARRAY_A);

        foreach ($plantillas as $plantilla) {
            $datos = json_decode($plantilla['datos'], true);
            $nivel1 = $datos[0]['nivel1'] ?? '';
            $nivel2 = $datos[0]['nivel2'] ?? '';
            $nivel3 = $datos[0]['nivel3'] ?? '';

            $this->wpdb->update(
                $tabla,
                array('nivel1' => $nivel1, 'nivel2' => $nivel2, 'nivel3' => $nivel3),
                array('id' => $plantilla['id']),
                array('%s', '%s', '%s'),
                array('%d')
            );
        }
    }

    public function eliminar_tablas() {
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->wpdb->prefix}navi_sedes");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->wpdb->prefix}navi_config");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->wpdb->prefix}navi_plantillas");
        error_log('Tablas de Navi eliminadas');
    }

    public function limpiar_tablas() {
        $this->wpdb->query("TRUNCATE TABLE {$this->wpdb->prefix}navi_sedes");
        $this->wpdb->query("TRUNCATE TABLE {$this->wpdb->prefix}navi_config");
        $this->wpdb->query("TRUNCATE TABLE {$this->wpdb->prefix}navi_plantillas");
        error_log('Tablas de Navi limpiadas');
    }

    public function tabla_existe($nombre_tabla) {
        $tabla = $this->wpdb->prefix . $nombre_tabla;
        return $this->wpdb->get_var("SHOW TABLES LIKE '$tabla'") === $tabla;
    }
}