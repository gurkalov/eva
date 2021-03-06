<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(schema="NewPosition", required={"name"},
 *     @OA\Property(property="beacons", format="array", type="array",
 *         @OA\Items(ref="#/components/schemas/BeaconSignal")
 *     )
 * )
 */

/**
 * @OA\Schema(schema="Position", type="object", required={"id", "user_id"},
 *    @OA\Property(property="id", format="int64", type="integer", example=1),
 *    @OA\Property(property="user_id", format="int64", type="integer", example=1),
 *    @OA\Property(property="created_at", format="string", type="string", example="2019-01-26 20:00:57"),
 *    @OA\Property(property="beacons", format="array", type="array", nullable=true,
 *        @OA\Items(ref="#/components/schemas/BeaconSignal")
 *    ),
 *    @OA\Property(property="routers", format="array", type="array", nullable=true,
 *        @OA\Items(ref="#/components/schemas/RouterSignal")
 *    )
 * )
 */

class Position extends Model
{
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'routers' => 'array',
        'beacons' => 'array',
    ];

    protected $fillable = [
        'routers',
        'beacons',
    ];

    protected $visible = [
        'id',
        'user_id',
        'routers',
        'beacons',
        'created_at'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function($model) {
            if (!is_array($model->routers)) {
                $model->routers = [];
            }
            if (!is_array($model->beacons)) {
                $model->beacons = [];
            }
        });

        static::created(function($model) {
            if (is_array($model->routers)) {
                foreach ($model->routers as $router) {
                    $validRouter = array_change_key_case($router, CASE_LOWER);
                    $routerModel = Router::firstOrNew([
                        'bssid' => $validRouter['bssid']
                    ]);
                    $routerModel->ssid = $validRouter['ssid'];
                    $routerModel->save();
                }
            }
        });
    }
}
