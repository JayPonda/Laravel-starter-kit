<?php

namespace Tests\Unit;

use App\Services\CrudGeneratorService;
use Tests\TestCase;

class CrudGeneratorServiceTest extends TestCase
{
    private CrudGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrudGeneratorService();
    }

    public function test_it_generates_correct_names(): void
    {
        $names = $this->service->getNames('BlogPost');

        $this->assertEquals('BlogPost', $names['studly']);
        $this->assertEquals('BlogPosts', $names['plural']);
        $this->assertEquals('blog_post', $names['snake']);
        $this->assertEquals('blog_posts', $names['pluralSnake']);
        $this->assertEquals('blogPost', $names['variable']);
        $this->assertEquals('blogPosts', $names['pluralVariable']);
    }

    public function test_it_generates_correct_controller_path(): void
    {
        $path = $this->service->getControllerPath('BlogPost');
        $this->assertStringEndsWith('app/Http/Controllers/BlogPostController.php', $path);
    }

    public function test_it_generates_correct_route_line(): void
    {
        $line = $this->service->getRouteLine('BlogPost');
        $this->assertEquals("Route::apiResource('blog_posts', \\App\Http\Controllers\\BlogPostController::class);", $line);
    }

    public function test_it_generates_valid_controller_stub(): void
    {
        $stub = $this->service->getControllerStub('BlogPost');

        $this->assertStringContainsString('class BlogPostController', $stub);
        $this->assertStringContainsString('use App\Models\BlogPost;', $stub);
        $this->assertStringContainsString('BlogPost::paginate(10)', $stub);
        $this->assertStringContainsString('new BlogPostResource($blogPost)', $stub);
    }
}
