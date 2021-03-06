<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UserTest extends TestCase
{
    use DatabaseMigrations;
    use TestCrudTrait;

    const ROUTE = '/api/v1/users';
    const CONTROLLER = UserController::class;

    /**
     * A basic test index.
     *
     * @return void
     */
    public function testIndex()
    {
        $model = $this->makeFactory();
        $model->save();

        $this->json('GET', static::ROUTE)
            ->seeStatusCode(JsonResponse::HTTP_OK);
    }

    /**
     * A basic test read.
     *
     * @return void
     */
    public function testRead()
    {
        $model = $this->makeFactory();
        $model->save();

        $this->json('GET', static::ROUTE . '/' . $model->id)
            ->seeStatusCode(JsonResponse::HTTP_OK)
            ->seeJson($model->toArray() + ['beacon' => null]);
    }
}
