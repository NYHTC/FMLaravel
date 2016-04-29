# Instalation

## Install the Laravel framework

You will need the Composer PHP package manager to install Laravel and FMLaravel.  You can install Composer from getcomposer.org

If you do not yet have the Laravel framework installed you will need to install Laravel by running the following command in terminal:

	composer create-project laravel/laravel YourProjectName

Once Composer finishes the instalation you will need to give Laravel write access to the storage directory by running the following command in terminal:

	chmod -R 777 storage

## Instal FMLaravel

In your text editor open composer.json and add the following line to the "require" section of the file.  This will tell Composer that your project requires FMLaravel.

	"andrewmile/fm-laravel": "0.3.*"

Run the following command in terminal to install FMLaravel

	composer update

# Config

Back in your text editor open config/app.php and add the following line to the providers array:

	'FMLaravel\Database\FileMakerServiceProvider',

In config/database.php change the default connection type to filemaker:

	'default' => 'filemaker',

Still in config/database.php add the following to the connections array:

	'filemaker' => [
		'driver'   => 'filemaker',
		'host'     => env('DB_HOST'),
		'database' => env('DB_DATABASE'),
		'username' => env('DB_USERNAME'),
		'password' => env('DB_PASSWORD'),
	],

In your root directory create a new file named .env and add the following while including your database connection details:

	DB_HOST=YourHost
	DB_DATABASE=YourDatabase
	DB_USERNAME=YourUsername
	DB_PASSWORD=YourPassword

Note that if you are using version control you do not want the .env file to be a part of your repository so it is included in .gitignore by default.

# Usage

## Creating a Model

Laravel includes a command line tool called artisan that you can use for many helpful tasks, including generating files to avoid typing repetative boilerplate code.

You will want to have one model class per table that you are using in your project.  Laravel uses a convention where it uses singular model names.  To create a model for a tasks table run the following command in terminal:

	php artisan make:model Task

The file that was generated for you is located at app/Task.php.  This class extends Laravel's Eloquent Model class but we need it to extend the FMLaravel Model class instead.  Delete the following line from the newly created Task.php file:

	use Illuminate\Database\Eloquent\Model;

Then add the following line in its place:

	use FMLaravel\Database\Model;

In your Model classes you will need to specify the layout that should be used when querying the tasks table in your FileMaker database.  In order to do this add the following line inside the Task class:

	protected $layoutName = 'YourTaskLayoutName';

By default Laravel will assume the primary key of your table is "id".  If you have a different primary key you will need to add the following inside your class:

	protected $primaryKey = 'YourTaskPrimaryKey';
	
	
### Container fields

Container fields do not contain data directly, but references which can come in two forms (also see (the documentation)[http://help.filemaker.com/app/answers/detail/a_id/5812/~/about-publishing-the-contents-of-container-fields-on-the-web]).
To access container field data you can either make a call to the FileMaker API or have your model handle this for you, whereas the reference typically contains the filename from which the type could be guessed.
 
#### Example using API call:

    // retrieve your model as you normally would
    $model = MyModel::find(124);
    
    // make API call on container field
    // NOTE this assumed you've set up filemaker as your default database driver
    $containerData = DB::getContainerData($model->myContainerField);
    
    // example route response
    return response($containerData)->header('Content-Type:','image/png');
    

#### Example using implicit model functionality:

Extend your model as follows:

    protected $autoloadContainerFields = true; // NOTE only set this if you want to autoload all container fields, which you likely do not want for large resultsets 
    protected $containerFields = ['myContainerField'];
    
    
In your controller:

    // retrieve your model as you normally would
    $model = MyModel::find(124);
    
    // original field is automatically mutated to an instance of class \FMLaravel\Database\ContainerField
    $myContainerField = $model->myContainerField;
    
    // now you can access the following attributes
    $myContainerField->key == 'myContainerField'; // original attribute name
    $myContainerField->url == '/fmi/xml/cnt/myImageFile.png? etc etc'; // original attribute value
    $myContainerField->file == 'myImageFile.png';
    $myContainerField->mimeType == 'image/png';
    $myContainerField->data == you-binary-image-data // NOTE if you have specified to NOT autoload container data a request to the server will be triggered before it is returned. 
    
NOTE
currently automatically transformed container fields are mutated on the fly and not cached which means that every call to the model's container attribute would potentially trigger a request to the server, so please think about about your data usage.



## Querying a table

In a file where you will query your FileMaker tasks data add the following at the top of the file:

	use App\Task;

Now that you have imported your Task model you can run the following types of queries against your tasks table:

Find all records

	$tasks = Task::all();

Find a record by its primary key

	$task = Task::find(3); //will find the task record with a primary key of 3

Find a task that matches your find criteria.  You can either pass in two parameters where the first is the field name and the second is the value to match on or you can pass in an array of field names and values.

	//will find tasks where the task_name is 'Go to the store'
	$tasks = Task::where('task_name', 'Go to the store')->get();

	//will find tasks where task_name is 'Go to the store' and priority is 1
	$tasks = Task::where([
		'task_name' => 'Go to the store',
		'priority'  => 1
	])->get();

If you want to limit your query to the first record that matches your criteria you can use first() instead of get()

	$tasks = Task::where('task_name', 'Go to the store')->first();

If you want to specify a number of records to limit your query by you can use the limit() method.

	//will find the first 10 records that match the find criteria
	$tasks = Task::where('task_name', 'Go to the store')->limit(10)->get();

You can also specify a number of records to skip with the skip() method.

	//will find records that match the find criteria after skipping the first 10
	$tasks = Task::where('task_name', 'Go to the store')->skip(10)->get();

These query methods can be chained so you can do something like the following:

	//will find 10 records that match the find criteria after skipping the first 100
	$tasks = Task::where('task_name', 'Go to the store')->skip(100)->limit(10)->get();

If you are using both skip() and limit() in the same query and would rather combine them into one method you can also use the following:

	//will find 10 records that match the find criteria after skipping the first 100
	$tasks = Task::where('task_name', 'Go to the store')->setRange(100, 10)->get();

By default the layout you set on the $layoutName property of your model will be used to query your data.  However, if you need to specify a different layout for a specific query you may use the setLayout() method.

	//will use the PastDue layout to perform the query
	$tasks = Task::where('task_name', 'Go to the store')->setLayout('PastDue')->get();

# To Dos

- write documentation for authentication
