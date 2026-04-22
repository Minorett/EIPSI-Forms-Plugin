# Roadmap Consentimiento Informado v2.5 - EIPSI Forms

**Fecha:** Abril 2026  
**Estado:** Listo para implementación  
**Versión:** 2.5 (actualizado post-feedback tutor)

---

## PRINCIPIO CLAVE

El bloque de consentimiento es opcional. El investigador decide si usarlo. El sistema provee herramientas pero no fuerza, no recomienda y no valida ética. El investigador asume toda responsabilidad sobre diseño experimental.

---

## CAMBIO CENTRAL

**Antes:** Checkbox ambiguo donde el usuario tilda para seguir  
**Ahora:** Dos botones explícitos
- **Botón izquierdo:** No deseo participar (outline rojo)
- **Botón derecho:** Acepto participar (verde sólido)

El usuario decide explícitamente, no omite.

---

## DOS TIPOS DE RECHAZO

### Tipo A - Consentimiento rechazado (T1)

| Aspecto | Descripción |
|---------|-------------|
| **Momento** | Primera toma (T1), cuando existe el bloque de consentimiento |
| **Efecto** | Bloqueo TOTAL del estudio. Nunca hubo participación. |
| **Dato guardado** | `consent_declined` en tabla `wp_survey_participants` |
| **Mensaje al usuario** | "Rechazaste el consentimiento. No podés participar en este estudio." |
| **Icono** | Cruz roja |

---

### Tipo B - Abandono voluntario del estudio (T2+, desde Dashboard)

**Solo aplica a estudios longitudinales** (no a formularios standalone).

| Aspecto | Descripción |
|---------|-------------|
| **Momento** | Cualquier momento después de T1, iniciado DESDE EL DASHBOARD del participante |
| **Iniciado por** | El participante voluntariamente (no el investigador) |
| **Efecto** | Bloqueo de waves FUTURAS. Los datos de TODAS las waves ANTERIORES se mantienen. |
| **Dato guardado** | `study_withdrawal` en tabla `wp_survey_participants` + `withdrawal_wave_id` |
| **Mensaje al usuario** | "Abandonaste el estudio. Tus datos previos se preservan." |
| **Icono** | Mano saludando |

**Diferencia clave:** El usuario ya había participado en waves anteriores. Su consentimiento original sigue válido para los datos ya recolectados.

**¿Por qué no en standalone?** Un formulario individual no tiene "waves" ni participación previa. Si el usuario no quiere participar, simplemente cierra el formulario. El abandono solo tiene sentido cuando hay un historial de participación que preservar y futuras waves que bloquear.

---

## DOS OPCIONES DE ABANDONO (Tipo B)

### Opción B1 - Abandono estándar (default)
**"Ya no quiero participar"**
- Conserva todos los datos recolectados para la investigación
- Bloquea participación en futuras waves
- Flujo de confirmación: 2 pasos

### Opción B2 - Eliminación de datos (extremo)
**"No deseo que usen mis datos"**
- Elimina TODOS los datos del participante del estudio
- Requiere confirmación con fricción máxima
- Flujo de confirmación: 4-5 pasos con verificación de texto

---

## FLUJO ABANDONO DESDE DASHBOARD

### Paso 1: Icono de configuración
- Dashboard del participante muestra icono "⚙️" en header (sólo icono, sin label)
- Tooltip al hover: "Configuración"

### Paso 2: Menú desplegable
```
┌─────────────────────────────┐
│  Configuración              │
├─────────────────────────────┤
│   No quiero participar    │
└─────────────────────────────┘
```

### Flujo único con gate de checkbox

1. Click en "Abandonar estudio..."
2. **Modal 1:** "¿Estás seguro?"
   - Texto: "Vas a finalizar tu participación en este estudio. No podrás participar en futuras tomas."
   - Botones: "Cancelar" / "Continuar"

3. **Modal 2:** "Consentimiento de retención de datos"
   - Texto: "Todos los datos que ya proporcionaste son valiosos para la investigación."
   - **Checkbox principal:** "Entiendo que mis datos se conservan para la investigación" *(unchecked por defecto)*
   - Botón primario: "Confirmar abandono" *(siempre habilitado)*

4. **Si checkbox checked → Camino B1 (estándar):**
   - Estado cambia a `study_withdrawal`
   - Redirección a pantalla de confirmación B1

5. **Si checkbox unchecked → Camino B2 (eliminación con confirmación extra):**
   - **Modal 3:** "¿Estás seguro?"
     - Texto: "Vas a eliminar permanentemente todos tus datos de este estudio. Esta acción no se puede deshacer."
     - Botones: "Cancelar" / "Sí, eliminar mis datos"
   - **Modal 4:** "Verificación final"
     - Texto: "Para confirmar la eliminación, escribí exactamente:"
     - Texto de referencia: `ELIMINAR MIS DATOS DE "[Nombre del Estudio]"`
     - Input con placeholder mostrando el hint exacto
     - Botón "Eliminar permanentemente" *(deshabilitado hasta coincidencia exacta, case-sensitive)*
   - Estado cambia a `study_withdrawal` + `data_deleted = 1`
   - Redirección a pantalla de confirmación B2

---

## DONDE SE GUARDA

### Formulario individual (standalone)
| Aspecto | Descripción |
|---------|-------------|
| **Tabla** | `wp_survey_assignments` |
| **Campos nuevos** | `consent_decision`, `consent_decided_at`, `consent_ip_address`, `consent_user_agent`, `consent_context` |
| **Alcance** | Solo ese formulario específico |
| **Retry** | El usuario puede volver a intentar con un nuevo assignment |

### Estudio longitudinal
| Aspecto | Descripción |
|---------|-------------|
| **Tabla** | `wp_survey_participants` |
| **Campos nuevos** | `consent_decision` (valores: `consent_declined`, `study_withdrawal`, `data_deleted`), `consent_decided_at`, `consent_ip_address`, `consent_user_agent`, `consent_context`, `consent_blocked_survey_id`, `withdrawal_wave_id`, `data_deleted` (boolean, solo para B2) |
| **Alcance** | Todo el estudio |

---

## VALIDACIÓN DE ACCESO

El sistema detecta automáticamente el contexto verificando `wave_id` y `survey_id`.

### Si es longitudinal:
1. Verifica tabla `wp_survey_participants`
2. Si encuentra `consent_declined` para ese `survey_id` → bloquea acceso
3. Si encuentra `study_withdrawal` → verifica `withdrawal_wave_id` para saber desde qué wave bloquear
4. Si la wave actual > `withdrawal_wave_id` → bloquea acceso

### Si es standalone:
1. Verifica tabla `wp_survey_assignments`
2. Si encuentra `declined` → bloquea ese assignment específico
3. Permite re-intentar con nuevo assignment

---

## PSEUDOCÓDIGO VALIDACIÓN

```
funcion validate_study_access(participant_id, survey_id, current_wave_id)
    tipo_estudio = detectar_si_es_longitudinal(survey_id)
    
    si tipo_estudio es longitudinal:
        participante = buscar_en_participants(participant_id)
        
        si participante.consent_decision es "consent_declined" 
           y participante.consent_blocked_survey_id es survey_id:
            retornar bloqueado, razon "consent_declined"
        
        si participante.consent_decision es "study_withdrawal"
           y participante.consent_blocked_survey_id es survey_id:
            si current_wave_id > participante.withdrawal_wave_id:
                retornar bloqueado, razon "study_withdrawal"
    
    si tipo_estudio es standalone:
        assignment = buscar_assignment_actual(participant_id, survey_id)
        
        si assignment.consent_decision es "declined":
            retornar bloqueado, permitir_retry = true
    
    retornar permitido
```

---

## DISEÑO DE INTERFAZ

### Bloque T1 (Gutenberg)

**Elementos:**
- Checkbox: "He leído y entiendo" (opcional para rechazar, obligatorio para aceptar)
- **Botón izquierdo:** "No deseo participar" (borde rojo #dc2626, fondo transparente)
- **Botón derecho:** "Acepto participar" (fondo verde #16a34a, texto blanco)
- Estados hover y active definidos para ambos

**Estilos CSS:**
```css
.eipsi-consent-btn-decline {
    border: 2px solid #dc2626;
    color: #dc2626;
    background: transparent;
}
.eipsi-consent-btn-decline:hover {
    background: #dc2626;
    color: white;
}
.eipsi-consent-btn-accept {
    background: #16a34a;
    color: white;
    border: none;
}
.eipsi-consent-btn-accept:hover {
    background: #15803d;
}
```

---

### Dashboard - Configuración de participación

**Ubicación:** Header del dashboard de participante  
**Icono:** ⚙️ (settings) o 🚪 (exit)  
**Tooltip:** "Configuración de participación"

**Menú desplegable:**
```
┌─────────────────────────────┐
│  🚪 Abandonar estudio...    │
└─────────────────────────────┘
```

*Solo aparece al clickear el icono ⚙️ en el header*

---

### Modales de confirmación

#### Modal T1 (rechazo consentimiento)

| Elemento | Valor |
|----------|-------|
| **Título** | "Confirmar decisión" |
| **Mensaje** | "¿Estás seguro? Esta acción bloqueará tu acceso al estudio de forma permanente." |
| **Icono** | ⚠️ |
| **Botones** | "Cancelar" (outline) / "Sí, confirmo" (rojo sólido) |

#### Modal Abandono B1 (estándar)

| Elemento | Valor |
|----------|-------|
| **Título** | "¿Estás seguro?" |
| **Mensaje** | "Estás a punto de abandonar el estudio." |
| **Gate de decisión** | Checkbox: "Entiendo que mis datos se conservan para la investigación" *(unchecked por defecto)* |
| **Si tilda checkbox** | ✅ Camino B1 inmediato: datos conservados |
| **Si NO tilda** | ⚠️ Camino B2: 2 modales de confirmación extra + verificación de texto |
| **Botón primario** | "Confirmar abandono" *(siempre habilitado, detecta estado del checkbox)* |

#### Modal Abandono B2 (eliminación) - Paso 1

| Elemento | Valor |
|----------|-------|
| **Título** | "⚠️ Advertencia grave" |
| **Mensaje** | "Esta acción eliminará permanentemente TODOS tus datos del estudio." |
| **Impacto** | "Esto afectará la integridad de la investigación en curso." |
| **Checkbox 1** | "Entiendo que mis datos serán eliminados permanentemente" |
| **Checkbox 2** | "Entiendo que esta acción no se puede deshacer" |
| **Botón continuar** | Habilitado solo tras ambos checkboxes |

#### Modal Abandono B2 (eliminación) - Paso 2

| Elemento | Valor |
|----------|-------|
| **Título** | "Verificación final" |
| **Instrucción** | "Para confirmar la eliminación, escribí exactamente el siguiente texto:" |
| **Texto de referencia** | Muestra visual con estilo `code/keyboard` del texto exacto:<br>`ELIMINAR MIS DATOS DE "Estudio de Psicología Cognitiva 2026"` |
| **Input** | Campo de texto vacío con `placeholder` mostrando el hint:<br>`placeholder="ELIMINAR MIS DATOS DE \"Estudio de Psicología Cognitiva 2026\""` |
| **Validación** | Verificación en tiempo real, botón deshabilitado hasta coincidencia **exacta** (case-sensitive, incluyendo comillas si las tiene) |
| **Botón** | "Eliminar mis datos permanentemente" (deshabilitado hasta coincidencia exacta) |
| **Estado del botón** | Rojo intenso `#dc2626`, habilita solo cuando `input.value === textoExacto` |

**Nota de implementación:** El texto interpolado debe incluir el `post_title` del estudio actual. Ejemplo de generación:
```javascript
const textoVerificacion = `ELIMINAR MIS DATOS DE "${studyTitle}"`;
```

---

### Pantallas de bloqueo

#### Versión A - Formulario individual

```
┌────────────────────────────────┐
│            ✕                   │
│   Decidiste no participar      │
│   ──────────────────────────   │
│   Rechazaste participar en     │
│   este formulario.             │
│                                │
│   Fecha: 21/04/2026 14:30      │
│   ──────────────────────────   │
│   [Intentar nuevamente]        │
└────────────────────────────────┘
```

#### Versión B - T1 consent_declined

```
┌────────────────────────────────┐
│            ✕                   │
│  Rechazaste el consentimiento  │
│       informado                │
│   ──────────────────────────   │
│   Decidiste no dar tu          │
│   consentimiento para          │
│   participar en este estudio.  │
│                                │
│   Estudio: Investigación X     │
│   Wave: Toma 1                 │
│   Fecha: 21/04/2026            │
│   ──────────────────────────   │
│   Como no aceptaste el         │
│   consentimiento informado,    │
│   no podés participar en       │
│   ninguna toma de este estudio.│
│   ──────────────────────────   │
│   ¿Cambiaste de opinión?       │
│   📧 investigador@mail.com     │
└────────────────────────────────┘
```

#### Versión C - T2+ study_withdrawal (B1 - estándar)

```
┌────────────────────────────────┐
│           👋                   │
│     Abandonaste el estudio     │
│   ──────────────────────────   │
│   Decidiste dejar de           │
│   participar en este estudio.  │
│                                │
│   Estudio: Investigación X     │
│   Abandono: Wave 3              │
│   Fecha: 21/04/2026            │
│   ──────────────────────────   │
│   ✅ Tus datos de la Toma 1    │
│      fueron recolectados con tu│
│      consentimiento y se         │
│      mantienen.                │
│   ❌ No podés participar en    │
│      futuras tomas.            │
│   ──────────────────────────   │
│   Derecho a retirarte:         │
│   Podés abandonar el estudio   │
│   en cualquier momento.        │
│   ──────────────────────────   │
│   ¿Querés reincorporarte?      │
│   📧 investigador@mail.com     │
└────────────────────────────────┘
```

#### Versión D - T2+ data_deleted (B2 - eliminación)

```
┌────────────────────────────────┐
│           🗑️                   │
│    Datos eliminados            │
│   ──────────────────────────   │
│   Solicitaste la eliminación   │
│   completa de tus datos del    │
│   estudio.                     │
│                                │
│   Estudio: Investigación X     │
│   Fecha de eliminación:        │
│   21/04/2026                   │
│   ──────────────────────────   │
│   ✅ Todos tus datos fueron    │
│      eliminados permanentemente│
│   ✅ No queda registro de tu   │
│      participación             │
│   ❌ No podés participar en    │
│      futuras tomas             │
│   ──────────────────────────   │
│   Esta acción se completó      │
│   conforme a tu solicitud.     │
└────────────────────────────────┘
```

---

## IMPLEMENTACIÓN - 4 DÍAS

### Día 1 - Frontend T1

**Archivos a modificar:**
- `src/blocks/consent-block/save.js` (reemplazar checkbox por 2 botones + checkbox lectura)
- `src/blocks/consent-block/edit.js` (actualizar preview en editor)
- `assets/css/eipsi-forms.css` (estilos botones rojo outline vs verde sólido)
- `assets/js/eipsi-forms.js` (lógica validación cruzada checkbox vs botón, handlers click)

**Nuevos archivos:**
- Componente modal confirmación T1

**Testing:** Flujos aceptar/rechazar en T1

---

### Día 2 - Backend T1

**Migration SQL:**
```sql
-- wp_survey_assignments
ALTER TABLE wp_survey_assignments 
ADD COLUMN consent_decision VARCHAR(20) NULL,
ADD COLUMN consent_decided_at DATETIME NULL,
ADD COLUMN consent_ip_address VARCHAR(45) NULL,
ADD COLUMN consent_user_agent TEXT NULL,
ADD COLUMN consent_context JSON NULL;

-- wp_survey_participants  
ALTER TABLE wp_survey_participants
ADD COLUMN consent_decision VARCHAR(20) NULL, -- 'consent_declined', 'study_withdrawal', 'data_deleted'
ADD COLUMN consent_decided_at DATETIME NULL,
ADD COLUMN consent_ip_address VARCHAR(45) NULL,
ADD COLUMN consent_user_agent TEXT NULL,
ADD COLUMN consent_context JSON NULL,
ADD COLUMN consent_blocked_survey_id BIGINT UNSIGNED NULL,
ADD COLUMN withdrawal_wave_id INT NULL,
ADD COLUMN data_deleted TINYINT(1) DEFAULT 0;

-- Índices
CREATE INDEX idx_consent_decision ON wp_survey_participants(consent_decision, consent_blocked_survey_id);
CREATE INDEX idx_withdrawal ON wp_survey_participants(withdrawal_wave_id);
```

**Archivos a modificar:**
- `admin/ajax-handlers.php`: aceptar decisión `declined`
- `includes/services/class-auth-service.php`: validar `consent_declined`

**Nuevos archivos:**
- Endpoint: `wp_ajax_eipsi_record_consent_decision`
- Template: pantalla bloqueo T1 (versión B)

---

### Día 3 - Abandono T2+ (Dashboard)

**Cambio respecto a versión anterior:**  
❌ ~~Botón en header/footer del formulario~~  
✅ **Menú en dashboard del participante**

**Archivos a modificar:**
- `templates/participant-dashboard.php`: agregar menú de configuración
- `assets/js/participant-dashboard.js`: handlers de abandono, modales B1/B2
- `assets/css/dashboard.css`: estilos menú desplegable y modales

**Nuevos archivos:**
- `templates/modals/withdrawal-b1-modal.php` (flujo estándar)
- `templates/modals/withdrawal-b2-modal.php` (eliminación con fricción)
- Endpoint: `wp_ajax_eipsi_record_study_withdrawal`
- Endpoint: `wp_ajax_eipsi_delete_participant_data` (para B2)

**Templates bloqueo:**
- `templates/blocked/study-withdrawal-b1.php` (versión C)
- `templates/blocked/study-withdrawal-b2.php` (versión D)

**Testing:**
- Flujo T1 acepta → T2 abandona B1 → bloqueo T3
- Flujo T1 acepta → T2 abandona B2 → eliminación → bloqueo T3
- Verificación texto exacto en B2

---

### Día 4 - Testing integración

**Testing end-to-end:**
- [ ] T1 rechazo → bloqueo total
- [ ] T1 acepta → T2 abandono B1 → bloqueo T3
- [ ] T1 acepta → T2 abandono B2 → eliminación → bloqueo T3
- [ ] T1 rechazo → intento T2 → bloqueo
- [ ] Formulario standalone rechazo → reintento

**Edge cases:**
- [ ] Refresh después de rechazo
- [ ] Back button
- [ ] Múltiples tabs abiertos
- [ ] Race condition doble click en botones
- [ ] Intento de bypass escribiendo URL directa
- [ ] B2: texto casi correcto pero no exacto
- [ ] B2: copiar-pegar texto funciona

**Técnicos:**
| Escenario | Mitigación implementada |
|-----------|------------------------|
| Usuario rechaza por error | Modal de confirmación explícita (2 pasos mínimo) |
| Bypass bloqueo con private mode | Validación server-side SIEMPRE, nunca depender de cookies |
| Pérdida datos si guarda y luego rechaza | Guardar rechazo ANTES de permitir submit del formulario |
| Race condition doble click | Deshabilitar botón tras primer click + debounce 500ms |
| Fallo de red en abandonar | Retry automático + mensaje de error amigable |
| Confusión entre B1 y B2 | B2 requiere 4-5 pasos, B2 solo 2, diferencia clara |
| Eliminar datos por error | Texto a escribir de 40+ caracteres + verificación exacta |

---

## CHECKLIST PRE-IMPLEMENTACIÓN

- [ ] MySQL 5.7+ verificado (soporte JSON columns)
- [ ] Backup base de datos antes de migration
- [ ] Email de contacto del investigador definido para pantallas bloqueo
- [ ] Política de retención de datos definida (¿cuánto tiempo conservar datos de abandonados?)
- [ ] Testing en staging con datos de prueba
- [ ] Revisión ética institucional (si aplica)

---

## RESUMEN DE CAMBIOS RESPECTO A v2.4

| Aspecto | v2.4 (anterior) | v2.5 (nuevo) |
|---------|-----------------|--------------|
| **Input T1** | Checkbox ambiguo | Dos botones explícitos |
| **Rechazo T1** | No se guardaba | `consent_declined` en DB |
| **Abandono T2+** | No existía | Flujo desde dashboard (B1/B2) |
| **Eliminación datos** | No existía | Opción B2 con fricción máxima |
| **Iniciado por** | N/A | Participante voluntariamente |
| **Dónde** | N/A | Dashboard, no formulario |
| **Persistencia datos B1** | N/A | Conserva todo |
| **Persistencia datos B2** | N/A | Elimina todo |

---

## NOTAS ÉTICAS Y LEGALES

### Freely given consent
- Botón rechazo IGUAL de prominente que aceptar (mismo tamaño, ambos visibles siempre)
- No pre-seleccionado, no checkbox por defecto

### Derecho al olvido vs integridad investigación
- **B1 (estándar):** Mantiene datos previos, bloquea futuras recolecciones → Balance estándar
- **B2 (eliminación):** Fricción máxima para prevenir uso accidental, pero permite ejercer derecho

### Transparencia
- Versión del texto de consentimiento guardada en `consent_context` (hash o texto completo)
- Fecha, IP, user agent de cada decisión registrada

### Contacto
- Siempre mostrar email y teléfono del investigador en pantallas de bloqueo
- Permitir reincorporación manual por contacto directo

---

## CAMBIOS REALIZADOS EN ESTA VERSIÓN (vs draft inicial)

| Cambio | Motivo |
|--------|--------|
| **Eliminado:** Bloque de consentimiento en T3/T4 como trigger de abandono | Caso "rarisimo" según tutor, no justifica complejidad |
| **Agregado:** Abandono voluntario desde dashboard | Más ético, iniciado por participante |
| **Agregado:** Opción B2 (eliminación de datos) | Respuesta a "poder retractarse" con transparencia |
| **Diseño B2:** Texto de verificación dinámico con hint visible | `ELIMINAR MIS DATOS DE "[Nombre del Estudio]"` - al estilo GitHub |

---

EIPSI Forms v2.5  
**Estado:** ✅ Listo para implementación  
**Próximo paso:** Iniciar Día 1 (Frontend T1)
