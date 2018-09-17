<?php

namespace WC\Session\Helpers;

use WC\Models\GroupModel;
use WC\Models\GroupsModel;
use WC\Models\PermissionsModel;
use WC\Models\UserModel;

final class PermissionsCalculator
{
    public static function calculate(UserModel &$userModel) {
        // format user permissions
        $userPermissions = $userModel->getPermissions()->getAsArray();
        self::formatPermissions($userPermissions);

        // fetch and format groups permissions
        $groups = $userModel->getGroups();
        $groupsPermissions = array();
        self::fetchGroupPermissions($groups, $groupsPermissions);

        // merge
        foreach ($groupsPermissions as $uuid=>$permission) {
            if (!isset($userPermissions[$uuid])) {
                $userPermissions[$uuid] = $permission;
            }
        }

        // reset
        $userModel->set('permissions', new PermissionsModel($userPermissions));
    }

    private static function fetchGroupPermissions(GroupsModel &$groupsModel, &$permissions) {
        foreach ($groupsModel->getAsArray() as $group) {
            if ($group instanceof GroupModel) {
                $tmpPermissions = $group->getPermissions()->getAsArray();
                self::formatPermissions($tmpPermissions);
                foreach ($tmpPermissions as $uuid=>$item) {
                    if (!isset($permissions[$uuid])) {
                        $permissions[$uuid] = $item;
                    }
                }
                if ($group->getGroups()->isNotEmpty()) {
                    $parentGroupsModel = $group->getGroups();
                    self::fetchGroupPermissions($parentGroupsModel, $permissions);
                }
            }
        }
    }

    private static function formatPermissions(array &$permissions) {
        $newPermissions = array();
        foreach ($permissions as $item) {
            $newPermissions[$item['uuid']] = $item;
        }
        $permissions = $newPermissions;
    }
}