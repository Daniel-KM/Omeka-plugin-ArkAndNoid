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
        'upgrade',
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
        'ark_protocol' => 'ark:',
        // 12345 means example and 99999 means test.
        'ark_naan' => '99999',
        'ark_format_name' => 'omeka_id',
        'ark_id_prefix' => '',
        'ark_id_prefix_collection' => '',
        'ark_id_prefix_item' => '',
        'ark_id_suffix' => '',
        'ark_id_suffix_collection' => '',
        'ark_id_suffix_item' => '',
        'ark_id_length' => 4,
        'ark_id_pad' => '0',
        'ark_id_salt' => 'RaNdOm SaLt',
        'ark_id_previous_salts' => '',
        'ark_id_alphabet' => 'alphanumeric_no_vowel',
        'ark_id_control_key' => true,
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
        'ark_routes_ini' => false,
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
     * Upgrade the plugin.
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        $db = $this->_db;

        if (version_compare($oldVersion, '2.4', '<')) {
            delete_option('ark_allow_short_urls');
            set_option('ark_protocol', $this->_options['ark_protocol']);
            set_option('ark_id_prefix', get_option('ark_prefix'));
            delete_option('ark_prefix');
            set_option('ark_id_prefix_collection', get_option('ark_prefix_collection'));
            delete_option('ark_prefix_collection');
            set_option('ark_id_prefix_item', get_option('ark_prefix_item'));
            delete_option('ark_prefix_item');
            set_option('ark_id_suffix', get_option('ark_suffix'));
            delete_option('ark_suffix');
            set_option('ark_id_suffix_collection', get_option('ark_suffix_collection'));
            delete_option('ark_suffix_collection');
            set_option('ark_id_suffix_item', get_option('ark_suffix_item'));
            delete_option('ark_suffix_item');
            set_option('ark_id_length', get_option('ark_length'));
            delete_option('ark_length');
            set_option('ark_id_pad', get_option('ark_pad'));
            delete_option('ark_pad');
            set_option('ark_id_salt', get_option('ark_salt'));
            delete_option('ark_salt');
            set_option('ark_id_previous_salts', get_option('ark_previous_salts'));
            delete_option('ark_previous_salts');
            set_option('ark_id_alphabet', get_option('ark_alphabet'));
            delete_option('ark_alphabet');
            set_option('ark_id_control_key', get_option('ark_control_key'));
            delete_option('ark_control_key');
        }
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
            'protocol' => $post['ark_protocol'],
            'naan' => $post['ark_naan'],
            'prefix' => $post['ark_id_prefix'] . $post['ark_id_prefix_collection'] . $post['ark_id_prefix_item'],
            'suffix' => $post['ark_id_suffix'] . $post['ark_id_suffix_collection'] . $post['ark_id_suffix_item'],
            'length' => $post['ark_id_length'],
            'pad' => $post['ark_id_pad'],
            'salt' => $post['ark_id_salt'],
            'alphabet' => $post['ark_id_alphabet'],
            'control_key' => $post['ark_id_control_key'],
            'command' => $post['ark_command'],
            // This value is used only to check if a zero may be prepended for
            // collections with the Omeka Id format.
            'identifix' => $post['ark_id_prefix_collection'] === $post['ark_id_prefix_item']
                && $post['ark_id_suffix_collection'] === $post['ark_id_suffix_item'],
        );

        try {
            $processor = $this->_getArkProcessor($format, null, $parameters);
        } catch (Ark_ArkException $e) {
            throw new Omeka_Validate_Exception($e->getMessage());
        }

        // Save the previous salt if needed.
        $salt = get_option('ark_id_salt');
        $previousSalts = get_option('ark_id_previous_salts');

        // Clean the file variants.
        $post['ark_file_variants'] = preg_replace('/\s+/', ' ', trim($post['ark_file_variants']));

        foreach ($this->_options as $optionKey => $optionValue) {
            if (isset($post[$optionKey])) {
                set_option($optionKey, $post[$optionKey]);
            }
        }

        // Save the previous salt if needed.
        $newSalt = get_option('ark_id_salt');
        if ($newSalt !== $salt && strlen($newSalt) > 0) {
            set_option('ark_id_previous_salts', $previousSalts . PHP_EOL . $newSalt);
        }
    }

    /**
     * Defines routes.
     */
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];

        if (get_option('ark_routes_ini')) {
            $router->addConfig(new Zend_Config_Ini(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'routes.ini', 'routes'));
            return;
        }

        $protocol = get_option('ark_protocol');
        if (empty($protocol)) {
            return;
        }

        // Routes is different with "ark", because there is a naan.
        if ($protocol == 'ark:') {
            $naan = get_option('ark_naan');
            if (empty($naan)) {
                return;
            }

            $router->addRoute('ark_policy', new Zend_Controller_Router_Route(
                "$protocol/$naan/",
                array(
                    'module' => 'ark',
                    'controller' => 'index',
                    'action' => 'policy',
                    'naan' => $naan,
            )));

            // Two non standard routes for ark.
            $router->addRoute('ark_policy_short', new Zend_Controller_Router_Route(
                'ark/policy',
                array(
                    'module' => 'ark',
                    'controller' => 'index',
                    'action' => 'policy',
                    'naan' => $naan,
            )));

            $router->addRoute('ark_policy_ark', new Zend_Controller_Router_Route(
                "$protocol/policy",
                array(
                    'module' => 'ark',
                    'controller' => 'index',
                    'action' => 'policy',
                    'naan' => $naan,
            )));

            $protocolBase = "ark:/$naan";
        }

        // Routes for non-arks unique identifiers.
        else {
            $router->addRoute('ark_policy', new Zend_Controller_Router_Route(
                $protocol . '/policy',
                array(
                    'module' => 'ark',
                    'controller' => 'index',
                    'action' => 'policy',
                    'naan' => $naan,
            )));

            $protocolBase = $protocol;
        }

        $router->addRoute('ark_id', new Zend_Controller_Router_Route(
            "$protocolBase/:name/:qualifier",
            array(
                'module' => 'ark',
                'controller' => 'index',
                'action' => 'index',
                'naan' => $naan,
                'qualifier' => '',
            ),
            array(
                'name' => '\w+',
        )));

        // A regex is needed, because a variant is separated by a ".", not a
        // "/".
        $router->addRoute('ark_file_variant', new Zend_Controller_Router_Route_Regex(
            $protocolBase . '/(\w+)/(.*)\.(' . str_replace(' ', '|', get_option('ark_file_variants')) . ')',
            array(
                'module' => 'ark',
                'controller' => 'index',
                'action' => 'index',
                'naan' => $naan,
            ),
            array(
                1 => 'name',
                2 => 'qualifier',
                3 => 'variant',
            ),
            "$protocolBase/%s/%s.%s"
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
                $prefix = get_option('ark_id_prefix') . get_option('ark_id_prefix_collection') . get_option('ark_id_prefix_item') ;
                $suffix = get_option('ark_id_suffix') . get_option('ark_id_suffix_collection') . get_option('ark_id_suffix_item');
            }
            // Adding an ark to a specific record.
            else {
                $recordType = strtolower($recordType);
                $prefix = get_option('ark_id_prefix') . get_option('ark_id_prefix_' . $recordType);
                $suffix = get_option('ark_id_suffix') . get_option('ark_id_suffix_' . $recordType);
            }

            $identifix = get_option('ark_id_prefix_collection') === get_option('ark_id_prefix_item')
                && get_option('ark_id_suffix_collection') === get_option('ark_id_suffix_item');

            $parameters = array(
                'protocol' => get_option('ark_protocol'),
                'naan' => get_option('ark_naan'),
                'prefix' => $prefix,
                'suffix' => $suffix,
                'length' => get_option('ark_id_length'),
                'pad' => get_option('ark_id_pad'),
                'salt' => get_option('ark_id_salt'),
                'alphabet' => get_option('ark_id_alphabet'),
                'control_key' => get_option('ark_id_control_key'),
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
