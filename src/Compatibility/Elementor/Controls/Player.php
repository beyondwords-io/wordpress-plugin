<?php
// phpcs:disable PSR1.Methods.CamelCapsMethodName
// phpcs:disable WordPressVIPMinimum.TemplatingEngines.UnescapedOutputMustache.OutputNotation

namespace Beyondwords\Wordpress\Compatibility\Elementor\Controls;

use Elementor\Base_Control;
use Elementor\Controls_Manager;

use Beyondwords\Wordpress\Component\Post\PostMetaUtils;

/**
 * Elementor BeyondWords Player control.
 *
 * @codeCoverageIgnore
 *
 * @since 3.10.0
 */
class Player extends Base_Control
{
    public function get_type()
    {
        return 'beyondwords_player';
    }

    public function get_default_settings()
    {
        $postId = get_the_ID();

        return [
            'label_block' => true,
            'separator' => 'before',
            'post_id' => $postId,
            'project_id' => null,
            'content_id' => null,
            'generate_audio' => null,
            'display_player' => null,
        ];
    }

    public function content_template()
    {
        // phpcs:disable
        ?>
        <div class="elementor-control-field">
            <div class="elementor-control-input-wrapper elementor-control-unit-5">
                <div id="beyondwords-elementor-player" data-post-id="{{ data.post_id }}">
                    <div
                        id="beyondwords-elementor-editor-player"
                        data-beyondwords-player="true"
                        data-beyondwords-project-id="{{ data.project_id }}"
                        data-beyondwords-content-id="{{ data.content_id }}"
                        contenteditable="false"
                    ></div>
                </div>

                <script type="text/javascript">
                    if (window.$e) {
                        window.$e.run('beyondwords/panel-open');
                    }
                </script>
            </div>
        </div>
        <?php
        // phpcs:enable
    }
}
