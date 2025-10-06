<?php

use PHPUnit\Framework\TestCase;
use App\Models\Forum;
use App\Models\Thread;
use App\Models\Post;

class ForumTest extends TestCase
{
    private $forumModel;
    private $threadModel;
    private $postModel;

    protected function setUp(): void
    {
        $this->forumModel = new Forum();
        $this->threadModel = new Thread();
        $this->postModel = new Post();
    }

    public function testCreateForum()
    {
        $forumData = [
            'name' => 'Test Forum',
            'description' => 'A test forum',
            'status' => 'active'
        ];

        $forumId = $this->forumModel->create($forumData);
        $this->assertIsString($forumId);
        $this->assertNotEmpty($forumId);
    }

    public function testCreateThreadInForum()
    {
        $forumData = [
            'name' => 'Test Forum',
            'description' => 'A test forum',
            'status' => 'active'
        ];

        $forumId = $this->forumModel->create($forumData);

        $threadData = [
            'forum_id' => $forumId,
            'user_id' => 1,
            'title' => 'Test Thread',
            'content' => 'This is a test thread',
            'status' => 'active'
        ];

        $threadId = $this->threadModel->create($threadData);
        $this->assertIsString($threadId);
        $this->assertNotEmpty($threadId);
    }

    public function testCreatePostInThread()
    {
        $forumData = [
            'name' => 'Test Forum',
            'description' => 'A test forum',
            'status' => 'active'
        ];

        $forumId = $this->forumModel->create($forumData);

        $threadData = [
            'forum_id' => $forumId,
            'user_id' => 1,
            'title' => 'Test Thread',
            'content' => 'This is a test thread',
            'status' => 'active'
        ];

        $threadId = $this->threadModel->create($threadData);

        $postData = [
            'thread_id' => $threadId,
            'user_id' => 1,
            'content' => 'This is a test post',
            'status' => 'active'
        ];

        $postId = $this->postModel->create($postData);
        $this->assertIsString($postId);
        $this->assertNotEmpty($postId);
    }

    public function testGetForumWithThreads()
    {
        $forumData = [
            'name' => 'Test Forum',
            'description' => 'A test forum',
            'status' => 'active'
        ];

        $forumId = $this->forumModel->create($forumData);

        $forum = $this->forumModel->getWithThreads($forumId);
        $this->assertIsArray($forum);
        $this->assertArrayHasKey('threads', $forum);
    }

    public function testGetForumStats()
    {
        $forumData = [
            'name' => 'Test Forum',
            'description' => 'A test forum',
            'status' => 'active'
        ];

        $forumId = $this->forumModel->create($forumData);

        $stats = $this->forumModel->getStats($forumId);
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('thread_count', $stats);
        $this->assertArrayHasKey('post_count', $stats);
    }

    public function testSearchForums()
    {
        $forums = $this->forumModel->search('test');
        $this->assertIsArray($forums);
    }

    public function testGetActiveForums()
    {
        $forums = $this->forumModel->getActive();
        $this->assertIsArray($forums);
    }

    public function testUpdateForum()
    {
        $forumData = [
            'name' => 'Test Forum',
            'description' => 'A test forum',
            'status' => 'active'
        ];

        $forumId = $this->forumModel->create($forumData);

        $updateData = [
            'name' => 'Updated Forum Name'
        ];

        $result = $this->forumModel->update($forumId, $updateData);
        $this->assertTrue($result);
    }

    public function testDeleteForum()
    {
        $forumData = [
            'name' => 'Test Forum',
            'description' => 'A test forum',
            'status' => 'active'
        ];

        $forumId = $this->forumModel->create($forumData);

        $result = $this->forumModel->delete($forumId);
        $this->assertTrue($result);
    }
}