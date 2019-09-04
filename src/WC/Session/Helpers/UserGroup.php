<?php

namespace WC\Session\Helpers;

use Doctrine\ORM\EntityManager;
use WC\Models\GroupModel;
use WC\Models\GroupsModel;
use WC\Models\PermissionsModel;
use WC\Models\UserModel;

final class UserGroup
{
    public static function fetchUser(string $data, EntityManager &$em, UserModel &$userModel) {
        if (is_numeric($data)) {
            $q = 'SELECT * FROM ' . WC_TBL_USR_USERS . ' WHERE id='.$em->quote($data);
        }
        else if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
            $q = 'SELECT * FROM ' . WC_TBL_USR_USERS . ' WHERE email='.$em->quote($data);
        }
        else {
            $q = 'SELECT * FROM ' . WC_TBL_USR_USERS . ' WHERE username='.$em->quote($data);
        }
        $rows = $em->getConnection()->fetchAll($q);
        $userModel->mergeReplace(is_array($rows)?$rows:[]);
    }

    public static function fetchUserGroups(UserModel &$userModel, EntityManager &$em) {
        $groups = self::getUserGroups((int)$userModel->getId(), $em);
        $groupsModel = new GroupsModel(array());
        if (sizeof($groups)) {
            foreach ($groups as $i=>$group) {
                $groups[$i] = new GroupModel($group);
                $permissions = self::getGroupPermissions((int)$groups[$i]->getId(), $em);
                $groups[$i]->set('permissions', new PermissionsModel($permissions));
                self::recursivelyFetchGroupGroups($groups[$i], $em);
                $groupsModel->add($groups[$i]);
            }
        }
        $userModel->set('groups', $groupsModel);
    }

    public static function fetchUserPermissions(UserModel &$userModel, EntityManager &$em) {
        $permissions = self::getUserPermissions((int)$userModel->getId(), $em);
        $userModel->set('permissions', new PermissionsModel($permissions));
    }

    public static function getGroupGroups(int $gid, EntityManager &$em): array {
        $q = 'SELECT g.* 
              FROM '.WC_TBL_USR_GROUP_GROUPS.' gg LEFT JOIN '.WC_TBL_USR_GROUPS.' g ON g.id=gg.parent_group_id
              WHERE gg.child_group_id=' . $em->quote($gid);
        $rows = $em->getConnection()->fetchAll($q);
        return is_array($rows) ? $rows : [];
    }

    public static function getUserGroups(int $uid, EntityManager &$em): array {
        $q = 'SELECT g.* 
              FROM '.WC_TBL_USR_USER_GROUPS.' ug LEFT JOIN '.WC_TBL_USR_GROUPS.' g ON g.id=ug.group_id
              WHERE ug.user_id=' . $em->quote($uid);
        $rows = $em->getConnection()->fetchAll($q);
        return is_array($rows) ? $rows : [];
    }

    public static function getGroupPermissions(int $id, EntityManager &$em): array {
        $q = 'SELECT * FROM '.WC_TBL_USR_PERMISSIONS.' WHERE user_or_group_id='.$em->quote($id).' AND user_or_group=' . $em->quote(WC_ENTITY_TYPE_GROUP);
        $rows = $em->getConnection()->fetchAll($q);
        return is_array($rows) ? $rows : [];
    }

    public static function getUserPermissions(int $id, EntityManager &$em): array {
        $q = 'SELECT * FROM '.WC_TBL_USR_PERMISSIONS.' WHERE user_or_group_id='.$em->quote($id).' AND user_or_group=' . $em->quote(WC_ENTITY_TYPE_USER);
        $rows = $em->getConnection()->fetchAll($q);
        return is_array($rows) ? $rows : [];
    }

    public static function calculatePermissions(UserModel &$userModel) {PermissionsCalculator::calculate($userModel);}

    private static function recursivelyFetchGroupGroups(GroupModel &$groupModel, EntityManager &$em) {
        $groups = self::getGroupGroups((int)$groupModel->getId(), $em);
        $groupsModel = new GroupsModel(array());
        if (sizeof($groups)) {
            foreach ($groups as $i=>$group) {
                $groups[$i] = new GroupModel($group);
                $permissions = self::getGroupPermissions((int)$groups[$i]->getId(), $em);
                $groups[$i]->set('permissions', new PermissionsModel($permissions));
                self::recursivelyFetchGroupGroups($groups[$i], $em);
                $groupsModel->add($groups[$i]);
            }
        }
        $groupModel->set('groups', $groupsModel);
    }
}