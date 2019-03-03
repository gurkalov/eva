<?php

use Illuminate\Database\Seeder;

class PositionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $position = factory(App\Position::class)->make();
        $position->user_id = 1;
        $position->save();
    }
}