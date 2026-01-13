# 🎲 Sistema de Aleatorización RCT - EIPSI Forms v1.3.1

## 📋 VISIÓN GENERAL

Sistema completo end-to-end para ejecutar **Randomized Controlled Trials (RCTs)** con persistencia de asignaciones basada en fingerprinting robusto del dispositivo/navegador del usuario.

**Característica Principal:** Un usuario que presiona F5 (refrescar) **siempre ve el mismo formulario asignado**, sin importar cuántas veces recargue o cuánto tiempo pase.

---

## 🏗️ ARQUITECTURA

### 1. Fingerprinting del Usuario (Frontend)

**Archivo:** `assets/js/eipsi-fingerprint.js`

**Técnicas Utilizadas:**
- ✅ Canvas fingerprinting (GPU/renderer único)
- ✅ WebGL fingerprinting (tarjeta gráfica)
- ✅ Screen resolution + color depth + pixel ratio
- ✅ Timezone + offset
- ✅ Language + languages array
- ✅ Platform + User Agent
- ✅ Hardware concurrency (CPU cores)
- ✅ Device memory
- ✅ Plugins
- ✅ Hash SHA-256

**Output:** `fp_abc123...` (32 caracteres)

**Persistencia:** El fingerprint se guarda en `sessionStorage` y se regenera automáticamente al cargar la página.

---

### 2. Base de Datos

#### Tabla 1: `wp_eipsi_randomization_configs`

Almacena la configuración de cada estudio RCT.

```sql
CREATE TABLE wp_eipsi_randomization_configs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    randomization_id VARCHAR(255) UNIQUE NOT NULL,
    formularios LONGTEXT NOT NULL,
    probabilidades LONGTEXT,
    method VARCHAR(20) DEFAULT 'seeded',
    manual_assignments LONGTEXT,
    show_instructions TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
);
```

**Ejemplo de Registro:**
```json
{
  "randomization_id": "rand_abc123xyz",
  "formularios": [
    {"postId": 123, "nombre": "Formulario A", "porcentaje": 50},
    {"postId": 456, "nombre": "Formulario B", "porcentaje": 50}
  ],
  "probabilidades": {"123": 50, "456": 50},
  "method": "seeded"
}
```

#### Tabla 2: `wp_eipsi_randomization_assignments`

Almacena la asignación de cada usuario (por fingerprint).

```sql
CREATE TABLE wp_eipsi_randomization_assignments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    randomization_id VARCHAR(255) NOT NULL,
    user_fingerprint VARCHAR(255) NOT NULL,
    assigned_form_id BIGINT NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_access DATETIME ON UPDATE CURRENT_TIMESTAMP,
    access_count INT DEFAULT 1,
    UNIQUE KEY (randomization_id, user_fingerprint)
);
```

**Ejemplo de Registro:**
```json
{
  "randomization_id": "rand_abc123xyz",
  "user_fingerprint": "fp_9a8c7b6d5e4f3g2h1i",
  "assigned_form_id": 123,
  "assigned_at": "2025-01-19 10:30:00",
  "last_access": "2025-01-19 10:35:00",
  "access_count": 5
}
```

---

### 3. Bloque de Aleatorización (Gutenberg)

**Archivo:** `src/blocks/randomization-block/edit.js`

**Características:**
- ✅ Título destacado: **🎲 Configuración** (bold)
- ✅ Dropdown carga formularios desde Form Library automáticamente
- ✅ Cálculo automático de porcentajes (siempre suman 100%)
- ✅ Asignaciones manuales (override ético por email)
- ✅ Método: Seeded (reproducible) vs Pure Random
- ✅ Generación automática de shortcode: `[eipsi_randomization id="..."]`
- ✅ Botón "Copiar Shortcode"
- ✅ Guardado automático en DB (debounced 2s)

**UI:**
```
┌─────────────────────────────────────┐
│ 🎲 Configuración                    │
├─────────────────────────────────────┤
│ [Dropdown: Formularios] [➕ Añadir] │
│                                     │
│ • Formulario A (50%) [×]            │
│ • Formulario B (50%) [×]            │
│ Total: 100% ✓                       │
│                                     │
│ Shortcode:                          │
│ [eipsi_randomization id="rand_..."] │
│ [Copiar Shortcode]                  │
└─────────────────────────────────────┘
```

---

### 4. Shortcode Handler (Backend)

**Archivo:** `admin/randomization-shortcode-handler.php`

**Flujo de Ejecución:**

```
1. Usuario visita página con shortcode [eipsi_randomization id="rand_abc123"]
2. Backend obtiene fingerprint del usuario (JS → POST)
3. Busca en DB: ¿Ya existe asignación para este fingerprint?
   
   SI EXISTE:
   ├─ Devolver formulario asignado (persistencia)
   ├─ Actualizar last_access
   └─ Incrementar access_count
   
   NO EXISTE:
   ├─ Verificar asignaciones manuales (por email)
   ├─ Si no hay manual, calcular asignación aleatoria
   │  ├─ Método seeded: usar fingerprint como seed (reproducible)
   │  └─ Método pure-random: completamente aleatorio
   ├─ Guardar asignación en DB
   └─ Renderizar formulario asignado

4. Renderizar el formulario completo
```

**Funciones Clave:**
- `eipsi_get_user_fingerprint()` - Obtener fingerprint del usuario
- `eipsi_get_existing_assignment()` - Buscar asignación previa
- `eipsi_create_assignment()` - Crear nueva asignación
- `eipsi_update_assignment_access()` - Actualizar contador de accesos
- `eipsi_calculate_random_assignment()` - Calcular asignación aleatoria
- `eipsi_check_manual_assignment()` - Verificar override manual

---

## 🔄 FLUJO USER COMPLETO

### Paso 1: Clínico Crea Template en Form Library

```
1. Form Library → Nueva plantilla
2. Insertar bloque "🎲 Configuración"
3. Activar aleatorización en sidebar
4. Dropdown → Seleccionar "Evaluación de Estrés" (50%)
5. Dropdown → Seleccionar "Evaluación de Burnout" (50%)
6. Total muestra: 100% ✓
7. Se genera automáticamente ID: rand_abc123xyz
8. Shortcode generado: [eipsi_randomization id="rand_abc123xyz"]
9. Click "Copiar Shortcode"
10. Publicar template
```

### Paso 2: Clínico Usa Shortcode en Página Regular

```
1. Crear nueva página (NO Form Library)
2. Pegar shortcode: [eipsi_randomization id="rand_abc123xyz"]
3. Publicar página
4. Compartir link: https://misite.com/encuesta-rct
```

### Paso 3: Usuario 1 Accede

```
1. Abre: https://misite.com/encuesta-rct
2. JS genera fingerprint: fp_xyz123abc
3. Backend busca en DB: ¿Existe (rand_abc123xyz, fp_xyz123abc)?
   NO EXISTE
4. Calcula asignación aleatoria:
   - Método seeded con seed = crc32("fp_xyz123abc" + "rand_abc123xyz")
   - Random: 45 (de 0-100)
   - Formulario asignado: "Evaluación de Estrés" (0-50)
5. Guarda en DB:
   - randomization_id: rand_abc123xyz
   - user_fingerprint: fp_xyz123abc
   - assigned_form_id: 123
   - access_count: 1
6. Renderiza Formulario A
```

### Paso 4: Usuario 1 Presiona F5 (10 veces)

```
F5 #1:
├─ Mismo fingerprint: fp_xyz123abc
├─ Busca en DB: EXISTE
├─ assigned_form_id: 123
├─ access_count: 2
└─ Renderiza Formulario A (MISMO)

F5 #5:
├─ access_count: 6
└─ Formulario A (MISMO)

F5 #10:
├─ access_count: 11
└─ Formulario A (MISMO)

RESULTADO: ✅ Persistencia perfecta
```

### Paso 5: Usuario 1 Vuelve Después de 3 Meses

```
1. Usuario accede con mismo dispositivo/navegador
2. JS genera fingerprint: fp_xyz123abc (MISMO)
3. Busca en DB: EXISTE (asignación de hace 3 meses)
4. assigned_form_id: 123
5. access_count: 12
6. Renderiza Formulario A (MISMO)

RESULTADO: ✅ Persistencia a largo plazo
```

### Paso 6: Usuario 2 Accede (Diferente Dispositivo)

```
1. Abre misma URL
2. JS genera fingerprint: fp_456def789 (DIFERENTE)
3. Busca en DB: NO EXISTE
4. Calcula con seed diferente
5. Random: 75
6. Formulario asignado: "Evaluación de Burnout" (51-100)
7. Guarda asignación
8. Renderiza Formulario B

RESULTADO: ✅ Usuarios diferentes ven formularios diferentes
```

---

## 📊 MÉTODOS DE ALEATORIZACIÓN

### Método 1: Seeded (Reproducible)

**Cómo funciona:**
```php
$seed = crc32( $user_fingerprint . $randomization_id );
mt_srand( $seed );
$random = mt_rand( 0, 100 );
```

**Ventaja:** Mismo usuario siempre obtiene el mismo resultado (incluso si se borra la DB).

**Uso:** Ideal para RCTs con seguimiento longitudinal.

### Método 2: Pure Random

**Cómo funciona:**
```php
$random = random_int( 0, 100 );
```

**Ventaja:** Completamente impredecible.

**Uso:** Ideal para estudios de una sola sesión.

---

## 🔧 ASIGNACIONES MANUALES (Override Ético)

**Caso de Uso:** Asignar manualmente un usuario específico a un formulario.

**Configuración:**
```
1. En bloque de aleatorización
2. Sección "Asignaciones Manuales"
3. Email: paciente@example.com
4. Formulario: Evaluación de Estrés
5. Click "Añadir"
```

**Comportamiento:**
- Si usuario accede con `?email=paciente@example.com`, recibe Formulario A
- Sobrescribe aleatorización
- Se trackea normalmente en DB

---

## 📈 TRACKING Y ANALYTICS

### Datos Almacenados por Usuario

- ✅ `randomization_id` - Qué estudio RCT
- ✅ `user_fingerprint` - Quién es (anonimizado)
- ✅ `assigned_form_id` - Cuál formulario le tocó
- ✅ `assigned_at` - Cuándo se asignó
- ✅ `last_access` - Última vez que accedió
- ✅ `access_count` - Cuántas veces accedió

### Exportar Datos para Análisis

```php
$assignments = eipsi_get_study_assignments( 'rand_abc123xyz' );
$stats = eipsi_get_study_stats( 'rand_abc123xyz' );

// $stats:
// {
//   "total_participants": 150,
//   "distribution": [
//     {"assigned_form_id": 123, "count": 75},
//     {"assigned_form_id": 456, "count": 75}
//   ],
//   "total_accesses": 450
// }
```

---

## ✅ VALIDACIÓN Y TESTING

### Test 1: Persistencia en F5

```
DADO que un usuario ya fue asignado a Formulario A
CUANDO presiona F5 10 veces
ENTONCES siempre ve Formulario A
```

### Test 2: Persistencia a Largo Plazo

```
DADO que un usuario fue asignado hace 3 meses
CUANDO vuelve al sitio con el mismo dispositivo
ENTONCES ve el mismo formulario asignado
```

### Test 3: Usuarios Diferentes

```
DADO que Usuario 1 ve Formulario A
CUANDO Usuario 2 accede desde otro dispositivo
ENTONCES puede ver Formulario B (aleatorio)
```

### Test 4: Distribución Balanceada

```
DADO 100 usuarios nuevos
CUANDO acceden al RCT con 50-50%
ENTONCES ~50 ven Formulario A y ~50 ven Formulario B
(con margen de ±10 por randomness)
```

---

## 🔒 PRIVACIDAD Y GDPR

### ✅ Compatible con GDPR

- ❌ NO se almacenan emails (a menos que el usuario los ingrese)
- ❌ NO se identifican usuarios nominalmente
- ✅ Fingerprint es hash SHA-256 (no reversible)
- ✅ No se usa tracking cross-site
- ✅ Datos almacenados solo para asignación

### Anonimización

```
Fingerprint real:
canvas:data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...||
webgl:ANGLE (Intel, Intel(R) HD Graphics 620 Direct3D11 vs_5_0 ps_5_0)||
screen:1920x1080||...

↓ SHA-256 ↓

Fingerprint almacenado:
fp_9a8c7b6d5e4f3g2h1i234567890abc
```

---

## 🐛 DEBUGGING

### Logs en PHP (WordPress error_log)

```bash
[EIPSI RCT] Usuario existente: fp_abc123 → Formulario: 123
[EIPSI RCT] Nuevo usuario: fp_def456 → Formulario: 456
[EIPSI RCT] Método seeded - seed: 1234567890
[EIPSI RCT] Random generado: 45 de 100
[EIPSI RCT] Formulario asignado: 123
```

### Logs en JS (Console)

```javascript
[EIPSI Fingerprint] Generated: fp_abc123...
[EIPSI RCT] Configuración guardada en DB: rand_abc123xyz
```

### Verificar Asignaciones en DB

```sql
SELECT * FROM wp_eipsi_randomization_assignments 
WHERE randomization_id = 'rand_abc123xyz'
ORDER BY assigned_at DESC;
```

---

## 🎯 CRITERIOS DE ACEPTACIÓN - COMPLETADOS

### ✅ Bloque de Aleatorización

- [x] Título: "🎲 Configuración" en bold, bien visible
- [x] Dropdown carga formularios desde Form Library automáticamente
- [x] Muestra todos los formularios disponibles (post type eipsi_form_template)
- [x] Botón "Agregar Formulario" funciona
- [x] Porcentajes se calculan automáticamente (siempre suman 100)
- [x] Shortcode se genera automáticamente
- [x] Botón "Copiar Shortcode" funciona
- [x] Método selectable: "Seeded" o "Pure Random"
- [x] Configuración se guarda en DB automáticamente

### ✅ Shortcode Funcional

- [x] `[eipsi_randomization id="rand_abc123xyz"]` se ejecuta sin errores
- [x] En primera visita, asigna un formulario aleatoriamente
- [x] El formulario asignado se renderiza correctamente
- [x] Al refrescar (F5), muestra el MISMO formulario (persistencia)
- [x] Al cerrar navegador y volver, MISMO formulario
- [x] Diferentes usuarios ven diferentes formularios

### ✅ Base de Datos

- [x] Tabla `wp_eipsi_randomization_configs` creada
- [x] Tabla `wp_eipsi_randomization_assignments` creada
- [x] Registro en configs cuando se genera configuración
- [x] Registro en assignments cuando usuario se asigna
- [x] `access_count` se incrementa con cada acceso
- [x] `last_access` se actualiza con cada visita

### ✅ Fingerprinting

- [x] Fingerprint generado correctamente en cliente (JS)
- [x] Mismo usuario siempre tiene el mismo fingerprint
- [x] Diferentes navegadores = diferentes fingerprints
- [x] Diferentes dispositivos = diferentes fingerprints
- [x] Fingerprint es único y no identificable personalmente

### ✅ Frontend

- [x] Página con shortcode carga sin errores
- [x] Formulario asignado se renderiza completamente
- [x] Estilos del formulario se aplican correctamente
- [x] Submit funciona y guarda respuestas
- [x] F5 no cambia de formulario
- [x] Historial del navegador no afecta asignación

### ✅ Build y Lint

- [x] `npm run build` sin errores (5266 ms)
- [x] `npm run lint:js` OK (0 errores)
- [x] No hay console.errors
- [x] Tablas se crean automáticamente en activación

---

## 🚀 PRÓXIMOS PASOS (Futuro)

1. **Panel de Analytics en Admin** - Ver distribución de asignaciones en tiempo real
2. **Export CSV de Asignaciones** - Para análisis estadístico externo
3. **Balancing Adaptativo** - Ajustar probabilidades en tiempo real para equilibrar grupos
4. **Soporte para Stratified Randomization** - Aleatorización estratificada por variables
5. **Multi-arm Trials** - Soporte para >2 formularios (3, 4, 5 brazos)

---

## 📞 SOPORTE

Para cualquier duda sobre el sistema RCT, contactar:

- **Desarrollador:** Mathias N. Rojas de la Fuente
- **Instagram:** [@enmediodel.contexto](https://www.instagram.com/enmediodel.contexto/)
- **Sitio:** https://enmediodelcontexto.com.ar

---

**EIPSI Forms v1.3.1** - Sistema RCT Completo ✓
