<div class="wrap">

    <div id="icon-options-general" class="icon32"><br/></div>
    <h2>cXense 4 WordPress</h2>

    <form method="post" action="options.php">

        <?php
        settings_fields('cxense-settings');
        do_settings_sections('cxense-settings');

        $languages = cxense_get_languages();

        if(!$languages) {
            $languages = [
                new stdClass()
            ];
            $language[0]->locale = '';
        }

        ?>

        <?php foreach ($languages as $language) {
            $localePostfix = empty($language->locale) ? '' : '_' . $language->locale;
        ?>

            <div id="icon-options-general" class="icon32"><br/></div>
            <h2>Widget IDs <?php echo $localePostfix ?></h2>

            <div id="cxense_widgets<?php echo $localePostfix ?>">
                <table class="widefat">

                <?php if( $widget_opts = cxense_get_opt('cxense_widgets_options' . $localePostfix) ){
                    $i = 0;
                    foreach ($widget_opts as $widget) {
                        ?>
                        <tr>
                            <td>
                                <input style="width: 100%" type="button" class="button-secondary" value="Remove" onclick="CxenseAdmin.removeWidget(jQuery(this).parent().parent())"/>
                            </td>
                            <td style="width: 45%">
                                <input style="width: 100%" type="text" name="cxense_widgets_options<?php echo $localePostfix ?>[<?= $i ?>][key]" value="<?= $widget['key'] ?>" />
                            </td>
                            <td style="width: 45%">
                                <input style="width: 100%" type="text" name="cxense_widgets_options<?php echo $localePostfix ?>[<?= $i ?>][widget_id]" value="<?= $widget['widget_id'] ?>" />
                            </td>
                            <br/>
                        </tr>
                        <?php
                        $i++;
                    }
                }
                ?>
                </table>
            </div>

            <input
                type="button"
                class="button-secondary"
                value="Add widget"
                onclick="CxenseAdmin.addWidget(
                    '<?php echo 'cxense_widgets_options' . $localePostfix ?>',
                    '<?php echo 'cxense_widgets' . $localePostfix ?>'
                    )"
                style="margin-top: 12px; margin-bottom: 5px"
            />

        <?php } ?>

        <?php submit_button(); ?>

    </form>
</div>
