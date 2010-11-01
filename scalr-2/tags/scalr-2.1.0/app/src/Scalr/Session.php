<?php

	class Scalr_Session
	{
		private
			$clientId,
			$userId,
			$userGroup,
			$envId,
			$environment,
			$sessionId,
			$authToken;

		private static $_session = null;

		const SESSION_CLIENT_ID = 'clientId';
		const SESSION_USER_ID ='userId';
		const SESSION_ENV_ID = 'envId';
		const SESSION_VARS = 'vars';
		const SESSION_USER_GROUP = 'userGroup';

		/**
		 * @return Scalr_Session
		 */
		public static function getInstance()
		{
			if (self::$_session === null) {
				self::$_session = new Scalr_Session();
			}

			return self::$_session;
		}

		public static function create($clientId, $userId, $userGroup)
		{
			$_SESSION[__CLASS__][self::SESSION_CLIENT_ID] = $clientId;
			$_SESSION[__CLASS__][self::SESSION_USER_ID] = $userId;
			$_SESSION[__CLASS__][self::SESSION_USER_GROUP] = $userGroup;
			self::restore();
		}

		public static function restore()
		{
			$session = self::getInstance();
			$session->clientId = isset($_SESSION[__CLASS__][self::SESSION_CLIENT_ID]) ? $_SESSION[__CLASS__][self::SESSION_CLIENT_ID] : 0;
			$session->userId = isset($_SESSION[__CLASS__][self::SESSION_USER_ID]) ? $_SESSION[__CLASS__][self::SESSION_USER_ID] : 0;

			if ($session->clientId) {
				$session->envId = isset($_SESSION[__CLASS__][self::SESSION_ENV_ID]) ?
					$_SESSION[__CLASS__][self::SESSION_ENV_ID] :
					Scalr_Model::init(Scalr_Model::ENVIRONMENT)->loadDefault($session->clientId)->id;
				$session->environment = Scalr_Model::init(Scalr_Model::ENVIRONMENT)->loadById($session->envId);
			}

			$session->userGroup = isset($_SESSION[__CLASS__][self::SESSION_USER_GROUP]) ? $_SESSION[__CLASS__][self::SESSION_USER_GROUP] : 0;
			$session->sessionId = session_id();

			$session->authToken = new Scalr_AuthToken($session);
		}

		public function getVar($name, $default = null)
		{
			if (isset($_SESSION[__CLASS__][self::SESSION_VARS][$name]))
				return unserialize($_SESSION[__CLASS__][self::SESSION_VARS][$name]);
			else
				return $default;
		}

		public function setVar($name, $value)
		{
			$_SESSION[__CLASS__][self::SESSION_VARS][$name] = serialize($value);
		}

		public function getClientId()
		{
			return $this->clientId;
		}

		public function getUserGroup()
		{
			return $this->userGroup;
		}

		public function getUserId()
		{
			return $this->userId;
		}

		/**
		 * @return Scalr_AuthToken
		 */
		public function getAuthToken()
		{
			return $this->authToken;
		}

		public function setEnvironmentId($envId)
		{
			$_SESSION[__CLASS__][self::SESSION_ENV_ID] = $envId;
		}

		/**
		 * @return Scalr_Environment
		 */
		public function getEnvironment()
		{
			return $this->environment;
		}

		/**
		 *
		 * @throws Exception
		 * @return integer
		 */
		public function getEnvironmentId()
		{
			if ($this->environment)
				return $this->environment->id;
			else
				throw new Exception("No environment defined for current session");
		}

		public function getSessionId()
		{
			return $this->sessionId;
		}

		public function logEvent($message) { }
	}
