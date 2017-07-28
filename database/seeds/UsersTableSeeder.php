<?php
/**
 *  User Seeder.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        //Populate admin table
        $admin_id = DB::table('admin')->insertGetId([
            'name' => 'Admin',
        ]);

        //Populate user table
        DB::table('users')->insert([
            'email' => 'admin@gergonzalez.com',
            'password' => app('hash')->make('admin'),
            'userable_id' => $admin_id,
            'userable_type' => 'App\Admin',
            'active' => 1,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
    }
}
