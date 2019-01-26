<?php

namespace WC\Session\Helpers;

use WC\Database\Driver;
use WC\Models\GroupModel;
use WC\Models\GroupsModel;
use WC\Models\PermissionsModel;
use WC\Models\UserModel;

final class UserGroup
{
    public static function fetchUser(string $idOrUsernameOrEmail, Driver &$em, UserModel &$userModel) {
        if (is_numeric($idOrUsernameOrEmail)) {
            $q = 'SELECT * FROM ' . WC_TBL_USR_USERS . ' WHERE id='.$em->quote($idOrUsernameOrEmail);
        }
        else if (filter_var($idOrUsernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            $q = 'SELECT * FROM ' . WC_TBL_USR_USERS . ' WHERE email='.$em->quote($idOrUsernameOrEmail);
        }
        else {
            $q = 'SELECT * FROM ' . WC_TBL_USR_USERS . ' WHERE username='.$em->quote($idOrUsernameOrEmail);
        }
        $userModel->mergeReplace($em->loadResult($q));
    }

    public static function fetchUserGroups(UserModel &$userModel, Driver &$em) {
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

    public static function fetchUserPermissions(UserModel &$userModel, Driver &$em) {
        $permissions = self::getUserPermissions((int)$userModel->getId(), $em);
        $userModel->set('permissions', new PermissionsModel($permissions));
    }

    public static function getGroupGroups(int $gid, Driver &$em): array {
        $q = 'SELECT g.* 
              FROM '.WC_TBL_USR_GROUP_GROUPS.' gg LEFT JOIN '.WC_TBL_USR_GROUPS.' g ON g.id=gg.parent_group_id
              WHERE gg.child_group_id=' . $em->quote($gid);
        return $em->loadResults($q);
    }

    public static function getUserGroups(int $uid, Driver &$em): array {
        $q = 'SELECT g.* 
              FROM '.WC_TBL_USR_USER_GROUPS.' ug LEFT JOIN '.WC_TBL_USR_GROUPS.' g ON g.id=ug.group_id
              WHERE ug.user_id=' . $em->quote($uid);
        return $em->loadResults($q);
    }

    public static function getGroupPermissions(int $id, Driver &$em): array {
        $q = 'SELECT * FROM '.WC_TBL_USR_PERMISSIONS.' WHERE user_or_group_id='.$em->quote($id).' AND user_or_group=' . $em->quote(WC_ENTITY_TYPE_GROUP);
        return $em->loadResults($q);
    }

    public static function getUserPermissions(int $id, Driver &$em): array {
        $q = 'SELECT * FROM '.WC_TBL_USR_PERMISSIONS.' WHERE user_or_group_id='.$em->quote($id).' AND user_or_group=' . $em->quote(WC_ENTITY_TYPE_USER);
        return $em->loadResults($q);
    }

    public static function calculatePermissions(UserModel &$userModel) {PermissionsCalculator::calculate($userModel);}

    private static function recursivelyFetchGroupGroups(GroupModel &$groupModel, &$em) {
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