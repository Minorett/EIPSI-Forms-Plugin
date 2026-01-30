<?php

class EIPSI_Email_Service_Tests extends WP_UnitTestCase {

    public function test_send_welcome_email() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'welcome@example.com',
            'Welcome Test'
        );
        
        $result = EIPSI_Email_Service::send_welcome_email(1, $participant_id);
        
        $this->assertTrue($result);
        
        // Verify email was logged
        global $wpdb;
        $email_log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_email_log WHERE participant_id = %d ORDER BY id DESC LIMIT 1",
                $participant_id
            )
        );
        
        $this->assertNotNull($email_log);
        $this->assertEquals('welcome', $email_log->email_type);
    }

    public function test_send_wave_reminder_email() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'reminder@example.com',
            'Reminder Test'
        );
        
        $wave_id = EIPSI_Wave_Service::create_wave(1, 1, 'T1', 7);
        
        $result = EIPSI_Email_Service::send_wave_reminder_email(1, $participant_id, 1);
        
        $this->assertTrue($result);
        
        $email_log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_email_log WHERE participant_id = %d ORDER BY id DESC LIMIT 1",
                $participant_id
            )
        );
        
        $this->assertNotNull($email_log);
        $this->assertEquals('wave_reminder', $email_log->email_type);
    }

    public function test_send_wave_confirmation_email() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'confirm@example.com',
            'Confirm Test'
        );
        
        $result = EIPSI_Email_Service::send_wave_confirmation_email(1, $participant_id, 1, 2);
        
        $this->assertTrue($result);
        
        $email_log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_email_log WHERE participant_id = %d ORDER BY id DESC LIMIT 1",
                $participant_id
            )
        );
        
        $this->assertNotNull($email_log);
        $this->assertEquals('wave_confirmation', $email_log->email_type);
    }

    public function test_send_dropout_recovery_email() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'recovery@example.com',
            'Recovery Test'
        );
        
        $result = EIPSI_Email_Service::send_dropout_recovery_email(1, $participant_id, 1);
        
        $this->assertTrue($result);
        
        $email_log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_email_log WHERE participant_id = %d ORDER BY id DESC LIMIT 1",
                $participant_id
            )
        );
        
        $this->assertNotNull($email_log);
        $this->assertEquals('dropout_recovery', $email_log->email_type);
    }

    public function test_email_logging() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'logging@example.com',
            'Logging Test'
        );
        
        $result = EIPSI_Email_Service::log_email(
            1,
            $participant_id,
            'test_email',
            'test@example.com',
            'Test Subject',
            'Test Content',
            'sent',
            null,
            false,
            json_encode(['test' => 'data'])
        );
        
        $this->assertTrue($result);
        
        $email_log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_email_log WHERE participant_id = %d ORDER BY id DESC LIMIT 1",
                $participant_id
            )
        );
        
        $this->assertNotNull($email_log);
        $this->assertEquals('test_email', $email_log->email_type);
    }

    public function test_rate_limiting_emails() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'ratelimit2@example.com',
            'Rate Limit Email Test'
        );
        
        // Send 3 emails quickly
        EIPSI_Email_Service::send_welcome_email(1, $participant_id);
        EIPSI_Email_Service::send_wave_reminder_email(1, $participant_id, 1);
        EIPSI_Email_Service::send_wave_confirmation_email(1, $participant_id, 1, 2);
        
        // Verify all were logged
        $email_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}survey_email_log WHERE participant_id = %d",
                $participant_id
            )
        );
        
        $this->assertEquals(3, $email_count);
    }

    public function test_magic_link_in_email() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'magicemail@example.com',
            'Magic Link Email Test'
        );
        
        $magic_link = EIPSI_Auth_Service::generate_magic_link($participant_id, 1);
        
        $email_content = EIPSI_Email_Service::render_template(
            'welcome',
            [
                'magic_link' => $magic_link,
                'participant_name' => 'Test User',
                'survey_name' => 'Test Survey'
            ]
        );
        
        $this->assertStringContainsString($magic_link, $email_content);
    }

    public function tearDown(): void {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}survey_participants WHERE email LIKE '%@example.com'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}survey_email_log");
        $wpdb->query("DELETE FROM {$wpdb->prefix}survey_waves");
        parent::tearDown();
    }

}
