<?php

namespace App\Http\Controllers;

use App\Position;
use App\Router;
use App\Beacon;
use App\User;
use App\Path;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Get(
 *     tags={"Position"},
 *     path="/api/v1/positions",
 *     description="Returns all positions",
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         description="maximum number of results to return",
 *         required=false,
 *         example="10",
 *         @OA\Schema(
 *             type="integer",
 *             format="int32"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Position response",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Position")
 *         ),
 *     ),
 * )
 */

/**
 * @OA\Get(
 *     tags={"Position"},
 *     path="/api/v1/positions/{id}",
 *     description="Returns a position based on a single ID",
 *     operationId="getPosition",
 *     @OA\Parameter(
 *         description="ID of position to fetch",
 *         in="path",
 *         name="id",
 *         required=true,
 *         example=1,
 *         @OA\Schema(
 *             type="integer",
 *             format="int64",
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Position response",
 *         @OA\JsonContent(ref="#/components/schemas/Position"),
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="unexpected error",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorModel"),
 *     )
 * )
 */

/**
 * @OA\Post(
 *     tags={"Position"},
 *     path="/api/v1/positions",
 *     operationId="createPosition",
 *     description="Creates a new position.",
 *     @OA\RequestBody(
 *         description="Position to add.",
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/NewPosition")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Position response",
 *         @OA\JsonContent(ref="#/components/schemas/Position")
 *     ),
 *     @OA\Response(
 *         response="default",
 *         description="unexpected error",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorModel"),
 *     ),
 *      security={
 *         {"bearerAuth": {"TkRVem4yTERSQTNQRHFxcmo4SUozNWZp"}}
 *     }
 * )
 */

/**
 * #OA\Put(
 *     tags={"Position"},
 *     path="/api/v1/positions/{id}",
 *     description="Update a position based on a single ID.",
 *     operationId="updatePosition",
 *     #OA\Parameter(
 *         description="ID of position to fetch",
 *         in="path",
 *         name="id",
 *         required=true,
 *         #OA\Schema(
 *             type="integer",
 *             format="int64",
 *         )
 *     ),
 *     #OA\RequestBody(
 *         description="Position to update.",
 *         required=true,
 *         #OA\MediaType(
 *             mediaType="application/json",
 *             #OA\Schema(ref="#/components/schemas/NewPosition")
 *         )
 *     ),
 *     #OA\Response(
 *         response=200,
 *         description="Position response",
 *         #OA\JsonContent(ref="#/components/schemas/Position"),
 *     ),
 *     #OA\Response(
 *         response="default",
 *         description="unexpected error",
 *         #OA\JsonContent(ref="#/components/schemas/ErrorModel"),
 *     )
 * )
 */

/**
 * #OA\Delete(
 *     tags={"Position"},
 *     path="/api/v1/positions/{id}",
 *     description="Deletes a single position based on the ID.",
 *     operationId="deletePosition",
 *     #OA\Parameter(
 *         description="ID of position to delete",
 *         in="path",
 *         name="id",
 *         required=true,
 *         #OA\Schema(
 *             format="int64",
 *             type="integer"
 *         )
 *     ),
 *     #OA\Response(
 *         response=204,
 *         description="Position deleted"
 *     ),
 *     #OA\Response(
 *         response="default",
 *         description="unexpected error",
 *         #OA\JsonContent(ref="#/components/schemas/ErrorModel"),
 *     )
 * )
 */
class PositionController extends Controller
{
    use Traits\CrudTrait;

    const MODEL = Position::class;

    /**
     * Simple strategy method
     *
     * @param  User  $user
     * @param  array $beacons
     * @return bool
     */
    protected function applySimpleStrategy(User $user, array $beacons)
    {
        $assignBeacon = self::assignBeacon($beacons);
        $beaconId = null;
        if (null === $assignBeacon) {
            return false;
        }

        $beaconId = $assignBeacon->id;
        $user->location_id = $assignBeacon->location_id;
        self::createPath($user, $beaconId);
        $user->beacon_id = $beaconId;

        return true;
    }

    /**
     * Sum strategy method
     *
     * @param  User  $user
     * @param  integer $strategy
     * @return void
     */
    protected function applyFlowStrategy(User $user, $strategy)
    {
        $lastPositions = Position::where('user_id', $user->id)->orderBy('id', 'desc')->limit($strategy)->get();
        $countLastPositions = $lastPositions->count();

        if ($countLastPositions < $strategy - 1) {
            return ;
        }

        $beacons = [];
        foreach ($lastPositions as $position) {
            $beacon = self::assignBeacon($position->beacons);
            $beaconId = 0;
            if (null !== $beacon) {
                $beaconId = $beacon->id;
            }
            $beacons[] = $beaconId;
        }

        $countBeacons = array_count_values($beacons);
        arsort($countBeacons);
        $realBeaconId = key($countBeacons);

        if (0 === $realBeaconId && $countBeacons[0] > $strategy - 1) {
            self::createPath($user, $realBeaconId);
            $user->beacon_id = $realBeaconId;
            return ;
        }

        $realBeacon = Beacon::find($realBeaconId);
        if (null === $realBeacon) {
            return ;
        }

        $user->location_id = $realBeacon->location_id;

        self::createPath($user, $realBeaconId);
        $user->beacon_id = $realBeaconId;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        DB::connection()->enableQueryLog();

        $modelClass = self::MODEL;
        $model= $modelClass::create($request->all());

        $user = $request->user();
        $model->user_id = $user->id;
        $model->save();

        $user->router_id = self::assignRouter($model->routers);

        $strategy = env('APP_STRATEGY', 1);
        $save = true;
        if ($strategy > 1) {
            $this->applyFlowStrategy($user, $strategy);
        } else {
            $save = $this->applySimpleStrategy($user, $model->beacons);
        }

        if ($save) {
            $user->touch();
            $user->save();
        }

        Log::info('Query Log:', array_map(function ($item) {
            unset($item['bindings']);
            return $item;
        }, DB::getQueryLog()));

        $response = $model->toArray();
        unset($response['routers']);
        unset($response['beacons']);
        $response['location_id'] = $user->location_id;

        return new JsonResponse($response, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function read($id)
    {
        $modelClass = self::MODEL;
        $model= $modelClass::findOrFail($id);
        return new JsonResponse($model, JsonResponse::HTTP_OK);
    }

    /**
     * @param User $user
     * @param integer $beaconId
     * @return void
     */
    protected function createPath(User $user, $beaconId)
    {
        $iBeaconId = (int)$beaconId;

        if ($user->beacon_id !== $beaconId) {
            Path::create([
                'user_id' => $user->id,
                'beacon_id' => $iBeaconId,
            ]);
            return;
        }

        $lastPath = Path::where('user_id', $user->id)->latest('id')->first();
        if (null !== $lastPath) {
            $lastPath->touch();
        }

        return;
    }

    /**
     * @param array $routers
     * @return integer
     */
    protected function assignRouter($routers)
    {
        if (!is_array($routers)) {
            return null;
        }
        $lowerRouters = [];
        foreach ($routers as $router) {
            $lowerRouters[] = array_change_key_case($router, CASE_LOWER);
        }

        $sort = array_column($lowerRouters, 'level', 'bssid');
        arsort($sort);
        if ([] !== $sort) {
            $bssid = key($sort);
            $router = Router::where('bssid', $bssid)->first();
            if (null !== $router) {
                return $router->id;
            }
        }
        return null;
    }

    /**
     * @param array $beacons
     * @return Beacon
     */
    protected function assignBeacon($beacons)
    {
        if (!is_array($beacons)) {
            return null;
        }
        $lowerBeacons = [];
        foreach ($beacons as $router) {
            $lowerBeacons[] = array_change_key_case($router, CASE_LOWER);
        }

        $sort = array_column($lowerBeacons, 'level', 'bssid');
        arsort($sort);
        if ([] !== $sort) {
            foreach ($sort as $key => $item) {
                $bssid = strtoupper($key);
                $beacon = Beacon::where('bssid', $bssid)->first();
                if (null !== $beacon) {
                    return $beacon;
                }
            }
        }
        return null;
    }
}
