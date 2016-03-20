<?php
/**
 * Php Noid format for Ark name.
 *
 * @package Ark
 */
class Ark_Name_Noid extends Ark_Name_Abstract
{
    protected $_isFullArk = true;

    protected $_database = '';
    protected $_noid = '';

    public function __construct($parameters = array())
    {
        require_once PLUGIN_DIR . DIRECTORY_SEPARATOR . 'Ark'
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'Noid4Php'
            . DIRECTORY_SEPARATOR . 'lib'
            . DIRECTORY_SEPARATOR . 'Noid.php';

        parent::__construct($parameters);
    }

    protected function _create()
    {
        // Record and parameters are not used to create the noid (an index), but
        // to store it for long term purposes.
        $record = &$this->_record;

        $noid = $this->_openDatabase(Noid::DB_WRITE);
        if (empty($noid)) {
            $message = __('Cannot open database: %s', Noid::errmsg(null, 1) ?: __('No database'));
            _log('[Ark&Noid] ' . $message, Zend_Log::ERR);
            return;
        }

        // Default public url of Omeka.
        set_theme_base_url('public');
        $recordUrl = $record->getRecordUrl();
        // TODO Routes should be loaded for background process, else exception.
        try {
            $recordUrl = absolute_url($recordUrl, 'id');
        } catch (Exception $e) {
            // An exception means a background process, so routes are not
            // defined, so the web root uses the special option.
            $webRoot = get_option('ark_web_root');
            if (empty($webRoot)) {
                $message = __('Unable to define a route for ark, because the option "ark_web_root" is not defined.');
                if (!empty($this->_errorMessage)) {
                    $message .= PHP_EOL . $this->_errorMessage;
                }
                throw new Ark_ArkException($message);
            }
            if (is_array($recordUrl)) {
                $recordUrl = '/' . $recordUrl['controller'] . '/' . $recordUrl['action'] . '/' . $recordUrl['id'];
            }
            elseif (!is_string($recordUrl)) {
                $message = __('Unable to define a route for ark: %s.', $e->getMessage());
                if (!empty($this->_errorMessage)) {
                    $message .= PHP_EOL . $this->_errorMessage;
                }
                throw new Ark_ArkException($message);
            }
            $recordUrl = $webRoot . $recordUrl;
        }
        revert_theme_base_url();

        // Check if the url is already set (only the Omeka id: the other ids are
        // not automatic and can't be checked the same).
        $ark = Noid::get_note($noid, 'locations/' . $recordUrl);
        if ($ark) {
            Noid::dbclose($noid);
            return $ark;
        }

        $recordUrls[] = $recordUrl;

        // Record url for Clean Url if any.
        if (plugin_is_active('CleanUrl')) {
            // Don't use the specific helper because the ark can be the default.
            $cleanUrl = get_view()->getRecordFullIdentifier($record, true, 'public', true);
            /*
            require_once PLUGIN_DIR . DIRECTORY_SEPARATOR . 'CleanUrl'
                . DIRECTORY_SEPARATOR . 'views'
                . DIRECTORY_SEPARATOR . 'helpers'
                . DIRECTORY_SEPARATOR . 'GetRecordFullIdentifier.php';
            $helper = new CleanUrl_View_Helper_GetRecordFullIdentifier();
            $helper->setView(get_view());
            $cleanUrl = $helper->getRecordFullIdentifier($record, true, 'public', true);
            */
            if ($cleanUrl != $recordUrl) {
                $recordUrls[] = $cleanUrl;
            }
        }

        $contact = $this->_getContact();

        $ark = Noid::mint($noid, $contact);
        if (strlen($ark) == '') {
            $message = __('Cannot create an Ark for %s #%d: %s',
                get_class($record), $record->id, Noid::errmsg($noid));
            _log('[Ark&Noid] ' . $message, Zend_Log::ERR);
            Noid::dbclose($noid);
            return;
        }

        // Bind the ark and the record.
        $locations = implode('|', $recordUrls);
        $result = Noid::bind($noid, $contact, 1, 'set', $ark, 'locations', $locations);
        if (empty($result)) {
            $message = __('Ark set, but not bound [%s, %s #%d]: %s',
                $ark, get_class($record), $record->id, Noid::errmsg($noid));
            _log('[Ark&Noid] ' . $message, Zend_Log::ERR);
        }

        // Save the reverse bind on Omeka id to find it instantly, as a "note".
        // If needed, other urls can be find in a second step via the ark.
        $result = Noid::note($noid, $contact, 'locations/' . $recordUrl, $ark);
        if (empty($result)) {
            $message = __('Ark set, but no reverse bind [%s, %s #%d]: %s',
                $ark, get_class($record), $record->id, Noid::errmsg($noid));
            _log('[Ark&Noid] ' . $message, Zend_Log::ERR);
        }

        Noid::dbclose($noid);

        return $ark;
    }

    /**
     * Check parameters.
     *
     * @return boolean
     */
    protected function _checkParameters()
    {
        if ($this->isDatabaseCreated()) {
            return true;
        }

        // Only the template is checked, because non-ark may be created.
        $template = $this->_getParameter('template');
        if (empty($template)) {
            return false;
        }

        $prefix = null;
        $mask = null;
        $gen_type = null;
        $message = null;

        $total = Noid::parse_template($template, $prefix, $mask, $gen_type, $message);
        if (empty($total)) {
            $this->_errorMessage = __('Template unparsable: %s', $message);
            return false;
        }

        return true;
    }

    public function isDatabaseCreated()
    {
        $noid = $this->_openDatabase();
        if (empty($noid)) {
            return false;
        }
        Noid::dbclose($noid);
        return true;
    }

    public function createDatabase()
    {
        $contact = $this->_getContact();

        $database = $this->_getParameter('database');
        $template = $this->_getParameter('template');
        $naan = $this->_getParameter('naan');
        $naa = $this->_getParameter('naa');
        $subnaa = $this->_getParameter('subnaa');

        $term = ($naan && $naa && $subnaa) ? 'long' : 'medium';

        $erc = Noid::dbcreate($database, $contact, $template, $term, $naan, $naa, $subnaa);
        if (empty($erc)) {
            return Noid::errmsg(null, 1);
        }
        // dbcreate() closes the database automatically.

        return true;
    }

    protected function _openDatabase($mode = Noid::DB_RDONLY)
    {
        $database = $this->_getParameter('database');
        if (empty($database) || !is_dir($database)) {
            return false;
        }

        $this->_database = $database
            . DIRECTORY_SEPARATOR . 'NOID' . DIRECTORY_SEPARATOR . 'noid.bdb';
        $this->_noid = Noid::dbopen($this->_database, $mode);
        return $this->_noid;
    }
}
