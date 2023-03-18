<?php

namespace App\Console\Commands;
use Carbon\Carbon;
use Discuz\Console\AbstractCommand;
use Discuz\Foundation\Application;
use App\Commands\ChatGpt\ChatGpt;
use App\Models\ChatGptKernel;
use App\Models\Post;
use App\Models\Thread;
use Discuz\Contracts\Setting\SettingsRepository;

class ChatGptCommand extends AbstractCommand
{
    protected $signature = 'ChatGpt';

    protected $description = 'ChatGpt后台';

    protected $app;
    protected $ChatGpt;

    /**
     * AvatarCleanCommand constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct();

        $this->app = $app;
        $this->ChatGpt = new ChatGpt();
    }

    public function handle()
    {
        $settings=app(SettingsRepository::class);;
        $aiuid = $settings->get('aiuid', 'chatgpt');
        $this->info('开始执行');
        while (true){
            $Kernels = ChatGptKernel::query()->where('status', 0)->orderBy('id', 'asc')->get()->toArray();
            foreach ($Kernels as $do){
                ChatGptKernel::query()->where('id', $do['id'])->update(['status' => 2]);//任务状态改为执行中
                $this->info($do['toid']);
                if ($do['msg_type'] == 0){
                    $arr = array();
                    $t = Post::query()->where('thread_id', $do['toid'])->where('is_first', 1)->first();
                    $t['content']  = htmlspecialchars_decode($t['content']);//把一些预定义的 HTML 实体转换为字符
                    $t['content']  = str_replace("&nbsp;","",$t['content']);//将空格替换成空
                    $t['content']  = strip_tags($t['content']);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
                    $this->info($t['content']);

                    array_push($arr,['role'=> 'user', 'content'=> $t['content']]);
                    var_dump($arr);
                    $this->ChatGpt->retid($do['toid'],$arr);
                }else{
                    $arr = array();
                    $postdata = Post::query()->where('thread_id', $do['toid'])->get()->toArray();
                    foreach ($postdata as $v){
                        if ($v['is_approved'] == 1 && $v['deleted_at'] == null) {
                            $v['content'] = htmlspecialchars_decode($v['content']);//把一些预定义的 HTML 实体转换为字符
                            $v['content'] = str_replace("&nbsp;","",$v['content']);//将空格替换成空
                            $v['content'] = strip_tags($v['content']);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
                            $this->info($v['content']);

                            if ($v['user_id'] == $aiuid){
                                array_push($arr,['role'=> 'assistant', 'content'=> $v['content']]);
                            }else{
                                array_push($arr,['role'=> 'user', 'content'=> $v['content']]);
                            }
                        }
                    };
                    $this->ChatGpt->retid($do['toid'],$arr);
                }

                ChatGptKernel::query()->where('id', $do['id'])->update(['status' => 1]);//任务状态改为完成
            }
            sleep(5);
        }
        $this->info('清理未发布主题视频数量：');
    }
}
