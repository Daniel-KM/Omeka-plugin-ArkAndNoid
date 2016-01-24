<p class="explanation">
<?php
    echo __('Ark allows to creates and manages unique, universel and persistent ark identifiers.') . '<br />';
    echo __('See %s and %sthe official help%s for more informations.',
        '<a href="https://github.com/Daniel-KM/ArkForOmeka">ReadMe</a>',
        '<a href="https://confluence.ucop.edu/display/Curation/ARK">', '</a>');
?>
</p>
<p class="explanation"><strong><?php echo __('Warning'); ?></strong>
    <?php echo __('Once set and arks made public, it is not recommended to change these parameters in order to keep the consistency and the sustainability of the names.'); ?>
    <?php echo __('Anyway, once created, an ark is never modified, even if these parameters are changed, unless the ark is manually removed from the record.'); ?>
</p>
<fieldset id="fieldset-ark-institution"><legend><?php echo __('Institution'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_naan', __('Name Assigning Authority Number (NAAN)')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_naan', get_option('ark_naan'), null); ?>
            <p class="explanation">
                <?php echo __('This required unique number, usually with five digits, is assigned for free by the California Digital Library to any institution with a historical or archival purposes.'); ?>
                <?php echo __('The naan "12345" is a special one and serves for example purposes and "99999" is for test purposes.'); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-ark-format"><legend><?php echo __('Identifier'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_format_name', __('Format of name')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php
                echo $this->formRadio('ark_format_name', get_option('ark_format_name'), null, $format_names);
            ?>
            <p class="explanation">
                <?php echo __('Select the format used to create arks.'); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-ark-format-parameters"><legend><i><?php echo __('Parameters'); ?></i></legend>
    <p class="explanation">
        <?php echo __('These parameters may be used by internal formats.'); ?>
    </p>
    <p class="explanation">
        <?php echo __('Prefixes and suffixes are a single character or a short string to prepend or to append to the main part.'); ?>
        <?php echo __('When set, the length should be set too.'); ?>
    </p>
    <p class="explanation">
        <strong><?php echo __('Warning:'); ?></strong>
        <?php echo __('When prefixes or suffixes are added, it is recommended to use a salt to avoid collisions, or to check arks with various collection and item ids.'); ?>
    </p>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_prefix', __('Main prefix')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_prefix', get_option('ark_prefix'), null); ?>
            <p class="explanation">
                <?php echo __('This optional field allows to identify all records used in Omeka, in particular when ark is used somewhere else.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_prefix_collection', __('Prefix for collections')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_prefix_collection', get_option('ark_prefix_collection'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_prefix_item', __('Prefix for items')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_prefix_item', get_option('ark_prefix_item'), null); ?>
            <p>
                <?php echo __('When prefixes and suffixes of collections and items are the same, the first letter of the alphabet is automatically prepended to the collection name, when needed.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_suffix', __('Main suffix')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_suffix', get_option('ark_suffix'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_suffix_collection', __('Suffix for collections')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_suffix_collection', get_option('ark_suffix_collection'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_suffix_item', __('Suffix for items')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_suffix_item', get_option('ark_suffix_item'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_length', __('Length of the name')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_length', get_option('ark_length'), null); ?>
            <p class="explanation">
                <?php echo __('The length of the name (main part of the identifier, without prefix, suffix and control key).'); ?>
                <?php echo __('A length of three alphanumeric characters is enough to create 200000 arks.'); ?>
                <?php echo __('A length of four lower-case alphanumeric characters is enough to create more than 1600000 arks.'); ?>
                <?php echo __("If the format creates a longer name, it won't be cut, except if it is hashed."); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_pad', __('Pad to prepend')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_pad', get_option('ark_pad'), null); ?>
            <p class="explanation">
                <?php echo __('The string pad to use when a specific length is required (generally "0" or "a").'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_salt', __('Salt to use')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_salt', get_option('ark_salt'), null); ?>
            <p class="explanation">
                <?php echo __('A salt can be used to randomize the ark.'); ?>
                <?php echo __('It is recommended to set a long meaningless string.'); ?>
                <?php echo __('Leave empty to not use one.'); ?>
                <?php $previousSalts = get_option('ark_previous_salts');
                    if ($previousSalts) {
                        echo __('Previous salts:');
                        echo '<ul><li>' . str_replace(PHP_EOL, '</li><li>', $previousSalts) . '</li></ul>';
                    }
                ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_alphabet', __('Alphabet')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php
            $options = array(
                'numeric' => __('Numeric'),
                'hexadecimal' => __('Hexadecimal'),
                'alphabetic' => __('Alphabetic'),
                'lower_case_alphabetic' => __('Lower case alphabetic'),
                'upper_case_alphabetic' => __('Upper case alphabetic'),
                'lower_case_alphabetic_no_vowel' => __('Lower case alphabetic, without vowel'),
                'upper_case_alphabetic_no_vowel' => __('Upper case alphabetic, without vowel'),
                'alphanumeric' => __('Alphanumeric'),
                'lowercase_alphanumeric' => __('Lower case alphanumeric'),
                'uppercase_alphanumeric' => __('Upper case alphanumeric'),
                'alphanumeric_no_vowel' => __('Alphanumeric, without vowels'),
                'alphanumeric_no_vowel_no_l' => __('Alphanumeric, without vowels and "l"'),
                'lowercase_alphanumeric_no_vowel' => __('Lower case alphanumeric, without vowels'),
                'lowercase_alphanumeric_no_vowel_no_l' => __('Lower case alphanumeric, without vowels and "l"'),
                'uppercase_alphanumeric_no_vowel' => __('Upper case alphanumeric, without vowels'),
            );
            echo $this->formSelect('ark_alphabet', get_option('ark_alphabet'), array(), $options); ?>
            <p class="explanation">
                <?php echo __('Select the alphabet used to create arks.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_control_key', __('Control character')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formCheckbox('ark_control_key', true, array('checked' => (boolean) get_option('ark_control_key'))); ?>
            <p class="explanation">
                <?php echo __('If checked, a character will be appended to the identifier to check quickly if the identifier is valid.'); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-ark-external-options"><legend><i><?php echo __('External Formats'); ?></i></legend>
    <p class="explanation">
        <?php echo __('External formats may or may not use other parameters.' ); ?>
    </p>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_command', __('External command')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_command', get_option('ark_command'), null); ?>
            <p class="explanation">
                <?php echo __('This parameter will be passed to the external processor.'); ?>
                <?php echo __('For NOID, the external command is generally "/usr/bin/noid mint 1" and none of the parameters above is passed.'); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-ark-qualifier"><legend><?php echo __('Qualifier'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_format_qualifier', __('File naming')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php
                echo $this->formSelect('ark_format_qualifier', get_option('ark_format_qualifier'), array(), $format_qualifiers);
            ?>
            <p class="explanation">
                <?php echo __('The ark of a file can be its order, its internal id, its filename or a custom value.'); ?>
                <?php echo __('If filenames are used, they should be unique.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_file_variants', __('Derivative files as variants')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_file_variants', get_option('ark_file_variants'), null); ?>
            <p class="explanation">
                <?php echo __('List the derivatives files that will be served as variant of a file.'); ?>
                <?php echo __('The url for these variants can be built with the standard function "%s" or via the route "ark_file_variant".', "record_url(\$file, 'original')"); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-ark-inflections"><legend><?php echo __('Inflections'); ?></legend>
    <p class="explanation">
        <?php echo __('When a "?" or a "??" is appended to an ark, a specific content may appear.'); ?>
        <?php echo __('It should comply with specifications.'); ?>
    </p>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_note', __('Note')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formTextarea('ark_note', get_option('ark_note'), array('rows' => 2)); ?>
            <p class="explanation">
                <?php echo __('Adding "/?" to the end of an ark will return a brief machine- and eye-readable metadata record using the Dublin Core Kernel.'); ?>
                <?php echo __('This field allows to add a short note to it.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_policy_statement', __('Policy statement')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formTextarea('ark_policy_statement', get_option('ark_policy_statement'), array('rows' => 5)); ?>
            <p class="explanation">
                <?php echo __('Adding "/??" to the end of an ark will return this policy statement.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_policy_main', __('Main Policy')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formTextarea('ark_policy_main', get_option('ark_policy_main'), array('rows' => 20)); ?>
            <p class="explanation">
                <?php echo __('Adding "/?" after the naan will return this main policy statement.'); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-ark-interfarce"><legend><?php echo __('Interface'); ?></legend>
    <p class="explanation">
        <?php echo __('If any, the ark can be used in place of the items/show/:id.'); ?>
        <?php echo __('The ark will not be used for other links (edit, delete...).'); ?>
        <?php echo __('The ark will not be used for urls that are built manually in the theme (without the function url() or record_url()).'); ?>
<?php if (plugin_is_active('CleanUrl')): ?>
        <br />
        <?php echo __('By default, the ark url is used before the clean url.'); ?>
        <?php echo __('To use the latter by default, uncheck these options and update the Clean Url ones.'); ?>
<?php endif; ?>
    </p>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_use_public', __('Public interface')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formCheckbox('ark_use_public', true, array('checked' => (boolean) get_option('ark_use_public'))); ?>
            <p class="explanation">
                <?php echo __('If checked, the ark will be used in the public interface, if any.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_use_admin', __('Admin interface')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formCheckbox('ark_use_admin', true, array('checked' => (boolean) get_option('ark_use_admin'))); ?>
            <p class="explanation">
                <?php echo __('If checked, the ark will be used in the admin interface, if any.'); ?>
            </p>
        </div>
    </div>
    <p class="explanation">
        <?php echo __('In the record, the metadata itself can be formatted.'); ?>
        <?php echo __('Use any string with one or more "%1$s" for the ark itself.'); ?>
        <?php echo __('Example: "&lt;a href="https://example.org/%1$s"&gt;%1$s&lt;/a&gt;"'); ?>
    </p>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_display_public', __('Format in public records')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_display_public', get_option('ark_display_public'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_display_admin', __('Format in admin records')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_display_admin', get_option('ark_display_admin'), null); ?>
        </div>
    </div>
</fieldset>
