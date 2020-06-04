<?php
/**
 * This file is part of the wangningkai/olaindex.
 * (c) wangningkai <i@ningkai.wang>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Models;

use App\Models\Traits\HelperModel;
use Illuminate\Database\Eloquent\Model;
use Cache;

class Setting extends Model
{
    use  HelperModel;

    /**
     * @var array $fillable
     */
    protected $fillable = ['name', 'value'];

    /**
     * @var array $casts
     */
    protected $casts = [
        'id' => 'int',
    ];

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 如果 value 是 json 则转成数组
     *
     * @param $value
     * @return mixed
     */
    public function getValueAttribute($value)
    {
        return is_json($value) ? json_decode($value, true) : $value;
    }

    /**
     * 如果 value 是数组 则转成 json
     *
     * @param $value
     */
    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 批量更新
     * @param $data
     * @return mixed
     */
    public static function batchUpdate($data)
    {
        $editData = [];
        foreach ($data as $k => $v) {
            $editData[] = [
                'name' => $k,
                'value' => is_array($v) ? json_encode($v) : $v
            ];
        }
        // 查询数据库中是否有配置
        $saved = self::query()->pluck('name')->all();

        $newData = collect($editData)->filter(static function ($value) use ($saved) {
            return !in_array($value['name'], $saved, false);
        })->toArray();
        $editData = collect($editData)->reject(static function ($value) use ($saved) {
            return !in_array($value['name'], $saved, false);
        })->toArray();
        // 存在新数据先插入
        if ($newData) {
            self::query()->insert($newData);
        }

        $model = new self;
        $model->updateBatch($editData);

        Cache::forget('setting');

        return $data;
    }
}
