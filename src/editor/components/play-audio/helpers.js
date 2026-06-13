/**
 * Whether PlayAudio will render a player for the current post.
 *
 * This is the single source of truth for PlayAudio's visibility: PlayAudioCheck
 * gates the player on it, and PreviewPanel uses it to decide whether to show the
 * "Preview" panel at all — so the panel never appears empty when PlayAudio would
 * render nothing.
 *
 * A player needs a project id (`beyondwords_project_id`), plus a content id when
 * the integration method is the REST API. Posts still in `pending` review never
 * preview. Legacy `beyondwords_podcast_id` / `speechkit_podcast_id` content-id
 * keys are honoured so posts upgraded from older plugin versions still preview.
 *
 * @param {Function} select Redux-style select() from `@wordpress/data`.
 *
 * @return {boolean} True when PlayAudio has a player to render.
 */
export function canPlayAudio( select ) {
	const { getEditedPostAttribute } = select( 'core/editor' );

	const meta = getEditedPostAttribute( 'meta' ) || {};

	const status = getEditedPostAttribute( 'status' );
	const projectId = meta.beyondwords_project_id;
	const integrationMethod = meta.beyondwords_integration_method;

	// Get Content ID, inc fallbacks for legacy field names.
	const contentId =
		meta.beyondwords_content_id ||
		meta.beyondwords_podcast_id ||
		meta.speechkit_podcast_id;

	const isClientSide = integrationMethod === 'client-side';

	const hasClientSideContent = isClientSide && projectId;

	const hasRestApiContent = ! isClientSide && projectId && contentId;

	return Boolean(
		status !== 'pending' && ( hasClientSideContent || hasRestApiContent )
	);
}
