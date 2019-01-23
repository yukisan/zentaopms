<?php
class xuanxuanChat extends chatModel
{
    public function getExtensionList($userID)
    {
        $entries = array();
        $baseURL = $this->getServer('zentao');

        $this->loadModel('user');
        $user = $this->dao->select('*')->from(TABLE_USER)->where('id')->eq($userID)->fetch();
        $user->admin  = strpos($this->app->company->admins, ",{$user->account},") !== false;
        $user->rights = $this->user->authorize($user->account);
        $user->groups = $this->user->getGroups($user->account);
        $user->view   = $this->user->grantUserView($user->account, $user->rights['acls']);

        $this->session->set('user', $user);
        $this->app->user = $this->session->user;

        $products  = $this->loadModel('product')->getPairs();
        $projects  = $this->loadModel('project')->getPairs();
        $products  = empty($products) ? array() : array_keys($products);
        $projects  = empty($projects) ? array() : array_keys($projects);
        $libIdList = array_keys($this->loadModel('doc')->getLibs('all'));
        $productID = isset($products[0])  ? $products[0]  : 1;
        $projectID = isset($projects[0])  ? $projects[0]  : 1;
        $libID     = isset($libIdList[0]) ? $libIdList[0] : 1;

        $actions = new stdclass();
        if(common::hasPriv('bug',   'create') and !empty($products)) $actions->createBug   = array('title' => $this->lang->chat->createBug,   'url' => $baseURL . str_replace('/xuanxuan.php', '/index.php', helper::createLink('bug', 'create', "product=$productID", 'xhtml')), 'height' => "600px", 'width' => "800px");
        if(common::hasPriv('doc',   'create') and !empty($libIdList))$actions->createDoc   = array('title' => $this->lang->chat->createDoc,   'url' => $baseURL . str_replace('/xuanxuan.php', '/index.php', helper::createLink('doc', 'create', "libID=$libID", 'xhtml')), 'height' => "600px", 'width' => "800px");
        if(common::hasPriv('story', 'create') and !empty($products)) $actions->createStory = array('title' => $this->lang->chat->createStory, 'url' => $baseURL . str_replace('/xuanxuan.php', '/index.php', helper::createLink('story', 'create', "product=$productID", 'xhtml')), 'height' => "600px", 'width' => "800px");
        if(common::hasPriv('task',  'create') and !empty($projects)) $actions->createTask  = array('title' => $this->lang->chat->createTask,  'url' => $baseURL . str_replace('/xuanxuan.php', '/index.php', helper::createLink('task', 'create', "project=$projectID", 'xhtml')), 'height' => "600px", 'width' => "800px");
        if(common::hasPriv('todo',  'create')) $actions->createTodo = array('title' => $this->lang->chat->createTodo,  'url' => $baseURL . str_replace('/xuanxuan.php', '/index.php', helper::createLink('todo', 'create', '', 'xhtml')), 'height' => "600px", 'width' => "800px");

        $urls = array();
        foreach($this->config->chat->cards as $moduleName => $methods)
        {
            foreach($methods as $methodName => $size)
            {
                if($this->config->requestType == 'GET')
                {
                    $url = $this->config->webRoot . "index.php?m={$moduleName}&f={$methodName}";
                }
                else
                {
                    $url = $this->config->webRoot . "{$moduleName}-{$methodName}-";
                }
                $urls[$url] = $size;
            }
        }

        $data = new stdClass();
        $data->entryID     = 1;
        $data->name        = 'zentao-integrated';
        $data->displayName = $this->lang->chat->zentaoIntegrate;
        $data->webViewUrl  = trim($baseURL . $this->config->webRoot, '/');
        $data->download    = $baseURL . $this->config->webRoot . 'data/xuanxuan/zentao-integrated.zip';
        $data->md5         = md5_file($this->app->getDataRoot() . 'xuanxuan/zentao-integrated.zip');

        $data->data['actions']  = $actions;
        $data->data['urls']     = $urls;
        $data->data['entryUrl'] = trim($baseURL . $this->config->webRoot, '/');

        $entries[] = $data;
        unset($_SESSION['user']);
        return $entries;
    }

    public function getUserListOutput($idList = array(), $userID)
    {
        $output = new stdclass();
        $output->module = 'chat';
        $output->method = 'userGetList';

        $users = $this->getUserList($status = '', $idList, $idAsKey = false);
        if(dao::isError())
        {
            $output->result  = 'fail';
            $output->message = 'Get userlist failed.';
        }
        else
        {
            $output->result = 'success';
            $output->users  = !empty($userID) ? array($userID) : array();
            $output->data   = $users;

            $this->app->loadLang('user', 'sys');
            $roles = $this->lang->user->roleList;

            $allDepts = $this->loadModel('dept')->getListByType('dept');
            $depts = array();
            foreach($allDepts as $id => $dept)
            {
                $depts[$id] = array('name' => $dept->name, 'order' => (int)$dept->order, 'parent' => (int)$dept->parent);
            }
            $output->roles = $roles;
            $output->depts = $depts;
        }
        return $output;
    }
}
