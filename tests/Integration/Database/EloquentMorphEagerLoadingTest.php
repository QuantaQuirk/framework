<?php

namespace QuantaQuirk\Tests\Integration\Database\EloquentMorphEagerLoadingTest;

use QuantaQuirk\Database\Eloquent\Model;
use QuantaQuirk\Database\Eloquent\Relations\MorphTo;
use QuantaQuirk\Database\Eloquent\SoftDeletes;
use QuantaQuirk\Database\Schema\Blueprint;
use QuantaQuirk\Support\Facades\Schema;
use QuantaQuirk\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphEagerLoadingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('post_id');
            $table->unsignedInteger('user_id');
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('video_id');
        });

        Schema::create('actions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('target_type');
            $table->integer('target_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $user = User::create();
        $user2 = User::forceCreate(['deleted_at' => now()]);

        $post = tap((new Post)->user()->associate($user))->save();

        $video = Video::create();

        (new Comment)->commentable()->associate($post)->save();
        (new Comment)->commentable()->associate($video)->save();

        (new Action)->target()->associate($video)->save();
        (new Action)->target()->associate($user2)->save();
    }

    public function testWithMorphLoading()
    {
        $comments = Comment::query()
            ->with(['commentable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([Post::class => ['user']]);
            }])
            ->get();

        $this->assertCount(2, $comments);

        $this->assertTrue($comments[0]->relationLoaded('commentable'));
        $this->assertInstanceOf(Post::class, $comments[0]->getRelation('commentable'));
        $this->assertTrue($comments[0]->commentable->relationLoaded('user'));
        $this->assertTrue($comments[1]->relationLoaded('commentable'));
        $this->assertInstanceOf(Video::class, $comments[1]->getRelation('commentable'));
    }

    public function testWithMorphLoadingWithSingleRelation()
    {
        $comments = Comment::query()
            ->with(['commentable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([Post::class => 'user']);
            }])
            ->get();

        $this->assertTrue($comments[0]->relationLoaded('commentable'));
        $this->assertTrue($comments[0]->commentable->relationLoaded('user'));
    }

    public function testMorphLoadingMixedWithTrashedRelations()
    {
        $action = Action::query()
            ->with('target')
            ->get();

        $this->assertCount(2, $action);

        $this->assertTrue($action[0]->relationLoaded('target'));
        $this->assertInstanceOf(Video::class, $action[0]->getRelation('target'));
        $this->assertTrue($action[1]->relationLoaded('target'));
        $this->assertInstanceOf(User::class, $action[1]->getRelation('target'));
    }
}

class Action extends Model
{
    public $timestamps = false;

    public function target()
    {
        return $this->morphTo()->withTrashed();
    }
}

class Comment extends Model
{
    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'post_id';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class User extends Model
{
    use SoftDeletes;

    public $timestamps = false;
}

class Video extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'video_id';
}
