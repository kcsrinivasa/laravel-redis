<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Http\Requests\BlogRequest;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
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

    /**
     * Display the specified resource.
     */
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


    /**
     * Update the specified resource in storage.
     */
    public function update(BlogRequest $request, $blog_id){
        $blog = Blog::find($blog_id);
        if(!empty($blog)){
            $blog->update($request->all());

            /** delete the blog from redis */
            Redis::del('blog_'.$blog->id);
            
            /** store the blog to redis */
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

    /**
     * Remove the specified resource from storage.
     */
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


    /**
     * destroy a listing of the resource from redis.
     */
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


}
