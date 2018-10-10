<?php
/**
 * @author Juan Pablo Villafáñez <jvillafanez@solidgeargroup.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */
namespace OCA\DataExporter\Model;

abstract class AbstractModel {
	/** @var AbstractModel|null */
	private $parent = null;

	/**
	 * Set the parent model of this one. Note that each model can only have
	 * one parent, and setting a different parent will overwrite the previous one
	 * @param AbstractModel $model the model to be set as parent
	 */
	final public function setParent(AbstractModel $model) {
		$this->parent = $model;
	}

	/**
	 * Get the parent set or null if no parent is set
	 * @return AbstractModel|null no model set with setParent or null if no parent
	 * has been set
	 */
	final public function getParent() {
		return $this->parent;
	}
}
