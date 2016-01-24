<?php
/**
 * Ark
 *
 * Creates and manages unique, universel and persistent ark identifiers.
 *
 * @copyright Daniel Berthereau, 2015-2016
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */

/**
 * The Ark plugin.
 * @package Omeka\Plugins\Ark
 */
class ArkPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array This plugin's hooks.
     */
    protected $_hooks = array(
        'initialize',
        'install',
        'uninstall',
        'config_form',
        'config',
        'define_routes',
        'after_save_collection',
        'after_save_item',
        // TODO Save ark for files?
    );

    /**
     * @var array This plugin's filters.
     */
    protected $_filters = array(
        'ark_format_names',
        'ark_format_qualifiers',
        'filterDisplayCollectionDublinCoreIdentifier' => array('Display', 'Collection', 'Dublin Core', 'Identifier'),
        'filterDisplayItemDublinCoreIdentifier' => array('Display', 'Item', 'Dublin Core', 'Identifier'),
    );

    /**
     * @var array This plugin's options.
     */
    protected $_options = array(
        // 12345 means example and 99999 means test.
        'ark_naan' => '99999',
        'ark_format_name' => 'omeka_id',
        'ark_prefix' => '',
        'ark_prefix_collection' => '',
        'ark_prefix_item' => '',
        'ark_suffix' => '',
        'ark_suffix_collection' => '',
        'ark_suffix_item' => '',
        'ark_length' => 4,
        'ark_pad' => '0',
        'ark_salt' => 'RaNdOm SaLt',
        'ark_previous_salts' => '',
        'ark_alphabet' => 'alphanumeric_no_vowel',
        'ark_control_key' => true,
        'ark_command' => '',
        'ark_format_qualifier' => 'order',
        'ark_file_variants' => 'original fullsize thumbnail square_thumbnail',
        'ark_note' => '',
        'ark_policy_statement' => 'erc-support:
who: Our Institution
what: Permanent: Stable Content:
when: 20160101
where: http://example.com/ark:/99999/',
        // From the policy statement of the California Digital Library.
        'ark_policy_main' => 'Our institution assigns identifiers within the ARK domain under the NAAN 99999 and according to the following principles:

* No ARK shall be re-assigned; that is, once an ARK-to-object association has been made public, that association shall be considered unique into the indefinite future.
* To help them age and travel well, the Name part of our institution-assigned ARKs shall contain no widely recognizable semantic information (to the extent possible).
* Our institution-assigned ARKs shall be generated with a terminal check character that guarantees them against single character errors and transposition errors.',
        'ark_use_public' => true,
        'ark_use_admin' => false,
        'ark_display_public' => '<a href="WEB_ROOT/%1$s">%1$s</a>',
        'ark_display_admin' => '<a href="WEB_ROOT/admin/%1$s">%1$s</a>',
    );

    /**
     * Add the translations and shortcode.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
        add_shortcode('ark', array($this, 'shortcodeArk'));
    }

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $length = 32;
        $this->_options['ark_salt'] = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
        $this->_options['ark_display_public'] = str_replace('WEB_ROOT', WEB_ROOT, $this->_options['ark_display_public']);
        $this->_options['ark_display_admin'] = str_replace('WEB_ROOT', WEB_ROOT, $this->_options['ark_display_admin']);

        $this->_installOptions();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial(
            'plugins/ark-config-form.php',
            array(
                'format_names' => $this->_getListOfFormats('ark_format_names'),
                'format_qualifiers' => $this->_getListOfFormats('ark_format_qualifiers'),
            )
        );
    }

    /**
     * Saves plugin configuration page.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];

        // Special check for prefix/suffix.
        $format = $post['ark_format_name'];

        // Check the parameters for the format.
        $format = $post['ark_format_name'];
        $parameters = array(
            'naan' => $post['ark_naan'],
            'prefix' => $post['ark_prefix'] . $post['ark_prefix_collection'] . $post['ark_prefix_item'],
            'suffix' => $post['ark_suffix'] . $post['ark_suffix_collection'] . $post['ark_suffix_item'],
            'length' => $post['ark_length'],
            'pad' => $post['ark_pad'],
            'salt' => $post['ark_salt'],
            'alphabet' => $post['ark_alphabet'],
            'control_key' => $post['ark_control_key'],
            'command' => $post['ark_command'],
            // This value is used only to check if a zero may be prepended for
            // collections with the Omeka Id format.
            'identifix' => $post['prefix_collection'] === $post['prefix_item']
                && $post['suffix_collection'] === $post['suffix_item'],
        );

        try {
            $processor = $this->_getArkProcessor($format, null, $parameters);
        } catch (Ark_ArkException $e) {
            throw new Omeka_Validate_Exception($e->getMessage());
        }

        // Save the previous salt if needed.
        $salt = get_option('ark_salt');
        $previousSalts = get_option('ark_previous_salts');

        foreach ($this->_options as $optionKey => $optionValue) {
            if (isset($post[$optionKey])) {
                set_option($optionKey, $post[$optionKey]);
            }
        }

        // Save the previous salt if needed.
        $newSalt = get_option('ark_salt');
        if ($newSalt !== $salt && strlen($newSalt) > 0) {
            set_option('ark_previous_salts', $previousSalts . PHP_EOL . $newSalt);
        }
    }

    /**
     * Defines routes.
     */
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];
        $router->addConfig(new Zend_Config_Ini(dirname(__FILE__) . '/routes.ini', 'routes'));

        // Add a main policy route.
        $router->addRoute('ark_policy_short', new Zend_Controller_Router_Route(
            'ark/policy',
            array(
                'module' => 'ark',
                'controller' => 'index',
                'action' => 'policy',
                'naan' => get_option('ark_naan'),
            )
        ));
    }

    /**
     * Create or check an ark when a collection is saved, with the id.
     */
    public function hookAfterSaveCollection($args)
    {
        $this->_addArk($args['record']);
    }

    /**
     * Create or check an ark when an item is saved, with the id.
     */
    public function hookAfterSaveItem($args)
    {
        $this->_addArk($args['record']);
    }

    /**
     * Add an ark to a record, if needed.
     *
     * @param Record $record
     * @return void
     */
    protected function _addArk($record)
    {
        // Check if an ark exists (no automatic change or update), else create.
        $ark = get_view()->ark($record);
        if (empty($ark)) {
            $format = get_option('ark_format_name');
            $recordType = get_class($record);

            try {
                $ark = $this->_getArkProcessor($format, $recordType);
            } catch (Ark_ArkException $e) {
                _log('[Ark] ' . __($e->getMessage()));
                throw $e;
            }

            $ark = $ark->create($record);
            if ($ark) {
                $element = $record->getElement('Dublin Core', 'Identifier');

                $elementText = new ElementText();
                $elementText->element_id = $element->id;
                $elementText->record_type = $recordType;
                $elementText->record_id = $record->id;
                $elementText->html = false;
                $elementText->setText($ark);
                $elementText->save();
            }
        }
    }

    /**
     * Add the formats that are available for names.
     *
     * @param array $formatNames Array of formats for names.
     * @return array Filtered formats array.
    */
    public function filterArkFormatNames($formatNames)
    {
        // Available default formats in the plugin.
        $formatNames['omeka_id'] = array(
            'class' => 'Ark_Name_OmekaId',
            'description' => __('Omeka Id'),
        );
        $formatNames['command'] = array(
            'class' => 'Ark_Name_Command',
            'description' => __('Command, like NOID'),
        );
        return $formatNames;
    }

    /**
     * Add the formats that are available for qualifiers.
     *
     * @param array $formatQualifiers Array of formats for qualifiers.
     * @return array Filtered formats array.
    */
    public function filterArkFormatQualifiers($formatQualifiers)
    {
        // Available default formats in the plugin.
        $formatQualifiers['omeka_id'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Omeka Id'),
        );
        $formatQualifiers['order'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Order'),
        );
        $formatQualifiers['filename'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Omeka filename'),
        );
        $formatQualifiers['filename_without_extension'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Omeka filename without extension'),
        );
        $formatQualifiers['original_filename'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Original filename'),
        );
        $formatQualifiers['original_filename_without_extension'] = array(
            'class' => 'Ark_Qualifier_Internal',
            'description' => __('Original filename without extension'),
        );
        return $formatQualifiers;
    }

    /**
     * Get the simple list of formats (name and description).
     *
     * @param string $filter Name of the filter.
     * @return array Associative array of the name and description of formats.
     */
    protected function _getListOfFormats($filter)
    {
        $values = apply_filters($filter, array());
        foreach ($values as $name => &$value) {
            if (class_exists($value['class'])) {
                $value = $value['description'];
            }
            else {
                unset($values[$name]);
            }
        }
        return $values;
    }

    /**
     * Filter for metadata.
     *
     * @param string $text
     * @param array $args
     * @return string
     */
    public function filterDisplayCollectionDublinCoreIdentifier($text, $args)
    {
        return $this->_displayArkIdentifier($text, $args);
    }

    /**
     * Filter for metadata.
     *
     * @param string $text
     * @param array $args
     * @return string
     */
    public function filterDisplayItemDublinCoreIdentifier($text, $args)
    {
        return $this->_displayArkIdentifier($text, $args);
    }

    /**
     * Filter the ark to display an url.
     *
     * @param string $text
     * @param array $args
     * @return string The filtered ark.
     */
    protected function _displayArkIdentifier($text, $args)
    {
        $arkDisplay = is_admin_theme()
            ? get_option('ark_display_admin')
            :  get_option('ark_display_public');

        if (empty($arkDisplay)) {
            return $text;
        }

        // Ark is the slowest check, so it's done later.
        $ark = get_view()->ark($args['record']);
        if ($text != $ark) {
            return $text;
        }

        return sprintf($arkDisplay, $text);
    }

    /**
     * Shortcode to display the ark of a record.
     *
     * @param array $args
     * @param Omeka_View $view
     * @return string
     */
    public function shortcodeArk($args, $view)
    {
        // Check required arguments
        if (empty($args['record_id'])) {
            return '';
        }
        $recordId = (integer) $args['record_id'];

        $recordType = isset($args['record_type']) ? $args['record_type'] : 'Item';
        $recordType = ucfirst(strtolower($recordType));

        // Quick checks.
        $record = get_record_by_id($recordType, $recordId);
        if (!$record) {
            return '';
        }

        // Get display values (link or text).
        $display = isset($args['display']) ? $args['display'] : null;

        return $view->ark($record, $display);
    }

    /**
     * Return the selected processor or throw an error.
     *
     * @param string $format
     * @param string|null $recordType
     * @return Ark_Name class.
     */
    protected function _getArkProcessor($format, $recordType = null, $parameters = array())
    {
        $formats = apply_filters('ark_format_names', array());

        // Check the selected format (avoid issue for extra plugin class).
        if (!isset($formats[$format])) {
            throw new Ark_ArkException(__('Ark format for names "%s" is missing.', $format));
        }

        $class = $formats[$format]['class'];
        if (!class_exists($class)) {
            throw new Ark_ArkException(__('Ark class "%s" is missing.', $class));
        }

        if (empty($parameters)) {
            // A check is automatically done internally.
            if (is_null($recordType)) {
                $prefix = get_option('ark_prefix') . get_option('ark_prefix_collection') . get_option('ark_prefix_item') ;
                $suffix = get_option('ark_suffix') . get_option('ark_suffix_collection') . get_option('ark_suffix_item');
            }
            // Adding an ark to a specific record.
            else {
                $recordType = strtolower($recordType);
                $prefix = get_option('ark_prefix') . get_option('ark_prefix_' . $recordType);
                $suffix = get_option('ark_suffix') . get_option('ark_suffix_' . $recordType);
            }

            $identifix = get_option('ark_prefix_collection') === get_option('ark_prefix_item')
                && get_option('ark_suffix_collection') === get_option('ark_suffix_item');

            $parameters = array(
                'naan' => get_option('ark_naan'),
                'prefix' => $prefix,
                'suffix' => $suffix,
                'length' => get_option('ark_length'),
                'pad' => get_option('ark_pad'),
                'salt' => get_option('ark_salt'),
                'alphabet' => get_option('ark_alphabet'),
                'control_key' => get_option('ark_control_key'),
                'command' => get_option('ark_command'),
                // This value is used only to check if a zero may be prepended
                // for collections with the Omeka Id format.
                'identifix' => $identifix,
            );
        }

        try {
            $arkProcessor = new $class($parameters);
        } catch (Ark_ArkException $e) {
            throw new Ark_ArkException(__('Parameters are incorrect: %s', $e->getMessage()));
        }
        return $arkProcessor;
    }
}
