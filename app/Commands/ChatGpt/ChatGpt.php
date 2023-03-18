<?php
namespace App\Commands\ChatGpt;

use App\Models\Thread;
use Discuz\Qcloud\QcloudManage;
use Discuz\Foundation\Application;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Support\Arr;
use Exception;

class ChatGpt
{
    public $settings = '';
    public $fandaiurl = '';
    public $hosturl = '';
    public $airenge = '';
    public $apikey = '';
    public $aiusername = '';
    public $aipassword = '';
    public $access_token = '';
    public $model = '';
    public $app;

    public $postData = [
        "model" => "gpt-3.5-turbo",
        "temperature" => 0.9,
        "stream" => false,
        "messages" => [],
    ];

    public $headers  = [];

    public function __construct() {
        $this->settings=app(SettingsRepository::class);
        $this->app = app(Application::class);
        $this->fandaiurl=$this->settings->get('fandaiurl', 'chatgpt');
        $this->airenge=$this->settings->get('airenge', 'chatgpt');
        $this->apikey=$this->settings->get('apikey', 'chatgpt');
        $this->aiusername=$this->settings->get('aiusername', 'chatgpt');
        $this->aipassword=$this->settings->get('aipassword', 'chatgpt');
        $this->hosturl=$this->settings->get('hosturl', 'chatgpt');
        $this->model=$this->settings->get('model', 'chatgpt');

        $this->headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apikey
        ];

//        $this->postData['model'] = $this->model;

        $rg = [
            'role'=> 'system',
            'content'=> $this->airenge
        ];
        array_push($this->postData['messages'],$rg);
        $this->access_token=$this->login();
    }

    public function login(){
        $header = array("Content-Type:application/json");

        $url = $this->hosturl."/api/login";


        $data = [
            'data'=>[
                'attributes'=>[
                    'username'=> $this->aiusername,
                    'password'=> $this->aipassword,
                ]
            ],
        ];

        $content = $this->curlPost($url, $data,  5, $header, "json");
        file_put_contents('./1.txt', 'login'.$content."\r\n", FILE_APPEND);
        $content = json_decode($content,true);

        $access_token = $content['data']['attributes']['access_token'];

        return $access_token;
    }

    public function repost($tid,$content){
        $header = array("Content-Type:application/json","authorization: Bearer ".$this->access_token);

        $url = $this->hosturl."/api/posts";

        $data = [
            'data'=>[
                'attributes'=>[
                    'content'=> $content,
                ],
                'relationships'=>[
                    'attachments'=> [
                        "data"=>[]
                    ],
                    'thread'=> [
                        "data"=>[
                            'id'=> $tid,
                            'type'=> "threads",
                        ]
                    ],
                ],
                'type'=>"posts"
            ],
        ];

        $content = $this->curlPost($url, $data,  5, $header, "json");
        file_put_contents('./1.txt', 'repost'.$content."\r\n", FILE_APPEND);
        $content = json_decode($content,true);
        return $content;
    }

    public function retid($touser,$content=[]){
        $tinfo = Thread::query()->where('id', $touser)->first();
        if ($tinfo->is_approved == 1){
            $usertext = $content[count($content)-1]['content'];
            file_put_contents('./1.txt', 'usertext'.json_encode($content)."\r\n", FILE_APPEND);
            file_put_contents('./1.txt', 'usertext'.$usertext."\r\n", FILE_APPEND);
            if ($this->tencentCloudCheck($usertext)){
                $text = $this->sendtext($content);
                if (!empty($text)){
                    return $this->repost($touser,$text);
                }
            }else{
                return $this->repost($touser,'您的问题包含违规内容,不能回答~');
            }
        }
    }

    public function sendtext($content=[]){
        $postData = $this->postData;
        if (!empty($content)){
            foreach ($content as $v){
                array_push($postData['messages'],$v);
            }
        }
        file_put_contents('./1.txt', 'sendtext'.json_encode($postData)."\r\n", FILE_APPEND);
        $content = $this->curl_request(json_encode($postData));
        file_put_contents('./1.txt', 'content'.$content['content']."\r\n", FILE_APPEND);
        file_put_contents('./1.txt', 'total_tokens'.$content['total_tokens']."\r\n", FILE_APPEND);
        file_put_contents('./1.txt', 'completion_tokens'.$content['completion_tokens']."\r\n", FILE_APPEND);
        return $content['content'];
    }

    public function tencentCloudCheck($content){
        if ($this->settings->get('qcloud_cms_text', 'qcloud', false)){
            $qcloud = $this->app->make('qcloud');
            $result = $qcloud->service('cms')->TextModeration($content);
            file_put_contents('./1.txt', 'tencentCloudCheck'.json_encode($result)."\r\n", FILE_APPEND);
            $keyWords = Arr::get($result, 'Data.Keywords', []);


            if (isset($result['Data']['DetailResult'])) {
                /**
                 * filter 筛选腾讯云敏感词类型范围
                 * Normal：正常，Polity：涉政，Porn：色情，Illegal：违法，Abuse：谩骂，Terror：暴恐，Ad：广告，Custom：自定义关键词
                 */
                $filter = ['Normal', 'Ad']; // Tag Setting 可以放入配置
                $filtered = collect($result['Data']['DetailResult'])->filter(function ($item) use ($filter) {
                    if (in_array($item['EvilLabel'], $filter)) {
                        $item = [];
                    }
                    return $item;
                });

                $detailResult = $filtered->pluck('Keywords');
                $detailResult = Arr::collapse($detailResult);
                $keyWords = array_merge($keyWords, $detailResult);
            }

            if (!blank($keyWords)) {
                return false;
            }
        }
        return true;
    }

    public function curl_request($postData){
        $curl = curl_init();

        $callback = function ($ch, $data) {
            file_put_contents('./1.txt', 'callback'.$data."\r\n", FILE_APPEND);
            $complete = json_decode($data);
            if (isset($complete->error)) {
                file_put_contents('./1.txt', 'callback'.$complete->error->message."\r\n", FILE_APPEND);
            }
            return strlen($data);
        };

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->fandaiurl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $this->headers,
//            CURLOPT_WRITEFUNCTION =>$callback,
        ));

        $response = curl_exec($curl);
        file_put_contents('./1.txt', 'curl_request'.$response."\r\n", FILE_APPEND);
        if($response=== FALSE ){
            $data = "CURL Error:".curl_error($curl);
            file_put_contents('./1.txt', 'curl_request'.$data."\r\n", FILE_APPEND);
            throw new Exception($data);
        }

        //{"total_tokens": response["usage"]["total_tokens"],
        //                    "completion_tokens": response["usage"]["completion_tokens"],
        //                    "content": response.choices[0]['message']['content']}
        $response = json_decode($response,true);

        $answer = [
            "total_tokens"=> $response["usage"]["total_tokens"],
            "completion_tokens"=> $response["usage"]["completion_tokens"],
            "content"=> $response["choices"][0]['message']['content']
        ];

//        if (substr(trim($response), -6) == "[DONE]") {
//            $response = substr(trim($response), 0, -6) . "{";
//        }
//        $responsearr = explode("}\n\ndata: {", $response);
//
//        foreach ($responsearr as $msg) {
//            $contentarr = json_decode("{" . trim($msg) . "}", true);
//            if (isset($contentarr['choices'][0]['delta']['content'])) {
//                $answer .= $contentarr['choices'][0]['delta']['content'];
//            }
//        }

        curl_close($curl);
        return $answer;
    }

    public function curlPost($url, $post_data = array(), $timeout = 5, $header = "", $data_type = "")
    {
        $header = empty($header) ? '' : $header;
        //支持json数据数据提交
        if ($data_type == 'json') {
            $post_string = json_encode($post_data);
        } elseif ($data_type == 'array') {
            $post_string = $post_data;
        } elseif (is_array($post_data)) {
            $post_string = http_build_query($post_data, '', '&');
        }

        $ch = curl_init();    // 启动一个CURL会话
        curl_setopt($ch, CURLOPT_URL, $url);     // 要访问的地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 对认证证书来源的检查   // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        //curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($ch, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);     // Post提交的数据包
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);     // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        //curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // 获取的信息以文件流的形式返回
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
        $result = curl_exec($ch);

        // 打印请求的header信息
        //$a = curl_getinfo($ch);
        //var_dump($a);

        if($result=== FALSE ){
            $data = "CURL Error:".curl_error($ch);
        }

        curl_close($ch);
        return $result;
    }

}
