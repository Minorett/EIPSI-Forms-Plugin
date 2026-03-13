# Fix: Redirect Post-Confirmación de Email

## 📋 Problema Identificado

Al confirmar su email vía double opt-in, los participantes ven una página estática de confirmación ("¡Email confirmado!") con un tilde verde pero **no son redirigidos a ningún lugar**. Deben navegar manualmente para buscar el email del magic link en su bandeja de entrada.

### Ubicación del Problema
**Archivo:** `/includes/class-survey-access-handler.php`
**Método:** `render_confirmation_success()` (líneas 129-171)

## ✅ Solución Implementada

### Cambio 1: Modificar `render_confirmation_success()` para redirigir

**Archivo:** `/includes/class-survey-access-handler.php`

**Antes:**
```php
private function render_confirmation_success($email) {
    // Mostrar página HTML estática con tilde verde
    // ... HTML completo ...
    exit;
}
```

**Después:**
```php
private function render_confirmation_success($email, $survey_id = 0) {
    // Encontrar página de login del estudio
    $login_url = $this->find_study_login_page($survey_id);

    // Agregar mensaje de confirmación como parámetro query
    $redirect_url = add_query_arg(array(
        'eipsi_msg' => 'email_confirmed',
        'eipsi_email' => urlencode($email)
    ), $login_url);

    // Redirigir a la página de login
    wp_redirect($redirect_url);
    exit;
}
```

### Cambio 2: Agregar método `find_study_login_page()`

**Nuevo método en `/includes/class-survey-access-handler.php` (líneas 147-204):**

Este método busca la página de login del estudio con 3 estrategias de fallback:

1. **Página de login específica del estudio** (por `_eipsi_survey_id`)
2. **Cualquier página con el shortcode `[eipsi_survey_login]`**
3. **Página del dashboard del participante** (por `_eipsi_has_dashboard`)
4. **Fallback:** Home page

### Cambio 3: Mostrar mensaje de confirmación en el login

**Archivo:** `/includes/templates/survey-login-form.php`

**Agregado (líneas 23-31):**
```php
// Check for confirmation message from email verification
$confirmation_message = '';
if (isset($_GET['eipsi_msg']) && $_GET['eipsi_msg'] === 'email_confirmed') {
    $confirmed_email = isset($_GET['eipsi_email']) ? sanitize_email(urldecode($_GET['eipsi_email'])) : '';
    $confirmation_message = sprintf(
        __('¡Email confirmado! Revisá %s para el enlace de acceso al estudio.', 'eipsi-forms'),
        !empty($confirmed_email) ? esc_html($confirmed_email) : __('tu bandeja de entrada', 'eipsi-forms')
    );
}
```

**Agregado en el template (líneas 51-57):**
```php
<!-- Email Confirmation Message -->
<?php if (!empty($confirmation_message)): ?>
<div class="eipsi-confirmation-message">
    <span class="confirmation-icon">✓</span>
    <p><?php echo wp_kses_post($confirmation_message); ?></p>
</div>
<?php endif; ?>
```

### Cambio 4: Estilos CSS para el mensaje de confirmación

**Archivo:** `/assets/css/survey-login-enhanced.css`

**Agregado (líneas 58-83):**
```css
/* Email Confirmation Message */
.eipsi-confirmation-message {
    background: linear-gradient(135deg, #e7f3ff 0%, #d1e8ff 100%);
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.15);
}

.eipsi-confirmation-message .confirmation-icon {
    font-size: 32px;
    line-height: 1;
    color: #1976D2;
    flex-shrink: 0;
}

.eipsi-confirmation-message p {
    margin: 0;
    color: #1565C0;
    font-size: 15px;
    line-height: 1.5;
    font-weight: 500;
}
```

## 🔄 Flujo Completo Implementado

1. ✅ **Participante confirma email** → `handle_confirmation_request()` valida token
2. ✅ **Sistema activa participante** → `is_active = 1`
3. ✅ **Sistema envía magic link** → `send_welcome_after_confirmation()`
4. 🔜 **Redirigir al login** → `render_confirmation_success()` redirige a login con parámetros
5. 📧 **Mensaje de confirmación** → Login muestra: *"¡Email confirmado! Revisá [email] para el enlace de acceso"*
6. 🔐 **Participante hace click en magic link** → Validación + autenticación
7. ✅ **Redirigir al formulario** → Sistema redirige al formulario del estudio

## 📁 Archivos Modificados

1. `/includes/class-survey-access-handler.php`
   - Modificado: `render_confirmation_success()` - ahora recibe `$survey_id`
   - Agregado: `find_study_login_page()` - busca página de login
   - Modificado: Llamada a `render_confirmation_success()` pasa `$survey_id`

2. `/includes/templates/survey-login-form.php`
   - Agregado: Lógica para detectar parámetro `eipsi_msg=email_confirmed`
   - Agregado: HTML del mensaje de confirmación

3. `/assets/css/survey-login-enhanced.css`
   - Agregado: Estilos para `.eipsi-confirmation-message`

## ✅ Criterios Cumplidos

- ✅ **Flujo lógico completo**: Confirmar email → Redirigir a login → Usuario ve mensaje claro
- ✅ **Magic link ya enviado**: El sistema envía el magic link antes de redirigir
- ✅ **UX mejorada**: Participante no queda atrapado en página estática
- ✅ **Mensaje contextual**: El login muestra el email confirmado
- ✅ **Fallbacks múltiples**: Busca login por estudio, por shortcode, por dashboard, o home
- ✅ **Seguridad**: `sanitize_email()`, `esc_html()`, `wp_kses_post()`
- ✅ **Sintaxis PHP válida**: Verificado con `php -l`

## 🧪 Testing Recomendado

1. Registrar un nuevo participante con Double Opt-In habilitado
2. Click en el link de confirmación del email
3. Verificar que redirija al login del estudio
4. Verificar que el mensaje de confirmación aparezca
5. Verificar que el magic link llegue al email del participante
6. Click en el magic link y verificar acceso correcto al formulario

## 🎯 Beneficios

- **Zero friction**: Participante no debe navegar manualmente
- **UX clara**: El mensaje indica exactamente qué hacer
- **Flujo completo**: Confirmar → Login → Magic Link → Acceso al formulario
- **Profesional**: Estilo moderno con gradientes y tipografía clara
