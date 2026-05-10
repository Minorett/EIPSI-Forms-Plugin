<?php
/**
 * EIPSI Forms - Migration Runner
 *
 * Coordinates and executes database migrations.
 *
 * @package EIPSI_Forms
 * @since 2.6.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EIPSI_Migration_Runner {

    /**
     * Option name for current migration version.
     */
    const VERSION_OPTION = 'eipsi_migration_version';

    /**
     * Option name for last migration date.
     */
    const DATE_OPTION = 'eipsi_migration_date';

    /**
     * Initialize the runner.
     */
    public static function init() {
        $instance = new self();
        $instance->run();
    }

    /**
     * Get the current migration version.
     * Seeds initial version if not set.
     *
     * @return int
     */
    public function get_current_version() {
        $version = get_option( self::VERSION_OPTION, false );

        if ( false === $version ) {
            $version = $this->seed_initial_version();
            $this->update_version( $version );
        }

        return (int) $version;
    }

    /**
     * Seed initial version based on legacy migration flags.
     * "Minimum candidate" approach ensures no legacy updates are skipped.
     *
     * @return int
     */
    private function seed_initial_version() {
        $candidates = array();

        // If eipsi_autofix_schema_version is missing, we need to run from version 4
        if ( ! get_option( 'eipsi_autofix_schema_version' ) ) {
            $candidates[] = 4;
        }

        // If eipsi_fk_fix_version is missing, we need to run from version 5
        if ( ! get_option( 'eipsi_fk_fix_version' ) ) {
            $candidates[] = 5;
        }

        // If eipsi_pools_migrated_v2 is missing, we need to run from version 7
        if ( ! get_option( 'eipsi_pools_migrated_v2' ) ) {
            $candidates[] = 7;
        }

        // If no legacy flags are missing, start from version 9 (fresh start for the new runner)
        if ( empty( $candidates ) ) {
            return 9;
        }

        // Start from the earliest missing state
        return min( $candidates );
    }

    /**
     * Update the migration version.
     *
     * @param int $version
     */
    public function update_version( $version ) {
        update_option( self::VERSION_OPTION, (int) $version );
        update_option( self::DATE_OPTION, current_time( 'mysql' ) );
    }

    /**
     * Execute migrations in sequence.
     * This serves as the entry point for future migration executions.
     */
    public function run() {
        $current_version = $this->get_current_version();

        // Migrations will be implemented here as needed.
        // Example:
        // if ( $current_version < 10 ) {
        //     $this->migrate_to_10();
        //     $this->update_version( 10 );
        // }
    }
}
