<?php
/**
 * @package     JSpace.Modules
 * @subpackage  Orcid
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

class ModOrcidLoginHelper
{
    public function getAuthorizeLink()
    {
        $token = JFactory::getSession()->getToken();

        $url = new JUri('https://sandbox.orcid.org/oauth/authorize');
        $url->setVar('client_id', '0000-0003-3811-7928');
        $url->setVar('response_type', 'code');
        $url->setVar('scope', '/authenticate');
        $url->setVar('redirect_uri', urlencode(JUri::getInstance()->toString(array('scheme', 'host', 'port')).JRoute::_('index.php?option=com_users&task=user.login&'.$token.'=1')));
        //$url->setVar('redirect_uri', JUri::getInstance()->toString(array('scheme', 'host', //'port')).JRoute::_('index.php?option=com_users&task=user.login&token='.JSession::getFormToken()));

        return $url;
    }
}