import { __ } from '@wordpress/i18n';

export default function TimingTable( {
	pages,
	totalTime,
	showTimingAnalysis,
} ) {
	if ( ! showTimingAnalysis || ! pages || pages.length === 0 ) {
		return null;
	}

	return (
		<div className="timing-analysis-panel">
			<div className="timing-summary">
				<h4>{ __( '⏱️ Tiempos por Página', 'eipsi-forms' ) }</h4>
				<p className="timing-total">
					{ __( '⏰ Tiempo total:', 'eipsi-forms' ) } { totalTime }{ ' ' }
					sec
				</p>
			</div>

			<table className="timing-table">
				<thead>
					<tr>
						<th>{ __( 'Página', 'eipsi-forms' ) }</th>
						<th>{ __( 'Duración', 'eipsi-forms' ) }</th>
						<th>{ __( 'Timestamp', 'eipsi-forms' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ pages.map( ( page, index ) => (
						<tr key={ index }>
							<td>{ page.name }</td>
							<td>{ page.duration }</td>
							<td>{ page.timestamp }</td>
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
}
