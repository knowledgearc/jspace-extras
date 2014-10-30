<?php
/**
 * @package     JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Listens for oAuth2 authorization tokens.
 *
 * @package  JSpace.Plugin
 */
class PlgSystemOauth2 extends JPlugin
{
    /**
     * Check for oAuth2 authentication/authorisation requests and fire
     * appropriate oAuth2 events.
     *
     * @return  void
     */
    public function onAfterRoute()
    {
        if (JFactory::getApplication()->getName() == 'site') {
            $app = JFactory::getApplication();

            JPluginHelper::importPlugin('authentication');
            $dispatcher = JEventDispatcher::getInstance();

            $uri = clone JUri::getInstance();
            $queries = $uri->getQuery(true);

            $task = JArrayHelper::getValue($queries, 'task');

            if ($task == 'oauth2.authenticate') {
                $data = $app->getUserState('users.login.form.data', array());
                $data['return'] = $app->input->get('return', null);
                $app->setUserState('users.login.form.data', $data);

                $dispatcher->trigger('onOauth2Authenticate', array());
            } else {
                $code = JArrayHelper::getValue($queries, 'code', null, 'WORD');

                if (count($queries) === 1 && $code) {
                    $array = $dispatcher->trigger('onOauth2Authorise', array());

                    // redirect user to appropriate area of site.
                    if ($array[0] === true) {
                        $data = $app->getUserState('users.login.form.data', array());
                        $app->setUserState('users.login.form.data', array());

                        if ($return = JArrayHelper::getValue($data, 'return')) {
                            $app->redirect(JRoute::_(base64_decode($return), false));
                        } else {
                            $app->redirect(JRoute::_(JUri::current(), false));
                        }
                    } else {
                        $app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
                    }
                }
            }
        }
    }
}