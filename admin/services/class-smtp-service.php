<?php
/**
 * EIPSI_SMTP_Service
 *
 * Servicio interno de SMTP para envío de correos sin plugins externos.
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_SMTP_Service {

    private $option_prefix = 'eipsi_smtp_';

    /**
     * Encrypt sensitive data before storing.
     */
    private function encrypt_data($data) {
        if (empty($data)) {
            return '';
        }

        $key = wp_salt('auth');
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $key,
            0,
            $iv
        );

        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * Decrypt stored credentials.
     */
    private function decrypt_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }

        $key = wp_salt('auth');
        $decoded = base64_decode($encrypted_data);

        if (strpos($decoded, '::') === false) {
            return '';
        }

        list($iv, $encrypted) = explode('::', $decoded, 2);

        return openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $key,
            0,
            $iv
        );
    }

    /**
     * Validate SMTP configuration.
     */
    public function validate_config($host, $port, $user, $password, $encryption, $allow_empty_password = false) {
        $host = sanitize_text_field($host);
        $user = sanitize_email($user);
        $port = absint($port);
        $encryption = sanitize_key($encryption);

        if (empty($host) || empty($user) || empty($port)) {
            return array(
                'valid' => false,
                'message' => __('Por favor completa todos los campos obligatorios.', 'eipsi-forms')
            );
        }

        if (!is_email($user)) {
            return array(
                'valid' => false,
                'message' => __('El usuario SMTP debe ser un correo válido.', 'eipsi-forms')
            );
        }

        if ($port < 1 || $port > 65535) {
            return array(
                'valid' => false,
                'message' => __('El puerto SMTP no es válido.', 'eipsi-forms')
            );
        }

        if (!$allow_empty_password && empty($password)) {
            return array(
                'valid' => false,
                'message' => __('La contraseña SMTP es obligatoria.', 'eipsi-forms')
            );
        }

        $allowed_encryption = array('tls', 'ssl', 'none');
        if (!in_array($encryption, $allowed_encryption, true)) {
            return array(
                'valid' => false,
                'message' => __('Selecciona un tipo de seguridad válido.', 'eipsi-forms')
            );
        }

        return array('valid' => true);
    }

    /**
     * Save SMTP configuration.
     */
    public function save_config($host, $port, $user, $password, $encryption) {
        $host = sanitize_text_field($host);
        $user = sanitize_email($user);
        $port = absint($port);
        $encryption = sanitize_key($encryption);

        $encrypted_password = $this->encrypt_data($password);

        update_option($this->option_prefix . 'host', $host);
        update_option($this->option_prefix . 'port', $port);
        update_option($this->option_prefix . 'user', $user);
        update_option($this->option_prefix . 'password', $encrypted_password);
        update_option($this->option_prefix . 'encryption', $encryption);
        update_option($this->option_prefix . 'enabled', true);
        update_option($this->option_prefix . 'last_updated', current_time('mysql'));

        return true;
    }

    /**
     * Disable SMTP configuration.
     */
    public function disable_config() {
        update_option($this->option_prefix . 'enabled', false);
        delete_option($this->option_prefix . 'host');
        delete_option($this->option_prefix . 'port');
        delete_option($this->option_prefix . 'user');
        delete_option($this->option_prefix . 'password');
        delete_option($this->option_prefix . 'encryption');
        delete_option($this->option_prefix . 'last_updated');
    }

    /**
     * Get current configuration.
     */
    public function get_config() {
        $enabled = get_option($this->option_prefix . 'enabled', false);

        if (!$enabled) {
            return null;
        }

        $host = get_option($this->option_prefix . 'host', '');
        $port = absint(get_option($this->option_prefix . 'port', 587));
        $user = get_option($this->option_prefix . 'user', '');
        $encrypted_password = get_option($this->option_prefix . 'password', '');
        $encryption = get_option($this->option_prefix . 'encryption', 'tls');

        if (empty($host) || empty($user) || empty($port)) {
            return null;
        }

        $password = $this->decrypt_data($encrypted_password);

        return array(
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'encryption' => $encryption,
            'enabled' => true
        );
    }

    /**
     * Check if SMTP is enabled.
     */
    public function is_enabled() {
        return (bool) get_option($this->option_prefix . 'enabled', false);
    }

    /**
     * Send a test email using provided config.
     */
    public function send_test_email($config, $recipient) {
        $subject = __('Prueba de correo SMTP — EIPSI Forms', 'eipsi-forms');
        $message = '<p>' . esc_html__('Este es un correo de prueba para confirmar tu configuración SMTP en EIPSI Forms.', 'eipsi-forms') . '</p>';
        $message .= '<p>' . esc_html__('Si recibiste este mensaje, tu servidor está listo para enviar recordatorios clínicos.', 'eipsi-forms') . '</p>';

        return $this->send_message($recipient, $subject, $message, $config);
    }

    /**
     * Send email using SMTP settings.
     */
    public function send_message($to, $subject, $content, $config = null) {
        if (!$config) {
            $config = $this->get_config();
        }

        if (!$config) {
            return array(
                'success' => false,
                'error' => __('SMTP no configurado.', 'eipsi-forms')
            );
        }

        $this->load_phpmailer();

        $from_email = $config['user'];
        $from_name = get_option('eipsi_investigator_name', get_bloginfo('name'));
        $reply_to = get_option('eipsi_investigator_email', get_option('admin_email'));

        try {
            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $config['host'];
            $mailer->Port = (int) $config['port'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config['user'];
            $mailer->Password = $config['password'];

            if (!empty($config['encryption']) && $config['encryption'] !== 'none') {
                $mailer->SMTPSecure = $config['encryption'];
            } else {
                $mailer->SMTPSecure = '';
                $mailer->SMTPAutoTLS = false;
            }

            $mailer->setFrom($from_email, $from_name);

            if (!empty($reply_to) && is_email($reply_to) && $reply_to !== $from_email) {
                $mailer->addReplyTo($reply_to, $from_name);
            }

            $mailer->addAddress($to);
            $mailer->CharSet = 'UTF-8';
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $content;
            $mailer->AltBody = wp_strip_all_tags($content);

            $mailer->send();

            return array('success' => true);
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Load PHPMailer classes bundled with WordPress.
     */
    private function load_phpmailer() {
        if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        }
    }
}
