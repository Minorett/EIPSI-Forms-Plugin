<?php

class EIPSI_Wave_Service_Tests extends WP_UnitTestCase {

    public function test_create_wave() {
        $wave_id = EIPSI_Wave_Service::create_wave(
            1, // survey_id
            1, // wave_index
            'T1 - Baseline',
            7 // days_between_waves
        );
        
        $this->assertIsInt($wave_id);
        $this->assertGreaterThan(0, $wave_id);
    }

    public function test_get_wave_by_index() {
        $wave_id = EIPSI_Wave_Service::create_wave(1, 1, 'T1', 7);
        
        $wave = EIPSI_Wave_Service::get_wave_by_index(1, 1);
        
        $this->assertNotNull($wave);
        $this->assertEquals($wave_id, $wave->id);
    }

    public function test_calculate_wave_due_date() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'due@example.com',
            'Due Date Test'
        );
        
        $wave_id = EIPSI_Wave_Service::create_wave(1, 1, 'T1', 7);
        
        $due_date = EIPSI_Wave_Service::calculate_wave_due_date($participant_id, $wave_id);
        
        $this->assertNotEmpty($due_date);
        $this->assertIsString($due_date);
    }

    public function test_get_pending_waves_for_participant() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'pending@example.com',
            'Pending Waves Test'
        );
        
        EIPSI_Wave_Service::create_wave(1, 1, 'T1', 7);
        EIPSI_Wave_Service::create_wave(1, 2, 'T2', 7);
        
        $pending_waves = EIPSI_Wave_Service::get_pending_waves_for_participant($participant_id);
        
        $this->assertIsArray($pending_waves);
        $this->assertCount(2, $pending_waves);
    }

    public function test_mark_wave_completed() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'complete@example.com',
            'Complete Test'
        );
        
        $wave_id = EIPSI_Wave_Service::create_wave(1, 1, 'T1', 7);
        
        $result = EIPSI_Wave_Service::mark_wave_completed($participant_id, $wave_id);
        
        $this->assertTrue($result);
    }

    public function test_get_completion_stats() {
        $participant_id = EIPSI_Participant_Service::create_participant(
            1,
            'stats@example.com',
            'Stats Test'
        );
        
        $wave_id = EIPSI_Wave_Service::create_wave(1, 1, 'T1', 7);
        EIPSI_Wave_Service::mark_wave_completed($participant_id, $wave_id);
        
        $stats = EIPSI_Wave_Service::get_completion_stats(1);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('pending', $stats);
    }

    public function tearDown(): void {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}survey_participants WHERE email LIKE '%@example.com'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}survey_waves");
        parent::tearDown();
    }

}
