<?php

class EIPSI_Participant_Service_Tests extends WP_UnitTestCase {

    public function test_create_participant() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1, // survey_id
            'test@example.com',
            'Test Participant'
        );
        
        $this->assertIsInt($participant_id);
        $this->assertGreaterThan(0, $participant_id);
    }

    public function test_get_participant_by_email() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'test2@example.com',
            'Test Participant 2'
        );
        
        $participant = EIPSI_Participant_Service::get_participant_by_email(
            1,
            'test2@example.com'
        );
        
        $this->assertNotNull($participant);
        $this->assertEquals($participant_id, $participant->id);
    }

    public function test_get_active_participants_for_survey() {
        EIPSI_Participant_Service::create_participant(1, 'active1@example.com', 'Active 1');
        EIPSI_Participant_Service::create_participant(1, 'active2@example.com', 'Active 2');
        
        $active_participants = EIPSI_Participant_Service::get_active_participants_for_survey(1);
        
        $this->assertCount(2, $active_participants);
    }

    public function test_update_participant_status() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'status@example.com',
            'Status Test'
        );
        
        $result = EIPSI_Participant_Service::update_participant_status(
            $participant_id,
            0 // inactive
        );
        
        $this->assertTrue($result);
        
        $participant = EIPSI_Participant_Service::get_participant_by_id($participant_id);
        $this->assertEquals(0, $participant->is_active);
    }

    public function test_anonymize_participant() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'anonymize@example.com',
            'Anonymize Test'
        );
        
        $result = EIPSI_Participant_Service::anonymize_participant($participant_id);
        
        $this->assertTrue($result);
        
        $participant = EIPSI_Participant_Service::get_participant_by_id($participant_id);
        $this->assertNull($participant->email);
        $this->assertNull($participant->name);
    }

    public function test_get_waves_completion_status() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'waves@example.com',
            'Waves Test'
        );
        
        $completion_status = EIPSI_Participant_Service::get_waves_completion_status(
            $participant_id
        );
        
        $this->assertIsArray($completion_status);
        $this->assertArrayHasKey('completed', $completion_status);
        $this->assertArrayHasKey('total', $completion_status);
    }

    public function tearDown(): void {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}survey_participants WHERE email LIKE '%@example.com'");
        parent::tearDown();
    }

}
