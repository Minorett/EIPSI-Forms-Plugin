# MANUAL DE TESTING - Task 2.4B

**Version:** 1.4.0  
**Fecha:** 2025-01-29  
**Propósito:** Validar que los assignments se marcan como 'submitted' y se muestran correctamente las próximas tomas

---

## 🧪 PREREQUISITOS

Antes de comenzar, asegúrate de tener:

- [x] WordPress con EIPSI Forms v1.4.0 instalado
- [x] Plugin activado
- [x] Base de datos con tablas:
  - `wp_survey_assignments`
  - `wp_survey_waves`
  - `wp_participants`
- [x] WP_DEBUG activado (opcional, para logs)
- [x] Acceso a phpMyAdmin o similar (para verificar DB)

---

## 📋 TEST 1: Submit con Próxima Toma Pendiente

### Objetivo
Validar que al completar una toma intermedia, se muestra la información de la próxima toma.

### Setup

1. **Crear estudio longitudinal en phpMyAdmin:**

```sql
-- 1. Crear participante de prueba
INSERT INTO wp_participants (email, survey_id, first_name, last_name, password_hash, status)
VALUES ('test@ejemplo.com', 1, 'Juan', 'Pérez', '$2y$10$...', 'active');

-- Anotar el ID generado (ej: participant_id = 100)

-- 2. Crear 3 waves para el estudio
INSERT INTO wp_survey_waves (survey_id, wave_index, name, due_at, created_at)
VALUES 
  (1, 1, 'Toma 1: Baseline', '2025-02-01', NOW()),
  (1, 2, 'Toma 2: Seguimiento 1 mes', '2025-03-01', NOW()),
  (1, 3, 'Toma 3: Seguimiento 3 meses', '2025-05-01', NOW());

-- Anotar los IDs generados (ej: wave_id = 1, 2, 3)

-- 3. Crear assignments para el participante
INSERT INTO wp_survey_assignments (participant_id, survey_id, wave_id, status, created_at)
VALUES 
  (100, 1, 1, 'pending', NOW()),   -- Wave 1: pending (la que vamos a completar)
  (100, 1, 2, 'pending', NOW()),   -- Wave 2: pending (próxima)
  (100, 1, 3, 'pending', NOW());   -- Wave 3: pending (futura)
```

2. **Configurar sesión PHP:**

En el frontend, antes de mostrar el formulario, asegurarse de setear:

```php
<?php
session_start();
$_SESSION['eipsi_wave_id'] = 1;  // Wave que se está completando
$_SESSION['eipsi_survey_id'] = 1; // Estudio
$_SESSION['eipsi_participant_id'] = 100; // Participante
?>
```

### Pasos

1. Abrir formulario en el navegador
2. Completar todos los campos del formulario
3. Click en "Enviar" o "Submit"
4. Esperar 1.5 segundos (loading + delay)

### Resultado Esperado

#### ✅ En la Thank You Page:

```
┌────────────────────────────────────────────┐
│ ✓ ¡GRACIAS!                                │
│   Tu respuesta ha sido guardada            │
│   exitosamente                             │
│                                            │
│ ┌──────────────────────────────────────┐  │
│ │ 📋 Próximas tomas                    │  │
│ │                                      │  │
│ │ Toma 2: Seguimiento 1 mes            │  │
│ │ 📅 Fecha estimada: 1 de marzo 2025  │  │
│ │                                      │  │
│ │ 📧 Recibirás un recordatorio por     │  │
│ │    email 7 días antes de la fecha    │  │
│ └──────────────────────────────────────┘  │
│                                            │
│           [ Comenzar de nuevo ]            │
└────────────────────────────────────────────┘
```

**Validar:**
- ✅ Box azul visible (`background: #f0f9ff`)
- ✅ Texto "Toma 2: Seguimiento 1 mes" correcto
- ✅ Fecha formateada en español: "1 de marzo de 2025"
- ✅ Íconos visibles (📋, 📅, 📧)
- ✅ Mensaje de recordatorio presente

#### ✅ En la base de datos:

```sql
SELECT * FROM wp_survey_assignments 
WHERE participant_id = 100 AND wave_id = 1;
```

**Validar:**
- ✅ `status` = `'submitted'`
- ✅ `updated_at` actualizado a timestamp reciente

#### ✅ En wp_vas_form_results:

```sql
SELECT wave_index, status FROM wp_vas_form_results 
WHERE participant_id = 'PID-...' 
ORDER BY id DESC LIMIT 1;
```

**Validar:**
- ✅ `wave_index` = `1`
- ✅ `status` = `'submitted'`

#### ✅ En console del navegador (F12):

```javascript
// Buscar respuesta AJAX
{
  "success": true,
  "data": {
    "message": "¡GRACIAS! Tu respuesta ha sido guardada exitosamente",
    "has_next": true,
    "next_wave": {
      "wave_index": 2,
      "due_at": "2025-03-01",
      "wave_name": "Toma 2: Seguimiento 1 mes"
    }
  }
}
```

**Validar:**
- ✅ `has_next` = `true`
- ✅ `next_wave` no es `null`
- ✅ Datos correctos de la wave 2

---

## 📋 TEST 2: Submit de Última Toma

### Objetivo
Validar que al completar la última toma, se muestra "Todas las tomas completadas".

### Setup

**Usar mismo setup del Test 1, pero:**

1. Marcar waves 1 y 2 como completadas en DB:

```sql
UPDATE wp_survey_assignments 
SET status = 'submitted', updated_at = NOW()
WHERE participant_id = 100 AND wave_id IN (1, 2);
```

2. Setear sesión para wave 3:

```php
$_SESSION['eipsi_wave_id'] = 3;  // Última wave
```

### Pasos

1. Abrir formulario
2. Completar todos los campos
3. Click en "Enviar"
4. Esperar 1.5 segundos

### Resultado Esperado

#### ✅ En la Thank You Page:

```
┌────────────────────────────────────────────┐
│ ✓ ¡GRACIAS!                                │
│   Tu respuesta ha sido guardada            │
│   exitosamente                             │
│                                            │
│ ┌──────────────────────────────────────┐  │
│ │ ✅ Todas las tomas completadas ✅   │  │
│ └──────────────────────────────────────┘  │
│                                            │
│           [ Volver a inicio ]              │
└────────────────────────────────────────────┘
```

**Validar:**
- ✅ Box verde visible (`background: #f0fdf4`)
- ✅ Texto "Todas las tomas completadas ✅"
- ✅ Botón dice "Volver a inicio" (NO "Comenzar de nuevo")

#### ✅ En la base de datos:

```sql
SELECT status FROM wp_survey_assignments 
WHERE participant_id = 100 AND wave_id = 3;
```

**Validar:**
- ✅ `status` = `'submitted'`

```sql
SELECT COUNT(*) FROM wp_survey_assignments 
WHERE participant_id = 100 AND status = 'submitted';
```

**Validar:**
- ✅ COUNT = 3 (todas las waves completadas)

#### ✅ En console del navegador:

```javascript
{
  "success": true,
  "data": {
    "message": "¡GRACIAS! Tu respuesta ha sido guardada exitosamente",
    "has_next": false,
    "next_wave": null,
    "completion_message": "Todas las tomas completadas ✅"
  }
}
```

**Validar:**
- ✅ `has_next` = `false`
- ✅ `next_wave` = `null`
- ✅ `completion_message` presente

---

## 📋 TEST 3: Submit Sin Contexto Longitudinal (Backward Compatibility)

### Objetivo
Validar que los formularios NO longitudinales siguen funcionando normalmente.

### Setup

1. Usar formulario estándar (sin estudio longitudinal)
2. NO setear `$_SESSION['eipsi_wave_id']`
3. O setear a `null`:

```php
$_SESSION['eipsi_wave_id'] = null;
```

### Pasos

1. Abrir formulario normal
2. Completar campos
3. Enviar

### Resultado Esperado

#### ✅ En la Thank You Page:

```
┌────────────────────────────────────────────┐
│ ✓ ¡Gracias por completar el cuestionario! │
│                                            │
│   Sus respuestas han sido registradas      │
│   correctamente.                           │
│                                            │
│           [ Comenzar de nuevo ]            │
└────────────────────────────────────────────┘
```

**Validar:**
- ✅ NO aparece box de próximas tomas
- ✅ Mensaje estándar sin modificar
- ✅ Botón estándar

#### ✅ En la base de datos:

```sql
-- NO debe haber UPDATE en wp_survey_assignments
-- (porque no hay contexto longitudinal)
```

#### ✅ En console del navegador:

```javascript
{
  "success": true,
  "data": {
    "message": "¡GRACIAS! Tu respuesta ha sido guardada exitosamente",
    "has_next": false,
    "next_wave": null
    // NO incluye "completion_message"
  }
}
```

**Validar:**
- ✅ `has_next` = `false`
- ✅ `next_wave` = `null`
- ✅ NO hay `completion_message`

---

## 📋 TEST 4: Edge Case - Assignment No Existe

### Objetivo
Validar que si el assignment no existe en DB, el submit continúa sin errores.

### Setup

1. **NO crear** assignment en `wp_survey_assignments`
2. Pero sí setear sesión:

```php
$_SESSION['eipsi_wave_id'] = 1;
$_SESSION['eipsi_survey_id'] = 1;
```

### Pasos

1. Completar formulario
2. Enviar

### Resultado Esperado

#### ✅ Comportamiento:

- ✅ Submit NO falla
- ✅ Thank you page se muestra normal
- ✅ NO aparece box de próximas tomas (porque no hay datos)

#### ✅ En WP_DEBUG log:

```
[EIPSI] Warning: No se pudo marcar assignment como submitted (participant_id=100, survey_id=1, wave_id=1)
```

**Validar:**
- ✅ Log presente
- ✅ Submit continúa exitosamente
- ✅ No se bloquea el flujo

---

## 🔍 DEBUGGING

### Si algo falla:

1. **Verificar sesión PHP:**

```php
echo '<pre>';
var_dump($_SESSION);
echo '</pre>';
```

**Debe tener:**
- `eipsi_wave_id`
- `eipsi_survey_id`
- `eipsi_participant_id` (opcional)

2. **Verificar respuesta AJAX en Network tab (F12):**

- Abrir DevTools → Network
- Filtrar por "eipsi_forms_submit_form"
- Ver Response JSON
- Verificar que `has_next` y `next_wave` están presentes

3. **Verificar WP_DEBUG logs:**

```bash
tail -f /path/to/wordpress/wp-content/debug.log
```

**Buscar:**
- `[Wave_Service]` - Logs del servicio
- `[EIPSI]` - Logs generales

4. **Verificar query SQL:**

```sql
-- Ver assignments
SELECT * FROM wp_survey_assignments 
WHERE participant_id = 100;

-- Ver waves
SELECT * FROM wp_survey_waves 
WHERE survey_id = 1 
ORDER BY wave_index;

-- Ver formularios guardados
SELECT wave_index, status, created_at 
FROM wp_vas_form_results 
WHERE participant_id LIKE 'PID-%' 
ORDER BY id DESC 
LIMIT 10;
```

---

## ✅ CHECKLIST FINAL

Después de completar todos los tests:

- [ ] Test 1: Próxima toma - Box azul mostrado ✅
- [ ] Test 1: Assignment marcado como 'submitted' ✅
- [ ] Test 1: Fecha formateada correctamente ✅
- [ ] Test 2: Última toma - Box verde mostrado ✅
- [ ] Test 2: Botón dice "Volver a inicio" ✅
- [ ] Test 2: Todas las waves en 'submitted' ✅
- [ ] Test 3: Formulario normal funciona igual ✅
- [ ] Test 3: NO aparece box de tomas ✅
- [ ] Test 4: Edge case no bloquea submit ✅
- [ ] Test 4: Log de warning presente ✅

---

## 🎉 RESULTADO ESPERADO

Si todos los tests pasan:

> **"Task 2.4B está funcionando perfectamente. Los participantes ahora tienen claridad total sobre su progreso en el estudio longitudinal."**

---

## 📞 SOPORTE

Si encuentras algún problema:

1. Revisar logs en `wp-content/debug.log`
2. Verificar que tablas existen en DB
3. Validar que sesión PHP tiene los datos correctos
4. Revisar CHANGELOG-Task-2.4B.md para detalles técnicos

---

**Fin del Manual de Testing**
