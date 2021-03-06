<?php

namespace mako\auth\models;

use \mako\core\Config;
use \mako\utility\UUID;
use \mako\security\Password;

use \LogicException;

/**
 * Gatekeeper user.
 * 
 * @author     Frederic G. Østby
 * @copyright  (c) 2008-2013 Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

class User extends \mako\database\midgard\ORM
{
	//---------------------------------------------
	// Class properties
	//---------------------------------------------

	/**
	 * User permissions.
	 * 
	 * @var array
	 */

	protected $permissions = array();

	//---------------------------------------------
	// Relations
	//---------------------------------------------

	/**
	 * Many to many relation to the groups table.
	 * 
	 * @access  public
	 * @return  \mako\database\midgard\relation\ManyToMany
	 */

	public function groups()
	{
		return $this->manyToMany(Config::get('gatekeeper.group_model'));
	}

	//---------------------------------------------
	// Getters and setters
	//---------------------------------------------

	/**
	 * Password setter.
	 * 
	 * @access  public
	 * @return  string
	 */

	public function set_password($password)
	{
		return Password::hash($password);
	}

	//---------------------------------------------
	// Class methods
	//---------------------------------------------

	/**
	 * Validates a users password.
	 * 
	 * @access  public
	 * @param   string   $password  User password
	 * @return  boolean
	 */

	public function validatePassword($password)
	{
		return Password::validate($password, $this->password);
	}

	/**
	 * Generates a random auth token.
	 * 
	 * @access  public
	 * @return  string
	 */

	public function generateToken()
	{
		if(!$this->exists)
		{
			throw new LogicException(vsprintf("%s(): You can only generate auth tokens for users that exist.", [__METHOD__]));
		}

		return $this->token = md5(UUID::v4() . $this->id);
	}

	/**
	 * Merges and returns the user permissions.
	 * 
	 * @access  public
	 * @return  array
	 */

	protected function mergePermissions()
	{
		if(empty($this->permissions))
		{
			foreach($this->groups as $group)
			{
				$this->permissions = array_merge($this->permissions, array_flip($group->permissions));
			}
		}

		return $this->permissions;
	}

	/**
	 * Returns the users permissions.
	 * 
	 * @access  public
	 * @return  array
	 */

	public function getPermissions()
	{
		if(!$this->exists)
		{
			throw new LogicException(vsprintf("%s(): You can only get permissions for users that exist.", [__METHOD__]));
		}

		return array_flip($this->mergePermissions());
	}

	/**
	 * Returns TRUE if a user has a permission and FALSE if not.
	 * 
	 * @access  public
	 * @param   string|array  $checks  Permission or array of permissions
	 * @return  boolean
	 */

	public function hasPermission($checks)
	{
		if(!$this->exists)
		{
			throw new LogicException(vsprintf("%s(): You can only check permissions for users that exist.", [__METHOD__]));
		}

		$permissions = $this->mergePermissions();

		foreach((array) $checks as $check)
		{
			if(isset($permissions[$check]))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns TRUE if a user is a member of the group(s) and FALSE if not.
	 * 
	 * @access  public
	 * @param   string|id|array  $checks  Group name, group id or an array of group names or group ids
	 * @return  boolean
	 */

	public function memberOf($checks)
	{
		if(!$this->exists)
		{
			throw new LogicException(vsprintf("%s(): You can only check memberships for users that exist.", [__METHOD__]));
		}

		foreach((array) $checks as $check)
		{
			foreach($this->groups as $group)
			{
				if(is_int($check))
				{
					if((int) $group->id === $check)
					{
						return true;
					}
				}
				else
				{
					if($group->name === $check)
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Returns TRUE if the user is activated and FALSE if not.
	 * 
	 * @access  public
	 * @return  boolean
	 */

	public function isActivated()
	{
		return $this->activated == 1;
	}

	/**
	 * Activates a user.
	 * 
	 * @access  public
	 */

	public function activate()
	{
		$this->activated = 1;
	}

	/**
	 * Deactivates a user.
	 * 
	 * @access  public
	 */

	public function deactivate()
	{
		$this->activated = 0;
	}

	/**
	 * Returns TRUE if the user is banned and FALSE if not.
	 * 
	 * @access  public
	 * @return  boolean
	 */

	public function isBanned()
	{
		return $this->banned == 1;
	}

	/**
	 * Bans the selected user.
	 * 
	 * @access  public
	 */

	public function ban()
	{
		$this->banned = 1;
	}

	/**
	 * Unbans the selected user.
	 * 
	 * @access  public
	 */

	public function unban()
	{
		$this->banned = 0;
	}
}

/** -------------------- End of file --------------------**/