<?php

class EIPSI_Auth_Service_Tests extends WP_UnitTestCase {

    public function test_generate_magic_link() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'magic@example.com',
            'Magic Link Test'
        );
        
        $magic_link = EIPSI_Auth_Service::generate_magic_link($participant_id, 1);
        
        $this->assertNotEmpty($magic_link);
        $this->assertStringContainsString('token=', $magic_link);
    }

    public function test_validate_magic_link() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'validate@example.com',
            'Validate Test'
        );
        
        $magic_link = EIPSI_Auth_Service::generate_magic_link($participant_id, 1);
        parse_str(parse_url($magic_link, PHP_URL_QUERY), $query);
        
        $is_valid = EIPSI_Auth_Service::validate_magic_link($query['token'], $participant_id);
        
        $this->assertTrue($is_valid);
    }

    public function test_magic_link_expiry() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'expiry@example.com',
            'Expiry Test'
        );
        
        $magic_link = EIPSI_Auth_Service::generate_magic_link($participant_id, 1);
        parse_str(parse_url($magic_link, PHP_URL_QUERY), $query);
        
        // Simulate expiry by updating created_at
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'survey_magic_links',
            ['created_at' => date('Y-m-d H:i:s', strtotime('-31 minutes'))],
            ['token' => $query['token']]
        );
        
        $is_valid = EIPSI_Auth_Service::validate_magic_link($query['token'], $participant_id);
        
        $this->assertFalse($is_valid);
    }

    public function test_create_session() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'session@example.com',
            'Session Test'
        );
        
        $session_token = EIPSI_Auth_Service::create_session($participant_id);
        
        $this->assertNotEmpty($session_token);
        $this->assertIsString($session_token);
    }

    public function test_validate_session() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'validatesession@example.com',
            'Validate Session Test'
        );
        
        $session_token = EIPSI_Auth_Service::create_session($participant_id);
        
        $is_valid = EIPSI_Auth_Service::validate_session($session_token);
        
        $this->assertTrue($is_valid);
    }

    public function test_session_ttl() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'ttl@example.com',
            'TTL Test'
        );
        
        $session_token = EIPSI_Auth_Service::create_session($participant_id);
        
        // Simulate expiry
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'survey_sessions',
            ['expires_at' => date('Y-m-d H:i:s', strtotime('-8 days'))],
            ['token' => $session_token]
        );
        
        $is_valid = EIPSI_Auth_Service::validate_session($session_token);
        
        $this->assertFalse($is_valid);
    }

    public function test_rate_limiting_login() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'ratelimit@example.com',
            'Rate Limit Test'
        );
        
        // Attempt 6 logins in quick succession
        for ($i = 0; $i < 6; $i++) {
            $magic_link = EIPSI_Auth_Service::generate_magic_link($participant_id, 1);
        }
        
        $last_link = EIPSI_Auth_Service::generate_magic_link($participant_id, 1);
        
        $this->assertNotEmpty($last_link);
    }

    public function tearDown(): void {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}survey_participants WHERE email LIKE '%@example.com'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}survey_magic_links");
        $wpdb->query("DELETE FROM {$wpdb->prefix}survey_sessions");
        parent::tearDown();
    }

}
