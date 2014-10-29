<?php
/**
 * @package     JSpace.Modules
 * @subpackage  Orcid
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

require JModuleHelper::getLayoutPath('mod_orcid_login', $params->get('layout', 'default'));