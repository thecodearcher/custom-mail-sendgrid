## Building a custom mailing system in Laravel using Twilio Sendgrid

Building an app and sharing it with the world can be an intimidating process. In the early stage of your development and marketing, the need to keep in touch with your users becomes critical to your growth.

One of the best ways to keep in touch with your users is via email. Emails can be used to keep your users in the loop of latest features, "bug" fixes, and also a great way to follow up on inactive users.

In this tutorial, we will look at how we can build a custom mailing system. After completing this tutorial, you will have built a custom mailing system using Laravel and [Twilio SendGrid](https://sendgrid.com/).

## Prerequisites

In order to follow this tutorial you will need:

- Basic knowledge of Laravel
- [Laravel](https://laravel.com/docs/master) installed on your local machine
- [Composer](https://getcomposer.org/) globally installed
- [MySQL](https://www.mysql.com/downloads/) set up on your local machine
- [SendGrid Account](https://sendgrid.com/pricing/)

## Getting Started

First, create a new Laravel project for your application. You can accomplish this using either the [Laravel installer](https://laravel.com/docs/6.x#installing-laravel) or Composer. For this tutorial, the Laravel installer will be used. If you don't have the Laravel installer already installed, simply head over to the [Laravel documentation](https://laravel.com/docs/6.x#installing-laravel) to see how to. If you already do then open up a terminal and run the following command to create a new Laravel project:

    $ laravel new custom-mail-system

The [Sendgrid PHP Library](https://github.com/sendgrid/sendgrid-php) will be used for communicating with the SendGrid service. Open up a terminal in your project directory and run the following command to installed it via Composer:

    $ composer require "sendgrid/sendgrid"

The SendGrid library makes use of your SendGrid API key to send out emails. Your API key can be retrieved from your account [dashboard](https://app.sendgrid.com/settings/api_keys). Head over to your [dashboard](https://app.sendgrid.com/settings/api_keys) to grab your API key.

![https://res.cloudinary.com/brianiyoha/image/upload/v1575608996/Articles%20sample/Group_13.png](https://res.cloudinary.com/brianiyoha/image/upload/v1575608996/Articles%20sample/Group_13.png)

An API key will need to be created if you don't already have one. You will be given an API key after successful creation. Remember to keep a copy in a safe place as you won't be able to retrieve it later.

![https://res.cloudinary.com/brianiyoha/image/upload/v1575608996/Articles%20sample/Group_14.png](https://res.cloudinary.com/brianiyoha/image/upload/v1575608996/Articles%20sample/Group_14.png)

Next, open up your `.env` file to add your API key to your environmental variables. Add the following at the end of the file:

    SENDGRID_API_KEY={YOUR API KEY}

## Setting up the Database

The next step is to set up your database for the application. This tutorial will make use of the [MySQL](https://www.mysql.com/) database if you don't have it already set up on your local machine, head over to their [official download page](https://www.mysql.com/downloads/) to have it installed. 

To create a database for your application, you will need to login to the MySQL client. To do this, simply run the following command:

    $ mysql -u {your_user_name}

***NOTE:** Add the -p flag if you have a password for your MySQL instance.*

Run the following command to create a database:

    mysql> create database custom-mailing;
    mysql> exit;

### Migrating the *Users* Table

Out of the box, Laravel scaffolds with a *Users* [migration](https://laravel.com/docs/6.x/migrations) and includes a basic schema like the following:

    /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        } 

You can tell from the code above that you don't need to do any modification to the schema as it already has the needed fields for this application, namely `email` and `name`. Although this migration already exists, it isn't yet *committed* to your database. To execute the *users* migration, run the following command:

    $ php artisan migrate

This will create a `users` table in your database alongside the listed fields in the `[up()](https://laravel.com/docs/6.x/migrations#migration-structure)` method of the migration file.

### Seeding the Users Table

You will need some users' data to continue with this tutorial. You can easily accomplish this by using [seeders](https://laravel.com/docs/6.x/seeding). To generate a seeder class, open up a terminal in your project directory and run the following command:

    $ php artisan make:seeder UsersTableSeeder

This will generate a `UsersTableSeeder` seeder class in `database/seeds/`. Open the newly created file ( `database/seeds/UsersTableSeeder.php` ) and make the following changes:

    <?php
    
    use Illuminate\Database\Seeder;
    use Illuminate\Support\Facades\DB;
    use Faker\Generator as Faker;
    use Illuminate\Support\Facades\Hash;
    
    class UsersTableSeeder extends Seeder
    {
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run(Faker $faker)
        {
            DB::table("users")->insert([
                [
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail, //use real email here
                    'email_verified_at' => now(),
                    'password' => Hash::make($faker->password()), // password
                ],
                [
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail, //use real email here
                    'email_verified_at' => now(),
                    'password' => Hash::make($faker->password()), // password
                ],
                [
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail, //use real email here
                    'email_verified_at' => now(),
                    'password' => Hash::make($faker->password()), // password
                ],
            ]);
        }
    }

***NOTE:** You have to swap out the email faker for real email addresses that you want to test your application with.*

Run the following command to seed your database with the data in the seeder class:

    $ php artisan db:seed --class=UsersTableSeeder

## Sending Emails

At this point your database is setup and seeded, and the SendGrid SDK is installed. Now you can move on to writing the *business logic* for the application. You will need a [controller](https://laravel.com/docs/6.x/controllers) class that will house the logic for sending out emails. To generate a controller, open up your terminal and run the following command:

    $ php artisan make:controller MailingController

Now open up the *MailingController* (`app/Http/Controllers/MailingController.php`) and make the following changes:

    <?php
    
    namespace App\Http\Controllers;
    
    use App\User;
    use Illuminate\Http\Request;
    use SendGrid;
    
    class MailingController extends Controller
    {
    
    
        public function sendMail(Request $request)
        {
            $validated = $request->validate([
                'from' => 'required|email',
                'users' => 'required|array',
                'users.*' => 'required',
                'subject' => 'required|string',
                'body' => 'required|string',
            ]);
    
            $from = new \SendGrid\Mail\From($validated['from']);
    
            /* Add selected users email to $tos array */
            $tos = [];
            foreach ($validated['users'] as $user) {
                array_push($tos, new \SendGrid\Mail\To(json_decode($user)->email, json_decode($user)->name));
            }
    
            /* Sent subject of mail */
            $subject = new \SendGrid\Mail\Subject($validated['subject']);
    
            /* Set mail body */
            $htmlContent = new \SendGrid\Mail\HtmlContent($validated['body']);
    
            $email = new \SendGrid\Mail\Mail(
                $from,
                $tos,
                $subject,
                null,
                $htmlContent
            );
    
            /* Create instance of Sendgrid SDK */
            $sendgrid = new SendGrid(getenv('SENDGRID_API_KEY'));
    
            /* Send mail using sendgrid instance */
            $response = $sendgrid->send($email);
            if ($response->statusCode() == 202) {
                return back()->with(['success' => "E-mails successfully sent out!!"]);
            }
    
            return back()->withErrors(json_decode($response->body())->errors);
        }
    }

The `sendMail()` method is where the magic happens! After successful [validation](https://laravel.com/docs/6.x/validation) of the data passed in from the *request* body, the data is then prepared to be passed into the SendGrid `send()` method. This can either be done using raw *arrays* or using the helper classes in the SendGrid SDK. Using the helper classes makes it easier to construct the needed objects *(From, To, Subject, Content)*. 

After the successful preparation of the data, the `SendGrid\Mail\Mail` class is used to construct the final object to prepare the body of the SendGrid API request. This class takes in five (5) arguments (`from`, `to`, `subject`, `plainTextContent`, `htmlContent`) which are used to construct the final *body* object. A new instance of the SendGrid class is then created using your SendGrid credentials. 

The `send()` method from the SendGrid SDK is what actually sends out the mail to the email(s) added in the `$tos` array. The `send()` method takes in just one argument of the `SendGrid\Mail\Mail` class used earlier to construct the body of the API request. The `send()` method returns a *response* with the `statusCode` and `body`. A successful request will return a `statusCode` of *202* which is used to determine the next line of action. In this case, when a request is successful, the user is redirected back to the `welcome` view with a success message. If not, the user is taken back to the previous page with the errors.

## Building the View

The logic for sending out mails is ready! Your users now need a way to actually interact with your application. To accomplish this a view with a form for users to fill out will be created to send out an email. To proceed, open up the default welcome page view (`resources/views/welcome.blade.php`) and replace it's content with the following:

    <!doctype html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    
        <title>Custom Mail Portal With Sendgrid</title>
    
        <!-- Styles -->
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
    
    </head>
    
    <body>
        <div class="container">
            <div class="jumbotron">
                @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
                @endif
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <div class="row">
    
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                Send Custom Mail
                            </div>
                            <div class="card-body">
                                <form method="POST" action="/sendmail">
                                    @csrf
                                    <div class="form-group">
                                        <label>From</label>
                                        <input required name="from" value="{{getenv('MAIL_FROM')}}" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Subject</label>
                                        <input required name="subject" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Select users to send mail to</label>
                                        <select required name="users[]" multiple class="form-control">
                                            @foreach ($users as $user)
                                            <option value="{{$user}}">{{$user->name}} - {{$user->email}}</option>
                                            @endforeach
    
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Message</label>
                                        <textarea required name="body" class="form-control" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Send Mail(s)</button>
    
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
    
            </div>
        </div>
    </body>
    
    </html>

If you take a closer look at the `select` tag, you will notice the `options` are retrieved from a `$users` property. Currently, this view is being served directly from the `web.php` file and does not pass in the needed `$user` data. To change this, add the following method to the `app/Http/Controllers/MailingController.php` file:

    public function index()
        {
            return view('welcome', ['users' => User::all()]);
    
        }

This method simply returns the `welcome` view with an array of all the users in your *users'* table.

## Updating the Routes

At this point, you have successfully built major features for your application. Now you need to update the routes used to access your application to better work with all the adjustments made. Open up `routes/web.php` and make the following changes:

    <?php
    
    use App\User;
    use Illuminate\Support\Facades\Route;
    
    /*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register web routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | contains the "web" middleware group. Now create something great!
    |
     */
    
    Route::get('/', 'MailingController@index');
    Route::post('/sendmail', 'MailingController@sendMail');

## Testing Application

It's time to test it out by serving your Laravel project! Open up your terminal and run the following command in your project directory:

    $ php artisan serve

This will serve your Laravel application on a localhost port, normally `8000`. Open up the localhost link printed out after running the command in your browser and you should be greeted with a page similar to this:

![https://res.cloudinary.com/brianiyoha/image/upload/v1575608369/Articles%20sample/Screenshot_from_2019-12-06_05-51-40.png](https://res.cloudinary.com/brianiyoha/image/upload/v1575608369/Articles%20sample/Screenshot_from_2019-12-06_05-51-40.png)

Proceed to fill out the form and hit the *Send Mails* button. If everything goes well then you should receive an email shortly with the content you typed in the *Message* box.

## Conclusion

Having successfully finished this tutorial, you have been able to build a custom mail system, while also learning how to send out emails to multiple recipients using the Twilio SendGrid SDK. If you would like to take a look at the complete source code for this tutorial, you can find it on [Github](https://github.com/thecodearcher/custom-mail-sendgrid). 

You can take this application a step further by also allowing sending emails to non-registered users.

I’d love to answer any question(s) you might have concerning this tutorial. You can reach me via:

- Email: [brian.iyoha@gmail.com](mailto:brian.iyoha@gmail.com)
- Twitter: [thecodearcher](https://twitter.com/thecodearcher)
- GitHub: [thecodearcher](https://github.com/thecodearcher)
