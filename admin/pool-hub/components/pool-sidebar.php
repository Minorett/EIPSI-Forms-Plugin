<?php
/**
 * Pool Hub Sidebar Component
 * Lists all pools with quick stats and selection
 *
 * @package EIPSI_Forms
 * @since 2.2.0
 */

// Get pool stats for display
$pool_stats_map = array();
if ( ! empty( $pools ) ) {
    $assignment_svc = class_exists( 'EIPSI_Pool_Assignment_Service' ) ? new EIPSI_Pool_Assignment_Service() : null;
    foreach ( $pools as $p ) {
        if ( $assignment_svc ) {
            $stats = $assignment_svc->get_pool_stats( $p['id'] );
            $pool_stats_map[ $p['id'] ] = $stats;
        } else {
            $pool_stats_map[ $p['id'] ] = array( 'total' => 0, 'by_study' => array() );
        }
    }
}
?>

<aside class="eipsi-pool-sidebar">
    <div class="eipsi-pool-sidebar-header">
        <h3><?php esc_html_e( 'Mis Pools', 'eipsi-forms' ); ?></h3>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=eipsi-pool-hub&pool_id=new' ) ); ?>" 
           class="eipsi-pool-add-btn"
           title="<?php esc_attr_e( 'Nuevo pool', 'eipsi-forms' ); ?>">
            <span class="dashicons dashicons-plus"></span>
        </a>
    </div>

    <div class="eipsi-pool-search">
        <input type="text" 
               id="eipsi-pool-search-input"
               placeholder="<?php esc_attr_e( 'Buscar pools...', 'eipsi-forms' ); ?>"
               autocomplete="off">
        <span class="dashicons dashicons-search"></span>
    </div>

    <div class="eipsi-pool-list">
        <?php if ( empty( $pools ) ) : ?>
            <div class="eipsi-pool-list-empty">
                <p><?php esc_html_e( 'No hay pools aún.', 'eipsi-forms' ); ?></p>
            </div>
        <?php else : ?>
            <?php foreach ( $pools as $p ) : 
                $is_active = (int) $p['id'] === $pool_id;
                $stats = $pool_stats_map[ $p['id'] ] ?? array( 'total' => 0 );
                $total = $stats['total'] ?? 0;
                $status_class = 'eipsi-status--' . ( $p['status'] ?? 'inactive' );
            ?>
                <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'eipsi-pool-hub', 'pool_id' => $p['id'], 'tab' => 'configuration' ), admin_url( 'admin.php' ) ) ); ?>"
                   class="eipsi-pool-list-item <?php echo $is_active ? 'is-active' : ''; ?>"
                   data-pool-id="<?php echo esc_attr( $p['id'] ); ?>">
                    
                    <div class="eipsi-pool-item-main">
                        <span class="eipsi-pool-item-status <?php echo esc_attr( $status_class ); ?>"></span>
                        <span class="eipsi-pool-item-name"><?php echo esc_html( $p['pool_name'] ); ?></span>
                    </div>
                    
                    <div class="eipsi-pool-item-meta">
                        <span class="eipsi-pool-item-count">
                            <?php
                            printf(
                                /* translators: %d: number of participants */
                                _n( '%d participante', '%d participantes', $total, 'eipsi-forms' ),
                                $total
                            );
                            ?>
                        </span>
                        <?php if ( $is_active ) : ?>
                            <span class="eipsi-pool-item-arrow dashicons dashicons-arrow-right-alt2"></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="eipsi-pool-sidebar-footer">
        <div class="eipsi-pool-total-summary">
            <span class="eipsi-pool-total-label"><?php esc_html_e( 'Total pools:', 'eipsi-forms' ); ?></span>
            <span class="eipsi-pool-total-value"><?php echo count( $pools ); ?></span>
        </div>
    </div>
</aside>

<style>
    /* Sidebar Styles - EIPSI Design System */
    .eipsi-pool-sidebar {
        width: var(--eipsi-sidebar-width, 280px);
        background: var(--eipsi-gray-100, #f8fafc);
        border-right: 1px solid var(--eipsi-gray-200, #e2e8f0);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    .eipsi-pool-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--eipsi-gray-200, #e2e8f0);
    }

    .eipsi-pool-sidebar-header h3 {
        font-size: 13px;
        font-weight: 600;
        color: var(--eipsi-gray-800, #1e293b);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin: 0;
    }

    .eipsi-pool-add-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        background: var(--eipsi-blue, #3B6CAA);
        color: #fff;
        border-radius: 4px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .eipsi-pool-add-btn:hover {
        background: var(--eipsi-blue-dark, #1E3A5F);
    }

    .eipsi-pool-add-btn .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    /* Search */
    .eipsi-pool-search {
        position: relative;
        padding: 12px 16px;
        border-bottom: 1px solid var(--eipsi-gray-200, #e2e8f0);
    }

    .eipsi-pool-search input {
        width: 100%;
        padding: 8px 32px 8px 12px;
        border: 1px solid var(--eipsi-gray-200, #e2e8f0);
        border-radius: 6px;
        font-size: 13px;
        background: #fff;
    }

    .eipsi-pool-search input:focus {
        outline: none;
        border-color: var(--eipsi-blue, #3B6CAA);
        box-shadow: 0 0 0 2px rgba(59, 108, 170, 0.1);
    }

    .eipsi-pool-search .dashicons {
        position: absolute;
        right: 24px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 16px;
        color: var(--eipsi-gray-600, #64748b);
        pointer-events: none;
    }

    /* Pool List */
    .eipsi-pool-list {
        flex: 1;
        overflow-y: auto;
        padding: 8px 0;
    }

    .eipsi-pool-list-empty {
        padding: 40px 20px;
        text-align: center;
        color: var(--eipsi-gray-600, #64748b);
        font-size: 13px;
    }

    .eipsi-pool-list-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 12px 16px;
        margin: 0 8px 4px;
        border-radius: 6px;
        text-decoration: none;
        color: inherit;
        transition: all 0.15s;
        cursor: pointer;
    }

    .eipsi-pool-list-item:hover {
        background: #fff;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .eipsi-pool-list-item.is-active {
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-left: 3px solid var(--eipsi-blue, #3B6CAA);
    }

    .eipsi-pool-item-main {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .eipsi-pool-item-status {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .eipsi-pool-item-status.eipsi-status--active {
        background: #22c55e;
    }

    .eipsi-pool-item-status.eipsi-status--inactive {
        background: #f59e0b;
    }

    .eipsi-pool-item-status.eipsi-status--paused {
        background: #ef4444;
    }

    .eipsi-pool-item-name {
        font-size: 13px;
        font-weight: 500;
        color: var(--eipsi-gray-800, #1e293b);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .eipsi-pool-item-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-left: 16px;
    }

    .eipsi-pool-item-count {
        font-size: 11px;
        color: var(--eipsi-gray-600, #64748b);
    }

    .eipsi-pool-item-arrow {
        font-size: 14px;
        color: var(--eipsi-blue, #3B6CAA);
    }

    /* Footer */
    .eipsi-pool-sidebar-footer {
        padding: 12px 20px;
        border-top: 1px solid var(--eipsi-gray-200, #e2e8f0);
        background: rgba(0,0,0,0.02);
    }

    .eipsi-pool-total-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
    }

    .eipsi-pool-total-label {
        color: var(--eipsi-gray-600, #64748b);
    }

    .eipsi-pool-total-value {
        font-weight: 600;
        color: var(--eipsi-gray-800, #1e293b);
    }

    /* Responsive */
    @media (max-width: 960px) {
        .eipsi-pool-sidebar {
            width: 100%;
            max-height: 200px;
            border-right: none;
            border-bottom: 1px solid var(--eipsi-gray-200, #e2e8f0);
        }
    }
</style>

<script>
(function() {
    'use strict';
    
    // Search functionality
    const searchInput = document.getElementById('eipsi-pool-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            const items = document.querySelectorAll('.eipsi-pool-list-item');
            
            items.forEach(function(item) {
                const name = item.querySelector('.eipsi-pool-item-name').textContent.toLowerCase();
                if (name.indexOf(term) > -1) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
})();
</script>
