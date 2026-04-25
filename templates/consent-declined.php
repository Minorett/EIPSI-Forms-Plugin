<?php
/**
 * Fase 2 - v2.5: Pantalla de bloqueo T1
 * Se muestra cuando un participante rechaza el consentimiento informado
 * 
 * Tipo A: Consentimiento rechazado (T1)
 * - Bloqueo TOTAL del estudio
 * - Nunca hubo participación
 * - No se puede volver a entrar
 * 
 * @since 2.5.0
 * @package EIPSI_Forms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<style>
.eipsi-consent-declined-container {
    max-width: 600px;
    margin: 60px auto;
    padding: 40px;
    text-align: center;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.eipsi-consent-declined-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: #fef2f2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.eipsi-consent-declined-icon svg {
    width: 40px;
    height: 40px;
    color: #dc2626;
}

.eipsi-consent-declined-title {
    font-size: 24px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 16px;
}

.eipsi-consent-declined-message {
    font-size: 16px;
    line-height: 1.6;
    color: #4b5563;
    margin-bottom: 32px;
}

.eipsi-consent-declined-details {
    background: #f9fafb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
    text-align: left;
}

.eipsi-consent-declined-details h3 {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 12px;
}

.eipsi-consent-declined-details ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.eipsi-consent-declined-details li {
    font-size: 13px;
    color: #6b7280;
    padding: 6px 0;
    border-bottom: 1px solid #e5e7eb;
}

.eipsi-consent-declined-details li:last-child {
    border-bottom: none;
}

.eipsi-consent-declined-contact {
    font-size: 14px;
    color: #6b7280;
    margin-top: 24px;
}

.eipsi-consent-declined-contact a {
    color: #2563eb;
    text-decoration: none;
}

.eipsi-consent-declined-contact a:hover {
    text-decoration: underline;
}

@media (prefers-color-scheme: dark) {
    .eipsi-consent-declined-container {
        background: #1f2937;
    }
    
    .eipsi-consent-declined-title {
        color: #f9fafb;
    }
    
    .eipsi-consent-declined-message {
        color: #9ca3af;
    }
    
    .eipsi-consent-declined-details {
        background: #374151;
    }
    
    .eipsi-consent-declined-details h3 {
        color: #e5e7eb;
    }
    
    .eipsi-consent-declined-details li {
        color: #9ca3af;
        border-color: #4b5563;
    }
    
    .eipsi-consent-declined-contact {
        color: #9ca3af;
    }
}
</style>

<div class="eipsi-consent-declined-container">
    <div class="eipsi-consent-declined-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </div>
    
    <h1 class="eipsi-consent-declined-title">
        <?php _e('Consentimiento rechazado', 'eipsi-forms'); ?>
    </h1>
    
    <p class="eipsi-consent-declined-message">
        <?php _e('Rechazaste el consentimiento. No podés participar en este estudio.', 'eipsi-forms'); ?>
    </p>
    
    <div class="eipsi-consent-declined-details">
        <h3><?php _e('¿Qué significa esto?', 'eipsi-forms'); ?></h3>
        <ul>
            <li>❌ <?php _e('No se han guardado datos tuyos', 'eipsi-forms'); ?></li>
            <li>❌ <?php _e('No podés volver a acceder a este estudio', 'eipsi-forms'); ?></li>
            <li>✓ <?php _e('Tu decisión ha sido registrada de forma anónima', 'eipsi-forms'); ?></li>
            <li>✓ <?php _e('Podés contactar al investigador si cambiás de opinión', 'eipsi-forms'); ?></li>
        </ul>
    </div>
    
    <p class="eipsi-consent-declined-contact">
        <?php 
        $investigator_email = get_option('eipsi_investigator_email', '');
        if ($investigator_email) {
            printf(
                __('Si tenés preguntas, podés contactar al investigador: %s', 'eipsi-forms'),
                '<a href="mailto:' . esc_attr($investigator_email) . '">' . esc_html($investigator_email) . '</a>'
            );
        } else {
            _e('Si tenés preguntas, podés contactar al investigador.', 'eipsi-forms');
        }
        ?>
    </p>
</div>

<?php
get_footer();
