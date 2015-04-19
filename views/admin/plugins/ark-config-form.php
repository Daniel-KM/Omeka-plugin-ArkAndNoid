<p>
<?php
    echo __('Ark allows to creates and manages unique, universel and persistent ark identifiers.') . '<br />';
    echo __('See %s and %sthe official help%s for more informations.',
        '<a href="https://github.com/Daniel-KM/Ark4Omeka">ReadMe</a>',
        '<a href="https://confluence.ucop.edu/display/Curation/ARK">', '</a>');
?>
</p>
<p><strong><?php echo __('Warning'); ?></strong>
    <?php echo __('Once set and arks made public, it is not recommended to change these parameters in order to keep the consistency and the sustainability of the names.'); ?>
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
                <?php echo __('The naan "12345" is a special one and serves for example purposes.'); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-ark"><legend><?php echo __('Identifiers'); ?></legend>
    <p class="explanation">
        <?php echo __('When created, an ark is never modified, even if these parameters are changed, unless the ark is manually removed from the record.'); ?>
    </p>
    <div class="field">    <div class="field">
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
    <p>
        <?php echo __('Below parameters can be used by some formats.'); ?>
    </p>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_prefix_collection', __('Prefix for collections')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_prefix_collection', get_option('ark_prefix_collection'), null); ?>
            <p class="explanation">
                <?php echo __('This optional field simplifies management of collections identifiers inside an institution.'); ?>
                <?php echo __('It can be a single character or longer.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_suffix_collection', __('Suffix for collections')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_suffix_collection', get_option('ark_suffix_collection'), null); ?>
            <p class="explanation">
                <?php echo __('This optional field allows to extend the name of the identifier for collections.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_prefix_item', __('Prefix for items')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_prefix_item', get_option('ark_prefix_item'), null); ?>
            <p class="explanation">
                <?php echo __('This optional field simplifies management of items identifiers inside an institution.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_suffix_item', __('Suffix for items')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_suffix_item', get_option('ark_suffix_item'), null); ?>
            <p class="explanation">
                <?php echo __('This optional field allows to extend the name of the identifier for items.'); ?>
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
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_length', __('Length of the identifier')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_length', get_option('ark_length'), null); ?>
            <p class="explanation">
                <?php echo __('The length of the identifier (without prefix, suffix and control key).'); ?>
                <?php echo __('A length of three alphanumeric characters is enough to create 200000 arks.'); ?>
                <?php echo __('A length of four lower-case alphanumeric characters is enough to create more than 1000000 arks.'); ?>
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
    <div class="field">    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('ark_options', __('Other options')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formText('ark_options', get_option('ark_options'), null); ?>
            <p class="explanation">
                <?php echo __('These options will be passed to the name processor.'); ?>
                <?php echo __('To use an external command, the full command should be set as an option below.'); ?>
                <?php echo __('For example, for Noid, the command can be : "/usr/bin/noid mint 1".'); ?>
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
