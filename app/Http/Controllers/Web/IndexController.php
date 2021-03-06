<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Tag;
use App\Models\Team;
use Illuminate\Http\Request;

class IndexController extends BaseController
{
    public $tagModel = null;

    public $limit = 0;

    public function __construct(Tag $tag)
    {
        $this->tagModel = $tag;
    }

    public function index()
    {

    }

    /**
     * @return \Dingo\Api\Http\Response
     */
    public function drawClass()
    {
        // 页面标签数据
        $hotTags = $this->tagModel->where('order', '>', 0)
            ->orderBy('order')
            ->get(['id', 'tag', 'order'])
            ->toArray();

        // 页面卡片数据
        $teamsData = [];
        $teamQuery = Team::leftJoin('categories', 'categories.id', '=', 'teams.category_id')
            ->orderByRaw('categories.order, teams.created_at desc')
            ->get([
                'teams.name',
                'teams.avatar',
                'teams.homepage',
                'teams.class_start_date',
                'teams.desc',
                'teams.tags_id',
                'teams.category_id',
                'categories.title as category_name'
            ])->groupBy(function ($gv) {
                return $gv->category_id . '-' . $gv->category_name;
            });
        $this->limit = 7;
        $this->handleItems($teamQuery, $teamsData);

        $data = [
            'hot_tags' => $hotTags,
            'teams'    => collect($teamsData)->values(),
        ];

        return $this->response->array($data);
    }

    /**
     * @param $categoryId
     * @return \Dingo\Api\Http\Response
     */
    public function more($categoryId)
    {
        if (empty($categoryId)) {
            $categoryId = 0;
        }
        // 分类卡片数据
        $teamsData = [];
        $teamQuery = Team::leftJoin('categories', 'categories.id', '=', 'teams.category_id')
            ->where('teams.category_id', $categoryId)
            ->orderByRaw('categories.order, teams.created_at desc')
            ->get([
                'teams.name',
                'teams.avatar',
                'teams.homepage',
                'teams.class_start_date',
                'teams.desc',
                'teams.tags_id',
                'teams.category_id',
                'categories.title as category_name'
            ])->groupBy(function ($gv) {
                return $gv->category_id . '-' . $gv->category_name;
            });
        $this->handleItems($teamQuery, $teamsData);

        $data = [
            'teams' => collect($teamsData)->values(),
        ];

        return $this->response->array($data);
    }

    /**
     * @param $data
     * @param $teamsData
     * @param $limit
     */
    public function handleItems($data, &$teamsData)
    {
        $data->map(function ($v, $categoryKey) use (&$teamsData) {
            $items = $v->toArray();
            // 首页每个分类只展示前4个
            [$categoryId, $categoryName] = explode('-', $categoryKey);
            $teamsData[$categoryId] = [
                'category_id'   => $categoryId,
                'category_name' => $categoryName,
                'items'         => [],
            ];
            foreach ($items as $key => $item) {
                if ($this->limit != 0) {
                    if ($key > $this->limit) {
                        break;
                    }
                }
                $tagsId = explode(',', $item['tags_id']);
                $tagsResult = $this->tagModel->whereIn('id', $tagsId)->get(['id', 'tag']);
                $tagsName = [];
                if (!$tagsResult->isEmpty()) {
                    $tagsName = $tagsResult->toArray();
                }
                $item['tags_name'] = $tagsName;
                $teamsData[$item['category_id']]['items'][] = $item;
            }
        });
    }

    /**
     * @param $tagId
     * @return \Dingo\Api\Http\Response
     */
    public function tagTeams($tagId)
    {
        if (empty($tagId)) {
            $tagId = 0;
        }
        // 分类卡片数据
        $teamsData = [];
        $teamQuery = Team::leftJoin('categories', 'categories.id', '=', 'teams.category_id')
            ->whereRaw("FIND_IN_SET('$tagId', teams.tags_id)")
            ->orderByRaw('categories.order, teams.created_at desc')
            ->get([
                'teams.name',
                'teams.avatar',
                'teams.homepage',
                'teams.class_start_date',
                'teams.desc',
                'teams.tags_id',
                'teams.category_id',
                'categories.title as category_name'
            ])->groupBy(function ($gv) {
                return $gv->category_id . '-' . $gv->category_name;
            });
        $this->handleItems($teamQuery, $teamsData);

        $data = [
            'teams' => collect($teamsData)->values(),
        ];

        return $this->response->array($data);
    }

    /**
     * @return \Dingo\Api\Http\Response
     */
    public function SiteNav()
    {
        $categoryList = $siteList = [];
        $siteQuery = Site::leftJoin('categories', 'categories.id', '=', 'sites.category_id')
            ->orderByRaw('categories.order, sites.created_at desc')
            ->get([
                'sites.name',
                'sites.thumb',
                'sites.url',
                'sites.desc',
                'sites.tags_id',
                'sites.category_id',
                'categories.title as category_name'
            ])->groupBy(function ($gv) {
                return $gv->category_id . '-' . $gv->category_name;
            });

        $siteQuery->map(function ($v, $categoryKey) use (&$siteList, &$categoryList) {
            $items = $v->toArray();
            [$categoryId, $categoryName] = explode('-', $categoryKey);
            array_push($categoryList, [
                'category_id'   => $categoryId,
                'category_name' => $categoryName,
            ]);
            $siteList[$categoryId] = [
                'category_id'   => $categoryId,
                'category_name' => $categoryName,
                'items'         => [],
            ];
            foreach ($items as $key => $item) {
                $tagsName = [];
                if (!empty($item['tags_id'])) {
                    $tagsId = explode(',', $item['tags_id']);
                    $tagsResult = $this->tagModel->whereIn('id', $tagsId)->get(['id', 'tag']);
                    if (!$tagsResult->isEmpty()) {
                        $tagsName = $tagsResult->toArray();
                    }
                }
                $item['tags_name'] = $tagsName;
                $siteList[$item['category_id']]['items'][] = $item;
            }
        });

        $data = [
            'category_list' => $categoryList,
            'sites'         => collect($siteList)->values(),
        ];

        return $this->response->array($data);
    }
}
