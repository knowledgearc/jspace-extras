<?php
/**
 * @package     JSpace.Plugin
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;
 
/**
 * Represents a JSpace Glacier archived item.
 *
 * @package     JSpace.Plugin
 * @subpackage  Table
 */
class JSpaceTableGlacierArchive extends JTable
{
    /**
     * Constructor
     *
     * @param   JDatabaseDriver  &$db  Database connector object
     *
     * @since   1.0
     */
    public function __construct(&$db)
    {
        parent::__construct('#__jspaceglacier_archives', array('hash', 'jspaceasset_id'), $db);
    }
}