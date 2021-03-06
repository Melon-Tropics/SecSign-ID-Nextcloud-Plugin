<?php
/**
 * @author SecSign Technologies Inc.
 * @copyright 2019 SecSign Technologies Inc.
 */
namespace OCA\SecSignID\Db;

use OCP\IDbConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCA\SecSignID\Db\ID;

class IDMapper extends QBMapper {

    public function __construct(IDbConnection $db) {
        parent::__construct($db, 'secsignid', ID::class);
    }

    public function addUser($id){
        try{
            $user = $this->find($id->getUserId());
            if($user !== null){
                $id->setId($user->getId());
                return $this->update($id);
            }else{
                return $this->insert($id);
            }
        }catch(DoesNotExistException $e){
            return $this->insert($id);
        }
    }

    public function disableUser($uid){
        $user = $this->find($uid);
        $user->setEnabled(0);
        return $this->update($user);
    }

    public function getUsersAndIDs(){
        $qb = $this->db->getQueryBuilder();
        $qb->select('u.uid','u.displayname','s.secsignid','s.enabled')
            ->from('users','u')
            ->leftJoin('u','secsignid','s','s.user_id = u.uid');
        $value = $qb->execute();
        $output = array();
        $row = $value->fetch();
        while($row !== false){
            $output[] = $row;
            $row = $value->fetch();
        }
        return $output;
    }

    public function find($userId) {
        $qb = $this->db->getQueryBuilder();
        $qb ->select('*')
            ->from('secsignid')
            ->where(
                $qb->expr()->eq('user_id', $qb->createNamedParameter($userId,IQueryBuilder::PARAM_STR))
            );
            
        try{
            return $this->findEntity($qb);
        }catch(DoesNotExistException $e){
            return null;
        }
        
    }

    public function findByName($secsignid) {
        $sql = 'SELECT * FROM *PREFIX*secsignid WHERE secsignid = ?';
        return $this->findEntity($sql, [$secsignid]);
    }

    public function findEnabled() {
        $sql = 'SELECT * FROM *PREFIX*secsignid WHERE enabled = 1';
        return $this->findEntities($sql);
    }

    public function findDisabled() {
        $sql = 'SELECT * FROM *PREFIX*secsignid WHERE enabled = 0';
        return $this->findEntities($sql);
    }

    public function findID($userId) {
        $sql = 'SELECT * FROM *PREFIX*secsignid WHERE user_id = ?';
        return $this->findEntities($sql, [$userId]);
    }

    public function findAll() {
        $qb = $this->db->getQueryBuilder();
        $qb ->select('*')
            ->from('secsignid');
        return $this->findEntities($qb);
    }

}