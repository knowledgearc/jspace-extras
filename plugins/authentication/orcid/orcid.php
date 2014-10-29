<?php
/**
 * @package     Authentication.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Authenticates a user using their ORCID credentials, authorises access to
 * ORCID-based profile information and registers the user details via the
 * Joomla user manager.
 *
 * @package  Authentication.Plugin
 */
class PlgAuthenticationOrcid extends JPlugin
{
    /**
     * @var  string  The authorisation url.
     */
    protected $authUrl;

    /**
     * @var  string  The access token url.
     */
    protected $tokenUrl;

    /**
     * @var  string  The REST request domain.
     */
    protected $domain;

    /**
     * @var  string[]  Scopes available based on mode settings.
     */
    protected $scopes;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();

        $this->scopes = array('/authenticate');

        if ($this->params->get('mode') == 2) {
            $this->authUrl = "https://sandbox.orcid.org/oauth/authorize";
            $this->domain = ".sandbox.orcid.org";
        } else {
            $this->authUrl = "https://orcid.org/oauth/authorize";
            $this->domain = ".orcid.org";
        }

        $this->tokenUrl = 'https://api'.$this->domain.'/oauth/token';

        if ($this->params->get('access') == 2) {
            $this->scopes[] = '/orcid-profile/read-limited';
            $this->domain = "api".$this->domain;
        } else {
            $this->domain = "pub".$this->domain;
        }
    }

    /**
     * Handles authentication via ORCID and reports back to the subject
     *
     * @param   array   $credentials  Array holding the user credentials
     * @param   array   $options      Array of extra options
     * @param   object  &$response    Authentication response object
     *
     * @return  boolean
     */
    public function onUserAuthenticate($credentials, $options, &$response)
    {
        $response->type = 'Orcid';

        if (JArrayHelper::getValue($options, 'action') == 'core.login.site') {
            $username = JArrayHelper::getValue($credentials, 'username');

            if (!$username) {
                $response->status = JAuthentication::STATUS_FAILURE;
                $response->error_message = JText::_('JGLOBAL_AUTH_NO_USER');

                return false;
            }

            try {
                $token = JArrayHelper::getValue($options, 'token');

                if ($user = new JUser(JUserHelper::getUserId($username))) {
                    if ($user->get('block') || $user->get('activation')) {
                        $response->status = JAuthentication::STATUS_FAILURE;
                        $response->error_message = JText::_('JGLOBAL_AUTH_ACCESS_DENIED');

                        return;
                    }
                }

                $url = 'https://'.$this->domain.'/v1.1/'.$username.'/orcid-profile';

                if ($this->params->get('access') == 2) {
                    $oauth2 = new JOAuth2Client;
                    $oauth2->setToken($token);

                    $result = $oauth2->query($url);
                } else {
                    $client = JHttpFactory::getHttp();
                    $result = $client->get($url);

                }

                $body = new SimpleXMLElement($result->body);

                $bio = $body->{'orcid-profile'}->{'orcid-bio'};

                $name = (string)$bio->{'personal-details'}->{'given-names'}.' '.
                        (string)$bio->{'personal-details'}->{'family-name'};
                $email = (string)$bio->{'contact-details'}->{'email'};

                $response->email = $email;
                $response->fullname = $name;
                $response->username = $username;

                $response->status = JAuthentication::STATUS_SUCCESS;
                $response->error_message = '';
            } catch (Exception $e) {
                // log error.
                $response->status = JAuthentication::STATUS_FAILURE;
                $message = JText::_('JGLOBAL_AUTH_UNKNOWN_ACCESS_DENIED');
                return false;
            }
        }
    }

    /**
     * Authenticate the user via the oAuth2 login and authorise access to the
     * appropriate REST API end-points.
     */
    public function onOauth2Authenticate()
    {
        $oauth2 = new JOAuth2Client;

        $oauth2->setOption('authurl', $this->authUrl);
        $oauth2->setOption('clientid', $this->params->get('clientid'));
        $oauth2->setOption('scope', $this->scopes);
        $oauth2->setOption('redirecturi', JUri::current());
        $oauth2->setOption('requestparams', array('access_type'=>'offline', 'approval_prompt'=>'auto'));
        $oauth2->setOption('sendheaders', true);

        $oauth2->authenticate();
    }

    /**
     * Swap the authorisation code for a persistent token and authorise access
     * to Joomla!.
     *
     * @return  bool  True if the authorisation is successful, false otherwise.
     */
    public function onOauth2Authorise()
    {
        $oauth2 = new JOAuth2Client;
        $oauth2->setOption('tokenurl', $this->tokenUrl);
        $oauth2->setOption('clientid', $this->params->get('clientid'));
        $oauth2->setOption('clientsecret', $this->params->get('clientsecret'));

        $result = $oauth2->authenticate();

        $token = json_decode(JArrayHelper::getValue(array_keys($result), 0), true);
        $token['created'] = json_decode(JArrayHelper::getValue($result, 'created'));
        // Get the log in options.
        $options = array();

        // Get the log in credentials.
        $credentials = array();
        $credentials['username']  = JArrayHelper::getValue($token, 'orcid');

        $options = array();
        $options['token']  = $token;

        $app = JFactory::getApplication();

        // Perform the log in.
        if (true === $app->login($credentials, $options)) {
            $user = new JUser(JUserHelper::getUserId($credentials['username']));
            $user->setParam('orcid.token', json_encode($token));
            $user->save();

            return true;
        } else {
            return false;
        }
    }
}