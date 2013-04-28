<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SecurityExtraBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @author Ville Mattila <ville@eventio.fi>
 */
class RequiredRolesMissingException extends AccessDeniedException
{
	protected $roles;
	
	protected $token;

	public function __construct($message, $roles, $token) {
		parent::__construct($message);
		
		$this->roles = $roles;
		$this->token = $token;
	}
	
	public function getRoles() {
		return $this->roles;
	}
	
	public function getToken() {
		return $this->token;
	}
}