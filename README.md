

![Laravel](https://github.com/kcsrinivasa/laravel-redis/blob/main/output/laravel_redis_logo.jpg?raw=true)


# Laravel using Redis with crud application

Hi All!

**Redis** which stands for *Remote Dictionary Server*, is a fast, open source, in-memory, key-value data store.  in-memory data structure store used as a database, cache, message broker, and streaming engine. Redis provides data structures such as strings, hashes, lists, sets, sorted sets with range queries, bitmaps, hyperloglogs, geospatial indexes, and streams.

One of the main advantages of Redis is its in-memory storage, which allows it to provide **fast access to data and high performance**. 

Here is the example focused on Laravel `usage of Redis`, `validate request`, `factory` and `rest api crud` application to handle the crud operation for Redis.

In this example we have focused on Rest API `get-redis-blogs`, `store`, `update`, `delete`, and `delete-all-redis-blogs`. and test the API request using postman tool.

For all routes requests must contain in header.
```
'headers' => [
    'Accept' => 'application/json',
]
```



### Preview using postman
get-redis-blogs
![get-redis-blogs](https://github.com/kcsrinivasa/laravel-redis/blob/main/output/get_redis_blogs.png?raw=true)


get individual blog
![get individual blog](https://github.com/kcsrinivasa/laravel-redis/blob/main/output/get_blog.png?raw=true)


store blog
![store blog](https://github.com/kcsrinivasa/laravel-redis/blob/main/output/store_blog.png?raw=true)


update blog 
![update blog](https://github.com/kcsrinivasa/laravel-redis/blob/main/output/update_blog.png?raw=true)


delete all blogs stored in redis
![delete blog](https://github.com/kcsrinivasa/laravel-redis/blob/main/output/delete_redis_blogs.png?raw=true)



Here are the following steps to achive laravel crud application with redis. 

### Step 1: Install Laravel
```bash
composer create-project laravel/laravel laravel-redis
```

### Step 2: Install redis package
```bash
composer require predis/predis
```

### Step 3: Update database/redis credentials in .env file
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_radis
DB_USERNAME=root
DB_PASSWORD=


REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=laravel_redis_
```

### Step 4: Install the Redis server

## For Ubuntu
1. First Method :

I. Installing and Configuring Redis
```bash
sudo apt-get update
sudo apt install redis-server
``` 

This will download and install Redis and its dependencies. Following this, there is one important configuration change to make in the Redis configuration file, which was generated automatically during the installation.

II. Open this file with your preferred text editor:
```bash
sudo nano /etc/redis/redis.conf
```
Inside the file, find the `supervised` directive. This directive allows you to declare an init system to manage Redis as a service, providing you with more control over its operation. The `supervised` directive is set to `no` by default. Since you are running Ubuntu, which uses the systemd init system, change this to `systemd`:

That’s the only change you need to make to the Redis configuration file at this point, so save and close it when you are finished. 

III. Restart the Redis service to reflect the changes you made to the configuration file:
```bash
sudo systemctl restart redis.service
```

IV. Check the Redis Version
```bash
redis-cli --version
```
V. Check Redis Service Status
```bash
sudo systemctl status redis
```
In the output, locate “Active: active (running)” line.



2. Second Method :

    URL: https://redis.io/docs/getting-started/
    After installation run the commond redis-cli

## 
## For Windows

    URL: https://github.com/microsoftarchive/redis/releases

    Download the msi extension (Redis-x64-3.0.504.msi) and install the application. After installation run the redis-cli.exe applicaion


### Step 5: Create the controller,request,factory
```bash
php artisan make:model Blog -fmcr
php artisan make:request BlogRequest
```
`f`: factroy, `m`: migration, `c`:controller, `r`:resource function


Update the model class in App/Models/Blog.php file 
```bash
use HasFactory;

protected $fillable = ['title','metaTitle','description','metaDescription'];
```

Update the schema in database/migrations/...create_blogs_table.php file
```bash
Schema::create('blogs', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('metaTitle')->nullable();
    $table->text('description');
    $table->string('metaDescription')->nullable();
    $table->timestamps();
});
```

Update the function in database/factories/BlogFactory.php file to store fake data
```bash
public function definition(): array
{
    return [
        'title' => fake()->text(),
        'description' => fake()->paragraph(),
    ];
}
```

Update the function in database/seeders/DatabaseSeeder.php file to create fake records
```bash
public function run(): void
{
    \App\Models\Blog::factory(10)->create();
}
```
# 
#### Update the function in App/Http/Requests/BlogRequest.php file to create fake records

#### * Add “HttpResponseException” and “Validator” facades
```bash
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
```
#### * Add the failedValidation function to return the error response as json if any validation fail. 

```bash
public function failedValidation(Validator $validator)
{
    throw new HttpResponseException(response([
     'success'   => false,
     'message'   => 'Validation errors',
     'data'      => $validator->errors()
   ],422));
}
```
#### * Update rules function

```bash
public function rules(): array{
    return [
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'metaTitle' => 'nullable|string',
        'metaDescription' => 'nullable|string',
    ];
}

```
#### * This function optional to return custom error message if any validation fail. 

```bash
public function messages(){
    return [
        'title.required' => 'Title is required',
        'description.required' => 'Description is required, Please add more details for the blog',
    ];
 }
```


### Step 6: Add Routes
```bash
Route::delete('/blogs/delete-all','App\Http\Controllers\BlogController@destroyAll');
Route::resource('/blogs','App\Http\Controllers\BlogController',['except'=>['create','edit']]);
```

### Step 7: Update the Blog Controller for Redis CRUD Operation

Grab complete BlogController from [app/Http/Controllers/BlogController.php file](https://github.com/kcsrinivasa/laravel-redis/blob/main/app/Http/Controllers/BlogController.php)

```bash
/** Get all blogs which are stored in Redis */
public function index(){
    $blogs = [];
    $keys = Redis::keys('blog_*'); /** get all matching keys */

    if(!empty($keys)){
        $keys = array_map(function($k){ return ltrim($k,env('REDIS_PREFIX')); },$keys);

        /** first method : get all matching keys value at once and decode the data */
        $blogs = Redis::mget($keys);
        $blogs = array_map(function($b){ return json_decode($b,true); },$blogs);

        /** second method : using loop get individual matching key value and decode the data */
        /*
            foreach($keys as $key){
                $blogs[] = json_decode(Redis::get($key),true);
            }
        */
    }
        $res = [          
            'success'   => true,
            'message'   => 'Fetched the blogs from Redis',
            'blogs'     => $blogs
        ];

        return response($res,200);
}


/** Store the Blog data in Redis as well as in database */
public function store(BlogRequest $request){
    /** store the blog in database */
    $blog = Blog::create([
        'title'             => $request->title ?? '',
        'metaTitle'         => $request->metaTitle ?? '',
        'description'       => $request->description ?? '',
        'metaDescription'   => $request->metaDescription ?? '',
    ]);

    /** store the blog to redis */
    Redis::set('blog_'.$blog->id, $blog);

    $res = [
        'success'   => true,
        'blog'      => $blog,
        'message'   => 'Successfully stored the blog'
    ];
    return response($res,201);
}

/** Fetch Blog data from the Redis */
public function show($blog_id){
    /** fetch the blog from redis */
    $blog = Redis::get('blog_'.$blog_id);

    if(!empty($blog)){
        $blog = json_decode($blog,true);
        $res = [
            'success'   => true,
            'blog'      => $blog,
            'message'   => 'Fetched blog from Redis'
        ];
    }else{
        $blog = Blog::find($blog_id);
        if(empty($blog)){
            $res = [ 
                'success'   => false,
                'message'   => 'requested blog not found' 
            ];
            return response($res,404);
        }
        Redis::set('blog_'.$blog->id, $blog);

        $res = [
            'success'   => true,
            'blog'      => $blog,
            'message'   => 'Fetched blog from Database'
        ];
    }
        return response($res,200);
}

/** Update the blog data to Redis */
public function update(BlogRequest $request, $blog_id){
    $blog = Blog::find($blog_id);
    if(!empty($blog)){
        $blog->update($request->all());

        /** delete the blog from redis */
        Redis::del('blog_'.$blog->id);
        
        /** store the updated blog data to redis */
        Redis::set('blog_'.$blog->id, $blog);

        $res = [
            'success'   => true,
            'blog'      => $blog,
            'message'   => 'Successfully updated the blog'
        ];
        return response($res,200);
    }else{
        $res = [ 
            'success'   => false,
            'message'   => 'requested blog not found'
        ];
        return response($res,404);
    }
}

/** Delete specified blog data from the Redis */
public function destroy($blog_id){
    $blog = Blog::find($blog_id);
    if(!empty($blog)){
        /** delete the blog from redis */
        Redis::del('blog_'.$blog_id);

        /** delete the blog from database */
        $blog->delete();

        $res = [ 
            'success'   => true,
            'message'   => 'Successfully deleted the blog'
        ];
        return response($res,200);
    }else{
        $res = [ 
            'success'   => false,
            'message'   => 'requested blog not found' 
        ];
        return response($res,404);
    }
}


/** Delete the all blog details from the Redis */
public function destroyAll(){
    $keys = Redis::keys('blog_*'); /** get all matching keys */

    if(!empty($keys)){
        $keys = array_map(function($k){ return ltrim($k,env('REDIS_PREFIX')); },$keys);

        Redis::del($keys); /** delete all matching keys data from redis */
    }

    $res = [ 
        'success'   => true,
        'message'   => 'deleted the all blogs from redis' 
    ];
    return response($res,200);
}


```

### Step 8: Final run and check
```bash
php artisan migrate --seed

php artisan serve
```
send request with basepath http://localhost:8000/api/*


## Note : Refer the documentation for API Requests
[![document-api](https://img.shields.io/badge/Documentation-APIs_(clieck_here)-blue)](https://github.com/kcsrinivasa/laravel-redis/blob/main/api-documentation.docx)

