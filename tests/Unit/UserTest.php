<?php

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    private $userModel;

    protected function setUp(): void
    {
        $this->userModel = new User();
    }

    public function testCreateUser()
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'display_name' => 'Test User',
            'password' => 'password123'
        ];

        $userId = $this->userModel->create($userData);
        $this->assertIsString($userId);
        $this->assertNotEmpty($userId);
    }

    public function testFindUser()
    {
        $user = $this->userModel->find(1);
        $this->assertIsArray($user);
    }

    public function testUpdateUser()
    {
        $updateData = [
            'display_name' => 'Updated Name'
        ];

        $result = $this->userModel->update(1, $updateData);
        $this->assertTrue($result);
    }

    public function testDeleteUser()
    {
        $result = $this->userModel->delete(1);
        $this->assertTrue($result);
    }

    public function testValidateEmail()
    {
        $this->assertTrue($this->userModel->validateEmail('test@example.com'));
        $this->assertFalse($this->userModel->validateEmail('invalid-email'));
    }

    public function testValidatePassword()
    {
        $errors = $this->userModel->validatePassword('weak');
        $this->assertNotEmpty($errors);

        $errors = $this->userModel->validatePassword('StrongPass123');
        $this->assertEmpty($errors);
    }

    public function testGetUserStats()
    {
        $stats = $this->userModel->getStats(1);
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('thread_count', $stats);
        $this->assertArrayHasKey('post_count', $stats);
    }

    public function testSearchUsers()
    {
        $users = $this->userModel->search('test');
        $this->assertIsArray($users);
    }

    public function testGetUsersByRole()
    {
        $users = $this->userModel->getByRole('user');
        $this->assertIsArray($users);
    }

    public function testGetUsersByStatus()
    {
        $users = $this->userModel->getByStatus('active');
        $this->assertIsArray($users);
    }
}