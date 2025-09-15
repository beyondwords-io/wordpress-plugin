<?php

namespace Beyondwords\Wordpress\Core\Player\Renderer;

use Beyondwords\Wordpress\Component\Settings\Fields\PlayerUI\PlayerUI;
use Beyondwords\Wordpress\Core\Environment;
use Beyondwords\Wordpress\Core\Player\ConfigBuilder;

/**
 * Class Javascript.
 *
 * Responsible for rendering the JavaScript BeyondWords player.
 */
class Javascript extends Base
{
    /**
     * Render the JavaScript player HTML.
     *
     * @param \WP_Post $post
     * @return string HTML output.
     */
    public static function render($post): string
    {
        if (PlayerUI::DISABLED === get_option(PlayerUI::OPTION_NAME)) {
            return '';
        }

        $params = ConfigBuilder::build($post);

        $jsonParams = wp_json_encode($params, JSON_UNESCAPED_SLASHES);
        $jsonParams = sprintf('{target:this, ...%s}', $jsonParams);

        $onload = sprintf('new BeyondWords.Player(%s);', $jsonParams);
        $onload = apply_filters('beyondwords_player_script_onload', $onload, $params);

        return sprintf(
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
            '<script async defer src="%s" onload=\'%s\'></script>',
            Environment::getJsSdkUrl(),
            $onload
        );
    }
}
