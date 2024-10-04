(function ($) {
    $(document).ready(function () {
        // Cargar plantillas y sedes al iniciar la página
        cargarPlantillas();
        cargarSedes();

        // Manejar el formulario de plantillas
        $('#navi-plantilla-form').on('submit', manejarSubmitPlantilla);

        // Descargar plantilla de ejemplo
        $('#descargar-plantilla-ejemplo').on('click', descargarPlantillaEjemplo);

        // Manejar el formulario de sedes
        $('#navi-sede-form').on('submit', manejarSubmitSede);

        // Manejar los selects dependientes
        $('#plantilla_id').on('change', function () {
            var plantilla_id = $(this).val();
            if (plantilla_id) {
                cargarNiveles(plantilla_id);
                cargarConfig(plantilla_id);
                $('#campos-mostrar-container, #mostrar-mapa-container').show();
            } else {
                $('#pais').empty().append('<option value="">Seleccione un país</option>');
                $('#campos-mostrar-container, #mostrar-mapa-container').hide();
                $('#campos-mostrar').empty();
                $('#mostrar_mapa').prop('checked', false);
            }
        });
    
        // Ocultar las opciones de configuración al cargar la página
        $('#campos-mostrar-container, #mostrar-mapa-container').hide();

        // Vista previa del logo
        $('#logo').on('change', mostrarVistaPrevia);

        // Manejar el formulario de configuración
        $('#navi-config-form').on('submit', function (e) {
            e.preventDefault();
            guardarConfig();
        });

        // Manejar la eliminación de plantillas
        $(document).on('click', '.eliminar-plantilla', function () {
            var plantilla_id = $(this).data('id');
            if (confirm('¿Estás seguro de que deseas eliminar esta plantilla? Esta acción eliminará también todas las sedes asociadas.')) {
                eliminarPlantilla(plantilla_id);
            }
        });

        // Manejar la eliminación de sedes
        $(document).on('click', '.eliminar-sede', function () {
            var sede_id = $(this).data('id');
            if (confirm('¿Estás seguro de que deseas eliminar esta sede?')) {
                eliminarSede(sede_id);
            }
        });
    });

    function manejarSubmitPlantilla(e) {
        e.preventDefault();

        var nombre_plantilla = $('input[name="nombre_plantilla"]').val();
        if (!nombre_plantilla) {
            alert('Por favor, ingrese un nombre para la plantilla.');
            return;
        }

        var fileInput = $('input[name="plantilla_excel"]')[0];
        var file = fileInput.files[0];
        var reader = new FileReader();

        reader.onload = function (e) {
            var data = new Uint8Array(e.target.result);
            var workbook = XLSX.read(data, { type: 'array' });
            var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            var jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

            // Procesar y validar los datos
            var processedData = processExcelData(jsonData);

            // Enviar los datos al servidor
            guardarPlantilla(processedData, nombre_plantilla);
        };

        reader.readAsArrayBuffer(file);
    }

    function descargarPlantillaEjemplo() {
        var wb = XLSX.utils.book_new();
        var ws_data = [
            ['Prefijo País', 'Nombre País', 'Nivel 1', 'Nivel 1 Dato', 'Nivel 2', 'Nivel 2 Dato', 'Nivel 3', 'Nivel 3 Dato'],
            ['ES', 'España', 'Comunidad Autónoma', 'Cataluña', 'Provincia', 'Barcelona', 'Municipio', 'Barcelona'],
            ['ES', 'España', 'Comunidad Autónoma', 'Cataluña', 'Provincia', 'Barcelona', 'Municipio', 'Hospitalet de Llobregat'],
            ['ES', 'España', 'Comunidad Autónoma', 'Madrid', 'Provincia', 'Madrid', 'Municipio', 'Madrid'],
            ['MX', 'México', 'Estado', 'Jalisco', 'Municipio', 'Guadalajara', '', ''],
            ['MX', 'México', 'Estado', 'Nuevo León', 'Municipio', 'Monterrey', '', ''],
            ['AR', 'Argentina', 'Provincia', 'Buenos Aires', 'Departamento', 'La Plata', 'Municipio', 'La Plata'],
            ['AR', 'Argentina', 'Provincia', 'Córdoba', 'Departamento', 'Capital', 'Municipio', 'Córdoba']
        ];
        var ws = XLSX.utils.aoa_to_sheet(ws_data);
        XLSX.utils.book_append_sheet(wb, ws, "Plantilla");
        XLSX.writeFile(wb, "plantilla_ejemplo_navi.xlsx");
    }

    function manejarSubmitSede(e) {
        e.preventDefault();

        if (!validarFormularioSede()) {
            return;
        }

        var formData = new FormData(this);
        formData.append('action', 'navi_guardar_sede');
        formData.append('nonce', navi_ajax.nonce);

        guardarSede(formData);
    }

    function mostrarVistaPrevia() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#logo-preview').html('<img src="' + e.target.result + '" alt="Logo preview" style="max-width: 100px; max-height: 100px;">');
            }
            reader.readAsDataURL(file);
        }
    }

    function processExcelData(data) {
        var processedData = [];

        for (var i = 1; i < data.length; i++) {
            var row = data[i];
            if (row.length >= 8) {
                var item = {
                    prefijo_pais: row[0],
                    nombre_pais: row[1],
                    nivel1: row[2],
                    nivel1_dato: row[3],
                    nivel2: row[4] || '',
                    nivel2_dato: row[5] || '',
                    nivel3: row[6] || '',
                    nivel3_dato: row[7] || ''
                };
                processedData.push(item);
            }
        }

        return processedData;
    }

    function validarFormularioSede() {
        var plantilla_id = $('#plantilla_id').val();
        var nombre = $('#nombre').val().trim();
        var coordenada = $('#coordenada').val().trim();
        var pais = $('#pais').val();
        var nivel1 = $('#nivel1_dato').val();
        var correo = $('#correo').val().trim();
        var telefono = $('#telefono').val().trim();
        var direccion = $('#direccion').val().trim();
        var pagina_web = $('#pagina_web').val().trim();

        if (!plantilla_id) {
            alert('Por favor, seleccione una plantilla.');
            return false;
        }

        if (nombre === '') {
            alert('Por favor, ingrese el nombre de la sede.');
            return false;
        }

        if (coordenada === '' || !/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/.test(coordenada)) {
            alert('Por favor, ingrese una coordenada válida (latitud,longitud).');
            return false;
        }

        if (pais === '') {
            alert('Por favor, seleccione un país.');
            return false;
        }

        if (nivel1 === '') {
            alert('Por favor, seleccione el nivel 1.');
            return false;
        }

        if (correo === '' || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
            alert('Por favor, ingrese un correo electrónico válido.');
            return false;
        }

        if (telefono === '' || !/^\+?[0-9]{6,15}$/.test(telefono)) {
            alert('Por favor, ingrese un número de teléfono válido (6-15 dígitos, puede incluir + al inicio).');
            return false;
        }

        if (direccion === '') {
            alert('Por favor, ingrese la dirección de la sede.');
            return false;
        }

        if (pagina_web !== '' && !/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/.test(pagina_web)) {
            alert('Por favor, ingrese una URL válida para la página web.');
            return false;
        }

        return true;
    }

    function cargarPlantillas() {
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'navi_obtener_plantillas',
                nonce: navi_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    var plantillas = response.data;
                    var tabla = $('#navi-plantilla-tabla');
                    tabla.empty();

                    plantillas.forEach(function (plantilla) {
                        var fila = '<tr>' +
                            '<td>' + plantilla.nombre + '</td>' +
                            '<td><button class="button eliminar-plantilla" data-id="' + plantilla.id + '">Eliminar</button></td>' +
                            '</tr>';
                        tabla.append(fila);
                    });

                    actualizarSelectsPlantillas(plantillas);
                } else {
                    console.error('Error al cargar plantillas:', response.data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error AJAX al cargar plantillas:', textStatus, errorThrown);
            }
        });
    }

    function actualizarSelectsPlantillas(plantillas) {
        var selects = $('#plantilla_id, #config-plantilla_id');
        selects.empty().append('<option value="">Seleccione una plantilla</option>');
        plantillas.forEach(function (plantilla) {
            selects.append('<option value="' + plantilla.id + '">' + plantilla.nombre + '</option>');
        });
    }

    function guardarPlantilla(datos, nombre_plantilla) {
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'navi_cargar_plantilla',
                nonce: navi_ajax.nonce,
                datos: JSON.stringify(datos),
                nombre_plantilla: nombre_plantilla
            },
            success: function (response) {
                if (response.success) {
                    $('#navi-plantilla-mensaje').html('<p class="success">' + response.data + '</p>');
                    cargarPlantillas();
                } else {
                    $('#navi-plantilla-mensaje').html('<p class="error">' + response.data + '</p>');
                }
            }
        });
    }

    function cargarSedes(plantilla_id) {
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'navi_obtener_sedes',
                nonce: navi_ajax.nonce,
                plantilla_id: plantilla_id
            },
            success: function (response) {
                if (response.success) {
                    var sedes = response.data;
                    var tabla = $('#navi-sedes-tabla');
                    tabla.empty();

                    sedes.forEach(function (sede) {
                        var fila = '<tr>' +
                            '<td>' + sede.nombre + '</td>' +
                            '<td>' + sede.nombre_plantilla + '</td>' +
                            '<td>' + sede.coordenada + '</td>' +
                            '<td><img src="' + sede.logo + '" alt="Logo" style="max-width: 50px; max-height: 50px;"></td>' +
                            '<td>' + sede.prefijo_pais + '</td>' +
                            '<td>' + sede.nivel1 + ': ' + sede.nivel1_dato + '</td>' +
                            '<td>' + (sede.nivel2 ? sede.nivel2 + ': ' + sede.nivel2_dato : '') + '</td>' +
                            '<td>' + (sede.nivel3 ? sede.nivel3 + ': ' + sede.nivel3_dato : '') + '</td>' +
                            '<td>' + sede.correo + '</td>' +
                            '<td>' + sede.telefono + '</td>' +
                            '<td>' + sede.direccion + '</td>' +
                            '<td>' + (sede.pagina_web || '') + '</td>' +
                            '<td><button class="button eliminar-sede" data-id="' + sede.id + '">Eliminar</button></td>' +
                            '</tr>';
                        tabla.append(fila);
                    });
                } else {
                    console.error('Error al cargar sedes:', response.data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error AJAX al cargar sedes:', textStatus, errorThrown);
            }
        });
    }

    function cargarNiveles(plantilla_id) {
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'navi_obtener_niveles',
                nonce: navi_ajax.nonce,
                plantilla_id: plantilla_id
            },
            success: function (response) {
                if (response.success) {
                    var data = response.data;
                    var paises = data.paises;
                    var niveles = data.niveles;
                    var nivelesContainer = $('#niveles-container');
                    nivelesContainer.empty();

                    // Actualizar el select de país
                    var paisSelect = $('#pais');
                    paisSelect.empty().append('<option value="">Seleccione un país</option>');
                    paises.forEach(function (pais) {
                        paisSelect.append('<option value="' + pais.prefijo + '">' + pais.nombre + '</option>');
                    });

                    // Añadir evento de cambio para el país
                    paisSelect.off('change').on('change', function () {
                        var paisSeleccionado = $(this).val();
                        if (paisSeleccionado) {
                            cargarNivelesPorPais(plantilla_id, paisSeleccionado);
                        } else {
                            nivelesContainer.empty();
                        }
                    });
                }
            }
        });
    }

    function cargarNivelesPorPais(plantilla_id, pais) {
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'navi_obtener_niveles_por_pais',
                nonce: navi_ajax.nonce,
                plantilla_id: plantilla_id,
                pais: pais
            },
            success: function (response) {
                if (response.success) {
                    var niveles = response.data;
                    var nivelesContainer = $('#niveles-container');
                    nivelesContainer.empty();

                    niveles.forEach(function (nivel, index) {
                        nivelesContainer.append(
                            '<tr>' +
                            '<th><label for="nivel' + (index + 1) + '_dato">' + nivel.nombre + '</label></th>' +
                            '<td>' +
                            '<input type="hidden" name="nivel' + (index + 1) + '" value="' + nivel.nombre + '">' +
                            '<select id="nivel' + (index + 1) + '_dato" name="nivel' + (index + 1) + '_dato" ' + (index === 0 ? 'required' : '') + ' ' + (index > 0 ? 'disabled' : '') + '>' +
                            '<option value="">Seleccione una opción</option>' +
                            '</select>' +
                            '</td>' +
                            '</tr>'
                        );
                        cargarOpcionesNivel(plantilla_id, index + 1, pais);
                    });

                    // Añadir eventos de cambio para cada nivel
                    niveles.forEach(function (nivel, index) {
                        $('#nivel' + (index + 1) + '_dato').on('change', function () {
                            var nivelActual = index + 1;
                            var valorSeleccionado = $(this).val();

                            // Limpiar y deshabilitar niveles inferiores
                            for (var i = nivelActual + 1; i <= niveles.length; i++) {
                                $('#nivel' + i + '_dato').empty().append('<option value="">Seleccione una opción</option>').prop('disabled', true);
                            }

                            // Cargar opciones para el siguiente nivel si existe y se ha seleccionado un valor
                            if (nivelActual < niveles.length && valorSeleccionado) {
                                cargarOpcionesNivel(plantilla_id, nivelActual + 1, pais, valorSeleccionado);
                            }
                        });
                    });
                }
            }
        });
    }

    function cargarOpcionesNivel(plantilla_id, nivel, pais, nivelAnterior) {
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'navi_obtener_opciones_nivel',
                nonce: navi_ajax.nonce,
                plantilla_id: plantilla_id,
                nivel: nivel,
                pais: pais,
                nivel_anterior: nivelAnterior
            },
            success: function (response) {
                if (response.success) {
                    var opciones = response.data;
                    var select = $('#nivel' + nivel + '_dato');
                    select.empty().append('<option value="">Seleccione una opción</option>');
                    opciones.forEach(function (opcion) {
                        select.append('<option value="' + opcion + '">' + opcion + '</option>');
                    });
                    select.prop('disabled', false);
                }
            }
        });
    }

    function guardarSede(formData) {
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    $('#navi-sede-mensaje').html('<p class="success">' + response.data + '</p>');
                    $('#navi-sede-form')[0].reset();
                    $('#logo-preview').empty();
                    cargarSedes();
                } else {
                    $('#navi-sede-mensaje').html('<p class="error">' + response.data + '</p>');
                }
            }
        });
    }

    function cargarConfig(plantilla_id) {
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'navi_obtener_config',
                nonce: navi_ajax.nonce,
                plantilla_id: plantilla_id
            },
            dataType: 'json', // Asegúrate de que esto esté presente
            success: function (response) {
                if (response.success) {
                    var config = response.data;
                    var camposMostrar = $('#campos-mostrar');
                    camposMostrar.empty();
    
                    var camposDisponibles = [
                        {nombre: 'nombre', etiqueta: 'Nombre'},
                        {nombre: 'coordenada', etiqueta: 'Coordenada'},
                        {nombre: 'logo', etiqueta: 'Logo'},
                        {nombre: 'nivel1', etiqueta: config.nivel1},
                        {nombre: 'nivel2', etiqueta: config.nivel2},
                        {nombre: 'nivel3', etiqueta: config.nivel3},
                        {nombre: 'correo', etiqueta: 'Correo'},
                        {nombre: 'telefono', etiqueta: 'Teléfono'},
                        {nombre: 'direccion', etiqueta: 'Dirección'},
                        {nombre: 'pagina_web', etiqueta: 'Página web'}
                    ];
    
                    camposDisponibles.forEach(function(campo) {
                        if (campo.etiqueta) {
                            var checked = config.campos_mostrar.includes(campo.nombre) ? 'checked' : '';
                            camposMostrar.append(
                                '<label>' +
                                '<input type="checkbox" name="campos_mostrar[]" value="' + campo.nombre + '" ' + checked + '> ' +
                                campo.etiqueta +
                                '</label><br>'
                            );
                        }
                    });
    
                    $('#mostrar_mapa').prop('checked', config.mostrar_mapa == 1);
                } else {
                    console.error('Error al cargar la configuración:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al cargar la configuración:', status, error);
                console.log('Respuesta del servidor:', xhr.responseText);
            }
        });
    }

    function guardarConfig() {
        var plantilla_id = $('#plantilla_id').val();
        var campos_mostrar = $('input[name="campos_mostrar[]"]:checked').map(function () {
            return this.value;
        }).get();
        var mostrar_mapa = $('#mostrar_mapa').is(':checked') ? 1 : 0;
    
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'navi_guardar_config',
                nonce: navi_ajax.nonce,
                plantilla_id: plantilla_id,
                campos_mostrar: JSON.stringify(campos_mostrar),
                mostrar_mapa: mostrar_mapa
            },
            success: function (response) {
                if (response.success) {
                    $('#navi-config-mensaje').html('<p class="success">' + response.data.mensaje + '</p>');
                    $('#navi-shortcode').html('<p>Shortcode: <code>' + response.data.shortcode + '</code></p>');
                } else {
                    $('#navi-config-mensaje').html('<p class="error">' + response.data + '</p>');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error al guardar la configuración:', error);
                $('#navi-config-mensaje').html('<p class="error">Error al guardar la configuración. Por favor, intenta de nuevo.</p>');
            }
        });
    }

    function eliminarPlantilla(plantilla_id) {
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'navi_eliminar_plantilla',
                nonce: navi_ajax.nonce,
                id: plantilla_id
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data);
                    cargarPlantillas();
                    cargarSedes();
                } else {
                    alert('Error al eliminar la plantilla: ' + response.data);
                }
            }
        });
    }

    function eliminarSede(sede_id) {
        $.ajax({
            url: navi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'navi_eliminar_sede',
                nonce: navi_ajax.nonce,
                id: sede_id
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data);
                    cargarSedes();
                } else {
                    alert('Error al eliminar la sede: ' + response.data);
                }
            }
        });
    }
})(jQuery);


