<?php
/**
 * @package     JSpace.Modules
 * @subpackage  Orcid
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;
?>
<form action="<?php echo JRoute::_('index.php?task=oauth2.authenticate&return='.base64_encode((string)JUri::getInstance())); ?>" method="post">
    <button type="submit" id="connect-orcid-link"><img id="orcid-id-logo" src="http://orcid.org/sites/default/files/images/orcid_16x16.png" width='16' height='16' alt="ORCID logo"/>Create or Connect your ORCID iD</button>
    <?php echo JHtml::_('form.token'); ?>
</form>