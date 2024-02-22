/* global $e */

// Example create and register new command.
// Important: Available to run in the console does not depends on anything else.

export class PanelOpen extends $e.modules.CommandBase {
	apply( args ) {
		return {
			'panel-open': {
				args,
			},
		};
	}
}
