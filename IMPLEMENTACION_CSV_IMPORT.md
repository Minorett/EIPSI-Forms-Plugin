# Implementación: Importación CSV de Participantes

## Descripción
Se ha implementado la funcionalidad para importar participantes mediante CSV en el Study Dashboard de EIPSI Forms. Esta característica permite a los investigadores importar múltiples participantes de forma eficiente para estudios longitudinales.

## Fecha de Implementación
2025-02-12

## Versión
v1.5.3

## Cambios Realizados

### 1. Archivos Modificados

#### `admin/study-dashboard-modal.php`
- **Agregado botón "Importar CSV"** en las Acciones Rápidas del Study Dashboard
- **Agregado modal de importación CSV** con 4 pasos:
  1. **Paso 1**: Área de arrastrar y soltar para subir archivo CSV
  2. **Paso 2**: Vista previa de datos con validaciones
  3. **Paso 3**: Barra de progreso durante la importación
  4. **Paso 4**: Resultados de la importación

#### `admin/study-dashboard-api.php`
- **Agregados handlers AJAX**:
  - `wp_ajax_eipsi_validate_csv_participants_handler()`: Valida datos CSV
  - `wp_ajax_eipsi_import_csv_participants_handler()`: Importa participantes y envía invitaciones
- **Funciones auxiliares**:
  - `eipsi_parse_csv_data()`: Parsea contenido CSV
  - `eipsi_parse_csv_line()`: Parsea línea individual respetando comillas

#### `admin/js/study-dashboard.js`
- **Agregada funcionalidad completa de importación CSV**:
  - Manejo de arrastrar y soltar archivos
  - Validación de formato y tamaño de archivo
  - Visualización de vista previa con estado de validación
  - Procesamiento por lotes con barra de progreso
  - Generación de plantilla CSV de ejemplo

#### `assets/css/study-dashboard.css`
- **Agregados estilos para el modal de importación CSV**:
  - Área de upload con efectos hover y dragover
  - Estilos para tabla de vista previa
  - Indicadores visuales de validación (válido, inválido, existente)
  - Barra de progreso animada
  - Estilos para resultados de importación

### 2. Funcionalidades Implementadas

#### Validaciones
- ✅ **Emails únicos por estudio**: Verifica que no existan duplicados
- ✅ **Formato de email válido**: Usa función `is_email()` de WordPress
- ✅ **Máximo de 500 participantes**: Límite de seguridad por importación
- ✅ **Tamaño máximo de archivo**: 1MB por archivo CSV
- ✅ **Formato CSV**: Soporte para comillas y campos escapados

#### Flujo de Importación
1. Usuario hace clic en "Importar CSV"
2. Selecciona archivo CSV (o arrastra y suelta)
3. Sistema valida datos y muestra vista previa
4. Usuario confirma importación
5. Sistema importa en lotes de 10 participantes
6. Se envían invitaciones por email automáticamente
7. Se muestran resultados de la importación

#### Formato CSV Soportado
```csv
email,first_name,last_name
juan.perez@email.com,Juan,Pérez
maria.garcia@email.com,María,García
carlos.lopez@email.com,Carlos,López
```

- **Columna 1**: Email (requerido)
- **Columna 2**: Nombre (opcional)
- **Columna 3**: Apellido (opcional)
- Se detecta y omite automáticamente la fila de encabezados

#### Estados de Validación
- **✓ Válido**: Participante listo para importar
- **✗ Inválido**: Email vacío o formato incorrecto
- **⚠ Existente**: Email ya registrado en el estudio (será omitido)

### 3. Manejo de Errores

El sistema implementa manejo robusto de errores:
- Validación de nonce y permisos en todos los endpoints
- Sanitización de datos de entrada
- Detección de participantes duplicados en el CSV
- Logging de errores en la base de datos
- Mensajes descriptivos para el usuario

### 4. Seguridad

- **Nonce verification**: Todos los endpoints AJAX verifican nonce
- **Capability checks**: Solo usuarios con `manage_options` pueden importar
- **Sanitización**: Emails y nombres se sanitizan con `sanitize_email()` y `sanitize_text_field()`
- **Prepared statements**: Consultas a base de datos usan prepared statements
- **Rate limiting implícito**: Procesamiento por lotes previene timeouts

## Criterios de Aceptación Cumplidos

✅ Los participantes pueden importarse mediante CSV desde el Study Dashboard  
✅ Las invitaciones se envían correctamente a todos los participantes importados  
✅ No hay errores en la consola al realizar estas acciones  
✅ Implementación robusta con manejo de errores adecuado  
✅ Documentación de cambios para futuras referencias  

## Notas para Desarrolladores

### Próximas Mejoras Sugeridas
1. Soporte para importación de campos personalizados
2. Historial de importaciones CSV
3. Exportar errores de importación a CSV
4. Soporte para actualización masiva de participantes existentes
5. Validación de dominios de email (lista blanca/negra)

### Testing Manual
1. Crear un estudio longitudinal
2. Ir a Study Dashboard → Importar CSV
3. Probar con archivos de diferentes tamaños
4. Verificar validaciones (emails duplicados, formato inválido)
5. Confirmar que los emails de invitación se envían
6. Verificar que el contador de participantes se actualiza

## Archivos Afectados
- `admin/study-dashboard-modal.php`
- `admin/study-dashboard-api.php`
- `admin/js/study-dashboard.js`
- `assets/css/study-dashboard.css`

## Compatibilidad
- WordPress 5.8+
- PHP 7.4+
- EIPSI Forms v1.5.2+
