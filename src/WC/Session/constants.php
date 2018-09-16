<?php

if (!defined('WC_SESSION_LOADED')) {
    define('WC_SESSION_LOADED', true);
    /* SESSION VARIABLES */
    if (!defined("WC_SESSION_ID")) {define('WC_SESSION_ID', md5('wc_session_id'));}
    if (!defined("WC_SESSION_LIFETIME")) {define('WC_SESSION_LIFETIME', 60*60*12);}
    if (!defined("WC_SESSION_ROOT_KEY")) {define("WC_SESSION_KEY_ROOT", "wc_session_root");}
    if (!defined("WC_SESSION_LIFETIME_KEY")) {define("WC_SESSION_KEY_LIFETIME", "wc_session_lifetime");}
    if (!defined("WC_SESSION_LOGIN_KEY")) {define("WC_SESSION_LOGIN_KEY", "wc_login_uid");}
    if (!defined("WC_SESSION_DATA_KEY")) {define("WC_SESSION_DATA_KEY", "session_user_data");}
    /* DATABASE TABLES */
    if (!defined("WC_TBL_USR_GROUPS")) {define("WC_TBL_USR_GROUPS", "usr_groups");}
    if (!defined("WC_TBL_USR_GROUP_GROUPS")) {define("WC_TBL_USR_GROUP_GROUPS", "usr_group_groups");}
    if (!defined("WC_TBL_USR_PERMISSIONS")) {define("WC_TBL_USR_PERMISSIONS", "usr_permissions");}
    if (!defined("WC_TBL_USR_PROFILE_FIELDS")) {define("WC_TBL_USR_PROFILE_FIELDS", "usr_profile_fields");}
    if (!defined("WC_TBL_USR_PROFILE_VALUES")) {define("WC_TBL_USR_PROFILE_VALUES", "usr_profile_values");}
    if (!defined("WC_TBL_USR_SESSION")) {define("WC_TBL_USR_SESSION", "usr_session");}
    if (!defined("WC_TBL_USR_USERS")) {define("WC_TBL_USR_USERS", "usr_users");}
    if (!defined("WC_TBL_USR_USER_GROUPS")) {define("WC_TBL_USR_USER_GROUPS", "usr_user_groups");}
    if (!defined("WC_ENTITY_TYPE_USER")) {define("WC_ENTITY_TYPE_USER", "u");}
    if (!defined("WC_ENTITY_TYPE_GROUP")) {define("WC_ENTITY_TYPE_GROUP", "g");}
}