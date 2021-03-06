<?php

/**
 * @name eolinker ams open source，eolinker开源版本
 * @link https://www.eolinker.com/
 * @package eolinker
 * @author www.eolinker.com 广州银云信息科技有限公司 2015-2017
 * eoLinker是目前全球领先、国内最大的在线API接口管理平台，提供自动生成API文档、API自动化测试、Mock测试、团队协作等功能，旨在解决由于前后端分离导致的开发效率低下问题。
 * 如在使用的过程中有任何问题，欢迎加入用户讨论群进行反馈，我们将会以最快的速度，最好的服务态度为您解决问题。
 *
 * eoLinker AMS开源版的开源协议遵循Apache License 2.0，如需获取最新的eolinker开源版以及相关资讯，请访问:https://www.eolinker.com/#/os/download
 *
 * 官方网站：https://www.eolinker.com/
 * 官方博客以及社区：http://blog.eolinker.com/
 * 使用教程以及帮助：http://help.eolinker.com/
 * 商务合作邮箱：market@eolinker.com
 * 用户讨论QQ群：284421832
 */
class DocumentGroupModule
{
    /**
     * 获取用户类型
     * @param $group_id
     * @return bool|int
     */
    public function getUserType(&$group_id)
    {
        $dao = new DocumentGroupDao();
        if (!($project_id = $dao->checkGroupPermission($group_id, $_SESSION['userID']))) {
            return -1;
        }
        $auth_dao = new AuthorizationDao();
        $result = $auth_dao->getProjectUserType($_SESSION['userID'], $project_id);
        if ($result === FALSE) {
            return -1;
        }
        return $result;
    }

    /**
     * 添加文档分组
     * @param $project_id int 项目ID
     * @param $user_id int 用户ID
     * @param $group_name string 分组名称
     * @param $parent_group_id int 父分组ID
     * @return bool|int
     */
    public function addGroup(&$project_id, &$user_id, &$group_name, &$parent_group_id)
    {
        $group_dao = new DocumentGroupDao();
        $project_dao = new ProjectDao();
        if (!($project_id = $project_dao->checkProjectPermission($project_id, $user_id))) {
            return FALSE;
        }

        //判断是否有父分组
        if (is_null($parent_group_id)) {
            //没有父分组
            $group_id = $group_dao->addGroup($project_id, $group_name);
            if ($group_id) {
                //更新项目的更新时间
                $project_dao->updateProjectUpdateTime($project_id);

                //将操作写入日志
                $log_dao = new ProjectLogDao();
                $log_dao->addOperationLog($project_id, $user_id, ProjectLogDao::$OP_TARGET_PROJECT_DOCUMENT_GROUP, $group_id, ProjectLogDao::$OP_TYPE_ADD, "添加项目文档分组:'{$group_name}'", date("Y-m-d H:i:s", time()));

                //返回分组的groupID
                return $group_id;
            } else {
                return FALSE;
            }
        } else {
            //有父分组
            $group_id = $group_dao->addChildGroup($project_id, $group_name, $parent_group_id);
            if ($group_id) {
                if (!$group_dao->checkGroupPermission($parent_group_id, $user_id)) {
                    return FALSE;
                }
                //更新项目的更新时间
                $project_dao->updateProjectUpdateTime($project_id);
                $parent_group_name = $group_dao->getGroupName($parent_group_id);

                //将操作写入日志
                $log_dao = new ProjectLogDao();
                $log_dao->addOperationLog($project_id, $user_id, ProjectLogDao::$OP_TARGET_PROJECT_DOCUMENT_GROUP, $group_id, ProjectLogDao::$OP_TYPE_ADD, "添加项目文档子分组:'{$parent_group_name}>>{$group_name}'", date("Y-m-d H:i:s", time()));

                //返回分组ID
                return $group_id;
            } else {
                return FALSE;
            }
        }
    }

    /**
     * 删除文档分组
     * @param $user_id int 用户ID
     * @param $group_id int 分组ID
     * @return bool
     */
    public function deleteGroup(&$user_id, &$group_id)
    {
        $group_dao = new DocumentGroupDao();
        if (!($project_id = $group_dao->checkGroupPermission($group_id, $user_id))) {
            return FALSE;
        }
        $group_name = $group_dao->getGroupName($group_id);
        if ($group_dao->deleteGroup($group_id)) {
            //更新项目的更新时间
            $project_dao = new ProjectDao();
            $project_dao->updateProjectUpdateTime($project_id);

            //将操作写入日志
            $log_dao = new ProjectLogDao();
            $log_dao->addOperationLog($project_id, $user_id, ProjectLogDao::$OP_TARGET_PROJECT_DOCUMENT_GROUP, $group_id, ProjectLogDao::$OP_TYPE_DELETE, "删除项目文档分组:'$group_name'", date("Y-m-d H:i:s", time()));

            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 获取项目分组
     * @param $project_id int 项目ID
     * @param $user_id int 用户ID
     * @return bool|mixed
     */
    public function getGroupList(&$project_id, &$user_id)
    {
        $project_dao = new ProjectDao();
        if (!$project_dao->checkProjectPermission($project_id, $user_id)) {
            return FALSE;
        }
        $group_dao = new DocumentGroupDao();
        return $group_dao->getGroupList($project_id);
    }

    /**
     * 修改文档分组
     * @param $user_id int 用户ID
     * @param $group_id int 分组ID
     * @param $group_name string 分组名称
     * @param $parent_group_id int 父分组ID
     * @return bool
     */
    public function editGroup(&$user_id, &$group_id, &$group_name, &$parent_group_id)
    {
        $group_dao = new DocumentGroupDao();
        if (!($project_id = $group_dao->checkGroupPermission($group_id, $user_id))) {
            return FALSE;
        }
        if ($parent_group_id && !$group_dao->checkGroupPermission($parent_group_id, $user_id)) {
            return FALSE;
        }
        if ($group_dao->editGroup($group_id, $group_name, $parent_group_id)) {
            //更新项目的更新时间
            $project_dao = new ProjectDao();
            $project_dao->updateProjectUpdateTime($project_id);

            //将操作写入日志
            $log_dao = new ProjectLogDao();
            $log_dao->addOperationLog($project_id, $user_id, ProjectLogDao::$OP_TARGET_PROJECT_DOCUMENT_GROUP, $group_id, ProjectLogDao::$OP_TYPE_UPDATE, "修改项目文档分组:'{$group_name}'", date("Y-m-d H:i:s", time()));

            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 更新分组排序
     * @param $project_id int 项目ID
     * @param $order_list string 排序列表
     * @param $user_id int 用户ID
     * @return bool
     */
    public function updateGroupOrder(&$project_id, &$order_list, &$user_id)
    {
        $project_dao = new ProjectDao();
        if (!$project_dao->checkProjectPermission($project_id, $user_id)) {
            return FALSE;
        }
        $group_dao = new DocumentGroupDao();
        $result = $group_dao->updateGroupOrder($project_id, $order_list);
        if ($result) {
            //将操作写入日志
            $log_dao = new ProjectLogDao();
            $log_dao->addOperationLog($project_id, $user_id, ProjectLogDao::$OP_TARGET_PROJECT_DOCUMENT_GROUP, $project_id, ProjectLogDao::$OP_TYPE_UPDATE, "修改项目文档分组排序", date('Y-m-d H:i:s', time()));
            return TRUE;
        } else {
            return FALSE;
        }
    }
}