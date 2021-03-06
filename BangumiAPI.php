<?php
    
    /** 
    *BangumiAPI文件 
    */  

    class BangumiAPI
    {
        /** OooOooOooO **/
        private static $bangumiAPI = null;
        /** 静态成员 **/
        //应用程序名
        private  static $appName = "BGMYetu";
        //api链接
        private static $apiUrl = "https://api.bgm.tv";
        /** 成员 **/
        //用户名（邮箱）
        public  $userName = "";
        //密码
        public  $passWord = "";
        //用户id
        private  $userID = "";
        //auth
        private  $auth = "";
        //auth urlencoding
        private  $authEncode = "";
        //收藏
        private $myCollection;
        //登陆api
        private  $loginApi = "";
        //收藏api
        private  $collectionApi = ""; 
        /** 方法 **/
        //OooOooO
        public static function GetInstance()
            {
            if (BangumiAPI::$bangumiAPI == null) {
            BangumiAPI::$bangumiAPI = new BangumiAPI();
            }
            return BangumiAPI::$bangumiAPI;
        }
        //构造方法
        private function __construct()
            {
            //echo "构造方法";
        }
        //对象属性初始化
        public function init($_userName,$_passWord,$_apiUrl = null)
            {
            if($_apiUrl){
                BangumiAPI::$apiUrl = $_apiUrl;
            }
            if ($_userName == null || $_passWord == null) {
            //程序返回
            return "初始化参数错误！";
            }
            $this->userName = $_userName;
            $this->passWord = $_passWord;
            //登陆api
            $this->loginApi = BangumiAPI::$apiUrl . "/auth?source=" . BangumiAPI::$appName;
            //用户id为空或auth为空
            if ($this->userID == ""  || $this->authEncode == ""){
            //登陆post字符串
            $postData = array('username' => $this->userName , 'password' => $this->passWord);
            //获取登陆返回json
            $userContent = BangumiAPI::http_post_contents($this->loginApi,$postData);
            //json to object
            $userData = json_decode($userContent);
            //存在error属性
            if (property_exists($userData, "error")) {
                //输出错误信息
                return "登陆错误：" . $userData->error;
                //程序返回
            }
            //初始化
            $this->userID = $userData->id;
            $this->auth = $userData ->auth;
            $this->authEncode = $userData ->auth_encode;
            }
            //初始化收藏字符串
            $this->collectionApi = BangumiAPI::$apiUrl . "/user/" . $this->userID ."/collection?cat=playing";
            return true;
        }
        //获取收藏json
        public function GetCollection()
            {
            if ($this->userID == "" || $this->collectionApi == "") {
            return null;
            }
            return BangumiAPI::http_get_contents($this->collectionApi);
        }
        //格式化收藏
        public function ParseCollection($content = null)
        {
            if($content == null || !$content)
            {
                $content = $this->GetCollection();
            }
            if ($content == null || $content == "") {
            echo "<br>ParseCollection:获取失败";
            return;
            }
            //返回不是json
            if (strpos($content, "[{") != false && $content != "") {
            echo "用户不存在！";
            return;
            }
            $collData = json_decode($content);
            if (sizeof($collData) == 0 || $collData == null) {
            //echo "还没有记录哦~";
            return;
            }
            $index = 0;
            foreach ($collData as $value) {
            $name = $value->name;
            $name_cn = $value->subject->name_cn;
            $theurl = $value->subject->url;
            $img_grid =$value->subject->images->grid;
            $this->myCollection[$index++] = $value;
            }
        }


        //打印收藏
        public function PrintCollecion($flag = true)
        {
            if ($this->myCollection == null) {
                $this->ParseCollection();
            }
            switch ($flag) {
            case true:
                if (sizeof($this->myCollection) == 0 || $this->myCollection == null) {
                echo "还没有记录哦~";
                return;
            }

            foreach ($this->myCollection as $value) {
                //print_r($value);
                //$id = $value->subject->id;
                $epsNum = '未知';
                if(@$value->subject->eps){
                    $epsNum = $value->subject->eps;
                }
                $progressNum = $value->ep_status;
                $myProgress = $progressNum . "/" . $epsNum;
                $name = $value->name;
                $name_cn = $value->subject->name_cn;
                if(@!$name_cn){
                    $name_cn = $name;
                }
                $air_date = $value->subject->air_date;
                $theurl = $value->subject->url;
                $img_grid =str_replace("http://", "//", $value->subject->images->common);
                $progressWidth = 0;
                if($epsNum=='未知'){
                    $progressWidth = 100;
                }else{
                    $progressWidth = $progressNum / $epsNum * 100;
                    if($progressWidth>100){
                        $progressWidth = 100;
                    }
                }
                echo "
                <a href=" . $theurl ." target='_blank' class='bangumItem'>
                    <img src='$img_grid' />
                    <div class='textBox'>$name_cn<br>
                    $name<br>
                    首播日期：$air_date<br>
                    <div class='jinduBG'>
                    <div class='jinduText'>进度:$myProgress</div>
                    <div class='jinduFG' style='width:" . $progressWidth . "%;'>
                    </div>
                    </div>
                    </div>
                </a>";
                //echo print_r($value);
            }
            break;
            case false:
                        echo $myCollection;
            break;
            default:
                    break;
            }
        }
        //get获取内容
        private static function http_get_contents($_url)
        {
            $m_req = wp_remote_get($_url,array("user-agent"=>"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:75.0) Gecko/20100101 Firefox/75.0"));
            $m_res = wp_remote_retrieve_body($m_req);
            return $m_res;
        }
        //post获取内容
        private static function http_post_contents($_url,$_postBody  = array())
        {
            $_postdata  = array(
                "user-agent"=>"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:75.0) Gecko/20100101 Firefox/75.0",
                'body' => $_postBody
            );
            $m_req = wp_remote_post($_url,$_postdata);
            $m_res = wp_remote_retrieve_body($m_req);
            return $m_res;
        }
    }
        //ajax处理函数
        function GetBangumiData(){
            $BangumiOptions = get_option('zm_bangumi');
            if(is_array($BangumiOptions) && $BangumiOptions["bangumiAccount"] && $BangumiOptions["bangumiPwd"]){
                $userId = $BangumiOptions["bangumiAccount"];
                $password = $BangumiOptions["bangumiPwd"];
                $isCache = (bool)$BangumiOptions["isCache"];
                $isProxy = (bool)$BangumiOptions["isProxy"];
                $mAPI = ($isProxy ? "http://api.bgm.atkoi.cn" : null);
                $mainColor = $BangumiOptions["color"];
                $singleItemNum = $BangumiOptions["singleItemNum"];
                $singleNavNum = $BangumiOptions["singleNavNum"];
                $bangum = BangumiAPI::GetInstance();
                
                $content = null;
                $zmBangumiResCode = 200;

                if($isCache){
                    $cachePath = __DIR__ . "/BangumiCache/";//缓存文件夹
                    $nowCache = date("Y-m-d") . ".json";//缓存文件名
                    $fullPath = $cachePath . $nowCache;
                    //$content = null;
                    if(is_file($fullPath)){
                        //echo "有缓存<br>";
                        $myfile = fopen($fullPath,"r");
                        $content = fread($myfile,filesize($fullPath));
                        fclose($myfile);
                    }else{
                        //echo "开启了缓存，但未缓存<br>";
                        //缓存文件夹不存在

                        if(!is_dir($cachePath))
                        {
                            mkdir ($cachePath,0755,true);
                        }
                        $loginRes = $bangum->init($userId,$password,$mAPI);
                        if(!($loginRes === true)){
                            $content = '"' . $loginRes . '"';
                        }else{
                            //删除之前存在的缓存
                            $allCaches = scandir($cachePath);
                            foreach($allCaches as $val){
                                if($val != "." && $val != "..")
                                {
                                    if(!is_dir($cachePath.$val)){
                                        unlink($cachePath.$val);
                                    }
                                }
                            }

                            $myfile = fopen($fullPath, "w");
                            $content = $bangum->GetCollection();
                            fwrite($myfile,$content);
                            fclose($myfile);
                        }
                    }
                }else{
                    $loginRes = $bangum->init($userId,$password,$mAPI);
                    if(!($loginRes === true)){
                        $content = '"' . $loginRes .'"';
                    }else{
                        $content = $bangum->GetCollection();
                    }
                }
                
            }else{
                $zmBangumiResCode = 202;
                $content = "Error,是不是忘记在后台填写Bangumi用户名与密码呢？";
            }
            $zmBangumiRes = '{
                "messageType": "zm_bangumi_data",
                "messageCode": ' . $zmBangumiResCode .',
                "messageContent": {
                    "singleItemNum": '. (intval($singleItemNum) <= 0?  6 : $singleItemNum) .',
                    "singleNavNum":'. (intval($singleNavNum) <= 0 ? 3 : $singleNavNum) .',
                    "mainColor": "'. (empty($mainColor) ? '#ff8c83': $mainColor) .'",
                    "content": ' . (empty($content) ? "[]" : $content)  . '
                    }
                }
                ';
            echo $zmBangumiRes;
            die();
        }
    
        add_action("wp_ajax_nopriv_GetBangumiData","GetBangumiData");
        add_action("wp_ajax_GetBangumiData","GetBangumiData");

?>