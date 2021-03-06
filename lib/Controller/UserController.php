<?php
/**
 * @author SecSign Technologies Inc.
 * @copyright 2019 SecSign Technologies Inc.
 */
namespace OCA\SecSignID\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;

use OCP\AppFramework\Controller;
use OCA\SecSignID\Service\IAPI;
use OCA\SecSignID\Db\IDMapper;
use OCA\SecSignID\Db\ID;
use OCA\SecSignID\Provider\SecSign2FA;
use OCA\SecSignID\Service\PermissionService;
use OCA\SecSignID\Service\UserService;
use OCA\SecSignID\Service\QRCode;

/**
 * The SecSignController links to required templates and handles requests to the server.
 */
class UserController extends Controller {
	private $userId;

	private $mapper;

	private $manager;

	private $registry;

	private $provider;

	private $enforce;

	private $userservice;

	private $groupManager;

	public function __construct($AppName, IRequest $request,		
								$UserId, 
								IDMapper $mapper, IUserManager $manager, IGroupmanager $groupManager, IRegistry $registry, MandatoryTwoFactor $enforce, SecSign2FA $provider, UserService $userservice){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
		$this->manager = $manager;
		$this->registry = $registry;
		$this->enforce = $enforce;
		$this->provider = $provider;
		$this->userservice = $userservice;
		$this->groupManager = $groupManager;
	}



	/**
	 * Sets a SecSign ID for the current user.
	 * 
	 * @NoAdminRequired
	 * 
	 * @param string $secsignid
	 */
	public function setID(string $secsignid){
		$entity = new ID();
		$entity->setUserId($this->userId);
		$entity->setSecsignid($secsignid);
		$entity->setEnabled(1);
		$this->changeUserState(true, $this->userId);
		return $this->mapper->addUser($entity)->jsonSerialize();
	}



	/**
	 * Disables the current users 2FA.
	 * 
	 * @NoAdminRequired
	 */
	public function disableID(){
		$this->changeUserState(false, $this->userId);
		return $this->mapper->disableUser($this->userId)->jsonSerialize();
	}


	/**
	 * Gets all users joined with their corresponding SecSign IDs.
	 * 
	 */
	public function usersWithIds(){
		$data = $this->mapper->getUsersAndIds();
		foreach($data as &$user){
			$user['enabled'] = $this->getUserState($user['uid']) ? "1" : "0";
			$user['enforced'] = $this->enforcedForUser($user['uid']) ? "1":"0";
			$user['groups'] = $this->groupsForUser($user['uid']);
		}
		return $data;
	}

	/**
	 * Checks if 2FA is enforced for a given user
	 * 
	 * @param string $uid of user
	 * @return bool
	 */
	private function enforcedForUser($uid): bool {
		$user = $this->manager->get($uid);
		return $this->enforce->isEnforcedFor($user);
	}

	/**
	 * Gets group names for user
	 * 
	 * @param string $uid of user
	 * @return array group names
	 */
	private function groupsForUser($uid): array {
		$user = $this->manager->get($uid);
		return $this->groupManager->getUserGroupIds($user);
	}

	/**
	 * Saves all changes made in the user management screen.
	 * 
	 * 
	 * @param array $data
	 */
	public function saveChanges($data){
		foreach($data as &$user){
			$previous = $this->mapper->find($user['uid']);
			if(isset($previous) && $previous->getSecsignid() !== $user['secsignid']){
				$this->userservice->setUserValue('logged_in', $user['uid'], 0);
			}
			$id = new ID();
			$id->setUserId($user['uid']);
			$id->setSecsignid($user['secsignid']);
			$id->setEnabled($user['enabled']);
			$this->changeUserState($user['enabled'], $user['uid']);
			$this->mapper->addUser($id);
		}
		return $this->usersWithIds();
	}
	/**
	 * Gets all users.
	 * 
	 */
	public function getUsers(){
		$ids = $this->mapper->findAll();
		foreach ($ids as &$id){
			$id = $id->jsonSerialize();
		}
		return $ids;
	}

	/**
	 * Finds the SecSign ID for the current user.
	 * 
	 * @NoAdminRequired
	 */
	public function findCurrent(){
		$current = $this->mapper->find($this->userId);
		if($current !== null){
			return $current->jsonSerialize();
		}else{
			return null;
		}
	}


	/**
	 * Enables or disables 2FA for the user with a given uid.
	 * 
	 * @param boolean $enable
	 * @param string $uid
	 */
	private function changeUserState($enable, $uid){
		$user = $this->manager->get($uid);
		if($enable){
			$this->registry->enableProviderFor($this->provider,$user);
		}else{
			$this->registry->disableProviderFor($this->provider,$user);
		}
	}

	/**
	 * Gets the state of 2FA for the user with a given uid.
	 * 
	 * @param string $uid
	 */
	private function getUserState($uid): bool {
		$user = $this->manager->get($uid);
		$states = $this->registry->getProviderStates($user);
		if(array_key_exists('secsignid',$states)){
			return $states['secsignid'];
		}else{
			return False;
		}
		
	}

}
