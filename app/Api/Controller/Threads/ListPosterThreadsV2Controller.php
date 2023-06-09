<?php
/**
 * Copyright (C) 2021 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Api\Controller\Threads;

use App\Common\ResponseCode;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Thread;
use App\Models\Category;
use App\Models\Setting;
use Discuz\Base\DzqController;
use App\Models\Attachment;
use Illuminate\Http\Request;

class ListPosterThreadsV2Controller extends DzqController
{

    public function main()
    {
        $categoryIds = $this->inPut('categoryids');
        $threads = Thread::query()->select(['id', 'category_id', 'title']);
        if (!empty($categoryIds)) {
            if (!is_array($categoryIds)) {
                $categoryIds = [$categoryIds];
            }
        }

        $isMiniProgramVideoOn = Setting::isMiniProgramVideoOn();
        if(!$isMiniProgramVideoOn){
            $threads = $threads->where('type', '<>', Thread::TYPE_OF_VIDEO);
        }

        $groups = $this->user->groups->toArray();
        $groupIds = array_column($groups, 'id');
        $permissions = Permission::categoryPermissions($groupIds);

        $categoryIds = Category::instance()->getValidCategoryIds($this->user, $categoryIds);
        if (!$categoryIds) {
            $this->outPut(ResponseCode::SUCCESS, '', []);
        }else{
            $threads = $threads->whereIn('category_id', $categoryIds);
        }

        $threads = $threads
            ->where('is_poster', 1)
            ->whereNull('deleted_at')
            ->get();
        $threadIds = $threads->pluck('id')->toArray();
        $posts = Post::query()
            ->whereIn('thread_id', $threadIds)
            ->whereNull('deleted_at')
            ->where('is_first', Post::FIRST_YES)
            ->get()->pluck(null, 'thread_id');
        $data = [];
        $linkString = '';
        // 获取当前网址url
        $url = Request::capture()->getSchemeAndHttpHost();
        foreach ($threads as $thread) {
            $title = $thread['title'];
            $id = $thread['id'];
            if (empty($title)) {
                if (isset($posts[$id])) {
                    $title = Post::instance()->getContentSummary($posts[$id]);
                }
            }
            $postsid = Post::query()->where('thread_id', $thread['id'])->first();
            $attachment = Attachment::query()->where('type_id', $postsid['id'])->where('type', 5)->get()->toArray();
            $urls = str_replace("public/","storage/",$url.'/'.$attachment[0]['file_path'].$attachment[0]['attachment']);
            $linkString .= $title;
            $data [] = [
                'pid' => $thread['id'],
                'categoryId' => $thread['category_id'],
                'title' => $title,
                'canViewPosts' => $this->canViewPosts($thread, $permissions),
                'url' => $urls,
                /*'attachment' => [
                    'id' => $attachment[0]['id'],
                    'type' => $attachment[0]['type'],
                    'url' => $urls,
                    ],*/
            ];
        }
        list($search, $replace) = Thread::instance()->getReplaceString($linkString);
        foreach ($data as &$item) {
            $item['title'] = str_replace($search, $replace, $item['title']);
        }
        $this->outPut(ResponseCode::SUCCESS, '', $data);
    }


    private function canViewPosts($thread, $permissions)
    {
        if ($this->user->isAdmin() || $this->user->id == $thread['user_id']) {
            return true;
        }
        $viewPostStr = 'category' . $thread['category_id'] . '.thread.viewPosts';
        if (in_array('thread.viewPosts', $permissions) || in_array($viewPostStr, $permissions)) {
            return true;
        }
        return false;
    }
}
