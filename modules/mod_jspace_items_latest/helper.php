<?php
/**
 * A module helper for listing the latest items that have been added to the repository.
 * 
 * @author		$Id$
 * @package		JSpace
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace Latest Items module for Joomla!.

   The JSpace Latest Items module for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSpace Latest Items module Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace component for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */

// no direct access
defined('_JEXEC') or die;

jimport('jspace.factory');

class modJSpaceItemsLatestHelper
{
	public static function getItems($params)
	{
		JLog::addLogger(array('text_file'=>'jspace.php'), JLog::ALL, 'module');
		
		$repository = JSpaceFactory::getRepository();
		$filter = $repository->createFilter(array(
			'name'			=> 'latest',
			'limitstart'	=> 0,
			'limit'			=> $params->get('shownumber', 10),
			'filterBy'		=> array(),
			'sortBy'		=> array(
				array('date_accessioned', 'desc')
			)
		));
		
		$items = $repository->getItems( $filter );
		
		return $items;
	}
}
